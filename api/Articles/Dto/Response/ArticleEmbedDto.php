<?php

namespace Api\Articles\Dto\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "ArticleEmbed", description: "Встраивание в статье")]
class ArticleEmbedDto
{
    #[OA\Property(example: "youtube")]
    public string $type;

    #[OA\Property(example: "https://www.youtube.com/watch?v=...")]
    public ?string $url = null;

    #[OA\Property(example: "44b2177f-2f5c-4e79-bc9a-812b3e8c7a24")]
    public ?string $mediaId = null;

    #[OA\Property(example: "Подпись")]
    public ?string $caption = null;

    #[OA\Property(example: "inline")]
    public ?string $position = null;

    #[OA\Property(example: "upload/articles/media/cover.webp")]
    public ?string $relativePath = null;

    #[OA\Property(example: "webp")]
    public ?string $extension = null;

    #[OA\Property(example: 1200)]
    public ?int $width = null;

    #[OA\Property(example: 800)]
    public ?int $height = null;
}
