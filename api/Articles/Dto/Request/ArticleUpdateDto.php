<?php

namespace Api\Articles\Dto\Request;

use OpenApi\Attributes as OA;
use Sonata\Framework\Http\ParamsDTO;

class ArticleUpdateDto extends ParamsDTO
{
    #[OA\Property(example: "Wonderwall — разбор")]
    public ?string $title = null;

    #[OA\Property(example: "song")]
    public ?string $type = null;

    #[OA\Property(example: "markdown")]
    public ?string $format = null;

    #[OA\Property(example: "@block verse Куплет 1\n[Em7]Today is gonna be...")]
    public ?string $body = null;

    #[OA\Property(example: "Разбор с аккордами и блоками.")]
    public ?string $excerpt = null;

    #[OA\Property(example: "german")]
    public ?string $chordsNotation = null;

    #[OA\Property(example: "44b2177f-2f5c-4e79-bc9a-812b3e8c7a24")]
    public ?string $coverMediaId = null;

    #[OA\Property(example: "{\"x\":0.5,\"y\":0.25}")]
    public ?array $coverPosition = null;

    #[OA\Property(example: "[{\"type\":\"image\",\"mediaId\":\"44b2177f-2f5c-4e79-bc9a-812b3e8c7a24\",\"caption\":\"Подпись\"}]")]
    public ?array $embeds = null;
}
