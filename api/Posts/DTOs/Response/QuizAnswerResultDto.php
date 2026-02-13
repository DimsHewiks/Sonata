<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "QuizAnswerResult", description: "Quiz answer result")]
class QuizAnswerResultDto
{
    #[OA\Property(example: "quiz-550e8400-e29b-41d4-a716-446655440000")]
    public string $feedId;

    #[OA\Property(example: "b")]
    public string $userAnswerId;

    #[OA\Property(example: true)]
    public bool $isCorrect;

    #[OA\Property(example: "c")]
    public string $correctOptionId;
}
