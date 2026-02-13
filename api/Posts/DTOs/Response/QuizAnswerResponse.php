<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "QuizAnswerResponse", description: "Quiz answer response")]
class QuizAnswerResponse
{
    #[OA\Property(ref: "#/components/schemas/QuizAnswerResult")]
    public QuizAnswerResultDto $result;
}
