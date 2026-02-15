<?php

namespace Api\User\Dto\Request;

use OpenApi\Attributes as OA;
use Sonata\Framework\Http\ParamsDTO;

class ProfileDescriptionUpdateDto extends ParamsDTO
{
    #[OA\Property(example: "Играю в рок-группе, ищу клавишника")]
    public ?string $description = null;
}
