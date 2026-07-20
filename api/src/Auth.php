<?php

declare(strict_types=1);

namespace Mmfuel;

final class Auth
{
    private const COOKIE_NAME = 'app_auth';
    private const COOKIE_LIFETIME = 31536000;

    /** @param array<string, string> $config */
    public static function requireAuthenticated(array $config): void
    {
        $expected = self::cookieValue($config);
        $actual = (string) ($_COOKIE[self::COOKIE_NAME] ?? '');

        if ($actual === '' || !hash_equals($expected, $actual)) {
            Http::json(['error' => '認証が必要です。初回認証URLへアクセスしてください。'], 401);
        }
    }

    /** @param array<string, string> $config */
    public static function login(array $config, string $providedToken): void
    {
        $loginToken = self::requiredSecret($config, 'APP_LOGIN_TOKEN');
        if ($providedToken === '' || !hash_equals(hash('sha256', $loginToken), hash('sha256', $providedToken))) {
            Http::json(['error' => '認証トークンが一致しません。'], 403);
        }

        $basePath = self::basePath($config);
        setcookie(self::COOKIE_NAME, self::cookieValue($config), [
            'expires' => time() + self::COOKIE_LIFETIME,
            'path' => $basePath . '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
        header('Location: ' . $basePath . '/', true, 303);
        exit;
    }

    /** @param array<string, string> $config */
    private static function cookieValue(array $config): string
    {
        return hash_hmac('sha256', 'mmfuel-app-auth-v1', self::requiredSecret($config, 'APP_COOKIE_SECRET'));
    }

    /** @param array<string, string> $config */
    private static function requiredSecret(array $config, string $key): string
    {
        $value = $config[$key] ?? '';
        if (strlen($value) < 32) {
            throw new \RuntimeException("{$key} は32文字以上で設定してください。");
        }
        return $value;
    }

    /** @param array<string, string> $config */
    private static function basePath(array $config): string
    {
        $configuredPath = $config['APP_BASE_PATH'] ?? '/mmfuel2';
        if ($configuredPath === '/') {
            return '';
        }

        $basePath = rtrim($configuredPath, '/');
        if (preg_match('#^/[A-Za-z0-9/_-]+$#', $basePath) !== 1) {
            throw new \RuntimeException('APP_BASE_PATH が不正です。');
        }
        return $basePath;
    }
}
