<?php

namespace Api\User\Dto;

use Sonata\Framework\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

class UserCreateDto extends ParamsDTO
{
    #[Assert\NotBlank(message: "Имя обязательно")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Имя не должно превышать {{ limit }} символов"
    )]
    #[OA\Property(
        description: "Полное имя пользователя",
        example: "Александр"
    )]
    public string $name;

    #[Assert\NotBlank(message: "Email обязателен")]
    #[Assert\Email(message: "Некорректный email")]
    #[OA\Property(
        description: "Электронная почта",
        example: "alex@example.com"
    )]
    public string $email;

    #[Assert\Range(
        notInRangeMessage: 'Возраст должен быть от {{ min }} до {{ max }} лет',
        min: 18,
        max: 120
    )]
    #[Assert\NotBlank(message: "Возраст обязателен")]
    #[OA\Property(
        description: "Возраст пользователя",
        example: 30
    )]
    public int $age;
}