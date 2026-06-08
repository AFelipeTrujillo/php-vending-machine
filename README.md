# PHP Vending Machine

A vending machine simulator built as a senior-level engineering challenge. The goal is production-quality PHP architecture using Domain-Driven Design — clean layer separation, no framework code in the domain, and a codebase that is easy to reason about and extend.

---

## Architecture

### Folder structure

```
src/
  Domain/               # Business rules. Zero framework or database dependencies.
    VendingMachine.php
    Item.php
    Coin.php
    Exception/
  Application/          # One use case per user action. Orchestrates domain and persistence.
    InsertCoinUseCase.php
    SelectItemUseCase.php
    ReturnCoinUseCase.php
    RestockUseCase.php
    ServiceUseCase.php
    GetStatusUseCase.php
    ServiceRequest.php
    Exception/
      ValidationException.php
  Infrastructure/
    Persistence/        # SQLite implementation of the repository interface.
    Http/               # Slim 4 controller and route definitions.
    Cli/                # Console command for live demos.
public/
  index.php             # HTTP entry point (Slim app bootstrap).
bin/
  vending-machine       # CLI entry point.
  migrate.php           # Database migration script (runs on container start).
database/
  schema.sql            # Table definitions and seed data.
tests/
  Unit/                 # Domain logic in isolation (no DB, no HTTP).
  Integration/          # Use cases against a real SQLite database.
  Functional/           # Full HTTP request/response tests via Slim.
```

### Layers

The project follows a three-layer DDD structure. Dependencies only flow inward: Infrastructure → Application → Domain.

#### Domain layer

This layer contains pure business logic with no knowledge of Slim, PDO, or any framework. It can be tested without a database or HTTP server.

| Class | Purpose |
|---|---|
| `VendingMachine` | Core aggregate. Holds item stock, coin inventory, and the currently inserted coins. Exposes all machine actions (`insertCoin`, `selectItem`, `returnCoin`, `service`) and enforces every business rule. |
| `Item` | Value object representing a product. Stores the selector, display name, price in cents, and current stock. Immutable — mutations return a new instance. |
| `Coin` | PHP 8.4 backed enum (`int`). Each case stores its value in cents (5, 10, 25, 100). Provides `fromFloat()` for input parsing and `toFloat()` for display. Using an enum prevents invalid denominations from ever existing in the system. |
| `Exception/InsufficientFunds` | Thrown when the inserted amount is less than the item price. Message includes both amounts for clarity. |
| `Exception/OutOfStock` | Thrown when a requested item has zero stock. |
| `Exception/CannotMakeChange` | Thrown when the greedy change algorithm cannot return the exact remainder. The purchase is rejected — the machine never keeps money it cannot change. |

#### Application layer

Use cases orchestrate the flow: load state from the repository, call domain methods, persist the result. Controllers and CLI commands call use cases — never the domain directly.

| Class | Purpose |
|---|---|
| `InsertCoinUseCase` | Parses a float input into a `Coin`, calls `VendingMachine::insertCoin`, saves state. Returns total inserted cents. |
| `SelectItemUseCase` | Calls `VendingMachine::selectItem` for a given selector. Returns the vended item and change coins. |
| `ReturnCoinUseCase` | Calls `VendingMachine::returnCoin`. Returns the exact coins that were inserted. |
| `RestockUseCase` | Operator action. Loads items from the database, applies provided stock/coin overrides. |
| `ServiceUseCase` | Applies a fully-specified `ServiceRequest` to the machine. Used by the HTTP `/service` endpoint. |
| `GetStatusUseCase` | Returns the current `VendingMachine` snapshot. |
| `ServiceRequest` | DTO for the HTTP service endpoint. Validates that items and coins are arrays of non-negative integers before the use case runs. |
| `ValidationException` | Carries a list of validation error messages. Caught by the controller and returned as a 422 response (Unprocessable Content). |

#### Infrastructure layer

Adapters that connect the domain to the outside world. Two entry points expose the same application logic through different interfaces.

**REST API (primary)**

Slim 4 handles routing and request parsing. `VendingMachineController` translates HTTP requests into use case calls and maps domain exceptions to appropriate status codes (400, 422). The route file registers all endpoints against the controller.

**CLI**

`VendingMachineCommand` reads from stdin. Each line can contain comma-separated tokens: coin values, item selectors, `name:value` pairs for restocking, and control commands. The CLI does not contain business logic — it routes tokens to use cases and prints their output.

**Persistence**

`SqliteVendingMachineRepository` implements the `VendingMachineRepository` (defined in Domain) using PDO and SQLite. The interface living in Domain means the domain never depends on Infrastructure — the dependency is inverted.

---

## How to run

### Start the container

```bash
docker compose up --build
```

The container runs the database migration automatically before starting the PHP built-in server on port 8080.

### REST API — curl examples

**Insert a coin**
```bash
curl -X POST http://localhost:8080/coins \
  -H "Content-Type: application/json" \
  -d '{"coin": "0.25"}'
```

**Buy an item (example: water)**
```bash
curl -X POST http://localhost:8080/items/water
```

**Buy water with a 1.00 coin — get change back**
```bash
curl -X POST http://localhost:8080/coins \
  -H "Content-Type: application/json" \
  -d '{"coin": 1}'

curl -X POST http://localhost:8080/items/water
# Response: {"item":"WATER","change":[0.25,0.10]}
```

**Return inserted coins**
```bash
curl -X POST http://localhost:8080/return-coin
```

**Restock the machine (operator)**
```bash
curl -X POST http://localhost:8080/service \
  -H "Content-Type: application/json" \
  -d '{
    "items": { "water": 10, "juice": 10, "soda": 10 },
    "coins": { "0.05": 20, "0.10": 20, "0.25": 20, "1.00": 10 }
  }'
```

**Check machine status**
```bash
curl http://localhost:8080/status
```

### CLI

```bash
docker compose exec app php bin/vending-machine
```

Commands are entered interactively, one per line. Multiple tokens can be separated by commas on a single line.

```
> 1
Inserted. Total: 1.00

> GET-WATER
WATER, 0.25, 0.10

> 0.25, 0.25, 0.25, GET-JUICE
JUICE

> RETURN-COIN
No coins to return.

> water:10, juice:5, 0.25:20, SERVICE
Machine restocked.

> STATUS
Inserted: 0.00
  water  10 units @ 0.65
  juice   5 units @ 1.00
  soda   10 units @ 1.50

> EXIT
Goodbye.
```

### Run tests

```bash
docker compose exec app vendor/bin/phpunit
```

```bash
docker compose exec app vendor/bin/phpstan analyse src --level=8
```

### Format

```bash
docker compose exec app vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes
```

Config:
```php
<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12'               => true,
        'declare_strict_types' => true,
        'array_syntax'         => ['syntax' => 'short'],
        'no_unused_imports'    => true,
        'ordered_imports'      => ['sort_algorithm' => 'alpha'],
    ])
    ->setFinder($finder);
```
