<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Coin;
use App\Domain\VendingMachineRepository;

final class RestockUseCase
{
    private const DEFAULT_ITEM_STOCK = 10;
    private const DEFAULT_COIN_COUNT = 20;

    public function __construct(
        private readonly VendingMachineRepository $repository,
    ) {
    }

    /**
     * @param array<string, int> $itemStocks  selector => stock (overrides; missing items use default)
     * @param array<string, int> $coinCounts  "0.25" => count (overrides; missing coins use default)
     */
    public function execute(array $itemStocks = [], array $coinCounts = []): void
    {
        $machine = $this->repository->load();

        $stocks = [];
        foreach ($machine->getItems() as $selector => $item) {
            $stocks[$selector] = $itemStocks[$selector] ?? self::DEFAULT_ITEM_STOCK;
        }

        $coins = [];
        foreach (Coin::cases() as $coin) {
            $key          = number_format($coin->toFloat(), 2);
            $coins[$key]  = $coinCounts[$key] ?? self::DEFAULT_COIN_COUNT;
        }

        $machine->service($stocks, $coins);
        $this->repository->save($machine);
    }
}
