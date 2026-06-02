<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class OutOfStock extends \DomainException
{
    public function __construct(string $selector)
    {
        parent::__construct(sprintf('%s is out of stock', strtoupper($selector)));
    }
}
