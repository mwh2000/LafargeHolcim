<?php

return function (PDO $pdo) {
    $check = $pdo->query("SHOW TABLES LIKE 'license_number_counters'");
    if (!$check->fetch()) {
        $pdo->exec("CREATE TABLE license_number_counters (
            counter_key VARCHAR(50) NOT NULL PRIMARY KEY,
            next_number INT UNSIGNED NOT NULL
        )");
        echo "✅ Created license_number_counters\n";
    } else {
        echo "✅ license_number_counters already exists\n";
    }

    // Seed each counter from the highest number already in use, so existing
    // (possibly duplicated) permit/equipment numbers are not reissued.
    $seed = function ($key, $table, $column) use ($pdo) {
        $exists = $pdo->prepare("SELECT 1 FROM license_number_counters WHERE counter_key = ?");
        $exists->execute([$key]);
        if ($exists->fetch()) {
            echo "✅ Counter '{$key}' already seeded\n";
            return;
        }

        $lastNo = $pdo->query("SELECT {$column} FROM {$table} ORDER BY id DESC LIMIT 1")->fetchColumn();
        $next = 1;
        if ($lastNo && preg_match('/(\d+)$/', $lastNo, $m)) {
            $next = (int)$m[1] + 1;
        }

        $pdo->prepare("INSERT INTO license_number_counters (counter_key, next_number) VALUES (?, ?)")
            ->execute([$key, $next]);
        echo "✅ Seeded counter '{$key}' at {$next}\n";
    };

    $seed('hot_work_permit', 'hot_work_permit', 'permit_no');
    $seed('energy_insulation_license', 'energy_insulation_license', 'equipment_no');
};
