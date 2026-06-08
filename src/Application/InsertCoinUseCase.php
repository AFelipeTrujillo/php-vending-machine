<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Coin;
use App\Domain\VendingMachineRepository;

final class InsertCoinUseCase
{
    public function __construct(
        private readonly VendingMachineRepository $repository,
    ) {
    }

    public function execute(float $coinValue): int
    {
        $machine = $this->repository->load();
        $machine->insertCoin(Coin::fromFloat($coinValue));
        $this->repository->save($machine);

        return $machine->getInsertedCents();
    }
}
