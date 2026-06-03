<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\InsertCoinUseCase;
use App\Application\ReturnCoinUseCase;
use App\Application\SelectItemUseCase;
use App\Application\ServiceRequest;
use App\Application\ServiceUseCase;
use App\Domain\Coin;
use App\Domain\Exception\InsufficientFunds;
use App\Infrastructure\Persistence\SqliteVendingMachineRepository;
use PHPUnit\Framework\TestCase;

final class UseCaseTest extends TestCase
{
    private SqliteVendingMachineRepository $repository;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/vending-test-' . uniqid() . '.db';
        $this->runMigration($this->dbPath);
        $this->repository = new SqliteVendingMachineRepository($this->dbPath);

        (new ServiceUseCase($this->repository))->execute(
            ServiceRequest::fromRawInput(
                ['water' => 10, 'juice' => 10, 'soda' => 10],
                ['0.05' => 20, '0.10' => 20, '0.25' => 20, '1.00' => 10],
            )
        );
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    private function runMigration(string $dbPath): void
    {
        $pdo    = new \PDO("sqlite:{$dbPath}");
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');
        $pdo->exec((string) $schema);
    }

    public function test_insert_coin_persists_across_loads(): void
    {
        $total = (new InsertCoinUseCase($this->repository))->execute(1.00);

        $this->assertSame(100, $total);

        // Reload from DB and verify state survived
        $machine = $this->repository->load();
        $this->assertSame(100, $machine->getInsertedCents());
    }

    public function test_select_item_persists_stock_change(): void
    {
        (new InsertCoinUseCase($this->repository))->execute(1.00);
        (new SelectItemUseCase($this->repository))->execute('water');

        $machine = $this->repository->load();
        $this->assertSame(9, $machine->getItems()['water']->stock);
    }

    public function test_return_coin_clears_inserted_state(): void
    {
        (new InsertCoinUseCase($this->repository))->execute(0.10);
        (new InsertCoinUseCase($this->repository))->execute(0.10);

        $coins = (new ReturnCoinUseCase($this->repository))->execute();

        $this->assertCount(2, $coins);

        $machine = $this->repository->load();
        $this->assertSame(0, $machine->getInsertedCents());
    }

    public function test_full_purchase_flow_with_change(): void
    {
        (new InsertCoinUseCase($this->repository))->execute(1.00);

        [$item, $change] = (new SelectItemUseCase($this->repository))->execute('water');

        $this->assertSame('Water', $item->name);
        $changeCents = array_sum(array_map(fn (Coin $c) => $c->value, $change));
        $this->assertSame(35, $changeCents);
    }

    public function test_insufficient_funds_does_not_alter_state(): void
    {
        (new InsertCoinUseCase($this->repository))->execute(0.25);

        try {
            (new SelectItemUseCase($this->repository))->execute('water');
        } catch (InsufficientFunds) {
            // expected
        }

        // Inserted coins must still be there
        $machine = $this->repository->load();
        $this->assertSame(25, $machine->getInsertedCents());
    }
}
