<?php

declare(strict_types=1);

namespace App\Domain;

enum Coin: int
{
    case FIVE  = 5;
    case TEN   = 10;
    case TWENTY_FIVE = 25;
    case ONE_HUNDRED  = 100;

    public static function fromFloat(float $value): self
    {
        return match ((int) round($value * 100)) {
            5   => self::FIVE,
            10  => self::TEN,
            25  => self::TWENTY_FIVE,
            100 => self::ONE_HUNDRED,
            default => throw new \InvalidArgumentException(
                "Invalid coin: {$value}. Accepted: 0.05, 0.10, 0.25, 1.00"
            ),
        };
    }

    public function toFloat(): float
    {
        return $this->value / 100;
    }
}
