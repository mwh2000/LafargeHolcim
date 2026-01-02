<?php
// make_migration.php

// 📌 اسم الـ migration يجي من الـ CLI arguments
if ($argc < 2) {
    die("❌ Usage: php make_migration.php <migration_name>\n");
}

$migrationName = $argv[1];

// 📌 مجلد المايغريشن
$migrationsDir = __DIR__ . '/migrations';

// إذا المجلد ما موجود، أنشئه
if (!is_dir($migrationsDir)) {
    mkdir($migrationsDir, 0777, true);
}

// 📌 صيغة اسم الملف: تاريخ + اسم
$datePrefix = date('Y_m_d_His'); // مثال: 2025_08_06_143200
$fileName = "{$datePrefix}_{$migrationName}.php";
$filePath = $migrationsDir . '/' . $fileName;

// 📌 محتوى القالب
$template = <<<PHP
<?php

return function(PDO \$pdo) {
    \$sql = "
        CREATE TABLE $migrationName (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    \$pdo->exec(\$sql);
    echo "✅ {$migrationName} table created\\n";
};
PHP;

// 📌 إنشاء الملف
if (file_put_contents($filePath, $template) !== false) {
    echo "✅ Migration created: {$fileName}\n";
} else {
    echo "❌ Failed to create migration.\n";
}
