<?php

namespace Api\Instruments\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "InstrumentListResponse", description: "Список инструментов")]
class InstrumentListResponse
{
    /**
     * @var array<int, InstrumentDto>
     */
    public array $items = [];
}
