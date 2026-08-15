<?php

return function (PDO $pdo) {
    $check = $pdo->query("SHOW COLUMNS FROM hot_work_permit LIKE 'done_at'");
    if ($check->fetch()) {
        echo "✅ hot_work_permit.done_at already exists\n";
        return;
    }

    $pdo->exec("ALTER TABLE hot_work_permit ADD COLUMN done_at DATETIME NULL AFTER finishing_time_updated_by");
    echo "✅ Added hot_work_permit.done_at\n";
};
