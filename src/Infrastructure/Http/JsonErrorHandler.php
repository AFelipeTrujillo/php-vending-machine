<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Exception\ValidationException;
use App\Domain\Exception\CannotMakeChange;
use App\Domain\Exception\InsufficientFunds;
use App\Domain\Exception\OutOfStock;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Throwable;

final class JsonErrorHandler
{
    public function __construct(
        private ResponseHandler $responseHandler,
        private LoggerInterface $logger,
        private bool $displayErrorDetails
    ) {
    }

    public function __invoke(
        Request $request,
        Throwable $exception
    ): Response {
        $status = match (true) {
            $exception instanceof HttpNotFoundException         => 404,
            $exception instanceof HttpMethodNotAllowedException => 405,
            $exception instanceof InsufficientFunds             => 400,
            $exception instanceof OutOfStock                    => 400,
            $exception instanceof CannotMakeChange              => 400,
            $exception instanceof ValidationException           => 400,
            $exception instanceof \InvalidArgumentException     => 400,
            default                                             => 500,
        };

        $this->logger->error($exception->getMessage(), [
            'exception' => $exception,
            'trace' => $exception->getTraceAsString(),
            'url' => (string)$request->getUri()
        ]);

        $message = $this->displayErrorDetails
            ? $exception->getMessage()
            : 'Internal server error';

        return $this->responseHandler->error(
            new \Slim\Psr7\Response(),
            $message,
            $status
        );
    }
}
