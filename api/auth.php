<?php

declare(strict_types=1);

use Mmfuel\Auth;
use Mmfuel\Config;
use Mmfuel\Http;

require __DIR__ . '/src/Config.php';
require __DIR__ . '/src/Http.php';
require __DIR__ . '/src/Auth.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        Http::json(['error' => '許可されていないメソッドです。'], 405);
    }

    $config = Config::load(dirname(__DIR__) . '/.env');
    Auth::login($config, (string) ($_GET['token'] ?? ''));
} catch (RuntimeException $error) {
    error_log((string) $error);
    Http::json(['error' => '認証設定に問題があります。'], 500);
} catch (Throwable $error) {
    error_log((string) $error);
    Http::json(['error' => 'サーバーエラーが発生しました。'], 500);
}
