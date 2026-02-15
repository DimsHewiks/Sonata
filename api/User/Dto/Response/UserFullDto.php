<?php

namespace Api\User\Dto\Response;

use Api\Instruments\Dto\InstrumentDto;
use OpenApi\Attributes as OA;

#[OA\Schema(title: "UserFull", description: "Полный профиль пользователя")]
class UserFullDto
{
    #[OA\Property(example: "00000000-0000-4000-8000-000000000001")]
    public string $uuid;

    #[OA\Property(example: "Алекс")]
    public string $name;

    #[OA\Property(example: 25)]
    public int $age;

    #[OA\Property(example: "alex")]
    public string $login;

    #[OA\Property(example: "alex@example.com")]
    public ?string $email = null;

    #[OA\Property(example: "upload/avatars/avatar.webp")]
    public ?string $avatarPath = null;

    #[OA\Property(example: "Играю в рок-группе, ищу клавишника")]
    public ?string $description = null;

    /**
     * @var array<int, InstrumentDto>
     */
    #[OA\Property(type: "array", items: new OA\Items(ref: "#/components/schemas/Instrument"))]
    public array $instruments = [];
}
