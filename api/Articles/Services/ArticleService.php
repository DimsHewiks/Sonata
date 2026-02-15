<?php

namespace Api\Articles\Services;

use Api\Articles\Dto\Request\ArticleCreateDto;
use Api\Articles\Dto\Request\ArticleUpdateDto;
use Api\Articles\Dto\Response\ArticleCoverDto;
use Api\Articles\Dto\Response\ArticleCoverMediaDto;
use Api\Articles\Dto\Response\ArticleCoverUploadResponseDto;
use Api\Articles\Dto\Response\ArticleDto;
use Api\Articles\Dto\Response\ArticleEmbedDto;
use Api\Articles\Dto\Response\ArticleMediaDto;
use Api\Articles\Dto\Response\ArticleMediaUploadResponseDto;
use Api\Articles\Dto\Response\ArticlePublishResponseDto;
use Api\Articles\Repositories\ArticleRepository;
use Api\Posts\Repositories\FeedRepository;
use Ramsey\Uuid\Uuid;
use Sonata\Framework\Attributes\Inject;
use Sonata\Framework\MediaHelper\MediaHelper;

class ArticleService
{
    public function __construct(
        #[Inject] private ArticleRepository $articleRepository,
        #[Inject] private FeedRepository $feedRepository
    ) {}

    public function createDraft(ArticleCreateDto $dto, string $authorUuid): ArticleDto
    {
        $title = trim((string)$dto->title);
        if ($title === '') {
            throw new \InvalidArgumentException('title is required');
        }

        $type = strtolower((string)($dto->type ?? 'text'));
        if (!in_array($type, ['text', 'song'], true)) {
            throw new \InvalidArgumentException('type must be text or song');
        }

        $format = strtolower((string)($dto->format ?? 'markdown'));
        if ($format !== 'markdown') {
            throw new \InvalidArgumentException('format must be markdown');
        }

        $uuid = Uuid::uuid7()->toString();
        $this->articleRepository->createDraft($uuid, $authorUuid, $title, $type, $format);
        $row = $this->articleRepository->findByUuid($uuid);
        if (!$row) {
            throw new \RuntimeException('Failed to load created article');
        }

        return $this->mapRowToDto($row);
    }

    public function update(string $uuid, string $authorUuid, ArticleUpdateDto $dto): ArticleDto
    {
        $row = $this->articleRepository->findByUuid($uuid);
        if (!$row) {
            throw new \InvalidArgumentException('Article not found');
        }

        if ((string)$row['author_uuid'] !== $authorUuid) {
            throw new \RuntimeException('Forbidden');
        }

        $currentType = (string)$row['type'];
        $type = $dto->type !== null ? strtolower((string)$dto->type) : $currentType;
        if (!in_array($type, ['text', 'song'], true)) {
            throw new \InvalidArgumentException('type must be text or song');
        }

        $fields = [];

        if ($dto->title !== null) {
            $title = trim((string)$dto->title);
            $fields['title'] = $title;
        }

        if ($dto->type !== null) {
            $fields['type'] = $type;
            if ($type === 'text') {
                $fields['chords_notation'] = null;
            }
        }

        if ($dto->format !== null) {
            $format = strtolower((string)$dto->format);
            if ($format !== 'markdown') {
                throw new \InvalidArgumentException('format must be markdown');
            }
            $fields['format'] = $format;
        }

        if ($dto->body !== null) {
            $body = (string)$dto->body;
            $this->validateBody($type, $body);
            $fields['body'] = $body;
        } elseif ($dto->type !== null && $type !== $currentType) {
            $this->validateBody($type, (string)$row['body']);
        }

        if ($dto->excerpt !== null) {
            $excerpt = trim((string)$dto->excerpt);
            $fields['excerpt'] = $excerpt === '' ? null : $excerpt;
        }

        if ($dto->chordsNotation !== null) {
            $notation = strtolower((string)$dto->chordsNotation);
            if ($type !== 'song') {
                $fields['chords_notation'] = null;
            } elseif (!in_array($notation, ['standard', 'german'], true)) {
                throw new \InvalidArgumentException('chords_notation must be standard or german');
            } else {
                $fields['chords_notation'] = $notation;
            }
        }

        if ($dto->coverMediaId !== null) {
            $coverMediaId = trim((string)$dto->coverMediaId);
            if ($coverMediaId === '') {
                $fields['cover_media_uuid'] = null;
            } else {
                if (!preg_match('/^[0-9a-fA-F-]{36}$/', $coverMediaId)) {
                    throw new \InvalidArgumentException('cover_media_id must be UUID');
                }
                $cover = $this->articleRepository->findCoverMediaByUuid($coverMediaId);
                if (!$cover) {
                    throw new \InvalidArgumentException('Cover media not found');
                }
                $fields['cover_media_uuid'] = $coverMediaId;
            }
        }

        if ($dto->coverPosition !== null) {
            $position = $this->normalizeCoverPosition($dto->coverPosition);
            $fields['cover_position'] = $position !== null
                ? json_encode($position, JSON_UNESCAPED_UNICODE)
                : null;
        }

        if ($dto->embeds !== null) {
            $embeds = $this->normalizeEmbeds($dto->embeds);
            $fields['embeds'] = json_encode($embeds, JSON_UNESCAPED_UNICODE);
        }

        $this->articleRepository->updateArticle($uuid, $fields);
        $updated = $this->articleRepository->findByUuid($uuid);
        if (!$updated) {
            throw new \RuntimeException('Failed to load updated article');
        }

        if ((string)$updated['status'] === 'published') {
            $this->syncFeedItem($updated);
        }

        return $this->mapRowToDto($updated);
    }

