<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\VendingMachine;

interface VendingMachineRepository
{
    public function load(): VendingMachine;

    public function save(VendingMachine $machine): void;
}
