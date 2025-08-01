<?php

namespace Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Params
{
    public function __construct(
        public string $class,
        public string $from = 'query'
    ){}
}