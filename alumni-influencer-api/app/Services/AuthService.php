<?php

namespace App\Services;

class AuthService
{
    private static ?array $user = null;

    public static function setUser(array $user): void
    {
        self::$user = $user;
    }

    public static function getUser(): ?array
    {
        return self::$user;
    }

    public static function getUserId(): ?int
    {
        return self::$user['id'] ?? null;
    }

    public static function clearUser(): void
    {
        self::$user = null;
    }
}
