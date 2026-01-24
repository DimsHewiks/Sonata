<?php

namespace Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Tag
{
    public function __construct(
        public string $name,
        public ?string $description = null
    ) {}
}