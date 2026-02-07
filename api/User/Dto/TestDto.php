<?php

namespace Api\User\Dto;

use Sonata\Framework\Http\ParamsDTO;

class TestDto extends ParamsDTO
{
    public ?string $name;
    public ?int $age;

    // Можно добавить валидацию
    public function validate(): array
    {
        $errors = [];
        if (empty($this->name)) {
            $errors['name'] = 'Name is required';
        }
        return $errors;
    }
}