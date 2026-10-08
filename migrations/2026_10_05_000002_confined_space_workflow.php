<?php

return function (PDO $pdo) {
    $columns = [
        'assigned_to' => 'BIGINT UNSIGNED DEFAULT NULL',
        'status' => "VARCHAR(20) NOT NULL DEFAULT 'open'",
        'closed_at' => 'DATETIME DEFAULT NULL',
        'finishing_time_updated_at' => 'DATETIME DEFAULT NULL',
        'finishing_time_updated_by' => 'BIGINT UNSIGNED DEFAULT NULL',
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

    $entrantColumns = [
        'added_by' => 'BIGINT UNSIGNED DEFAULT NULL',
        'added_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ];
    foreach ($entrantColumns as $column => $definition) {
        $check = $pdo->prepare('SHOW COLUMNS FROM confined_space_entrants LIKE ?');
        $check->execute([$column]);
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec("ALTER TABLE confined_space_entrants ADD COLUMN `{$column}` {$definition}");
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS confined_space_finishing_time_history (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        confined_space_permit_id BIGINT UNSIGNED NOT NULL,
        previous_finishing_time DATETIME NOT NULL,
        new_finishing_time DATETIME NOT NULL,
        changed_by BIGINT UNSIGNED NOT NULL,
        changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_confined_space_finishing_time_permit
            FOREIGN KEY (confined_space_permit_id) REFERENCES confined_space_permit (id) ON DELETE CASCADE,
        INDEX idx_confined_space_finishing_history (confined_space_permit_id, changed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
