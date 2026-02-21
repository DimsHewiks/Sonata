<?php

namespace Api\Posts\Services;

use Api\Auth\Auth;
use Api\Posts\DTOs\Request\CommentCreateDTO;
use Api\Posts\DTOs\Request\CommentReactionToggleDTO;
use Api\Posts\DTOs\Request\CommentsQueryDTO;
use Api\Posts\DTOs\Request\FeedCreateDTO;
use Api\Posts\DTOs\Request\QuizAnswerDTO;
use Api\Posts\DTOs\Response\CommentCreateResponse;
use Api\Posts\DTOs\Response\CommentDeleteResponse;
use Api\Posts\DTOs\Response\CommentDto;
use Api\Posts\DTOs\Response\CommentListResponse;
use Api\Posts\DTOs\Response\CommentMediaDto;
use Api\Posts\DTOs\Response\CommentReactionDto;
use Api\Posts\DTOs\Response\CommentReactionToggleResponse;
use Api\Posts\DTOs\Response\DeleteFeedResponse;
use Api\Posts\DTOs\Response\FeedAuthorDto;
use Api\Posts\DTOs\Response\FeedAvatarDto;
use Api\Posts\DTOs\Response\FeedCreateResponse;
use Api\Posts\DTOs\Response\FeedCoverDto;
use Api\Posts\DTOs\Response\FeedItemDto;
use Api\Posts\DTOs\Response\FeedListResponse;
use Api\Posts\DTOs\Response\FeedMediaDto;
use Api\Posts\DTOs\Response\FeedOptionDto;
use Api\Posts\DTOs\Response\FeedStatsDto;
use Api\Posts\DTOs\Response\PostMediaDto;
use Api\Posts\DTOs\Response\PostMediaListResponse;
use Api\Posts\DTOs\Response\QuizAnswerResponse;
use Api\Posts\DTOs\Response\QuizAnswerResultDto;
use Api\Posts\Repositories\FeedRepository;
use Ramsey\Uuid\Uuid;
use Sonata\Framework\Attributes\Inject;
use Sonata\Framework\MediaHelper\MediaHelper;

class FeedService
{
    private const DEFAULT_FEED_LIMIT = 20;
    private const MAX_FEED_LIMIT = 100;
    private const MAX_REACTIONS_PER_COMMENT_PER_USER = 2;

    public function __construct(
        #[Inject] private FeedRepository $feedRepository
    ) {}

    public function create(FeedCreateDTO $dto): FeedCreateResponse
    {
        $authUser = Auth::getOrThrow();
        $type = strtolower((string)$dto->type);

        $text = null;
        $payload = null;
        $mediaItems = [];

        if ($type === 'post') {
            $text = $dto->text !== null ? trim($dto->text) : null;
            $mediaItems = $this->uploadPostMedia();
            if (($text === null || $text === '') && empty($mediaItems)) {
                throw new \InvalidArgumentException('For post, text or media is required');
            }
        } else {
            if ($dto->payload === null || trim($dto->payload) === '') {
                throw new \InvalidArgumentException('payload is required for this type');
            }

            $decoded = json_decode($dto->payload, true);
            if (!is_array($decoded)) {
                throw new \InvalidArgumentException('payload must be valid JSON');
            }

            $payload = match ($type) {
                'poll' => $this->validateAndBuildPollPayload($decoded),
                'quiz' => $this->validateAndBuildQuizPayload($decoded),
                'article' => $this->validateAndBuildArticlePayload($decoded),
                default => throw new \InvalidArgumentException('Unsupported feed type'),
            };
        }

        $feedItemUuid = Uuid::uuid7()->toString();
        $this->feedRepository->createFeedItem(
            feedItemUuid: $feedItemUuid,
            userUuid: $authUser->uuid,
            wallUserUuid: $authUser->uuid,
            type: $type,
            text: $text,
            payloadJson: $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null
        );

        if (!empty($mediaItems)) {
            $this->feedRepository->createFeedMediaBatch($feedItemUuid, $mediaItems);
        }

        $row = $this->feedRepository->findFeedItemByUuid($feedItemUuid);
        if (!$row) {
            throw new \RuntimeException('Failed to load created feed item');
        }

        $storedMedia = $this->feedRepository->findFeedMediaByItemUuid($feedItemUuid);
        $response = new FeedCreateResponse();
        $response->item = $this->mapRowToCreateResponseItem($row, $storedMedia);
        return $response;
    }

