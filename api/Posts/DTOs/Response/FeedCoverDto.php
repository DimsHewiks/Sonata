<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "FeedCover", description: "Обложка в ленте")]
class FeedCoverDto
{
    #[OA\Property(example: "upload/articles/cover.webp")]
    public string $relative_path;

    #[OA\Property(example: "webp")]
    public string $extension;

    #[OA\Property(example: "{\"x\":0.5,\"y\":0.25}")]
    public ?array $position = null;
}
