<?php

namespace Api\Articles\Dto\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "ArticleCoverUploadResponse", description: "Ответ загрузки обложки статьи")]
class ArticleCoverUploadResponseDto
{
    #[OA\Property(ref: "#/components/schemas/ArticleCoverMedia")]
    public ArticleCoverMediaDto $media;
}
