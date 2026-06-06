<?php

declare(strict_types=1);

namespace App\Application\Response;

final class StatusResponse
{
    /**
     * @param array<string, array{'price': float, 'stock': int}> $items
     * @param array<string, int> $coins
     */
    public function __construct(
        public readonly float $total_inserted,
        public readonly array $items,
        public readonly array $coins,
    ) {
    }
}
