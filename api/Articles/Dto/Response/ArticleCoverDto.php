<?php

namespace Api\Articles\Dto\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "ArticleCover", description: "Обложка статьи")]
class ArticleCoverDto
{
    #[OA\Property(example: "550e8400-e29b-41d4-a716-446655440000")]
    public string $mediaId;

    #[OA\Property(example: "upload/articles/cover.webp")]
    public string $relativePath;

    #[OA\Property(example: "webp")]
    public string $extension;

    #[OA\Property(example: '{"x":0.5,"y":0.35}')]
    public ?array $position = null;
}
