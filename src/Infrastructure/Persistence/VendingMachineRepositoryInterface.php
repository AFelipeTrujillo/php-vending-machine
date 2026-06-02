<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\VendingMachine;

interface VendingMachineRepositoryInterface
{
    public function load(): VendingMachine;

    public function save(VendingMachine $machine): void;
}
