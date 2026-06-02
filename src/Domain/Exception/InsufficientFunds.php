<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InsufficientFunds extends \DomainException
{
    public function __construct(int $neededCents, int $insertedCents)
    {
        parent::__construct(sprintf(
            'Insufficient funds. Needed: %.2f, inserted: %.2f',
            $neededCents / 100,
            $insertedCents / 100,
        ));
    }
}
