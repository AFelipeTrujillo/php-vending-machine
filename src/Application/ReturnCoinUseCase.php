<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Coin;
use App\Domain\VendingMachineRepository;

final class ReturnCoinUseCase
{
    public function __construct(
        private readonly VendingMachineRepository $repository,
    ) {
    }

    /**
     * @return Coin[]
     */
    public function execute(): array
    {
        $machine = $this->repository->load();
        $coins   = $machine->returnCoin();
        $this->repository->save($machine);

        return $coins;
    }
}
