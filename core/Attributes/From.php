<?php

namespace Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class From
{
    public function __construct(public string $source) {}
}