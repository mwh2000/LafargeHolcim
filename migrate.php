<?php
// migration.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';

// تحميل إعدادات الاتصال
$config = require __DIR__ . '/config/config.php';

try {
    $db = new Database($config['db']);
    $pdo = $db->getConnection();
} catch (Throwable $e) {
    die("❌ Failed to connect to DB: " . $e->getMessage() . PHP_EOL);
}

// مجلد الـ migrations
$migrationsDir = __DIR__ . '/migrations';
if (!is_dir($migrationsDir)) {
    die("❌ Migrations folder not found at $migrationsDir" . PHP_EOL);
}

// اقرأ ملفات المايغريشن
$files = glob($migrationsDir . '/*.php');
sort($files);

// نفذ كل ملف
foreach ($files as $file) {
    $basename = basename($file);
    $migration = require $file;

    if (!is_callable($migration)) {
        echo "⚠️ Skipped (not callable): {$basename}" . PHP_EOL;
        continue;
    }

    try {
        $migration($pdo);
        echo "✅ Migrated: {$basename}" . PHP_EOL;
    } catch (PDOException $e) {
        // إذا الخطأ أن الجدول موجود مسبقًا، تخطاه
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠️ Skipped (table already exists): {$basename}" . PHP_EOL;
            continue;
        }

        // غيرها: أوقف التنفيذ
        echo "❌ Error in {$basename}: " . $e->getMessage() . PHP_EOL;
        exit(1);
    } catch (Throwable $e) {
        echo "❌ Error in {$basename}: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}
