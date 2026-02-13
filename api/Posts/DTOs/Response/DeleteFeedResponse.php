<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "DeleteFeedResponse", description: "Delete feed item response")]
class DeleteFeedResponse
{
    #[OA\Property(example: true)]
    public bool $deleted;

    #[OA\Property(example: "post-550e8400-e29b-41d4-a716-446655440000")]
    public string $feedId;
}
