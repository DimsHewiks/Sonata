<?php

namespace Api\Instruments\Dto;

use OpenApi\Attributes as OA;
use Sonata\Framework\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;

class InstrumentCreateDto extends ParamsDTO
{
    #[Assert\NotBlank(message: "name is required")]
    #[OA\Property(example: "Гитара")]
    public ?string $name = null;

    #[OA\Property(example: "guitar")]
    public ?string $sticker = null;
}
