<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Response\StatusResponse;
use App\Domain\VendingMachineRepository;

final class GetStatusUseCase
{
    public function __construct(
        private readonly VendingMachineRepository $repository,
    ) {
    }

    public function execute(): StatusResponse
    {
        $machine = $this->repository->load();

        $itemsData = [];
        foreach ($machine->getItems() as $selector => $item) {
            $itemsData[$selector] = [
                'price' => $item->priceInCents / 100,
                'stock' => $item->stock,
            ];
        }

        $coinsData = [];
        foreach ($machine->getCoinInventory() as $cents => $count) {
            $coinsData[number_format($cents / 100, 2)] = $count;
        }

        return new StatusResponse(
            total_inserted: $machine->getInsertedCents() / 100,
            items: $itemsData,
            coins: $coinsData,
        );
    }
}
