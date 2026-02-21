<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "CommentReaction", description: "Comment reaction item")]
class CommentReactionDto
{
    #[OA\Property(example: "❤️")]
    public string $emoji;

    #[OA\Property(example: 12)]
    public int $count;

    #[OA\Property(example: true)]
    public bool $active = false;
}
