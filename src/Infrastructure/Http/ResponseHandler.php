<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Psr\Http\Message\ResponseInterface as Response;

final class ResponseHandler
{
    /**
     * Summary of success
     * @param Response $response
     * @param array<string, mixed> $data
     * @param int $status
     * @return Response
     */
    public function success(Response $response, array $data, int $status = 200): Response
    {
        return $this->json($response, $data, $status);
    }

    /**
     * Summary of error
     * @param Response $response
     * @param string $message
     * @param int $status
     * @return Response
     */
    public function error(Response $response, string $message, int $status = 400): Response
    {
        return $this->json($response, ['error' => $message], $status);
    }

    /**
     * Summary of json
     * @param Response $response
     * @param array<string, mixed> $data
     * @param int $status
     * @return Response
     */
    private function json(Response $response, array $data, int $status): Response
    {
        $response->getBody()->write((string) json_encode($data, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
