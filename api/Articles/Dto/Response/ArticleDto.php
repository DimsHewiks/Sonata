<?php

namespace Api\Articles\Dto\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "Article", description: "Статья")]
class ArticleDto
{
    #[OA\Property(example: "8b0d4c7b-52d4-4c32-84ab-7b51d6a19a5b")]
    public string $id;

    #[OA\Property(example: "2f6c2f9e-3f2a-4b3c-9b88-6c3d1a1a3b18")]
    public string $authorId;

    #[OA\Property(example: "Wonderwall — разбор")]
    public string $title;

    #[OA\Property(example: "song")]
    public string $type;

    #[OA\Property(example: "markdown")]
    public string $format;

    #[OA\Property(example: "@block verse Куплет 1\n[Em7]Today is gonna be...")]
    public string $body;

    #[OA\Property(example: "Разбор с аккордами и блоками.")]
    public ?string $excerpt = null;

    #[OA\Property(example: "draft")]
    public string $status;

    #[OA\Property(ref: "#/components/schemas/ArticleCover")]
    public ?ArticleCoverDto $cover = null;

    /**
     * @var array<int, ArticleEmbedDto>
     */
    public array $embeds = [];

    #[OA\Property(example: "german")]
    public ?string $chordsNotation = null;

    #[OA\Property(example: "2026-02-14T06:27:13Z")]
    public string $createdAt;

    #[OA\Property(example: "2026-02-14T06:40:10Z")]
    public string $updatedAt;

    #[OA\Property(example: "2026-02-14T07:00:00Z")]
    public ?string $publishedAt = null;
}
