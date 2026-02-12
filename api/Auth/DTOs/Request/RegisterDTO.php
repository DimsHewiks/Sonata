<?php

namespace Api\Auth\DTOs\Request;

use Sonata\Framework\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

class RegisterDTO extends ParamsDTO
{
    #[Assert\NotBlank(message: "Имя обязательно")]
    #[OA\Property(
        description: "Имя пользователя",
        example: "Алекс"
    )]
    public ?string $name = null;

    #[Assert\NotBlank(message: "Возраст обязателен")]
    #[Assert\Positive(message: "Возраст должен быть положительным")]
    #[OA\Property(
        description: "Возраст пользователя",
        example: 25
    )]
    public ?int $age = null;

    #[Assert\NotBlank(message: "Логин обязателен")]
    #[OA\Property(
        description: "Логин",
        example: "alex"
    )]
    public ?string $login = null;

    #[Assert\Email(message: "Некорректный email")]
    #[OA\Property(
        description: "Электронная почта",
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
