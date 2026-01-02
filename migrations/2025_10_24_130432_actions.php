<?php

return function (PDO $pdo) {
    $sql = "
        CREATE TABLE actions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type_id INT NOT NULL,
            title VARCHAR(100) NOT NULL,
            description VARCHAR(255) NOT NULL,
            assigned_user_id BIGINT UNSIGNED NOT NULL,
            expiry_date DATETIME NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            attachment VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_by VARCHAR(255) DEFAULT NULL,

            CONSTRAINT fk_actions_type
                FOREIGN KEY (type_id)
                REFERENCES types(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE,

            CONSTRAINT fk_actions_user
                FOREIGN KEY (assigned_user_id)
                REFERENCES users(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        );
    ";
    $pdo->exec($sql);

    $indexes = [
        'idx_actions_type_id' => 'type_id',
        'idx_actions_assigned_user_id' => 'assigned_user_id',
        'idx_actions_expiry_date' => 'expiry_date',
        'idx_actions_created_at' => 'created_at'
    ];

    foreach ($indexes as $indexName => $column) {
        $checkStmt = $pdo->prepare("SHOW INDEX FROM actions WHERE Key_name = :indexName");
        $checkStmt->execute(['indexName' => $indexName]);

        if ($checkStmt->rowCount() === 0) {
            $pdo->exec("CREATE INDEX `$indexName` ON actions($column);");
        }
    }

    echo "✅ actions table created\n";
};