    public function getFeed(int $limit = self::DEFAULT_FEED_LIMIT, int $offset = 0): FeedListResponse
    {
        $authUser = Auth::getOrThrow();
        $limit = $this->normalizePageLimit($limit);
        $offset = $this->normalizePageOffset($offset);
        $rows = $this->feedRepository->findFeedItemsForUser($authUser->uuid, $limit, $offset);
        return $this->buildFeedListResponse($rows, $authUser->uuid);
    }

    public function getGlobalFeed(int $limit = self::DEFAULT_FEED_LIMIT, int $offset = 0): FeedListResponse
    {
        $authUser = Auth::get();
        $limit = $this->normalizePageLimit($limit);
        $offset = $this->normalizePageOffset($offset);
        $rows = $this->feedRepository->findFeedItemsGlobal($limit, $offset);
        return $this->buildFeedListResponse($rows, $authUser?->uuid);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function buildFeedListResponse(array $rows, ?string $viewerUuid): FeedListResponse
    {
        $quizUuids = [];
        foreach ($rows as $row) {
            if (($row['type'] ?? '') === 'quiz') {
                $quizUuids[] = (string)$row['feed_item_uuid'];
            }
        }

        $quizAnswers = [];
        if ($viewerUuid !== null && !empty($quizUuids)) {
            $quizAnswers = $this->feedRepository->findQuizAnswersByItemUuidsForUser($quizUuids, $viewerUuid);
        }

        $quizAnswerByItemUuid = [];
        foreach ($quizAnswers as $answerRow) {
            $quizAnswerByItemUuid[(string)$answerRow['feed_item_uuid']] = $answerRow;
        }

        $itemUuids = array_map(static fn(array $r): string => (string)$r['feed_item_uuid'], $rows);
        $mediaRows = $this->feedRepository->findFeedMediaByItemUuids($itemUuids);
        $mediaByItemId = [];

        foreach ($mediaRows as $mediaRow) {
            $itemUuid = (string)$mediaRow['feed_item_uuid'];
            $mediaByItemId[$itemUuid] ??= [];
            $mediaByItemId[$itemUuid][] = [
                'relative_path' => (string)$mediaRow['relative_path'],
                'extension' => strtolower((string)$mediaRow['extension'])
            ];
        }

        $items = [];
        foreach ($rows as $row) {
            $itemUuid = (string)$row['feed_item_uuid'];
            $quizAnswer = $quizAnswerByItemUuid[$itemUuid] ?? null;
            $items[] = $this->mapRowToFeedItem($row, $mediaByItemId[$itemUuid] ?? [], $quizAnswer);
        }

        $response = new FeedListResponse();
        $response->items = $items;
        return $response;
    }

    private function normalizePageLimit(int $limit): int
    {
        if ($limit < 1) {
            return self::DEFAULT_FEED_LIMIT;
        }

        if ($limit > self::MAX_FEED_LIMIT) {
            return self::MAX_FEED_LIMIT;
        }

        return $limit;
    }

    private function normalizePageOffset(int $offset): int
    {
        return max(0, $offset);
    }

    public function submitQuizAnswer(QuizAnswerDTO $dto): QuizAnswerResponse
    {
        $authUser = Auth::getOrThrow();

        $feedItemUuid = $this->normalizeFeedItemUuid((string)$dto->feedId, 'quiz');
        $answerId = strtolower((string)$dto->answerId);

        $row = $this->feedRepository->findFeedItemByUuid($feedItemUuid);
        if (!$row || ($row['type'] ?? '') !== 'quiz') {
            throw new \InvalidArgumentException('Quiz not found');
        }

        $authorUuid = (string)($row['user_uuid'] ?? '');
        $wallUuid = (string)($row['wall_user_uuid'] ?? '');
        if ($authorUuid !== $authUser->uuid && $wallUuid !== $authUser->uuid) {
            throw new \RuntimeException('Forbidden');
        }

        $payload = $this->decodePayload((string)($row['payload_json'] ?? ''));
        $correctOptionId = isset($payload['correctOptionId'])
            ? strtolower((string)$payload['correctOptionId'])
            : null;
        if (!$correctOptionId) {
            throw new \RuntimeException('Quiz correct answer is missing');
        }

        if (!preg_match('/^[a-z]$/', $answerId)) {
            throw new \InvalidArgumentException('answerId must be a single letter');
        }

        $isCorrect = $answerId === $correctOptionId;
        $this->feedRepository->createQuizAnswer($feedItemUuid, $authUser->uuid, $answerId, $isCorrect);

        $stored = $this->feedRepository->findQuizAnswerByItemUuidAndUser($feedItemUuid, $authUser->uuid);
        $finalAnswerId = $stored ? (string)$stored['answer_id'] : $answerId;
        $finalIsCorrect = $stored ? (bool)$stored['is_correct'] : $isCorrect;

        $result = new QuizAnswerResultDto();
        $result->feedId = 'quiz-' . $feedItemUuid;
        $result->userAnswerId = $finalAnswerId;
        $result->isCorrect = $finalIsCorrect;
        $result->correctOptionId = $correctOptionId;

        $response = new QuizAnswerResponse();
        $response->result = $result;
        return $response;
    }

    public function deleteFeedItem(string $feedId): DeleteFeedResponse
    {
        $authUser = Auth::getOrThrow();

        $feedItemUuid = $this->normalizeFeedItemUuid($feedId, null);
        $row = $this->feedRepository->findFeedItemByUuid($feedItemUuid);
        if (!$row) {
            throw new \InvalidArgumentException('Feed item not found');
        }

        $type = (string)($row['type'] ?? '');
        $authorUuid = (string)($row['user_uuid'] ?? '');
        $wallUuid = (string)($row['wall_user_uuid'] ?? '');
        if ($authorUuid !== $authUser->uuid && $wallUuid !== $authUser->uuid) {
            throw new \RuntimeException('Forbidden');
        }

        $this->feedRepository->createFeedDeleteAudit($feedItemUuid, $authUser->uuid);
        $this->feedRepository->deleteFeedItemByUuid($feedItemUuid);

        $response = new DeleteFeedResponse();
        $response->deleted = true;
        $response->feedId = $type . '-' . $feedItemUuid;
        return $response;
    }

    public function getMyPostMedia(int $limit = 200): PostMediaListResponse
    {
        $authUser = Auth::getOrThrow();
        $rows = $this->feedRepository->findPostMediaForUser($authUser->uuid, $limit);
        $response = new PostMediaListResponse();
        foreach ($rows as $row) {
            $dto = new PostMediaDto();
            $dto->relative_path = (string)$row['relative_path'];
            $dto->extension = strtolower((string)$row['extension']);
            $dto->feedId = 'post-' . (string)$row['feed_item_uuid'];
            $response->items[] = $dto;
        }
        return $response;
    }

    public function createComment(string $feedId, CommentCreateDTO $dto): CommentCreateResponse
    {
        $authUser = Auth::getOrThrow();
        $feedItemUuid = $this->normalizeFeedItemUuid($feedId, null);

        $feedRow = $this->feedRepository->findFeedItemByUuid($feedItemUuid);
        if (!$feedRow) {
            throw new \InvalidArgumentException('Feed item not found');
        }

        $text = $dto->text !== null ? trim($dto->text) : null;
        $mediaItems = $this->uploadCommentMedia();
        if (($text === null || $text === '') && empty($mediaItems)) {
            throw new \InvalidArgumentException('For comment, text or media is required');
        }

        $parentUuid = null;
        if ($dto->parentId) {
            $parentUuid = $this->normalizeCommentUuid($dto->parentId);
            $parent = $this->feedRepository->findCommentByUuid($parentUuid);
            if (!$parent) {
                throw new \InvalidArgumentException('Parent comment not found');
            }
            if ((string)$parent['feed_item_uuid'] !== $feedItemUuid) {
                throw new \InvalidArgumentException('Parent comment is not in this feed');
            }
        }

        $commentUuid = Uuid::uuid7()->toString();
        $this->feedRepository->createComment(
            commentUuid: $commentUuid,
            feedItemUuid: $feedItemUuid,
            userUuid: $authUser->uuid,
            parentUuid: $parentUuid,
            text: $text
        );
        $this->feedRepository->incrementCommentsCount($feedItemUuid);

        if (!empty($mediaItems)) {
            $this->feedRepository->createCommentMediaBatch($commentUuid, $mediaItems);
        }

        $row = $this->feedRepository->findCommentByUuid($commentUuid);
        if (!$row) {
            throw new \RuntimeException('Failed to load created comment');
        }

        $media = $this->feedRepository->findCommentMediaByCommentUuids([$commentUuid]);
        $item = $this->mapCommentRowToDto($row, $this->groupCommentMedia($media)[$commentUuid] ?? [], []);

        $response = new CommentCreateResponse();
        $response->item = $item;
        return $response;
    }

    public function getComments(string $feedId, CommentsQueryDTO $dto): CommentListResponse
    {
        $feedItemUuid = $this->normalizeFeedItemUuid($feedId, null);

        $feedRow = $this->feedRepository->findFeedItemByUuid($feedItemUuid);
        if (!$feedRow) {
            throw new \InvalidArgumentException('Feed item not found');
        }

        $order = $dto->order ? strtolower($dto->order) : 'asc';
        $rows = $this->feedRepository->findCommentsByFeedItemUuid($feedItemUuid, $order);
        $commentUuids = array_map(static fn(array $r): string => (string)$r['comment_uuid'], $rows);
        $mediaRows = $this->feedRepository->findCommentMediaByCommentUuids($commentUuids);
        $viewerUuid = Auth::get()?->uuid;
        $reactionRows = $this->feedRepository->findCommentReactionsByCommentUuids($commentUuids, $viewerUuid);
        $mediaByComment = $this->groupCommentMedia($mediaRows);
        $reactionsByComment = $this->groupCommentReactions($reactionRows);

        $byUuid = [];
        $topLevel = [];
        foreach ($rows as $row) {
            $uuid = (string)$row['comment_uuid'];
            $comment = $this->mapCommentRowToDto(
                $row,
                $mediaByComment[$uuid] ?? [],
                $reactionsByComment[$uuid] ?? []
            );
            $byUuid[$uuid] = $comment;
        }

        foreach ($rows as $row) {
            $uuid = (string)$row['comment_uuid'];
            $parentUuid = $row['parent_uuid'] ? (string)$row['parent_uuid'] : null;
            if ($parentUuid && isset($byUuid[$parentUuid])) {
                $byUuid[$parentUuid]->children[] = $byUuid[$uuid];
            } else {
                $topLevel[] = $byUuid[$uuid];
            }
        }

        $response = new CommentListResponse();
        $response->items = $topLevel;
        return $response;
    }

    public function deleteComment(string $commentId): CommentDeleteResponse
    {
        $authUser = Auth::getOrThrow();
        $commentUuid = $this->normalizeCommentUuid($commentId);

        $row = $this->feedRepository->findCommentByUuid($commentUuid);
        if (!$row) {
            throw new \InvalidArgumentException('Comment not found');
        }

        $commentUserUuid = (string)($row['comment_user_uuid'] ?? '');
        $feedUserUuid = (string)($row['feed_user_uuid'] ?? '');
        $wallUuid = (string)($row['wall_user_uuid'] ?? '');
        if ($commentUserUuid !== $authUser->uuid && $feedUserUuid !== $authUser->uuid && $wallUuid !== $authUser->uuid) {
            throw new \RuntimeException('Forbidden');
        }

        $deletedCount = $this->feedRepository->countCommentSubtree($commentUuid);
        $this->feedRepository->deleteCommentByUuid($commentUuid);
        $this->feedRepository->decrementCommentsCount((string)$row['feed_item_uuid'], max(1, $deletedCount));

        $response = new CommentDeleteResponse();
        $response->deleted = true;
        $response->commentId = 'comment-' . $commentUuid;
        return $response;
    }

    public function toggleCommentReaction(string $commentId, CommentReactionToggleDTO $dto): CommentReactionToggleResponse
    {
        $authUser = Auth::getOrThrow();
        $commentUuid = $this->normalizeCommentUuid($commentId);

        $comment = $this->feedRepository->findCommentByUuid($commentUuid);
        if (!$comment) {
            throw new \InvalidArgumentException('Comment not found');
        }

        $emoji = $this->normalizeEmoji($dto->emoji);
        $this->feedRepository->toggleCommentReaction(
            commentUuid: $commentUuid,
            userUuid: $authUser->uuid,
            emoji: $emoji,
            maxPerUser: self::MAX_REACTIONS_PER_COMMENT_PER_USER
        );

        $rows = $this->feedRepository->findCommentReactionsByCommentUuids([$commentUuid], $authUser->uuid);
        $grouped = $this->groupCommentReactions($rows);

        $response = new CommentReactionToggleResponse();
        $response->commentId = $commentUuid;
        $response->reactions = $grouped[$commentUuid] ?? [];
        return $response;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function uploadPostMedia(): array
    {
        if (!isset($_FILES['media'])) {
            return [];
        }

        $mediaHelper = new MediaHelper('/feed');
        $mediaHelper->setNames(['media']);
        try {
            $uploadResult = $mediaHelper->import();
        } catch (\RuntimeException $e) {
            throw new \InvalidArgumentException($e->getMessage(), 0, $e);
        }
        $raw = $uploadResult['media'] ?? [];
        $normalized = $this->normalizeMediaObjects($raw);

        foreach ($normalized as &$file) {
            $file['extension'] = strtolower((string)($file['extension'] ?? ''));
            if (!$this->isSupportedPostMediaExtension($file['extension'])) {
                throw new \InvalidArgumentException('Unsupported media extension: ' . $file['extension']);
            }
            if (!isset($file['errors']) || $file['errors'] === null) {
                $file['errors'] = '';
            }
        }

        return $normalized;
    }

    /**
     * @param mixed $raw
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMediaObjects(mixed $raw): array
    {
        if (!is_array($raw) || empty($raw)) {
            return [];
        }

        if (isset($raw['relative_path']) || isset($raw['extension'])) {
            return [$raw];
        }

        $result = [];
        foreach ($raw as $item) {
            if (is_array($item) && (isset($item['relative_path']) || isset($item['extension']))) {
                $result[] = $item;
            }
        }

        return $result;
    }

    private function isSupportedPostMediaExtension(string $extension): bool
    {
        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'mov'], true);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function validateAndBuildPollPayload(array $payload): array
    {
        $question = trim((string)($payload['question'] ?? ''));
        $options = $payload['options'] ?? null;
        $multiple = $payload['multiple'] ?? null;
        $duration = trim((string)($payload['duration'] ?? ''));

        if ($question === '' || !is_array($options) || !is_bool($multiple) || $duration === '') {
            throw new \InvalidArgumentException('Poll payload is invalid');
        }

        if (count($options) < 2 || count($options) > 6) {
            throw new \InvalidArgumentException('Poll options must be 2..6');
        }

        $builtOptions = [];
        foreach (array_values($options) as $index => $optionText) {
            $text = trim((string)$optionText);
            if ($text === '') {
                throw new \InvalidArgumentException('Poll options must be non-empty strings');
            }
            $builtOptions[] = [
                'id' => $this->indexToOptionId($index),
                'text' => $text,
                'votes' => 0
            ];
        }

        return [
            'question' => $question,
            'options' => $builtOptions,
            'multiple' => $multiple,
            'totalVotes' => 0,
            'duration' => $duration,
            'userVoteIds' => []
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function validateAndBuildQuizPayload(array $payload): array
    {
        $question = trim((string)($payload['question'] ?? ''));
        $options = $payload['options'] ?? null;
        $correctOptionId = isset($payload['correctOptionId']) ? strtolower(trim((string)$payload['correctOptionId'])) : null;
        $correctOptionIndex = $payload['correctOptionIndex'] ?? null;
        $explanation = isset($payload['explanation']) ? trim((string)$payload['explanation']) : null;

        if ($question === '' || !is_array($options)) {
            throw new \InvalidArgumentException('Quiz payload is invalid');
        }

        if (count($options) < 3 || count($options) > 4) {
            throw new \InvalidArgumentException('Quiz options must be 3..4');
        }

        $builtOptions = [];
        foreach (array_values($options) as $index => $optionText) {
            $text = trim((string)$optionText);
            if ($text === '') {
                throw new \InvalidArgumentException('Quiz options must be non-empty strings');
            }
            $builtOptions[] = [
                'id' => $this->indexToOptionId($index),
                'text' => $text
            ];
        }

        if ($correctOptionId !== null && $correctOptionId !== '') {
            if (!preg_match('/^[a-z]$/', $correctOptionId)) {
                throw new \InvalidArgumentException('Quiz correctOptionId must be a single letter');
            }

            $idIndex = ord($correctOptionId) - ord('a');
            if ($idIndex < 0 || $idIndex >= count($builtOptions)) {
                throw new \InvalidArgumentException('Quiz correctOptionId is out of range');
            }
        } elseif (is_int($correctOptionIndex)) {
            if ($correctOptionIndex < 0 || $correctOptionIndex >= count($builtOptions)) {
                throw new \InvalidArgumentException('Quiz correctOptionIndex is out of range');
            }
            $correctOptionId = $this->indexToOptionId($correctOptionIndex);
        } else {
            throw new \InvalidArgumentException('Quiz correctOptionId is required');
        }

        $result = [
            'question' => $question,
            'options' => $builtOptions,
            'correctOptionId' => $correctOptionId,
            'userAnswerId' => null
        ];

        if ($explanation !== null && $explanation !== '') {
            $result['explanation'] = $explanation;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function validateAndBuildArticlePayload(array $payload): array
    {
        $title = trim((string)($payload['title'] ?? ''));
        $description = trim((string)($payload['description'] ?? ''));

        if ($title === '' || $description === '') {
            throw new \InvalidArgumentException('Article payload is invalid');
        }

        return [
            'title' => $title,
            'description' => $description,
            'readTime' => $this->calculateReadTime($description)
        ];
    }

    private function calculateReadTime(string $text): string
    {
        $words = preg_split('/\s+/', trim($text));
        $wordCount = is_array($words) ? count(array_filter($words)) : 0;
        $minutes = max(1, (int)ceil($wordCount / 200));
        return $minutes . ' min';
    }

    private function indexToOptionId(int $index): string
    {
        return chr(ord('a') + $index);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, array<string, mixed>> $media
     */
    private function mapRowToCreateResponseItem(array $row, array $media): FeedItemDto
    {
        $item = $this->buildBaseItem($row);

        if ($row['type'] === 'post') {
            $item->text = !empty($row['text']) ? (string)$row['text'] : null;
            $item->media = $this->buildMediaFull($media);
            return $item;
        }

        $payload = $this->normalizePayloadByType(
            (string)$row['type'],
            $this->decodePayload((string)($row['payload_json'] ?? '')),
            null
        );
        $this->applyPayloadToItem($item, (string)$row['type'], $payload);
        return $item;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, array<string, mixed>> $media
     * @param array<string, mixed>|null $quizAnswer
     */
    private function mapRowToFeedItem(array $row, array $media, ?array $quizAnswer = null): FeedItemDto
    {
        $item = $this->buildBaseItem($row);

        if ($row['type'] === 'post') {
            $item->text = !empty($row['text']) ? (string)$row['text'] : null;
            $item->media = $this->buildMediaCompact($media);
            return $item;
        }

        $payload = $this->normalizePayloadByType(
            (string)$row['type'],
            $this->decodePayload((string)($row['payload_json'] ?? '')),
            $quizAnswer
        );
        $this->applyPayloadToItem($item, (string)$row['type'], $payload);
        return $item;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function buildBaseItem(array $row): FeedItemDto
    {
        $item = new FeedItemDto();
        $item->id = (string)$row['type'] . '-' . (string)$row['feed_item_uuid'];
        $item->type = (string)$row['type'];
        $item->author = $this->buildAuthor($row);
        $item->createdAt = gmdate('Y-m-d\TH:i:s\Z', strtotime((string)$row['created_at']));

        if ($row['type'] === 'post') {
            $stats = new FeedStatsDto();
            $stats->likes = (int)$row['likes_count'];
            $stats->comments = (int)$row['comments_count'];
            $item->stats = $stats;
        }

        return $item;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function buildAuthor(array $row): FeedAuthorDto
    {
        $author = new FeedAuthorDto();
        $author->name = (string)$row['author_name'];
        $author->login = (string)$row['author_login'];

        if (!empty($row['author_avatar_relative_path']) && !empty($row['author_avatar_extension'])) {
            $avatar = new FeedAvatarDto();
            $avatar->relative_path = (string)$row['author_avatar_relative_path'];
            $avatar->extension = strtolower((string)$row['author_avatar_extension']);
            $author->avatar = $avatar;
        }

        return $author;
    }

    /**
     * @param array<int, array<string, mixed>> $media
     * @return array<int, FeedMediaDto>
     */
    private function buildMediaFull(array $media): array
    {
        $result = [];
        foreach ($media as $m) {
            $dto = new FeedMediaDto();
            $dto->original_name = $m['original_name'] ?? null;
            $dto->saved_name = $m['saved_name'] ?? null;
            $dto->full_path = $m['full_path'] ?? null;
            $dto->relative_path = (string)($m['relative_path'] ?? '');
            $dto->size = isset($m['size']) ? (int)$m['size'] : null;
            $dto->extension = strtolower((string)($m['extension'] ?? ''));
            $dto->uploaded = isset($m['uploaded']) ? (bool)$m['uploaded'] : null;
            $dto->errors = $m['errors'] ?? null;
            $result[] = $dto;
        }
        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $media
     * @return array<int, FeedMediaDto>
     */
    private function buildMediaCompact(array $media): array
    {
        $result = [];
        foreach ($media as $m) {
            $dto = new FeedMediaDto();
            $dto->relative_path = (string)($m['relative_path'] ?? '');
            $dto->extension = strtolower((string)($m['extension'] ?? ''));
            $result[] = $dto;
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyPayloadToItem(FeedItemDto $item, string $type, array $payload): void
    {
        if ($type === 'poll') {
            $item->question = isset($payload['question']) ? (string)$payload['question'] : null;
            $item->options = $this->buildOptions($payload['options'] ?? []);
            $item->multiple = isset($payload['multiple']) ? (bool)$payload['multiple'] : null;
            $item->totalVotes = isset($payload['totalVotes']) ? (int)$payload['totalVotes'] : null;
            $item->duration = isset($payload['duration']) ? (string)$payload['duration'] : null;
            $item->userVoteIds = isset($payload['userVoteIds']) && is_array($payload['userVoteIds'])
                ? array_values($payload['userVoteIds'])
                : [];
            return;
        }

        if ($type === 'quiz') {
            $item->question = isset($payload['question']) ? (string)$payload['question'] : null;
            $item->options = $this->buildOptions($payload['options'] ?? []);
            $item->correctOptionId = $payload['correctOptionId'] ?? null;
            $item->explanation = $payload['explanation'] ?? null;
            $item->userAnswerId = $payload['userAnswerId'] ?? null;
            $item->isCorrect = isset($payload['isCorrect']) ? (bool)$payload['isCorrect'] : null;
            return;
        }

        if ($type === 'article') {
            $item->title = isset($payload['title']) ? (string)$payload['title'] : null;
            if (isset($payload['excerpt'])) {
                $item->description = (string)$payload['excerpt'];
            } elseif (isset($payload['description'])) {
                $item->description = (string)$payload['description'];
            }
            $item->readTime = isset($payload['readTime']) ? (string)$payload['readTime'] : null;
            $item->articleId = isset($payload['articleId']) ? (string)$payload['articleId'] : null;
            $item->articleType = isset($payload['articleType']) ? (string)$payload['articleType'] : null;
            if (isset($payload['cover']) && is_array($payload['cover'])) {
                $cover = $payload['cover'];
                if (!empty($cover['relative_path']) && !empty($cover['extension'])) {
                    $coverDto = new FeedCoverDto();
                    $coverDto->relative_path = (string)$cover['relative_path'];
                    $coverDto->extension = strtolower((string)$cover['extension']);
                    $coverDto->position = isset($cover['position']) && is_array($cover['position'])
                        ? $cover['position']
                        : null;
                    $item->cover = $coverDto;
                }
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $options
     * @return array<int, FeedOptionDto>
     */
    private function buildOptions(array $options): array
    {
        $result = [];
        foreach ($options as $opt) {
            if (!is_array($opt)) {
                continue;
            }
            $dto = new FeedOptionDto();
            $dto->id = (string)($opt['id'] ?? '');
            $dto->text = (string)($opt['text'] ?? '');
            $dto->votes = isset($opt['votes']) ? (int)$opt['votes'] : null;
            $result[] = $dto;
        }
        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $payloadJson): array
    {
        if ($payloadJson === '') {
            return [];
        }

        $decoded = json_decode($payloadJson, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    /**
     * @param array<string, mixed>|null $quizAnswer
     */
    private function normalizePayloadByType(string $type, array $payload, ?array $quizAnswer): array
    {
        if ($type === 'poll' && !array_key_exists('userVoteIds', $payload)) {
            $payload['userVoteIds'] = [];
        }

        if ($type === 'quiz') {
            $hasAnswer = $quizAnswer !== null;
            $payload['userAnswerId'] = $hasAnswer ? (string)$quizAnswer['answer_id'] : null;
            if ($hasAnswer) {
                $payload['isCorrect'] = (bool)$quizAnswer['is_correct'];
            } else {
                unset($payload['correctOptionId']);
            }
        }

        return $payload;
    }

    private function normalizeFeedItemUuid(string $feedId, ?string $expectedType): string
    {
        $feedId = trim($feedId);
        if (str_contains($feedId, '-')) {
            [$type, $uuid] = explode('-', $feedId, 2);
            if ($expectedType !== null && $type !== $expectedType) {
                throw new \InvalidArgumentException('Invalid feed type');
            }
            $feedId = $uuid;
        }

        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $feedId)) {
            throw new \InvalidArgumentException('Invalid feedId');
        }

        return strtolower($feedId);
    }

    private function normalizeCommentUuid(string $commentId): string
    {
        $commentId = trim($commentId);
        if (str_starts_with(strtolower($commentId), 'comment-')) {
            $commentId = substr($commentId, 8);
        }

        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $commentId)) {
            throw new \InvalidArgumentException('Invalid commentId');
        }

        return strtolower($commentId);
    }

    private function normalizeEmoji(?string $emoji): string
    {
        $emoji = trim((string)$emoji);
        if ($emoji === '') {
            throw new \InvalidArgumentException('emoji is required');
        }

        return $emoji;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function uploadCommentMedia(): array
    {
        if (!isset($_FILES['media'])) {
            return [];
        }

        $mediaHelper = new MediaHelper('/comments');
        $mediaHelper->setNames(['media']);
        try {
            $uploadResult = $mediaHelper->import();
        } catch (\RuntimeException $e) {
            throw new \InvalidArgumentException($e->getMessage(), 0, $e);
        }
        $raw = $uploadResult['media'] ?? [];
        $normalized = $this->normalizeMediaObjects($raw);

        foreach ($normalized as &$file) {
            $file['extension'] = strtolower((string)($file['extension'] ?? ''));
            if (!$this->isSupportedPostMediaExtension($file['extension'])) {
                throw new \InvalidArgumentException('Unsupported media extension: ' . $file['extension']);
            }
            if (!isset($file['errors']) || $file['errors'] === null) {
                $file['errors'] = '';
            }
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $mediaRows
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupCommentMedia(array $mediaRows): array
    {
        $byComment = [];
        foreach ($mediaRows as $row) {
            $uuid = (string)$row['comment_uuid'];
            $byComment[$uuid] ??= [];
            $byComment[$uuid][] = $row;
        }
        return $byComment;
    }

    /**
     * @param array<int, array<string, mixed>> $reactionRows
     * @return array<string, array<int, CommentReactionDto>>
     */
    private function groupCommentReactions(array $reactionRows): array
    {
        $byComment = [];
        foreach ($reactionRows as $row) {
            $uuid = (string)$row['comment_uuid'];
            $byComment[$uuid] ??= [];

            $dto = new CommentReactionDto();
            $dto->emoji = (string)$row['emoji'];
            $dto->count = isset($row['reactions_count']) ? (int)$row['reactions_count'] : 0;
            $dto->active = !empty($row['is_active']);
            $byComment[$uuid][] = $dto;
        }

        return $byComment;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, array<string, mixed>> $media
     * @param array<int, CommentReactionDto> $reactions
     */
    private function mapCommentRowToDto(array $row, array $media, array $reactions): CommentDto
    {
        $comment = new CommentDto();
        $comment->id = 'comment-' . (string)$row['comment_uuid'];
        $comment->author = $this->buildAuthor($row);
        $comment->createdAt = gmdate('Y-m-d\TH:i:s\Z', strtotime((string)$row['created_at']));
        $comment->text = $row['text'] !== null ? (string)$row['text'] : null;
        $comment->parentId = $row['parent_uuid'] ? 'comment-' . (string)$row['parent_uuid'] : null;
        $comment->media = $this->buildCommentMedia($media);
        $comment->reactions = $reactions;
        $comment->children = [];
        return $comment;
    }

    /**
     * @param array<int, array<string, mixed>> $media
     * @return array<int, CommentMediaDto>
     */
    private function buildCommentMedia(array $media): array
    {
        $result = [];
        foreach ($media as $m) {
            $dto = new CommentMediaDto();
            $dto->original_name = $m['original_name'] ?? null;
            $dto->saved_name = $m['saved_name'] ?? null;
            $dto->full_path = $m['full_path'] ?? null;
            $dto->relative_path = (string)($m['relative_path'] ?? '');
            $dto->size = isset($m['size']) ? (int)$m['size'] : null;
            $dto->extension = strtolower((string)($m['extension'] ?? ''));
            $dto->uploaded = isset($m['uploaded']) ? (bool)$m['uploaded'] : null;
            $dto->errors = $m['errors'] ?? null;
            $result[] = $dto;
        }
        return $result;
    }
}