    public function get(string $uuid, string $viewerUuid): ArticleDto
    {
        $row = $this->articleRepository->findByUuid($uuid);
        if (!$row) {
            throw new \InvalidArgumentException('Article not found');
        }

        $status = (string)$row['status'];
        if ($status !== 'published' && (string)$row['author_uuid'] !== $viewerUuid) {
            throw new \RuntimeException('Forbidden');
        }

        return $this->mapRowToDto($row);
    }

    public function publish(string $uuid, string $authorUuid): ArticlePublishResponseDto
    {
        $row = $this->articleRepository->findByUuid($uuid);
        if (!$row) {
            throw new \InvalidArgumentException('Article not found');
        }

        if ((string)$row['author_uuid'] !== $authorUuid) {
            throw new \RuntimeException('Forbidden');
        }

        $title = trim((string)$row['title']);
        $body = (string)$row['body'];
        if ($title === '') {
            throw new \InvalidArgumentException('title is required to publish');
        }

        if (strlen(trim($body)) < 20) {
            throw new \InvalidArgumentException('body must be at least 20 characters');
        }

        $this->validateBody((string)$row['type'], $body);
        $this->articleRepository->publish($uuid);

        $updated = $this->articleRepository->findByUuid($uuid);
        if (!$updated) {
            throw new \RuntimeException('Failed to load published article');
        }

        $this->syncFeedItem($updated);

        $response = new ArticlePublishResponseDto();
        $response->id = (string)$updated['uuid'];
        $response->status = (string)$updated['status'];
        $response->publishedAt = gmdate('Y-m-d\TH:i:s\Z', strtotime((string)$updated['published_at']));
        $response->updatedAt = gmdate('Y-m-d\TH:i:s\Z', strtotime((string)$updated['updated_at']));
        return $response;
    }

    public function uploadCover(): ArticleCoverUploadResponseDto
    {
        $file = $this->uploadArticleFile('cover', '/articles');
        $uuid = $this->articleRepository->createCoverMedia($file);
        $media = $this->articleRepository->findCoverMediaByUuid($uuid);
        if (!$media) {
            throw new \RuntimeException('Failed to load cover media');
        }

        $mediaDto = new ArticleCoverMediaDto();
        $mediaDto->mediaId = (string)$media['uuid'];
        $mediaDto->relativePath = (string)$media['relative_path'];
        $mediaDto->extension = strtolower((string)$media['extension']);

        $response = new ArticleCoverUploadResponseDto();
        $response->media = $mediaDto;
        return $response;
    }

    public function uploadMedia(): ArticleMediaUploadResponseDto
    {
        $file = $this->uploadArticleFile('file', '/articles/media');
        $uuid = $this->articleRepository->createArticleMedia($file, 'image');
        $media = $this->articleRepository->findMediaByUuid($uuid);
        if (!$media) {
            throw new \RuntimeException('Failed to load article media');
        }

        $mediaDto = new ArticleMediaDto();
        $mediaDto->mediaId = (string)$media['uuid'];
        $mediaDto->relativePath = (string)$media['relative_path'];
        $mediaDto->extension = strtolower((string)$media['extension']);
        $mediaDto->width = isset($media['width']) ? (int)$media['width'] : null;
        $mediaDto->height = isset($media['height']) ? (int)$media['height'] : null;

        $response = new ArticleMediaUploadResponseDto();
        $response->media = $mediaDto;
        return $response;
    }

