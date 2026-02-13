<?php

namespace Api\Posts\DTOs\Request;

use OpenApi\Attributes as OA;
use Sonata\Framework\Http\ParamsDTO;
use Symfony\Component\Validator\Constraints as Assert;

class CommentCreateDTO extends ParamsDTO
{
    #[OA\Property(example: "Топовый пост!")]
    public ?string $text = null;

    #[OA\Property(example: "comment-550e8400-e29b-41d4-a716-446655440000")]
    public ?string $parentId = null;
}
