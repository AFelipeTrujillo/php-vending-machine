# PHP Vending Machine — Project Guide

## What this project is

A vending machine simulator built as a senior-level engineering challenge.
The goal is to show production-quality PHP architecture, not just make it work.

## Stack

| Concern | Choice |
|---|---|
| Language | PHP 8.4 |
| HTTP framework | Slim 4 |
| Persistence | SQLite via PDO |
| Tests | PHPUnit |
| Container | Docker Compose |

## Interfaces

Two ways to interact with the machine:

- **REST API** — JSON endpoints via Slim (primary, for evaluation)
- **CLI** — console command for live demos and manual testing

## Project structure

```
src/
  Domain/               # Business rules only, zero framework dependencies
    VendingMachine.php  # Core aggregate
    Item.php            # Value object: name, price (cents), stock
    Coin.php            # Backed enum: 5 | 10 | 25 | 100 cents
    Exception/          # InsufficientFunds, OutOfStock, CannotMakeChange
  Application/          # One use case per user action
    InsertCoinUseCase.php
    SelectItemUseCase.php
    ReturnCoinUseCase.php
    ServiceUseCase.php
  Infrastructure/
    Persistence/        # SqliteVendingMachineRepository implements the interface
    Http/               # Slim controller + routes
tests/
  Unit/                 # Domain logic in isolation (no DB, no HTTP)
  Integration/          # Use cases against a real SQLite DB
  Functional/           # HTTP endpoints end-to-end
public/
  index.php             # Slim entry point
database/
  schema.sql
```

## Core business rules (never break these)

- Accepted coins: 0.05, 0.10, 0.25, 1.00
- Items: Water = 0.65, Juice = 1.00, Soda = 1.50
- **All money is stored as integer cents** (avoid float precision bugs)
- If the machine cannot make exact change, reject the purchase (CannotMakeChange)
- RETURN-COIN gives back all currently inserted money
- SERVICE lets an operator restock items and refill change coins
- Machine tracks: item stock, coin inventory, currently inserted amount

## Actions and responses

| Action | Input |
|---|---|
| Insert coin | `0.05` / `0.10` / `0.25` / `1` |
| Select item | `GET-WATER` / `GET-JUICE` / `GET-SODA` |
| Return coin | `RETURN-COIN` |
| Service | `SERVICE` |

| Response | Output |
|---|---|
| Vend item | `WATER` / `JUICE` / `SODA` |
| Return change | `0.05` / `0.10` / `0.25` (one per coin) |

## REST API Endpoints

### `POST /coins` — Insert a coin
```json
// Request
{ "coin": 0.25 }

// Response 200
{ "inserted": 0.25, "total_inserted": 0.25 }

// Response 400 — invalid coin
{ "error": "Invalid coin. Accepted: 0.05, 0.10, 0.25, 1.00" }
```

### `POST /items/{item}` — Buy an item
```
POST /items/water
POST /items/juice
POST /items/soda
```
```json
// Response 200 — exact change
{ "item": "WATER", "change": [] }

// Response 200 — with change back
{ "item": "WATER", "change": [0.25, 0.10] }

// Response 400 — not enough money
{ "error": "Insufficient funds. Needed: 0.65, inserted: 0.35" }

// Response 400 — out of stock
{ "error": "WATER is out of stock" }

// Response 400 — machine can't make change
{ "error": "Cannot make exact change. Please use exact amount or insert different coins" }
```

### `POST /return-coin` — Return all inserted money
```json
// Response 200
{ "returned": [1.00, 0.10, 0.10] }

// Response 200 — nothing inserted
{ "returned": [] }
```

### `POST /service` — Restock items and coins (operator)
```json
// Request
{
  "items": {
    "water": 10,
    "juice": 10,
    "soda":  10
  },
  "coins": {
    "0.05": 20,
    "0.10": 20,
    "0.25": 20,
    "1.00": 10
  }
}

// Response 200
{ "message": "Machine restocked successfully" }
```

### `GET /status` — Current machine state (debug / demo)
```json
// Response 200
{
  "total_inserted": 0.50,
  "items": {
    "water": { "price": 0.65, "stock": 9 },
    "juice": { "price": 1.00, "stock": 10 },
    "soda":  { "price": 1.50, "stock": 10 }
  },
  "coins": {
    "0.05": 20,
    "0.10": 18,
    "0.25": 20,
    "1.00": 10
  }
}
```

### Full flow example — README Example 3
```bash
# "Buy Water without exact change: 1, GET-WATER → WATER, 0.25, 0.10"

curl -X POST http://localhost:8080/coins \
  -H "Content-Type: application/json" -d '{"coin": 1}'

curl -X POST http://localhost:8080/items/water

# Response: { "item": "WATER", "change": [0.25, 0.10] }
```

## Key architectural decisions

1. **Domain layer is framework-free** — VendingMachine.php has no Slim, no PDO, no globals
2. **Use cases orchestrate** — they load state from the repo, call domain methods, persist back
3. **Repository interface in Domain** — concrete SQLite impl lives in Infrastructure
4. **Coin enum** — PHP 8.4 backed enum with `int` values in cents; no magic strings
5. **Change algorithm** — greedy (largest coin first); throws CannotMakeChange if impossible

## Commands

```bash
# Start with Docker
docker compose up --build

# Run tests
docker compose exec app vendor/bin/phpunit

# CLI demo
docker compose exec app php bin/vending-machine

# API (examples)
curl -X POST http://localhost:8080/coins -d '{"coin": 1}'
curl -X POST http://localhost:8080/items/water
curl -X POST http://localhost:8080/return-coin
curl -X POST http://localhost:8080/service -d '{"items": {...}, "coins": {...}}'
```

## What NOT to do

- Do not handle money as floats anywhere in the codebase
- Do not put business logic in controllers or repositories
- Do not add abstractions not required by the current spec
- Do not reference the company name anywhere in code or docs