    private function syncFeedItem(array $row): void
    {
        $payload = $this->buildFeedPayload($row);
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $feedItemUuid = $row['feed_item_uuid'] ?? null;
        if (!$feedItemUuid) {
            $newFeedItemUuid = Uuid::uuid7()->toString();
            $this->feedRepository->createFeedItem(
                feedItemUuid: $newFeedItemUuid,
                userUuid: (string)$row['author_uuid'],
                wallUserUuid: (string)$row['author_uuid'],
                type: 'article',
                text: null,
                payloadJson: $payloadJson
            );
            $this->articleRepository->setFeedItemUuid((string)$row['uuid'], $newFeedItemUuid);
            return;
        }

        $this->feedRepository->updateFeedItemPayload((string)$feedItemUuid, $payloadJson);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFeedPayload(array $row): array
    {
        $excerpt = isset($row['excerpt']) ? trim((string)$row['excerpt']) : '';
        $body = (string)($row['body'] ?? '');
        $readTime = $this->calculateReadTime($body);

        $payload = [
            'articleId' => 'article-' . (string)$row['uuid'],
            'articleType' => (string)$row['type'],
            'title' => (string)$row['title'],
            'excerpt' => $excerpt !== '' ? $excerpt : null,
            'readTime' => $readTime
        ];

        if (!empty($row['cover_relative_path']) && !empty($row['cover_extension'])) {
            $cover = [
                'relative_path' => (string)$row['cover_relative_path'],
                'extension' => strtolower((string)$row['cover_extension'])
            ];
            $position = $this->decodeJsonArray($row['cover_position'] ?? null);
            if ($position !== null) {
                $cover['position'] = $position;
            }
            $payload['cover'] = $cover;
        }

        return $payload;
    }

    private function mapRowToDto(array $row): ArticleDto
    {
        $dto = new ArticleDto();
        $dto->id = (string)$row['uuid'];
        $dto->authorId = (string)$row['author_uuid'];
        $dto->title = (string)$row['title'];
        $dto->type = (string)$row['type'];
        $dto->format = (string)$row['format'];
        $dto->body = (string)$row['body'];
        $dto->excerpt = $row['excerpt'] !== null ? (string)$row['excerpt'] : null;
        $dto->status = (string)$row['status'];
        $dto->chordsNotation = $row['chords_notation'] !== null ? (string)$row['chords_notation'] : null;
        $dto->createdAt = gmdate('Y-m-d\TH:i:s\Z', strtotime((string)$row['created_at']));
        $dto->updatedAt = gmdate('Y-m-d\TH:i:s\Z', strtotime((string)$row['updated_at']));
        $dto->publishedAt = $row['published_at'] ? gmdate('Y-m-d\TH:i:s\Z', strtotime((string)$row['published_at'])) : null;

        if (!empty($row['cover_media_uuid']) && !empty($row['cover_relative_path']) && !empty($row['cover_extension'])) {
            $cover = new ArticleCoverDto();
            $cover->mediaId = (string)$row['cover_media_uuid'];
            $cover->relativePath = (string)$row['cover_relative_path'];
            $cover->extension = strtolower((string)$row['cover_extension']);
            $cover->position = $this->decodeJsonArray($row['cover_position'] ?? null);
            $dto->cover = $cover;
        }

        $embeds = $this->decodeJsonArray($row['embeds'] ?? null) ?? [];
        foreach ($embeds as $embed) {
            if (!is_array($embed)) {
                continue;
            }
            $type = trim((string)($embed['type'] ?? ''));
            if ($type === '') {
                continue;
            }
            $embedDto = new ArticleEmbedDto();
            $embedDto->type = $type;

            if ($type === 'image') {
                $mediaId = trim((string)($embed['mediaId'] ?? ''));
                if ($mediaId === '') {
                    continue;
                }
                $embedDto->mediaId = $mediaId;
                $caption = trim((string)($embed['caption'] ?? ''));
                $embedDto->caption = $caption !== '' ? $caption : null;
                $position = trim((string)($embed['position'] ?? ''));
                $embedDto->position = $position !== '' ? $position : null;
                $media = $this->articleRepository->findMediaByUuid($mediaId);
                if ($media) {
                    $embedDto->relativePath = (string)$media['relative_path'];
                    $embedDto->extension = strtolower((string)$media['extension']);
                    $embedDto->width = isset($media['width']) ? (int)$media['width'] : null;
                    $embedDto->height = isset($media['height']) ? (int)$media['height'] : null;
                }
            } else {
                $url = trim((string)($embed['url'] ?? ''));
                if ($url === '') {
                    continue;
                }
                $embedDto->url = $url;
            }

            $dto->embeds[] = $embedDto;
        }

        return $dto;
    }

    private function validateBody(string $type, string $body): void
    {
        if ($type !== 'song' && $this->containsBlockDirective($body)) {
            throw new \InvalidArgumentException('block directives are only allowed for song');
        }

        if ($type === 'song') {
            $this->validateChordTokens($body);
        }
    }

    private function containsBlockDirective(string $body): bool
    {
        foreach (preg_split('/\R/', $body) as $line) {
            if (preg_match('/^@block\s+/i', trim($line))) {
                return true;
            }
        }
        return false;
    }

    private function validateChordTokens(string $body): void
    {
        if (!preg_match_all('/\[([^\]]+)\]/', $body, $matches)) {
            return;
        }

        foreach ($matches[1] as $token) {
            if (strlen($token) > 32) {
                throw new \InvalidArgumentException('Chord token is too long');
            }
        }
    }

    private function calculateReadTime(string $text): string
    {
        $clean = preg_replace('/\[[^\]]+\]/', '', $text);
        $clean = preg_replace('/^@block\s+.*$/m', '', $clean);
        $words = preg_split('/\s+/', trim((string)$clean));
        $wordCount = is_array($words) ? count(array_filter($words)) : 0;
        $minutes = max(1, (int)ceil($wordCount / 200));
        return $minutes . ' min';
    }

    private function normalizeCoverPosition(array $position): ?array
    {
        if (!isset($position['x']) || !isset($position['y'])) {
            return null;
        }
        $x = (float)$position['x'];
        $y = (float)$position['y'];
        if ($x < 0 || $x > 1 || $y < 0 || $y > 1) {
            throw new \InvalidArgumentException('cover_position must be in range 0..1');
        }
        return ['x' => $x, 'y' => $y];
    }

    /**
     * @param array<int, mixed> $embeds
     * @return array<int, array<string, string>>
     */
    private function normalizeEmbeds(array $embeds): array
    {
        $result = [];
        foreach ($embeds as $embed) {
            if (!is_array($embed)) {
                continue;
            }
            $type = trim((string)($embed['type'] ?? ''));
            if ($type === '') {
                continue;
            }

            if ($type === 'image') {
                $mediaId = trim((string)($embed['mediaId'] ?? ''));
                if ($mediaId === '') {
                    continue;
                }
                if (!preg_match('/^[0-9a-fA-F-]{36}$/', $mediaId)) {
                    throw new \InvalidArgumentException('mediaId must be UUID');
                }
                if (!$this->articleRepository->findMediaByUuid($mediaId)) {
                    throw new \InvalidArgumentException('media not found');
                }
                $caption = trim((string)($embed['caption'] ?? ''));
                $position = trim((string)($embed['position'] ?? ''));
                $result[] = [
                    'type' => 'image',
                    'mediaId' => $mediaId,
                    'caption' => $caption !== '' ? $caption : null,
                    'position' => $position !== '' ? $position : null
                ];
                continue;
            }

            $url = trim((string)($embed['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $result[] = [
                'type' => $type,
                'url' => $url
            ];
        }
        return $result;
    }

    private function decodeJsonArray(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : null;
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

    /**
     * @return array<string, mixed>
     */
    private function uploadArticleFile(string $field, string $dir): array
    {
        if (!isset($_FILES[$field])) {
            throw new \InvalidArgumentException($field . ' file is required');
        }

        $mediaHelper = new MediaHelper($dir);
        $mediaHelper->setNames([$field]);
        try {
            $uploadResult = $mediaHelper->import();
        } catch (\RuntimeException $e) {
            throw new \InvalidArgumentException($e->getMessage(), 0, $e);
        }

        $raw = $uploadResult[$field] ?? [];
        $normalized = $this->normalizeMediaObjects($raw);
        if (empty($normalized)) {
            throw new \InvalidArgumentException($field . ' file is required');
        }

        $file = $normalized[0];
        $file['extension'] = strtolower((string)($file['extension'] ?? ''));
        if (!in_array($file['extension'], ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new \InvalidArgumentException('Unsupported media extension: ' . $file['extension']);
        }

        if (!isset($file['errors']) || $file['errors'] === null) {
            $file['errors'] = '';
        }

        $size = $this->detectImageSize($file['full_path'] ?? null);
        if ($size !== null) {
            $file['width'] = $size['width'];
            $file['height'] = $size['height'];
        }

        return $file;
    }

    /**
     * @return array<string, int>|null
     */
    private function detectImageSize(?string $path): ?array
    {
        if (!$path || !is_file($path)) {
            return null;
        }
        $info = @getimagesize($path);
        if (!is_array($info) || empty($info[0]) || empty($info[1])) {
            return null;
        }
        return ['width' => (int)$info[0], 'height' => (int)$info[1]];
    }
}
