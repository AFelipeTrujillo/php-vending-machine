<?php

declare(strict_types=1);

namespace App\Application;

final class ServiceRequest
{
    private const VALID_SELECTORS = ['water', 'juice', 'soda'];
    private const VALID_COINS     = ['0.05', '0.10', '0.25', '1.00'];

    /**
     * @param array<string, int> $items
     * @param array<string, int> $coins
     */
    private function __construct(
        public readonly array $items,
        public readonly array $coins,
    ) {}

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
                    if (!in_array(strtolower((string) $selector), self::VALID_SELECTORS, true)) {
                        $errors[] = "Unknown item \"{$selector}\". Valid: " . implode(', ', self::VALID_SELECTORS);
                    } elseif (!is_int($stock) || $stock < 0) {
                        $errors[] = "Stock for \"{$selector}\" must be a non-negative integer";
                    }
                }
            }
        }

        if ($coins !== null) {
            if (!is_array($coins)) {
                $errors[] = '"coins" must be an object';
            } else {
                foreach ($coins as $value => $count) {
                    if (!in_array((string) $value, self::VALID_COINS, true)) {
                        $errors[] = "Unknown coin \"{$value}\". Valid: " . implode(', ', self::VALID_COINS);
                    } elseif (!is_int($count) || $count < 0) {
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
