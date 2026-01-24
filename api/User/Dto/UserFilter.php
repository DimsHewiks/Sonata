<?php

namespace Api\User\Dto;

use Core\Http\ParamsDTO;

class UserFilter extends ParamsDTO
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
}