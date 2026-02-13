<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "FeedOption", description: "Option for poll/quiz")]
class FeedOptionDto
{
    #[OA\Property(example: "a")]
    public string $id;

    #[OA\Property(example: "Плейлисты")]
    public string $text;

    #[OA\Property(example: 0)]
    public ?int $votes = null;
}
