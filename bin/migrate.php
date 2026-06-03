#!/usr/bin/env php
<?php

declare(strict_types=1);

$dbPath = $_ENV['DB_PATH'] ?? dirname(__DIR__) . '/database/vending-machine.db';
$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');

$pdo = new PDO("sqlite:{$dbPath}");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec($schema);

echo "Migration complete: {$dbPath}\n";
