<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\GetStatusUseCase;
use App\Application\InsertCoinUseCase;
use App\Application\ReturnCoinUseCase;
use App\Application\SelectItemUseCase;
use App\Application\ServiceUseCase;
use App\Domain\Coin;
use App\Infrastructure\Http\Request\ServiceRequest;
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

        $this->service->execute(
            items: $serviceRequest->items,
            coins: $serviceRequest->coins,
        );

        return $this->responseHandler->success($response, ['message' => 'Machine restocked successfully']);
    }

    public function status(Request $request, Response $response): Response
    {
        $statusResponse = $this->getStatus->execute();

        return $this->responseHandler->success($response, [
            'total_inserted' => $statusResponse->total_inserted,
            'items'          => $statusResponse->items,
            'coins'          => $statusResponse->coins,
        ]);
    }

}
