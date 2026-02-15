<?php

namespace Api\User\Dto\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "UserAvatar", description: "Аватар пользователя")]
class UserAvatarDto
{
    #[OA\Property(example: "upload/avatars/avatar.webp")]
    public string $relativePath;

    #[OA\Property(example: "webp")]
    public string $extension;
}
