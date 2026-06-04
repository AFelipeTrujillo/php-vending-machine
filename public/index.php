<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(require __DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler(
    function (
        \Psr\Http\Message\ServerRequestInterface $request,
        \Throwable $exception,
        bool $displayErrorDetails,
    ) use ($app): \Psr\Http\Message\ResponseInterface {
        $status = match (true) {
            $exception instanceof \Slim\Exception\HttpNotFoundException         => 404,
            $exception instanceof \Slim\Exception\HttpMethodNotAllowedException => 405,
            default                                                             => 500,
        };

        $response = $app->getResponseFactory()->createResponse($status);
        $response->getBody()->write((string) \json_encode([
            'error' => $displayErrorDetails ? $exception->getMessage() : 'Internal server error',
        ], \JSON_PRETTY_PRINT));

        return $response->withHeader('Content-Type', 'application/json');
    }
);

(require __DIR__ . '/../src/Infrastructure/Http/routes.php')($app);

$app->run();
