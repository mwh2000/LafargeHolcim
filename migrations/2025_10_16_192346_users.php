<?php

return function (PDO $pdo) {
    $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            department VARCHAR(100) NULL,
            manager_id BIGINT UNSIGNED NULL,
            time_target TIME NULL,
            role_id BIGINT UNSIGNED NULL,
            email_verified_at DATETIME NULL,
            is_active BOOLEAN DEFAULT TRUE,
            failed_logins INT DEFAULT 0,
            last_failed_login DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_users_manager
                FOREIGN KEY (manager_id)
                REFERENCES users(id)
                ON DELETE SET NULL
                ON UPDATE CASCADE,
            
            CONSTRAINT fk_users_role
                FOREIGN KEY (role_id)
                REFERENCES roles(id)
                ON DELETE SET NULL
                ON UPDATE CASCADE
        );
    ";
    $pdo->exec($sql);

    // قائمة الفهارس المطلوبة
    $indexes = [
        'idx_users_department' => 'department',
        // manager_id له فهرس تلقائي من الـ foreign key لذلك ما نعيده
        'idx_users_role_id' => 'role_id',
        'idx_users_is_active' => 'is_active',
        'idx_users_created_at' => 'created_at',
        'idx_users_email' => 'email'
    ];

    // إنشاء الفهارس إذا لم تكن موجودة مسبقًا
    foreach ($indexes as $indexName => $column) {
        $checkStmt = $pdo->prepare("SHOW INDEX FROM users WHERE Key_name = :indexName");
        $checkStmt->execute(['indexName' => $indexName]);

        if ($checkStmt->rowCount() === 0) {
            $pdo->exec("CREATE INDEX `$indexName` ON users($column);");
        }
    }

    echo "✅ users table created\n";
};