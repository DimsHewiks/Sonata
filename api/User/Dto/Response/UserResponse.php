<?php

namespace Api\User\Dto\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "User", description: "Информация о пользователе")]
class UserResponse
{
    #[OA\Property(example: 1)]
    public int $id;

    #[OA\Property(example: "alex@example.com")]
    public string $email;

    #[OA\Property(ref: "#/components/schemas/ProfileResponse")]
    public ProfileResponse $profile;
}