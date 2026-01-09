<?php
require_once '../core/Database.php';

$config = require __DIR__ . '/../config/config.php';

$db = (new Database($config['db']))->getConnection();

$csvFile = __DIR__ . '/Safety reporting users.csv';

if (!file_exists($csvFile)) {
    die("CSV file not found");
}

$handle = fopen($csvFile, 'r');
$header = fgetcsv($handle); // skip header

$inserted = 0;

$stmt = $db->prepare("
    INSERT INTO users 
    (name, email, password, department, role_id, is_active)
    VALUES (?, ?, ?, ?, ?, ?)
");

while (($row = fgetcsv($handle)) !== false) {

    [$name, $email, $password, $department, $role_id, $is_active] = $row;

    $email = trim($email);
    if (!$email)
        continue;

    // تجاهل المكرر
    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch())
        continue;

    $hashedPassword = password_hash(trim($password), PASSWORD_DEFAULT);

    // تنظيف is_active
    $is_active = trim($is_active);
    $isActive = ($is_active === '' || strtolower($is_active) === 'null')
        ? 1
        : (int) $is_active;

    $stmt->execute([
        trim($name),
        $email,
        $hashedPassword,
        $department ?: null,
        $role_id ?: null,
        $isActive
    ]);

    $inserted++;
}


fclose($handle);

echo "✅ Imported users: {$inserted}";
