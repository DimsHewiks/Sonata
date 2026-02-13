<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "CommentDeleteResponse", description: "Comment delete response")]
class CommentDeleteResponse
{
    #[OA\Property(example: true)]
    public bool $deleted;

    #[OA\Property(example: "comment-550e8400-e29b-41d4-a716-446655440000")]
    public string $commentId;
}
