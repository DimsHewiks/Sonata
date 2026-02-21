<?php

namespace Api\Posts\DTOs\Request;

use OpenApi\Attributes as OA;
use Sonata\Framework\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;

class CommentReactionToggleDTO extends ParamsDTO
{
    #[Assert\NotBlank(message: "emoji is required")]
    #[Assert\Length(
        min: 1,
        max: 32,
        minMessage: "emoji must not be empty",
        maxMessage: "emoji is too long"
    )]
    #[OA\Property(example: "🔥")]
    public ?string $emoji = null;
}
