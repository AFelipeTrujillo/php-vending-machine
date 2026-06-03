<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Coin;
use App\Domain\Item;
use App\Domain\VendingMachine;

final class SqliteVendingMachineRepository implements VendingMachineRepositoryInterface
{
    private \PDO $pdo;

    public function __construct(string $databasePath)
    {
        $this->pdo = new \PDO("sqlite:{$databasePath}");
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }

    public function load(): VendingMachine
    {
        return new VendingMachine(
            $this->loadItems(),
            $this->loadCoinInventory(),
            $this->loadInsertedCoins(),
        );
    }

    public function save(VendingMachine $machine): void
    {
        $this->saveItems($machine->getItems());
        $this->saveCoinInventory($machine->getCoinInventory());
        $this->saveInsertedCoins($machine->getInsertedCoins());
    }

    /** @return array<string, Item> */
    private function loadItems(): array
    {
        $stmt  = $this->pdo->query('SELECT * FROM items');
        assert($stmt instanceof \PDOStatement);
        $rows  = $stmt->fetchAll();
        $items = [];

        foreach ($rows as $row) {
            $items[$row['selector']] = new Item(
                $row['selector'],
                $row['name'],
                (int) $row['price_cents'],
                (int) $row['stock'],
            );
        }

        return $items;
    }

    /** @return array<int, int> */
    private function loadCoinInventory(): array
    {
        $stmt      = $this->pdo->query('SELECT * FROM coin_inventory');
        assert($stmt instanceof \PDOStatement);
        $rows      = $stmt->fetchAll();
        $inventory = [];

        foreach ($rows as $row) {
            $inventory[(int) $row['value_cents']] = (int) $row['count'];
        }

        return $inventory;
    }

    /** @return Coin[] */
    private function loadInsertedCoins(): array
    {
        $stmt   = $this->pdo->query('SELECT inserted_coins FROM machine_state WHERE id = 1');
        assert($stmt instanceof \PDOStatement);
        $row    = $stmt->fetch();
        $values = json_decode(is_array($row) ? $row['inserted_coins'] : '[]', true);

        return array_map(fn (int $v) => Coin::from($v), (array) $values);
    }

    /** @param array<string, Item> $items */
    private function saveItems(array $items): void
    {
        $stmt = $this->pdo->prepare('UPDATE items SET stock = :stock WHERE selector = :selector');

        foreach ($items as $item) {
            $stmt->execute(['stock' => $item->stock, 'selector' => $item->selector]);
        }
    }

    /** @param array<int, int> $coinInventory */
    private function saveCoinInventory(array $coinInventory): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO coin_inventory (value_cents, count) VALUES (:value, :count)
             ON CONFLICT(value_cents) DO UPDATE SET count = excluded.count'
        );

        foreach ($coinInventory as $valueCents => $count) {
            $stmt->execute(['value' => $valueCents, 'count' => $count]);
        }
    }

    /** @param Coin[] $coins */
    private function saveInsertedCoins(array $coins): void
    {
        $values = array_map(fn (Coin $c) => $c->value, $coins);
        $stmt   = $this->pdo->prepare(
            'INSERT INTO machine_state (id, inserted_coins) VALUES (1, :coins)
             ON CONFLICT(id) DO UPDATE SET inserted_coins = excluded.inserted_coins'
        );
        $stmt->execute(['coins' => json_encode($values)]);
    }

}
