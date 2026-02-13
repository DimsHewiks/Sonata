<?php

namespace Api\Auth;

class AuthUser
{
    public function __construct(
        public string $uuid,
        public ?string $email = null
    ) {}
}
