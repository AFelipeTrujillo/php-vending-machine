<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Coin;
use App\Domain\Exception\CannotMakeChange;
use App\Domain\Exception\InsufficientFunds;
use App\Domain\Exception\OutOfStock;
use App\Domain\Item;
use App\Domain\VendingMachine;
use PHPUnit\Framework\TestCase;

final class VendingMachineTest extends TestCase
{
    private VendingMachine $machine;

    protected function setUp(): void
    {
        $items = [
            'water' => new Item('water', 'Water', 65, 10),
            'juice' => new Item('juice', 'Juice', 100, 10),
            'soda'  => new Item('soda', 'Soda', 150, 10),
        ];

        $coinInventory = [5 => 20, 10 => 20, 25 => 20, 100 => 10];

        $this->machine = new VendingMachine($items, $coinInventory);
    }

    public function test_insert_coin_accumulates_total(): void
    {
        $this->machine->insertCoin(Coin::ONE_HUNDRED);
        $this->machine->insertCoin(Coin::TWENTY_FIVE);

        $this->assertSame(125, $this->machine->getInsertedCents());
    }

    public function test_return_coin_gives_back_exact_inserted_coins(): void
    {
        $this->machine->insertCoin(Coin::TEN);
        $this->machine->insertCoin(Coin::TEN);

        $returned = $this->machine->returnCoin();

        $this->assertCount(2, $returned);
        $this->assertSame(Coin::TEN, $returned[0]);
        $this->assertSame(Coin::TEN, $returned[1]);
        $this->assertSame(0, $this->machine->getInsertedCents());
    }

    public function test_return_coin_resets_inserted_state(): void
    {
        $this->machine->insertCoin(Coin::TWENTY_FIVE);
        $this->machine->returnCoin();

        $this->assertSame(0, $this->machine->getInsertedCents());
        $this->assertEmpty($this->machine->getInsertedCoins());
    }

    public function test_select_item_with_exact_change(): void
    {
        $this->machine->insertCoin(Coin::ONE_HUNDRED);
        $this->machine->insertCoin(Coin::TWENTY_FIVE);
        $this->machine->insertCoin(Coin::TWENTY_FIVE);

        [$item, $change] = $this->machine->selectItem('soda');

        $this->assertSame('Soda', $item->name);
        $this->assertEmpty($change);
        $this->assertSame(0, $this->machine->getInsertedCents());
    }

    public function test_select_item_returns_correct_change(): void
    {
        $this->machine->insertCoin(Coin::ONE_HUNDRED);

        [$item, $change] = $this->machine->selectItem('water');

        $this->assertSame('Water', $item->name);
        $changeCents = array_sum(array_map(fn (Coin $c) => $c->value, $change));
        $this->assertSame(35, $changeCents);
    }

    public function test_select_item_decrements_stock(): void
    {
        $this->machine->insertCoin(Coin::ONE_HUNDRED);
        $this->machine->selectItem('water');

        $this->assertSame(9, $this->machine->getItems()['water']->stock);
    }

    public function test_throws_insufficient_funds(): void
    {
        $this->machine->insertCoin(Coin::TWENTY_FIVE);

        $this->expectException(InsufficientFunds::class);
        $this->machine->selectItem('water');
    }

    public function test_throws_out_of_stock(): void
    {
        $machine = new VendingMachine(
            ['water' => new Item('water', 'Water', 65, 0)],
            [5 => 20, 10 => 20, 25 => 20],
        );
        $machine->insertCoin(Coin::ONE_HUNDRED);

        $this->expectException(OutOfStock::class);
        $machine->selectItem('water');
    }

    public function test_throws_cannot_make_change(): void
    {
        $machine = new VendingMachine(
            ['water' => new Item('water', 'Water', 65, 10)],
            [],
        );
        $machine->insertCoin(Coin::ONE_HUNDRED);

        $this->expectException(CannotMakeChange::class);
        $machine->selectItem('water');
    }

    public function test_throws_invalid_argument_for_unknown_item(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->machine->selectItem('beer');
    }

    public function test_service_updates_stock_and_coins(): void
    {
        $this->machine->service(
            ['water' => 5, 'soda' => 3],
            ['0.25' => 50],
        );

        $this->assertSame(5, $this->machine->getItems()['water']->stock);
        $this->assertSame(3, $this->machine->getItems()['soda']->stock);
        $this->assertSame(50, $this->machine->getCoinInventory()[25]);
    }

    // README examples

    public function test_readme_example_1_buy_soda_exact_change(): void
    {
        $this->machine->insertCoin(Coin::ONE_HUNDRED);
        $this->machine->insertCoin(Coin::TWENTY_FIVE);
        $this->machine->insertCoin(Coin::TWENTY_FIVE);

        [$item, $change] = $this->machine->selectItem('soda');

        $this->assertSame('Soda', $item->name);
        $this->assertEmpty($change);
    }

    public function test_readme_example_2_return_coin(): void
    {
        $this->machine->insertCoin(Coin::TEN);
        $this->machine->insertCoin(Coin::TEN);

        $returned    = $this->machine->returnCoin();
        $totalCents  = array_sum(array_map(fn (Coin $c) => $c->value, $returned));

        $this->assertSame(20, $totalCents);
    }

    public function test_readme_example_3_buy_water_no_exact_change(): void
    {
        $this->machine->insertCoin(Coin::ONE_HUNDRED);

        [$item, $change] = $this->machine->selectItem('water');

        $this->assertSame('Water', $item->name);
        $changeCents = array_sum(array_map(fn (Coin $c) => $c->value, $change));
        $this->assertSame(35, $changeCents);
    }
}
