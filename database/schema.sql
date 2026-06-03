CREATE TABLE IF NOT EXISTS items (
    selector    TEXT    PRIMARY KEY,
    name        TEXT    NOT NULL,
    price_cents INTEGER NOT NULL,
    stock       INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS coin_inventory (
    value_cents INTEGER PRIMARY KEY,
    count       INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS machine_state (
    id              INTEGER PRIMARY KEY CHECK (id = 1),
    inserted_coins  TEXT    NOT NULL DEFAULT '[]'
);

-- Seed default items (ignored if they already exist)
INSERT OR IGNORE INTO items (selector, name, price_cents, stock) VALUES
    ('water', 'Water', 65,  0),
    ('juice', 'Juice', 100, 0),
    ('soda',  'Soda',  150, 0);

-- Seed accepted coin denominations
INSERT OR IGNORE INTO coin_inventory (value_cents, count) VALUES
    (5,   0),
    (10,  0),
    (25,  0),
    (100, 0);

-- Initialize machine state row (singleton)
INSERT OR IGNORE INTO machine_state (id, inserted_coins) VALUES (1, '[]');
