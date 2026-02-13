<?php

namespace Api\Posts\DTOs\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "FeedCreateResponse", description: "Feed create response")]
class FeedCreateResponse
{
    #[OA\Property(ref: "#/components/schemas/FeedItem")]
    public FeedItemDto $item;
}
