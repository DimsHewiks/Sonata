<?php

namespace Api\Articles\Dto\Request;

use OpenApi\Attributes as OA;
use Sonata\Framework\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;

class ArticleCreateDto extends ParamsDTO
{
    #[Assert\NotBlank(message: "title is required")]
    #[OA\Property(example: "mhnfgfd")]
    public ?string $title = null;

    #[Assert\Choice(choices: ["text", "song"], message: "type must be text or song")]
    #[OA\Property(example: "text")]
    public ?string $type = null;

    #[Assert\Choice(choices: ["markdown"], message: "format must be markdown")]
    #[OA\Property(example: "markdown")]
    public ?string $format = null;
}
