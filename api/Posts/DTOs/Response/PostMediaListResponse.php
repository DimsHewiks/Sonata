<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "PostMediaListResponse", description: "Список медиа постов")]
class PostMediaListResponse
{
    /**
     * @var array<int, PostMediaDto>
     */
    public array $items = [];
}
