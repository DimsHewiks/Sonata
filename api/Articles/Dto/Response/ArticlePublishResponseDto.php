<?php

namespace Api\Articles\Dto\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "ArticlePublishResponse", description: "Публикация статьи")]
class ArticlePublishResponseDto
{
    #[OA\Property(example: "8b0d4c7b-52d4-4c32-84ab-7b51d6a19a5b")]
    public string $id;

    #[OA\Property(example: "published")]
    public string $status;

    #[OA\Property(example: "2026-02-14T07:00:00Z")]
    public string $publishedAt;

    #[OA\Property(example: "2026-02-14T07:00:00Z")]
    public string $updatedAt;
}
