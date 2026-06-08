<?php

declare(strict_types=1);

use App\Infrastructure\Http\JsonErrorHandler;
use App\Infrastructure\Http\ResponseHandler;
use App\Infrastructure\Persistence\SqliteVendingMachineRepository;
use App\Domain\VendingMachineRepository;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

return [
    LoggerInterface::class => \DI\factory(function () {
        $logger = new Logger('vending-machine');

        if ($_ENV['APP_ENV'] === 'dev') {
            $logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
        }

        return $logger;
    }),

    JsonErrorHandler::class => \DI\factory(function (
        ResponseHandler $responseHandler,
        LoggerInterface $logger
    ) {
        $displayErrorDetails = ($_ENV['APP_ENV'] ?? 'dev') === 'dev';

        return new JsonErrorHandler($responseHandler, $logger, $displayErrorDetails);
    }),

    VendingMachineRepository::class => \DI\get(SqliteVendingMachineRepository::class),

    SqliteVendingMachineRepository::class => \DI\factory(function () {
        $dbPath = $_ENV['DB_PATH'] ?? dirname(__DIR__) . '/database/vending-machine.db';

        return new SqliteVendingMachineRepository($dbPath);
    }),
];
