<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Coin;

final class ServiceRequest
{
    /**
     * @param array<string, int> $items
     * @param array<string, int> $coins
     */
    private function __construct(
        public readonly array $items,
        public readonly array $coins,
    ) {
    }

    public static function fromRawInput(mixed $items, mixed $coins): self
    {
        if ($items === null && $coins === null) {
            throw new ValidationException(['Body must include at least "items" or "coins"']);
        }

        $errors = [];

        if ($items !== null) {
            if (!is_array($items)) {
                $errors[] = '"items" must be an object';
            } else {
                foreach ($items as $selector => $stock) {
                    if (!\is_int($stock) || $stock < 0) {
                        $errors[] = "Stock for \"{$selector}\" must be a non-negative integer";
                    }
                }
            }
        }

        if ($coins !== null) {
            if (!\is_array($coins)) {
                $errors[] = '"coins" must be an object';
            } else {
                $validCoins = array_map(fn (Coin $c) => number_format($c->toFloat(), 2), Coin::cases());

                foreach ($coins as $value => $count) {
                    if (!\in_array((string) $value, $validCoins, true)) {
                        $errors[] = "Invalid coin \"{$value}\". Accepted: " . implode(', ', $validCoins);
                    } elseif (!\is_int($count) || $count < 0) {
                        $errors[] = "Count for coin \"{$value}\" must be a non-negative integer";
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return new self($items ?? [], $coins ?? []);
    }
}
