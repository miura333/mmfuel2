<?php

declare(strict_types=1);

namespace Mmfuel;

final class Config
{
    /** @return array<string, string> */
    public static function load(string $file): array
    {
        if (!is_file($file)) {
            throw new \RuntimeException('.env がありません。.env.example をコピーしてDB接続情報を設定してください。');
        }

        $values = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $values[$key] = trim($value, "\"'");
        }

        return $values;
    }
}
