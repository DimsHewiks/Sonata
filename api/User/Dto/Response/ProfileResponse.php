<?php

namespace Api\User\Dto\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "Profile", description: "Профиль пользователя")]
class ProfileResponse
{
    #[OA\Property(example: "Александр")]
    public string $firstName;

    #[OA\Property(example: "Иванов")]
    public string $lastName;

    #[OA\Property(example: 30)]
    public int $age;
}