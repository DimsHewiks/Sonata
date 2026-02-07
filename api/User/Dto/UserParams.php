<?php

namespace Api\User\Dto;

use Sonata\Framework\Http\ParamsDTO;

class UserParams extends ParamsDTO
{
    public ?int $id;
    public ?string $name;
    public ?string $email;

    public function validate(): array
    {
        $errors = [];

        if ($this->id && !is_numeric($this->id)) {
            $errors['id'] = 'ID must be numeric';
        }

        if ($this->email && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        return $errors;
    }
}