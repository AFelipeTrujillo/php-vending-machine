<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Coin;
use App\Infrastructure\Persistence\VendingMachineRepositoryInterface;

final class ReturnCoinUseCase
{
    public function __construct(
        private readonly VendingMachineRepositoryInterface $repository,
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
