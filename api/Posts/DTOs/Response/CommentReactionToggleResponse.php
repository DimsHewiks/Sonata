<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "CommentReactionToggleResponse", description: "Toggle comment reaction response")]
class CommentReactionToggleResponse
{
    #[OA\Property(example: "550e8400-e29b-41d4-a716-446655440000")]
    public string $commentId;

    /**
     * @var array<int, CommentReactionDto>
     */
    public array $reactions = [];
}
