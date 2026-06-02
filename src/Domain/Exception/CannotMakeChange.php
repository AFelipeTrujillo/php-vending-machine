<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class CannotMakeChange extends \DomainException
{
    public function __construct()
    {
        parent::__construct(
            'Cannot make exact change. Please use exact amount or insert different coins'
        );
    }
}
