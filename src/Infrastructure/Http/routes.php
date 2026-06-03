<?php

declare(strict_types=1);

use App\Infrastructure\Http\VendingMachineController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app): void {
    $app->get('/health', function (Request $request, Response $response): Response {
        $response->getBody()->write((string) json_encode(['status' => 'ok']));
        return $response->withHeader('Content-Type', 'application/json');
    });
    $app->post('/coins', [VendingMachineController::class, 'insertCoin']);
    $app->post('/items/{item}', [VendingMachineController::class, 'selectItem']);
    $app->post('/return-coin', [VendingMachineController::class, 'returnCoin']);
    $app->post('/service', [VendingMachineController::class, 'service']);
    $app->get('/status', [VendingMachineController::class, 'status']);
};
