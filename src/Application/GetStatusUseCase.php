<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\VendingMachine;
use App\Infrastructure\Persistence\VendingMachineRepositoryInterface;

final class GetStatusUseCase
{
    public function __construct(
        private readonly VendingMachineRepositoryInterface $repository,
    ) {
    }

    public function execute(): VendingMachine
    {
        return $this->repository->load();
    }
}
