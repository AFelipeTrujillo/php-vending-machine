<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\VendingMachineRepository;

final class ServiceUseCase
{
    public function __construct(
        private readonly VendingMachineRepository $repository,
    ) {
    }

    /**
     * @param array<string, int> $items   selector => stock
     * @param array<string, int> $coins   "0.25" => count
     */
    public function execute(array $items, array $coins): void
    {
        $machine = $this->repository->load();
        $machine->service($items, $coins);
        $this->repository->save($machine);
    }
}
