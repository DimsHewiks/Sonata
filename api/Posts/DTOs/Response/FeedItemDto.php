<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "FeedItem", description: "Feed item")]
class FeedItemDto
{
    #[OA\Property(example: "post-550e8400-e29b-41d4-a716-446655440000")]
    public string $id;

    #[OA\Property(example: "post")]
    public string $type;

    #[OA\Property(ref: "#/components/schemas/FeedAuthor")]
    public FeedAuthorDto $author;

    #[OA\Property(example: "2026-02-12T10:10:00Z")]
    public string $createdAt;

    #[OA\Property(example: "Новый плейлист")]
    public ?string $text = null;

    /**
     * @var array<int, FeedMediaDto>|null
     */
    public ?array $media = null;

    #[OA\Property(ref: "#/components/schemas/FeedStats")]
    public ?FeedStatsDto $stats = null;

    #[OA\Property(example: "Какой формат хотите чаще?")]
    public ?string $question = null;

    /**
     * @var array<int, FeedOptionDto>|null
     */
    public ?array $options = null;

    #[OA\Property(example: true)]
    public ?bool $multiple = null;

    #[OA\Property(example: 0)]
    public ?int $totalVotes = null;

    #[OA\Property(example: "3 дня")]
    public ?string $duration = null;

    /**
     * @var array<int, string>|null
     */
    public ?array $userVoteIds = null;

    #[OA\Property(example: "b")]
    public ?string $correctOptionId = null;

    #[OA\Property(example: "Chorus расширяет стереобазу.")]
    public ?string $explanation = null;

    #[OA\Property(example: "a")]
    public ?string $userAnswerId = null;

    #[OA\Property(example: true)]
    public ?bool $isCorrect = null;

    #[OA\Property(example: "Как собрать лайв сет")]
    public ?string $title = null;

    #[OA\Property(example: "Подборка практических советов...")]
    public ?string $description = null;

    #[OA\Property(example: "4 min")]
    public ?string $readTime = null;

    #[OA\Property(example: "article-550e8400-e29b-41d4-a716-446655440000")]
    public ?string $articleId = null;

    #[OA\Property(example: "song")]
    public ?string $articleType = null;

    #[OA\Property(ref: "#/components/schemas/FeedCover")]
    public ?FeedCoverDto $cover = null;
}
