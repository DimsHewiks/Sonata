<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "FeedStats", description: "Post stats")]
class FeedStatsDto
{
    #[OA\Property(example: 0)]
    public int $likes;

    #[OA\Property(example: 0)]
    public int $comments;
}
