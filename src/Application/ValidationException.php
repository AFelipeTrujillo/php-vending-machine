<?php

declare(strict_types=1);

namespace App\Application;

final class ValidationException extends \InvalidArgumentException
{
    /** @param string[] $errors */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed');
    }

    /** @return string[] */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
