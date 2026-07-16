<?php

declare(strict_types=1);

use Mmfuel\Config;
use Mmfuel\Database;
use Mmfuel\FuelRepository;
use Mmfuel\Http;

require __DIR__ . '/src/Config.php';
require __DIR__ . '/src/Database.php';
require __DIR__ . '/src/Http.php';
require __DIR__ . '/src/FuelRepository.php';

try {
    $config = Config::load(dirname(__DIR__) . '/.env');
    $repository = new FuelRepository(Database::connect($config));
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = '/' . trim($_SERVER['PATH_INFO'] ?? '', '/');

    if ($method === 'GET' && $path === '/dashboard') {
        $carId = filter_input(INPUT_GET, 'carId', FILTER_VALIDATE_INT);
        if (isset($_GET['carId']) && $carId === false) {
            Http::json(['error' => 'carIdが不正です。'], 422);
        }
        $dashboard = $repository->dashboard($carId ?: null);
        Http::json($dashboard ?? ['error' => '車両が登録されていません。'], $dashboard === null ? 404 : 200);
    }

    if ($method === 'GET' && $path === '/cars') {
        Http::json(['cars' => $repository->cars()]);
    }

    if ($method === 'POST' && $path === '/cars') {
        Http::requireSameOrigin();
        $body = Http::jsonBody();
        $name = trim((string) ($body['carName'] ?? ''));
        if ($name === '' || mb_strlen($name) > 255) {
            Http::json(['error' => '車名は1〜255文字で入力してください。'], 422);
        }
        Http::json(['id' => $repository->addCar($name)], 201);
    }

    if ($method === 'POST' && $path === '/fuel-records') {
        Http::requireSameOrigin();
        $body = Http::jsonBody();
        $carId = filter_var($body['carId'] ?? null, FILTER_VALIDATE_INT);
        $trip = filter_var($body['trip'] ?? null, FILTER_VALIDATE_INT);
        $price = filter_var($body['price'] ?? null, FILTER_VALIDATE_INT);
        $fuel = filter_var($body['fuel'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($carId === false || $carId < 1 || $trip === false || $trip < 0 ||
            $price === false || $price < 0 || $fuel === false || $fuel <= 0) {
            Http::json(['error' => '入力値を確認してください。'], 422);
        }
        Http::json(['id' => $repository->addFuelRecord($carId, $trip, $fuel, $price)], 201);
    }

    Http::json(['error' => 'APIが見つかりません。'], 404);
} catch (DomainException $error) {
    Http::json(['error' => $error->getMessage()], 422);
} catch (PDOException $error) {
    error_log((string) $error);
    Http::json([
        'error' => 'データベース処理に失敗しました。',
        'diagnostic' => [
            'type' => 'database_error',
            'sqlState' => (string) $error->getCode(),
            'driverCode' => isset($error->errorInfo[1]) ? (int) $error->errorInfo[1] : null,
        ],
    ], 500);
} catch (RuntimeException $error) {
    error_log((string) $error);
    Http::json([
        'error' => 'サーバー設定の読み込みに失敗しました。',
        'diagnostic' => ['type' => 'configuration_error'],
    ], 500);
} catch (Throwable $error) {
    error_log((string) $error);
    Http::json([
        'error' => 'サーバーエラーが発生しました。',
        'diagnostic' => ['type' => 'application_error'],
    ], 500);
}
