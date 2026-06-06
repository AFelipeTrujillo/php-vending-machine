<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\GetStatusUseCase;
use App\Application\InsertCoinUseCase;
use App\Application\ReturnCoinUseCase;
use App\Application\SelectItemUseCase;
use App\Application\ServiceRequest;
use App\Application\ServiceUseCase;
use App\Application\ValidationException;
use App\Domain\Coin;
use App\Domain\Exception\CannotMakeChange;
use App\Domain\Exception\InsufficientFunds;
use App\Domain\Exception\OutOfStock;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class VendingMachineController
{
    public function __construct(
        private readonly InsertCoinUseCase $insertCoin,
        private readonly SelectItemUseCase $selectItem,
        private readonly ReturnCoinUseCase $returnCoin,
        private readonly ServiceUseCase    $service,
        private readonly GetStatusUseCase  $getStatus,
        private readonly ResponseHandler $responseHandler,
    ) {
    }

    public function insertCoin(Request $request, Response $response): Response
    {
        $body      = $request->getParsedBody();
        $coinValue = is_array($body) ? ($body['coin'] ?? null) : null;

        if ($coinValue === null) {
            return $this->responseHandler->error($response, 'Missing coin value', 400);
        }

        $totalCents = $this->insertCoin->execute((float) $coinValue);

        return $this->responseHandler->success($response, [
            'inserted'       => (float) $coinValue,
            'total_inserted' => $totalCents / 100,
        ]);
    }

    /** @param array<string, string> $args */
    public function selectItem(Request $request, Response $response, array $args): Response
    {
        [$item, $change] = $this->selectItem->execute($args['item']);

        return $this->responseHandler->success($response, [
            'item'   => strtoupper($item->name),
            'change' => array_map(fn (Coin $c) => $c->toFloat(), $change),
        ]);
    }

    public function returnCoin(Request $request, Response $response): Response
    {
        $coins = $this->returnCoin->execute();

        return $this->responseHandler->success($response, [
            'returned' => array_map(fn (Coin $c) => $c->toFloat(), $coins),
        ]);
    }

    public function service(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        $serviceRequest = ServiceRequest::fromRawInput(
            \is_array($body) ? ($body['items'] ?? null) : null,
            \is_array($body) ? ($body['coins'] ?? null) : null,
        );

        $this->service->execute($serviceRequest);

        return $this->responseHandler->success($response, ['message' => 'Machine restocked successfully']);
    }

    public function status(Request $request, Response $response): Response
    {
        $machine   = $this->getStatus->execute();
        $itemsData = [];

        foreach ($machine->getItems() as $selector => $item) {
            $itemsData[$selector] = [
                'price' => $item->priceInCents / 100,
                'stock' => $item->stock,
            ];
        }

        $coinsData = [];
        foreach ($machine->getCoinInventory() as $cents => $count) {
            $coinsData[number_format($cents / 100, 2)] = $count;
        }

        return $this->formatter->success($response, [
            'total_inserted' => $machine->getInsertedCents() / 100,
            'items'          => $itemsData,
            'coins'          => $coinsData,
        ]);
    }

}
