<?php

declare(strict_types=1);

namespace App\Domain;

enum Coin: int
{
    case FIVE_CENTS  = 5;
    case TEN_CENTS   = 10;
    case TWENTY_FIVE = 25;
    case ONE_DOLLAR  = 100;

    public static function fromFloat(float $value): self
    {
        return match ((int) round($value * 100)) {
            5   => self::FIVE_CENTS,
            10  => self::TEN_CENTS,
            25  => self::TWENTY_FIVE,
            100 => self::ONE_DOLLAR,
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
