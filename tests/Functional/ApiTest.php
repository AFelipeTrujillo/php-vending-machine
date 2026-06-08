<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Application\ServiceUseCase;
use App\Infrastructure\Persistence\SqliteVendingMachineRepository;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class ApiTest extends TestCase
{
    private \Slim\App $app;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/vending-api-test-' . uniqid() . '.db';
        $this->runMigration($this->dbPath);

        $repository = new SqliteVendingMachineRepository($this->dbPath);

        (new ServiceUseCase($repository))->execute(
            ['water' => 10, 'juice' => 10, 'soda' => 10],
            ['0.05' => 20, '0.10' => 20, '0.25' => 20, '1.00' => 10],
        );

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(require __DIR__ . '/../../config/container.php');

        $containerBuilder->addDefinitions([
            \App\Domain\VendingMachineRepository::class =>
                \DI\value($repository),
            SqliteVendingMachineRepository::class =>
                \DI\value($repository),
        ]);

        $container = $containerBuilder->build();

        AppFactory::setContainer($container);
        $this->app = AppFactory::create();
        $this->app->addBodyParsingMiddleware();

        $errorHandler = $container->get(\App\Infrastructure\Http\JsonErrorHandler::class);
        $errorMiddleware = $this->app->addErrorMiddleware(false, false, false);
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        (require __DIR__ . '/../../src/Infrastructure/Http/routes.php')($this->app);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function test_insert_coin_returns_200(): void
    {
        $request  = $this->makeRequest('POST', '/coins', ['coin' => "1"]);
        $response = $this->app->handle($request);

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals(1.0, $body['inserted']);
        $this->assertEquals(1.0, $body['total_inserted']);
    }

    public function test_insert_invalid_coin_returns_400(): void
    {
        $request  = $this->makeRequest('POST', '/coins', ['coin' => "0.30"]);
        $response = $this->app->handle($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_buy_item_with_exact_change(): void
    {
        $this->app->handle($this->makeRequest('POST', '/coins', ['coin' => "1"]));
        $this->app->handle($this->makeRequest('POST', '/coins', ['coin' => "0.25"]));
        $this->app->handle($this->makeRequest('POST', '/coins', ['coin' => "0.25"]));

        $response = $this->app->handle($this->makeRequest('POST', '/items/soda'));
        $body     = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('SODA', $body['item']);
        $this->assertEmpty($body['change']);
    }

    public function test_buy_item_returns_change(): void
    {
        $this->app->handle($this->makeRequest('POST', '/coins', ['coin' => "1"]));

        $response = $this->app->handle($this->makeRequest('POST', '/items/water'));
        $body     = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('WATER', $body['item']);

        $changeCents = (int) round(array_sum($body['change']) * 100);
        $this->assertSame(35, $changeCents);
    }

    public function test_buy_item_with_insufficient_funds_returns_400(): void
    {
        $this->app->handle($this->makeRequest('POST', '/coins', ['coin' => "0.25"]));

        $response = $this->app->handle($this->makeRequest('POST', '/items/water'));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_return_coin(): void
    {
        $this->app->handle($this->makeRequest('POST', '/coins', ['coin' => "0.10"]));
        $this->app->handle($this->makeRequest('POST', '/coins', ['coin' => "0.10"]));

        $response = $this->app->handle($this->makeRequest('POST', '/return-coin'));
        $body     = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $body['returned']);
    }

    public function test_status_returns_machine_state(): void
    {
        $response = $this->app->handle($this->makeRequest('GET', '/status'));
        $body     = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('items', $body);
        $this->assertArrayHasKey('coins', $body);
        $this->assertArrayHasKey('total_inserted', $body);
    }

    private function runMigration(string $dbPath): void
    {
        $pdo    = new \PDO("sqlite:{$dbPath}");
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');
        $pdo->exec((string) $schema);
    }

    private function makeRequest(string $method, string $uri, array $body = []): \Psr\Http\Message\ServerRequestInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);

        if (!empty($body)) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withParsedBody($body);
        }

        return $request;
    }
}
