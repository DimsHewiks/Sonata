<?php

namespace Api\Articles\Dto\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "ArticleMediaUploadResponse", description: "Ответ загрузки медиа статьи")]
class ArticleMediaUploadResponseDto
{
    #[OA\Property(ref: "#/components/schemas/ArticleMedia")]
    public ArticleMediaDto $media;
}
