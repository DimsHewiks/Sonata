<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "PostMedia", description: "Медиа постов")]
class PostMediaDto
{
    #[OA\Property(example: "upload/feed/photo.webp")]
    public string $relative_path;

    #[OA\Property(example: "webp")]
    public string $extension;

    #[OA\Property(example: "post-550e8400-e29b-41d4-a716-446655440000")]
    public ?string $feedId = null;
}
