<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Coin;
use App\Domain\Item;
use App\Domain\VendingMachineRepository;

final class SelectItemUseCase
{
    public function __construct(
        private readonly VendingMachineRepository $repository,
    ) {
    }

    /**
     * @return array{0: Item, 1: Coin[]}
     */
    public function execute(string $selector): array
    {
        $machine = $this->repository->load();
        $result  = $machine->selectItem($selector);
        $this->repository->save($machine);

        return $result;
    }
}
