<?php

// Creates the admin-managed lookup lists and seeds them (only when empty) with the values
// that used to be hard-coded in the permit forms.
return function (PDO $pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS confined_space_equipment (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_confined_space_equipment_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hot_work_welders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            inspection_date DATE NULL DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_hot_work_welders_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hot_work_fire_sentries (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            inspection_date DATE NULL DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_hot_work_fire_sentries_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $seeds = [
        'confined_space_equipment' => [['name' => 'اعمال لحام'], ['name' => 'كوسره'], ['name' => 'ماكنة هدم'], ['name' => 'ماكنة بناء'], ['name' => 'سقالة'], ['name' => 'معدات يدوية'], ['name' => 'دريل'], ['name' => 'فابريتر'], ['name' => 'Bob cat'], ['name' => 'أخرى']],
        'hot_work_welders' => [
            ['name' => 'رسول محمد جلوب', 'inspection_date' => '2025-11-19'],
            ['name' => 'علي حمود يوسف', 'inspection_date' => '2025-05-25'],
            ['name' => 'سالم عبد الكاظم محمد', 'inspection_date' => '2025-06-01'],
            ['name' => 'زمان كاظم حسين', 'inspection_date' => '2026-04-29'],
            ['name' => 'ياسر نيسان بلم', 'inspection_date' => '2026-04-30'],
            ['name' => 'علي صباح محمد', 'inspection_date' => '2025-05-18'],
            ['name' => 'كاظم علي مجيد', 'inspection_date' => '2025-05-14'],
            ['name' => 'مشتاق نوري سويد المحمد', 'inspection_date' => '2025-10-15'],
            ['name' => 'علي عامر حميد', 'inspection_date' => '2026-04-20'],
            ['name' => 'صادق يوسف هاشم', 'inspection_date' => '2025-11-24'],
            ['name' => 'زيد علاء', 'inspection_date' => '2025-06-12'],
            ['name' => 'موسى ياسر', 'inspection_date' => '2026-06-14'],
            ['name' => 'وائل سلمي سلامة (سيمنتك)', 'inspection_date' => '2025-10-20'],
            ['name' => 'عبدالله عبدالباسط محمد (سيمنتك)', 'inspection_date' => '2025-11-15'],
            ['name' => 'ناصر جاسم (سيمنتك)', 'inspection_date' => '2025-05-20'],
            ['name' => 'هشام ناصر جاسم (سيمنتك)', 'inspection_date' => '2026-04-10'],
            ['name' => 'رعد يوسف (سيمنتك)', 'inspection_date' => '2025-06-05'],
            ['name' => 'ابراهيم رشدي (سيمنتك)', 'inspection_date' => '2025-11-22'],
            ['name' => 'محمد سيد (سيمنتك)', 'inspection_date' => '2026-05-12'],
            ['name' => 'ابراهيم سلامة (سيمنتك)', 'inspection_date' => '2025-10-18'],
        ],
        'hot_work_fire_sentries' => [
            ['name' => 'انمار مدلول', 'inspection_date' => '2025-12-07'],
            ['name' => 'حسين علي حمود', 'inspection_date' => '2026-10-20'],
            ['name' => 'شافي معيوف', 'inspection_date' => '2025-10-18'],
            ['name' => 'حسين عبد محمد', 'inspection_date' => '2025-10-18'],
            ['name' => 'عدي هادي', 'inspection_date' => '2025-10-18'],
            ['name' => 'سعد مدلل', 'inspection_date' => '2025-10-16'],
            ['name' => 'ياس غالي', 'inspection_date' => '2025-10-20'],
            ['name' => 'عقيل عباس', 'inspection_date' => '2025-10-20'],
            ['name' => 'نزار علي', 'inspection_date' => '2025-10-15'],
            ['name' => 'غانم علي', 'inspection_date' => '2025-11-20'],
            ['name' => 'حيدر عطا الله محسن', 'inspection_date' => '2025-11-25'],
            ['name' => 'هاشم ناصر', 'inspection_date' => '2025-10-16'],
            ['name' => 'وائل مكي شريف', 'inspection_date' => '2026-04-30'],
            ['name' => 'محمد عباس فاضل', 'inspection_date' => '2025-12-22'],
            ['name' => 'عماد هاشم', 'inspection_date' => '2025-05-18'],
            ['name' => 'محمد عليوي هاشم', 'inspection_date' => '2025-12-28'],
            ['name' => 'بشار رحمن رضا', 'inspection_date' => '2025-11-19'],
            ['name' => 'رسول حمزه فاضل', 'inspection_date' => '2025-11-20'],
            ['name' => 'عمر رحمان راضي', 'inspection_date' => '2024-04-15'],
            ['name' => 'محمد علي عبيد بريسم المسعودي', 'inspection_date' => '2025-10-15'],
            ['name' => 'سليم حسن وهاب', 'inspection_date' => '2025-10-16'],
            ['name' => 'ياسر رضا عبد الحسين', 'inspection_date' => '2026-04-15'],
            ['name' => 'متعب فاضل حماد', 'inspection_date' => '2026-04-15'],
            ['name' => 'حسن حميد يوسف', 'inspection_date' => '2025-11-23'],
            ['name' => 'عباس حميد يوسف', 'inspection_date' => '2025-11-23'],
            ['name' => 'محمد علي خليل', 'inspection_date' => '2025-11-23'],
            ['name' => 'علي عامر حميد', 'inspection_date' => null],
            ['name' => 'محمد صادق', 'inspection_date' => '2025-11-23'],
            ['name' => 'سعد خزعل', 'inspection_date' => null],
            ['name' => 'معين سهل امين', 'inspection_date' => '2024-10-08'],
            ['name' => 'وضاح نعمه حمود', 'inspection_date' => '2024-10-08'],
            ['name' => 'امير محمد يوسف', 'inspection_date' => '2024-10-20'],
            ['name' => 'محمد صاحب مزعل', 'inspection_date' => '2024-10-20'],
            ['name' => 'علي شريف رضا', 'inspection_date' => '2024-10-20'],
            ['name' => 'نصطفى محمد مهدي', 'inspection_date' => '2024-10-20'],
            ['name' => 'حسين هادي جاسم', 'inspection_date' => '2024-10-20'],
            ['name' => 'زيد صباح عليوي', 'inspection_date' => '2024-10-20'],
            ['name' => 'مرتضى محمد مهدي', 'inspection_date' => '2024-10-21'],
            ['name' => 'علي حسين جياد', 'inspection_date' => '2024-10-21'],
            ['name' => 'بدر معيوف ابراهيم', 'inspection_date' => '2024-10-21'],
            ['name' => 'محمد ياسين غضيب', 'inspection_date' => '2024-10-21'],
            ['name' => 'جاسم كنوبي عباس', 'inspection_date' => '2024-10-28'],
            ['name' => 'حسن حازم كشموط', 'inspection_date' => '2025-02-02'],
            ['name' => 'سعد خزعل مغير', 'inspection_date' => '2025-03-02'],
            ['name' => 'علي كاظم جياد', 'inspection_date' => '2025-01-28'],
            ['name' => 'عادل جبر لفته', 'inspection_date' => '2025-03-24'],
            ['name' => 'انور خليل هادي', 'inspection_date' => '2025-03-24'],
            ['name' => 'مرتجى ياس خضير', 'inspection_date' => '2025-03-24'],
            ['name' => 'عبدالله طالب نعمه', 'inspection_date' => '2025-04-07'],
            ['name' => 'حامد هاشم حميد', 'inspection_date' => '2025-04-07'],
            ['name' => 'عادل ناصر حسين', 'inspection_date' => '2025-04-13'],
            ['name' => 'رافد مهدي جاسم', 'inspection_date' => '2025-04-13'],
            ['name' => 'ثائر رشيد صالح', 'inspection_date' => '2025-04-29'],
            ['name' => 'حامد هاشم كريم', 'inspection_date' => '2026-02-16'],
            ['name' => 'علي ياس بشير', 'inspection_date' => '2026-02-16'],
            ['name' => 'علي خضير حميد', 'inspection_date' => '2026-02-16'],
            ['name' => 'نصير عبدالحسين وحيد', 'inspection_date' => '2026-02-23'],
            ['name' => 'حسين عبد محمد قاطع', 'inspection_date' => '2026-05-14'],
            ['name' => 'عدي مهدي هادي', 'inspection_date' => '2026-06-01'],
            ['name' => 'عوني عطيه راشد', 'inspection_date' => '2026-07-19'],
            ['name' => 'مشتاق نوري سويد', 'inspection_date' => '2026-07-19'],
            ['name' => 'ليث ابراهيم محمود', 'inspection_date' => '2026-07-19'],
            ['name' => 'وليد خليفه محمد', 'inspection_date' => '2026-09-24'],
            ['name' => 'جميل مطلب', 'inspection_date' => '2026-09-24'],
            ['name' => 'جهاد محمد حسين', 'inspection_date' => '2026-09-24'],
            ['name' => 'عباس عواد عبيد', 'inspection_date' => '2026-09-24'],
            ['name' => 'حسنين عماد ابراهيم', 'inspection_date' => '2026-10-06'],
            ['name' => 'عماد ياسر نيسان', 'inspection_date' => '2027-10-06'],
            ['name' => 'فلاح هديب', 'inspection_date' => '2028-10-06'],
            ['name' => 'علي ياسر بشر', 'inspection_date' => '2029-10-06'],
            ['name' => 'دريد احمد عبد الواحد', 'inspection_date' => '2030-10-06'],
            ['name' => 'احمد رياض جاسم', 'inspection_date' => '2031-10-06'],
            ['name' => 'برير حسين عباس', 'inspection_date' => '2032-10-06'],
            ['name' => 'ميثم عبد الحسين صالح', 'inspection_date' => '2033-10-06'],
            ['name' => 'عبد علي حمزد', 'inspection_date' => '2034-10-06'],
            ['name' => 'عدنان سليمان حسن', 'inspection_date' => '2035-10-06'],
            ['name' => 'صالح بحر جاسم', 'inspection_date' => '2036-10-06'],
            ['name' => 'خضير عباس هاشم', 'inspection_date' => '2037-10-06'],
            ['name' => 'ليث صباح موسى', 'inspection_date' => '2038-10-06'],
            ['name' => 'ناصر عباس', 'inspection_date' => '2039-10-06'],
            ['name' => 'حيدر رشيد حميد', 'inspection_date' => '2040-10-06'],
        ],
    ];

    foreach ($seeds as $table => $rows) {
        if ((int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn() > 0) continue;
        $columns = array_keys($rows[0]);
        $stmt = $pdo->prepare("INSERT INTO {$table} (" . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')');
        foreach ($rows as $row) $stmt->execute(array_values($row));
    }
};
