<?php

declare(strict_types=1);

function evergreen_db(string $path): PDO
{
    $pdo = new PDO('sqlite:' . $path, options: [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS uploads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            stored_filename TEXT NOT NULL,
            original_filename TEXT,
            mime TEXT,
            size_bytes INTEGER,
            created_at TEXT NOT NULL,
            api_success INTEGER NOT NULL DEFAULT 0,
            zones_count INTEGER NOT NULL DEFAULT 0,
            error_message TEXT,
            location_label TEXT,
            focus_label TEXT,
            consultation_full TEXT,
            layout_json TEXT,
            rendered_image TEXT
        )
        SQL);

    // Migrate existing tables that predate the feed columns
    $existing = array_column(
        $pdo->query('PRAGMA table_info(uploads)')->fetchAll(),
        'name'
    );
    $add = [
        'location_label'    => 'TEXT',
        'focus_label'       => 'TEXT',
        'consultation_full' => 'TEXT',
        'layout_json'       => 'TEXT',
        'rendered_image'    => 'TEXT',
    ];
    foreach ($add as $col => $type) {
        if (!in_array($col, $existing, true)) {
            $pdo->exec("ALTER TABLE uploads ADD COLUMN {$col} {$type}");
        }
    }

    return $pdo;
}
