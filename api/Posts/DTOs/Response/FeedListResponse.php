<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "FeedListResponse", description: "Feed list response")]
class FeedListResponse
{
    /**
     * @var array<int, FeedItemDto>
     */
    public array $items = [];
}
