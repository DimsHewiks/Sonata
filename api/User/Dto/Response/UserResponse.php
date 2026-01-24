<?php

namespace Api\User\Dto\Response;

use Core\Http\ApiResponse;
use OpenApi\Attributes as OA;

#[OA\Schema(title: "User", description: "Информация о пользователе")]
class UserResponse  extends ApiResponse //Если хотим возвращать как json
{
    #[OA\Property(example: 1)]
    public int $id;

    #[OA\Property(example: "Александр")]
    public string $name;

    #[OA\Property(example: "alex@example.com")]
    public string $email;

    public function __construct(
        int $id,
        string $name,
        string $email,
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
    }
}