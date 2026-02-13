<?php

namespace Api\Posts\DTOs\Request;

use OpenApi\Attributes as OA;
use Sonata\Framework\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;

class FeedCreateDTO extends ParamsDTO
{
    #[Assert\NotBlank(message: "type is required")]
    #[Assert\Choice(choices: ["post", "poll", "quiz", "article"], message: "Unsupported type")]
    #[OA\Property(example: "post")]
    public ?string $type = null;

    #[OA\Property(example: "New playlist")]
    public ?string $text = null;

    #[OA\Property(example: "{\"question\":\"How often?\"}")]
    public ?string $payload = null;
}
