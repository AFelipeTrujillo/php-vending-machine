<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\CannotMakeChange;
use App\Domain\Exception\InsufficientFunds;
use App\Domain\Exception\OutOfStock;

final class VendingMachine
{
    /**
     * @param array<string, Item> $items          selector => Item
     * @param array<int, int>     $coinInventory  cents => count
     * @param Coin[]              $insertedCoins
     */
    public function __construct(
        private array $items,
        private array $coinInventory,
        private array $insertedCoins = [],
    ) {
    }

    public function insertCoin(Coin $coin): void
    {
        $this->insertedCoins[] = $coin;
    }

    /**
     * @return array{0: Item, 1: Coin[]}
     */
    public function selectItem(string $selector): array
    {
        $selector = strtolower($selector);
        $item     = $this->items[$selector] ?? null;

        if ($item === null) {
            throw new \InvalidArgumentException("Unknown item: {$selector}");
        }

        if (!$item->isAvailable()) {
            throw new OutOfStock($selector);
        }

        $insertedCents = $this->getInsertedCents();

        if ($insertedCents < $item->priceInCents) {
            throw new InsufficientFunds($item->priceInCents, $insertedCents);
        }

        // Merge inserted coins into inventory before calculating change
        $inventory = $this->coinInventory;
        foreach ($this->insertedCoins as $coin) {
            $inventory[$coin->value] = ($inventory[$coin->value] ?? 0) + 1;
        }

        $changeCents = $insertedCents - $item->priceInCents;
        $changeCoins = $this->makeChange($changeCents, $inventory);

        // Commit state
        $this->coinInventory = $inventory;
        foreach ($changeCoins as $coin) {
            $this->coinInventory[$coin->value]--;
        }

        $this->items[$selector] = $item->decrementStock();
        $this->insertedCoins    = [];

        return [$item, $changeCoins];
    }

    /**
     * @return Coin[]
     */
    public function returnCoin(): array
    {
        $coins               = $this->insertedCoins;
        $this->insertedCoins = [];

        return $coins;
    }

    /**
     * @param array<string, int> $itemStocks  selector => stock
     * @param array<string, int> $coinCounts  "0.05" => count
     */
    public function service(array $itemStocks, array $coinCounts): void
    {
        foreach ($itemStocks as $selector => $stock) {
            $selector = strtolower($selector);
            if (isset($this->items[$selector])) {
                $this->items[$selector] = $this->items[$selector]->withStock($stock);
            }
        }

        foreach ($coinCounts as $valueStr => $count) {
            $cents                        = (int) round((float) $valueStr * 100);
            $this->coinInventory[$cents]  = $count;
        }
    }

    public function getInsertedCents(): int
    {
        return (int) array_sum(array_map(fn (Coin $c) => $c->value, $this->insertedCoins));
    }

    /** @return array<string, Item> */
    public function getItems(): array
    {
        return $this->items;
    }

    /** @return array<int, int> */
    public function getCoinInventory(): array
    {
        return $this->coinInventory;
    }

    /** @return Coin[] */
    public function getInsertedCoins(): array
    {
        return $this->insertedCoins;
    }

    /**
     * Greedy change-making: largest coin first.
     *
     * @param  array<int, int> $inventory
     * @return Coin[]
     */
    private function makeChange(int $cents, array $inventory): array
    {
        if ($cents === 0) {
            return [];
        }

        $change       = [];
        $denominations = [Coin::ONE_HUNDRED, Coin::TWENTY_FIVE, Coin::TEN, Coin::FIVE];

        foreach ($denominations as $coin) {
            $available = $inventory[$coin->value] ?? 0;
            while ($cents >= $coin->value && $available > 0) {
                $change[] = $coin;
                $cents   -= $coin->value;
                $available--;
            }
        }

        if ($cents > 0) {
            throw new CannotMakeChange();
        }

        return $change;
    }
}
