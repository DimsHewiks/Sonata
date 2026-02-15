<?php

namespace Api\Articles\Dto\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "ArticleMedia", description: "Медиа статьи")]
class ArticleMediaDto
{
    #[OA\Property(example: "44b2177f-2f5c-4e79-bc9a-812b3e8c7a24")]
    public string $mediaId;

    #[OA\Property(example: "upload/articles/media/cover.webp")]
    public string $relativePath;

    #[OA\Property(example: "webp")]
    public string $extension;

    #[OA\Property(example: 1200)]
    public ?int $width = null;

    #[OA\Property(example: 800)]
    public ?int $height = null;
}
