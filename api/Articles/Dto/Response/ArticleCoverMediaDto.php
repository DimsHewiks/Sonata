<?php

namespace Api\Articles\Dto\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "ArticleCoverMedia", description: "Медиа обложки статьи")]
class ArticleCoverMediaDto
{
    #[OA\Property(example: "44b2177f-2f5c-4e79-bc9a-812b3e8c7a24")]
    public string $mediaId;

    #[OA\Property(example: "upload/articles/cover.webp")]
    public string $relativePath;

    #[OA\Property(example: "webp")]
    public string $extension;
}
