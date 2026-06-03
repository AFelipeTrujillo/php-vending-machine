<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\SqliteVendingMachineRepository;
use App\Infrastructure\Persistence\VendingMachineRepositoryInterface;

return [
    
    VendingMachineRepositoryInterface::class => \DI\get(SqliteVendingMachineRepository::class),

    SqliteVendingMachineRepository::class => \DI\factory(function () {
        $dbPath = $_ENV['DB_PATH'] ?? dirname(__DIR__) . '/database/vending-machine.db';

        return new SqliteVendingMachineRepository($dbPath);
    }),
];
