<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "Comment", description: "Comment item")]
class CommentDto
{
    #[OA\Property(example: "comment-550e8400-e29b-41d4-a716-446655440000")]
    public string $id;

    #[OA\Property(ref: "#/components/schemas/FeedAuthor")]
    public FeedAuthorDto $author;

    #[OA\Property(example: "2026-02-12T10:10:00Z")]
    public string $createdAt;

    #[OA\Property(example: "Отличный пост!")]
    public ?string $text = null;

    /**
     * @var array<int, CommentMediaDto>|null
     */
    public ?array $media = null;

    #[OA\Property(example: "comment-550e8400-e29b-41d4-a716-446655440001")]
    public ?string $parentId = null;

    /**
     * @var array<int, CommentReactionDto>
     */
    public array $reactions = [];

    /**
     * @var array<int, CommentDto>
     */
    public array $children = [];
}
