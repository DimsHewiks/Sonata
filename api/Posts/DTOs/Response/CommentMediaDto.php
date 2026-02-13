<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "CommentMedia", description: "Comment media object")]
class CommentMediaDto
{
    #[OA\Property(example: "mix.jpg")]
    public ?string $original_name = null;

    #[OA\Property(example: "f91a1c.jpg")]
    public ?string $saved_name = null;

    #[OA\Property(example: "/var/www/sonata/uploads/f91a1c.jpg")]
    public ?string $full_path = null;

    #[OA\Property(example: "uploads/f91a1c.jpg")]
    public string $relative_path;

    #[OA\Property(example: 245123)]
    public ?int $size = null;

    #[OA\Property(example: "jpg")]
    public string $extension;

    #[OA\Property(example: true)]
    public ?bool $uploaded = null;

    #[OA\Property(example: "")]
    public ?string $errors = null;
}
