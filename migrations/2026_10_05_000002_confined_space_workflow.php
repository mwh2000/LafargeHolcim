<?php

return function (PDO $pdo) {
    $columns = [
        'assigned_to' => 'BIGINT UNSIGNED DEFAULT NULL',
        'status' => "VARCHAR(20) NOT NULL DEFAULT 'open'",
        'closed_at' => 'DATETIME DEFAULT NULL',
    ];
    foreach ($columns as $column => $definition) {
        $check = $pdo->prepare('SHOW COLUMNS FROM confined_space_permit LIKE ?');
        $check->execute([$column]);
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec("ALTER TABLE confined_space_permit ADD COLUMN `{$column}` {$definition}");
        }
    }

    $gasColumns = [
        'added_by' => 'BIGINT UNSIGNED DEFAULT NULL',
        'added_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'added_signature_path' => 'VARCHAR(500) DEFAULT NULL',
    ];
    foreach ($gasColumns as $column => $definition) {
        $check = $pdo->prepare('SHOW COLUMNS FROM confined_space_gas_measurements LIKE ?');
        $check->execute([$column]);
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec("ALTER TABLE confined_space_gas_measurements ADD COLUMN `{$column}` {$definition}");
        }
    }
};
