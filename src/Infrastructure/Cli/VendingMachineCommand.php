<?php

declare(strict_types=1);

namespace App\Infrastructure\Cli;

use App\Application\GetStatusUseCase;
use App\Application\InsertCoinUseCase;
use App\Application\RestockUseCase;
use App\Application\ReturnCoinUseCase;
use App\Application\SelectItemUseCase;
use App\Domain\Coin;
use App\Domain\Exception\CannotMakeChange;
use App\Domain\Exception\InsufficientFunds;
use App\Domain\Exception\OutOfStock;

final class VendingMachineCommand
{
    public function __construct(
        private readonly InsertCoinUseCase $insertCoin,
        private readonly SelectItemUseCase $selectItem,
        private readonly ReturnCoinUseCase $returnCoin,
        private readonly RestockUseCase    $restock,
        private readonly GetStatusUseCase  $getStatus,
    ) {
    }

    /**
     * @param resource|null $input
     */
    public function run(mixed $input = null): void
    {
        echo "PHP Vending Machine\n";
        echo "Commands: 0.05 | 0.10 | 0.25 | 1 | GET-WATER | GET-JUICE | GET-SODA | RETURN-COIN | STATUS | EXIT\n";
        echo "Restock:  water:10, juice:5, 0.25:20, SERVICE\n\n";

        $stdin = $input ?? fopen('php://stdin', 'r');

        if (!$stdin) {
            throw new \RuntimeException('Unable to open standard input.');
        }

        while (true) {
            echo '> ';
            $line = fgets($stdin);

            if ($line === false) {
                break;
            }

            /** @var array<string, int> $restockItems */
            $restockItems = [];
            /** @var array<string, int> $restockCoins */
            $restockCoins = [];
            $exit         = false;

            foreach (array_map('trim', explode(',', $line)) as $raw) {
                $command = strtoupper($raw);

                if ($command === '') {
                    continue;
                }

                if ($command === 'EXIT') {
                    echo "Goodbye.\n";
                    $exit = true;
                    break;
                }

                // name:value pair — accumulate for SERVICE
                if (str_contains($raw, ':')) {
                    [$key, $value] = array_map('trim', explode(':', $raw, 2));
                    if (is_numeric($key)) {
                        $restockCoins[number_format((float) $key, 2)] = (int) $value;
                    } else {
                        $restockItems[strtolower($key)] = (int) $value;
                    }
                    continue;
                }

                if ($command === 'SERVICE') {
                    $this->restock->execute($restockItems, $restockCoins);
                    $restockItems = [];
                    $restockCoins = [];
                    echo "Machine restocked.\n";
                    continue;
                }

                $this->handle($command);
            }

            if ($exit) {
                break;
            }
        }
    }

    private function handle(string $command): void
    {
        try {
            if (\is_numeric($command)) {

                if (!preg_match('/^\d+(\.\d{1,2})?$/', $command)) {
                    throw new \InvalidArgumentException('Invalid coin format');
                }

                $total = $this->insertCoin->execute((float) $command);
                echo sprintf("Inserted. Total: %.2f\n", $total / 100);

            } elseif (str_starts_with($command, 'GET-')) {
                $selector        = strtolower(substr($command, 4));
                [$item, $change] = $this->selectItem->execute($selector);
                $output          = strtoupper($item->name);

                foreach ($change as $coin) {
                    $output .= sprintf(', %.2f', $coin->toFloat());
                }

                echo $output . "\n";

            } elseif ($command === 'RETURN-COIN') {
                $coins = $this->returnCoin->execute();

                if (empty($coins)) {
                    echo "No coins to return.\n";
                } else {
                    echo implode(', ', array_map(fn (Coin $c) => number_format($c->toFloat(), 2), $coins)) . "\n";
                }

            } elseif ($command === 'STATUS') {
                $status = $this->getStatus->execute();
                echo sprintf("Inserted: %.2f\n", $status->total_inserted);

                foreach ($status->items as $selector => $itemData) {
                    echo sprintf("  %-6s %d units @ %.2f\n", ucfirst($selector), $itemData['stock'], $itemData['price']);
                }

            } else {
                echo "Unknown command: {$command}\n";
            }
        } catch (InsufficientFunds | OutOfStock | CannotMakeChange | \InvalidArgumentException $e) {
            echo 'Error: ' . $e->getMessage() . "\n";
        }
    }
}
