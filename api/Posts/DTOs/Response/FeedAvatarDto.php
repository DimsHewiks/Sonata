<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "FeedAvatar", description: "Avatar data")]
class FeedAvatarDto
{
    #[OA\Property(example: "avatars/a1.jpg")]
    public string $relative_path;

    #[OA\Property(example: "jpg")]
    public string $extension;
}
