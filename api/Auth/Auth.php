<?php

namespace Api\Auth;

class Auth
{
    private static ?AuthUser $currentUser = null;

    public static function set(AuthUser $user): void
    {
        self::$currentUser = $user;
    }

    public static function clear(): void
    {
        self::$currentUser = null;
    }

    public static function getOrThrow(): AuthUser
    {
        if (self::$currentUser === null) {
            throw new \RuntimeException('Unauthorized');
        }

        return self::$currentUser;
    }
}
