<?php

declare(strict_types=1);

namespace Mmfuel;

use PDO;

final class FuelRepository
{
    private const USER_ID = 1;

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function cars(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, car_name FROM cars WHERE user_id = :user_id ORDER BY id ASC'
        );
        $statement->execute(['user_id' => self::USER_ID]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function dashboard(?int $requestedCarId): ?array
    {
        $car = $requestedCarId === null ? $this->latestCar() : $this->ownedCar($requestedCarId);
        if ($car === null) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT id, date, trip, fuel, price_of_fuel, fuel_rate
             FROM fuel_records
             WHERE user_id = :user_id AND car_id = :car_id
             ORDER BY date DESC, id DESC'
        );
        $statement->execute(['user_id' => self::USER_ID, 'car_id' => $car['id']]);
        $records = $statement->fetchAll();

        $rates = array_values(array_filter(
            array_map(static fn (array $record): float => (float) $record['fuel_rate'], $records),
            static fn (float $rate): bool => $rate > 0
        ));

        $history = array_map(static function (array $record): array {
            return [
                'id' => (int) $record['id'],
                'date' => date('Y/m/d', (int) $record['date']),
                'trip' => (int) $record['trip'],
                'fuel' => (float) $record['fuel'],
                'price_of_fuel' => (int) $record['price_of_fuel'],
                'fuel_rate' => number_format((float) $record['fuel_rate'], 3, '.', ''),
            ];
        }, $records);

        return [
            'latestRate' => number_format($rates[0] ?? 0, 1, '.', ''),
            'averageRate' => number_format($rates === [] ? 0 : array_sum($rates) / count($rates), 1, '.', ''),
            'carName' => $car['car_name'],
            'carId' => (int) $car['id'],
            'history' => $history,
            'carList' => $this->cars(),
        ];
    }

    public function addCar(string $name): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO cars (user_id, car_name, created_at, updated_at)
             VALUES (:user_id, :car_name, NOW(), NOW())'
        );
        $statement->execute(['user_id' => self::USER_ID, 'car_name' => $name]);
        return (int) $this->pdo->lastInsertId();
    }

    public function addFuelRecord(int $carId, int $trip, float $fuel, int $price): int
    {
        if ($this->ownedCar($carId) === null) {
            throw new \DomainException('指定された車両が見つかりません。');
        }

        $statement = $this->pdo->prepare(
            'SELECT trip FROM fuel_records
             WHERE user_id = :user_id AND car_id = :car_id
             ORDER BY date DESC, id DESC LIMIT 1'
        );
        $statement->execute(['user_id' => self::USER_ID, 'car_id' => $carId]);
        $lastTrip = $statement->fetchColumn();
        if ($lastTrip !== false && $trip < (int) $lastTrip) {
            throw new \DomainException('tripは前回の走行距離以上を入力してください。');
        }
        $rate = $lastTrip === false ? 0.0 : ($trip - (int) $lastTrip) / $fuel;

        $insert = $this->pdo->prepare(
            'INSERT INTO fuel_records
                (user_id, car_id, date, fuel, fuel_rate, price_of_fuel, trip, created_at, updated_at)
             VALUES
                (:user_id, :car_id, :date, :fuel, :fuel_rate, :price, :trip, NOW(), NOW())'
        );
        $insert->execute([
            'user_id' => self::USER_ID,
            'car_id' => $carId,
            'date' => time(),
            'fuel' => $fuel,
            'fuel_rate' => $rate,
            'price' => $price,
            'trip' => $trip,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    private function latestCar(): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, car_name FROM cars WHERE user_id = :user_id ORDER BY id DESC LIMIT 1'
        );
        $statement->execute(['user_id' => self::USER_ID]);
        return $statement->fetch() ?: null;
    }

    /** @return array<string, mixed>|null */
    private function ownedCar(int $carId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, car_name FROM cars WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $statement->execute(['id' => $carId, 'user_id' => self::USER_ID]);
        return $statement->fetch() ?: null;
    }
}
