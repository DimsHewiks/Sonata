<?php

namespace Api\Auth\DTOs\Request;

use Core\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

class RegistDTO extends ParamsDTO
{
    #[Assert\NotBlank(message: "Email обязателен")]
    #[Assert\Email(message: "Некорректный email")]
    #[OA\Property(
        description: "Электронная почта",
        example: "alex@example.com"
    )]
    public string $email;

    #[Assert\NotBlank(message: "пароль обязателен")]
    #[OA\Property(
        description: "Пароль",
        example: "153sdfAS-aszfda-as"
    )]
    public string $password;
}