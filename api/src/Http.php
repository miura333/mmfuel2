<?php

declare(strict_types=1);

namespace Mmfuel;

final class Http
{
    /** @param mixed $data */
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    /** @return array<string, mixed> */
    public static function jsonBody(): array
    {
        $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
        if ($contentType !== 'application/json') {
            self::json(['error' => 'Content-Type は application/json を指定してください。'], 415);
        }

        try {
            $body = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            self::json(['error' => 'JSONの形式が不正です。'], 400);
        }

        if (!is_array($body)) {
            self::json(['error' => 'JSONオブジェクトを送信してください。'], 400);
        }
        return $body;
    }

    public static function requireSameOrigin(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin === '') {
            return;
        }
        $expected = sprintf(
            '%s://%s',
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
            $_SERVER['HTTP_HOST'] ?? ''
        );
        if (!hash_equals($expected, $origin)) {
            self::json(['error' => '異なるオリジンからの更新は許可されていません。'], 403);
        }
    }
}
