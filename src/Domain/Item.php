<?php

declare(strict_types=1);

namespace App\Domain;

final class Item
{
    public function __construct(
        public readonly string $selector,
        public readonly string $name,
        public readonly int $priceInCents,
        public readonly int $stock,
    ) {}

    public function isAvailable(): bool
    {
        return $this->stock > 0;
    }

    public function withStock(int $stock): self
    {
        return new self($this->selector, $this->name, $this->priceInCents, $stock);
    }

    public function decrementStock(): self
    {
        return new self($this->selector, $this->name, $this->priceInCents, $this->stock - 1);
    }
}
