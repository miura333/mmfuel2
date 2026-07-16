<?php

declare(strict_types=1);

namespace Mmfuel;

use PDO;

final class Database
{
    /** @param array<string, string> $config */
    public static function connect(array $config): PDO
    {
        foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'] as $key) {
            if (($config[$key] ?? '') === '') {
                throw new \RuntimeException("{$key} が設定されていません。");
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['DB_HOST'],
            $config['DB_PORT'],
            $config['DB_DATABASE']
        );

        return new PDO($dsn, $config['DB_USERNAME'], $config['DB_PASSWORD'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
