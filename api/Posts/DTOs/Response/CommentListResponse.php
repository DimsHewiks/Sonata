<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "CommentListResponse", description: "Comments list response")]
class CommentListResponse
{
    /**
     * @var array<int, CommentDto>
     */
    public array $items = [];
}
