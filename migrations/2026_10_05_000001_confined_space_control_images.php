<?php

return function (PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS confined_space_control_images (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        control_measure_id BIGINT UNSIGNED NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        CONSTRAINT fk_confined_space_control_image_measure
            FOREIGN KEY (control_measure_id) REFERENCES confined_space_control_measures (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    foreach (['traffic_control_required', 'issuer_qualified', 'issuer_medically_fit'] as $column) {
        $check = $pdo->prepare("SHOW COLUMNS FROM confined_space_permit LIKE ?");
        $check->execute([$column]);
        if ($check->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec("ALTER TABLE confined_space_permit DROP COLUMN `{$column}`");
        }
    }
};
