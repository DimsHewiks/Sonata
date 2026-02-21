<?php

namespace Api\Posts\DTOs\Request;

use OpenApi\Attributes as OA;
use Sonata\Framework\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;

class FeedListQueryDTO extends ParamsDTO
{
    #[Assert\PositiveOrZero(message: "offset must be greater than or equal to 0")]
    #[OA\Property(example: 0, default: 0)]
    public ?int $offset = 0;

    #[Assert\Range(min: 1, max: 100, notInRangeMessage: "limit must be between {{ min }} and {{ max }}")]
    #[OA\Property(example: 20, default: 20)]
    public ?int $limit = 20;
}
