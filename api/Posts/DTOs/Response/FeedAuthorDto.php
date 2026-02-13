<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "FeedAuthor", description: "Author data")]
class FeedAuthorDto
{
    #[OA\Property(example: "Марина")]
    public string $name;

    #[OA\Property(example: "marina")]
    public string $login;

    #[OA\Property(ref: "#/components/schemas/FeedAvatar")]
    public ?FeedAvatarDto $avatar = null;
}
