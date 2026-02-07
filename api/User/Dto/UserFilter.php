<?php

namespace Api\User\Dto;

use Sonata\Framework\Http\ParamsDTO;

class UserFilter extends ParamsDTO
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
}