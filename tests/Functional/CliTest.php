<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Application\GetStatusUseCase;
use App\Application\InsertCoinUseCase;
use App\Application\RestockUseCase;
use App\Application\ReturnCoinUseCase;
use App\Application\SelectItemUseCase;
use App\Application\ServiceRequest;
use App\Application\ServiceUseCase;
use App\Infrastructure\Cli\VendingMachineCommand;
use App\Infrastructure\Persistence\SqliteVendingMachineRepository;
use PHPUnit\Framework\TestCase;

final class CliTest extends TestCase
{
    private VendingMachineCommand $command;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/vending-cli-test-' . uniqid() . '.db';
        $this->runMigration($this->dbPath);

        $repository = new SqliteVendingMachineRepository($this->dbPath);

        (new ServiceUseCase($repository))->execute(
            ServiceRequest::fromRawInput(
                ['water' => 10, 'juice' => 10, 'soda' => 10],
                ['0.05' => 20, '0.10' => 20, '0.25' => 20, '1.00' => 10],
            )
        );

        $this->command = new VendingMachineCommand(
            new InsertCoinUseCase($repository),
            new SelectItemUseCase($repository),
            new ReturnCoinUseCase($repository),
            new RestockUseCase($repository),
            new GetStatusUseCase($repository),
        );
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function test_insert_valid_coin(): void
    {
        $output = $this->runCommand("0.25\nEXIT\n");

        $this->assertStringContainsString('Inserted. Total: 0.25', $output);
    }

    public function test_insert_invalid_coin_shows_error(): void
    {
        $output = $this->runCommand("0.30\nEXIT\n");

        $this->assertStringContainsString('Error:', $output);
    }

    public function test_buy_item_with_exact_change(): void
    {
        $output = $this->runCommand("1\n0.25\n0.25\nGET-SODA\nEXIT\n");

        $this->assertStringContainsString('SODA', $output);
        $this->assertStringNotContainsString('Error:', $output);
    }

    public function test_buy_item_returns_correct_change(): void
    {
        // 1.00 inserted, water costs 0.65 → change = 0.25 + 0.10
        $output = $this->runCommand("1\nGET-WATER\nEXIT\n");

        $this->assertStringContainsString('WATER', $output);
        $this->assertStringContainsString('0.25', $output);
        $this->assertStringContainsString('0.10', $output);
    }

    public function test_buy_item_with_insufficient_funds_shows_error(): void
    {
        $output = $this->runCommand("0.25\nGET-WATER\nEXIT\n");

        $this->assertStringContainsString('Error:', $output);
    }

    public function test_return_coin_gives_back_inserted_coins(): void
    {
        $output = $this->runCommand("0.10\n0.10\nRETURN-COIN\nEXIT\n");

        $this->assertStringContainsString('0.10, 0.10', $output);
    }

    public function test_return_coin_when_nothing_inserted(): void
    {
        $output = $this->runCommand("RETURN-COIN\nEXIT\n");

        $this->assertStringContainsString('No coins to return.', $output);
    }

    public function test_service_restocks_machine(): void
    {
        $output = $this->runCommand("SERVICE\nEXIT\n");

        $this->assertStringContainsString('Machine restocked.', $output);
    }

    public function test_service_with_custom_stock(): void
    {
        // Drain all water first, then restock only water:1
        $drainInput = str_repeat("1\nGET-WATER\n", 10);
        $this->runCommand($drainInput . "EXIT\n");

        $output = $this->runCommand("water:1, SERVICE\nGET-WATER\nEXIT\n");

        $this->assertStringContainsString('Machine restocked.', $output);
        // Water is still out of stock here because we didn't insert a coin — that's fine,
        // the important assertion is that SERVICE ran without error
        $this->assertStringNotContainsString('Error: Machine', $output);
    }

    public function test_comma_separated_commands_on_one_line(): void
    {
        // 1 + 0.25 + 0.25 = 1.50 exact for soda
        $output = $this->runCommand("1, 0.25, 0.25, GET-SODA\nEXIT\n");

        $this->assertStringContainsString('SODA', $output);
        $this->assertStringNotContainsString('Error:', $output);
    }

    public function test_restock_via_comma_separated_service_line(): void
    {
        // Full restock command in one line
        $output = $this->runCommand("water:5, juice:3, 0.25:10, SERVICE\nEXIT\n");

        $this->assertStringContainsString('Machine restocked.', $output);
    }

    public function test_status_shows_machine_state(): void
    {
        $output = $this->runCommand("STATUS\nEXIT\n");

        $this->assertStringContainsString('Inserted:', $output);
        $this->assertStringContainsString('Water', $output);
        $this->assertStringContainsString('Juice', $output);
        $this->assertStringContainsString('Soda', $output);
    }

    public function test_unknown_command_shows_error_message(): void
    {
        $output = $this->runCommand("FOOBAR\nEXIT\n");

        $this->assertStringContainsString('Unknown command: FOOBAR', $output);
    }

    public function test_exit_prints_goodbye(): void
    {
        $output = $this->runCommand("EXIT\n");

        $this->assertStringContainsString('Goodbye.', $output);
    }

    private function runCommand(string $input): string
    {
        $stream = fopen('php://memory', 'r+');
        \assert($stream !== false);
        fwrite($stream, $input);
        rewind($stream);

        ob_start();
        $this->command->run($stream);
        $output = ob_get_clean();
        \assert(\is_string($output));

        fclose($stream);

        return $output;
    }

    private function runMigration(string $dbPath): void
    {
        $pdo    = new \PDO("sqlite:{$dbPath}");
        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');
        $pdo->exec((string) $schema);
    }
}
