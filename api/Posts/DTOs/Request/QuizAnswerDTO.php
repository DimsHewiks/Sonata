<?php

namespace Api\Posts\DTOs\Request;

use OpenApi\Attributes as OA;
use Sonata\Framework\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;

class QuizAnswerDTO extends ParamsDTO
{
    #[Assert\NotBlank(message: "feedId is required")]
    #[OA\Property(example: "quiz-550e8400-e29b-41d4-a716-446655440000")]
    public ?string $feedId = null;

    #[Assert\NotBlank(message: "answerId is required")]
    #[Assert\Regex(pattern: "/^[a-z]$/", message: "answerId must be a single letter")]
    #[OA\Property(example: "b")]
    public ?string $answerId = null;
}
