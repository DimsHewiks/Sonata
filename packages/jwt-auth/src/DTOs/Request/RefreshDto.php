<?php

namespace Sonata\JwtAuth\DTOs\Request;

use Sonata\Framework\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

class RefreshDto extends ParamsDTO
{
    #[Assert\NotBlank(message: "Обязательный параметр")]
    #[OA\Property(
        description: "Рефреш токен",
        example: "aAdbggGvb2*##kso-csxzvafs-90"
    )]
    public string $refreshToken;
}