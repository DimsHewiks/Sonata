<?php

namespace Api\Auth\DTOs\Request;

use Sonata\Framework\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

class LoginDTO extends ParamsDTO
{
    #[OA\Property(
        description: "Логин",
        example: "alex"
    )]
    public ?string $login = null;

    #[Assert\Email(message: "Некорректный email")]
    #[OA\Property(
        description: "Email (альтернатива логину)",
        example: "alex@example.com"
    )]
    public ?string $email = null;

    #[Assert\NotBlank(message: "Пароль обязателен")]
    #[OA\Property(
        description: "Пароль",
        example: "153sdfAS-aszfda-as"
    )]
    public ?string $password = null;
}
