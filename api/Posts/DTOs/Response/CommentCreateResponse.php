<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "CommentCreateResponse", description: "Comment create response")]
class CommentCreateResponse
{
    #[OA\Property(ref: "#/components/schemas/Comment")]
    public CommentDto $item;
}
