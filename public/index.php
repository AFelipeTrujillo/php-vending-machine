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

$errorHandler = $container->get(App\Infrastructure\Http\JsonErrorHandler::class);
$errorMiddleware = $app->addErrorMiddleware(
    displayErrorDetails: ($_ENV['APP_ENV'] ?? 'dev') === 'dev',
    logErrors: true,
    logErrorDetails: true,
);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

(require __DIR__ . '/../src/Infrastructure/Http/routes.php')($app);

$app->run();
