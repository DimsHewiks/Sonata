<?php

namespace Api\Instruments\Dto;

use OpenApi\Attributes as OA;
use Sonata\Framework\Http\ParamsDTO;

class UserInstrumentsUpdateDto extends ParamsDTO
{
    #[OA\Property(example: "[1, 2, 3]")]
    public ?array $instrumentIds = null;
}
