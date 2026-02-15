<?php

namespace Api\Instruments\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "Instrument", description: "Музыкальный инструмент")]
class InstrumentDto
{
    #[OA\Property(example: 1)]
    public int $id;

    #[OA\Property(example: "Гитара")]
    public string $name;

    #[OA\Property(example: "guitar")]
    public ?string $sticker = null;
}
