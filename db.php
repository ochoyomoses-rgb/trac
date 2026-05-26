<?php

if (!file_exists('data')) {
    mkdir('data', 0777, true);
}

$db = new PDO('sqlite:data/tracker.sqlite');

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec(
    "CREATE TABLE IF NOT EXISTS visits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        website TEXT,
        ip TEXT,
        page TEXT,
        user_agent TEXT,
        referrer TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )"
);
