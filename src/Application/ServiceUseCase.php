<?php

declare(strict_types=1);

namespace App\Application;

use App\Infrastructure\Persistence\VendingMachineRepositoryInterface;

final class ServiceUseCase
{
    public function __construct(
        private readonly VendingMachineRepositoryInterface $repository,
    ) {
    }

    public function execute(ServiceRequest $request): void
    {
        $machine = $this->repository->load();
        $machine->service($request->items, $request->coins);
        $this->repository->save($machine);
    }
}
