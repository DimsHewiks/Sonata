<?php

namespace Api\Posts\DTOs\Request;

use OpenApi\Attributes as OA;
use Sonata\Framework\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;

class CommentsQueryDTO extends ParamsDTO
{
    #[Assert\Choice(choices: ["asc", "desc"], message: "order must be asc or desc")]
    #[OA\Property(example: "asc")]
    public ?string $order = "asc";
}
