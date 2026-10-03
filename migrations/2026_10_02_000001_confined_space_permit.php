<?php

return function (PDO $pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS confined_space_permit (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            permit_no VARCHAR(100) NOT NULL UNIQUE,
            issuing_date_time DATETIME NOT NULL,
            wo VARCHAR(255) NOT NULL,
            company_name VARCHAR(255) NOT NULL,
            location VARCHAR(255) NOT NULL,
            supervisor VARCHAR(255) NOT NULL,
            equipment_used TEXT DEFAULT NULL,
            maintenance_type VARCHAR(100) NOT NULL,
            task_start_datetime DATETIME NOT NULL,
            finishing_time DATETIME NOT NULL,
            work_description TEXT NOT NULL,
            ventilation_unit_no VARCHAR(255) DEFAULT NULL,
            ventilation_within_limits TINYINT(1) NOT NULL DEFAULT 0,
            emergency_responsible VARCHAR(255) DEFAULT NULL,
            rescue_equipment_available VARCHAR(10) DEFAULT NULL,
            traffic_control_required VARCHAR(10) DEFAULT NULL,
            gas_device_model VARCHAR(255) DEFAULT NULL,
            gas_qualified_person VARCHAR(255) DEFAULT NULL,
            gas_device_calibrated VARCHAR(10) DEFAULT NULL,
            continuous_monitoring VARCHAR(10) DEFAULT NULL,
            issuer_id BIGINT UNSIGNED NOT NULL,
            issuer_name VARCHAR(255) NOT NULL,
            issuer_qualified TINYINT(1) NOT NULL DEFAULT 1,
            issuer_medically_fit TINYINT(1) NOT NULL DEFAULT 1,
            issuer_declaration TINYINT(1) NOT NULL DEFAULT 1,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_confined_space_created_by (created_by),
            INDEX idx_confined_space_issuing_date (issuing_date_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS additional_confined_space_permits (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            confined_space_permit_id BIGINT UNSIGNED NOT NULL,
            permit_name VARCHAR(255) NOT NULL,
            permit_number VARCHAR(255) DEFAULT NULL,
            CONSTRAINT fk_additional_confined_space_permit
                FOREIGN KEY (confined_space_permit_id) REFERENCES confined_space_permit (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS confined_space_control_measures (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            confined_space_permit_id BIGINT UNSIGNED NOT NULL,
            measure_text VARCHAR(500) NOT NULL,
            status VARCHAR(50) NOT NULL,
            CONSTRAINT fk_confined_space_control_permit
                FOREIGN KEY (confined_space_permit_id) REFERENCES confined_space_permit (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS confined_space_entrants (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            confined_space_permit_id BIGINT UNSIGNED NOT NULL,
            person_name VARCHAR(255) NOT NULL,
            medically_fit TINYINT(1) NOT NULL DEFAULT 1,
            authorized_to_enter TINYINT(1) NOT NULL DEFAULT 1,
            CONSTRAINT fk_confined_space_entrant_permit
                FOREIGN KEY (confined_space_permit_id) REFERENCES confined_space_permit (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS confined_space_communications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            confined_space_permit_id BIGINT UNSIGNED NOT NULL,
            communication_method VARCHAR(100) NOT NULL,
            CONSTRAINT fk_confined_space_communication_permit
                FOREIGN KEY (confined_space_permit_id) REFERENCES confined_space_permit (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS confined_space_rescue_equipment (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            confined_space_permit_id BIGINT UNSIGNED NOT NULL,
            equipment_name VARCHAR(255) NOT NULL,
            CONSTRAINT fk_confined_space_rescue_permit
                FOREIGN KEY (confined_space_permit_id) REFERENCES confined_space_permit (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS confined_space_gas_measurements (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            confined_space_permit_id BIGINT UNSIGNED NOT NULL,
            measurement_time VARCHAR(100) DEFAULT NULL,
            oxygen_percent VARCHAR(100) DEFAULT NULL,
            lel_uel_percent VARCHAR(100) DEFAULT NULL,
            co_ppm VARCHAR(100) DEFAULT NULL,
            h2s_ppm VARCHAR(100) DEFAULT NULL,
            CONSTRAINT fk_confined_space_gas_permit
                FOREIGN KEY (confined_space_permit_id) REFERENCES confined_space_permit (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->prepare("INSERT IGNORE INTO license_number_counters (counter_key, next_number) VALUES (?, 1)")
        ->execute(['confined_space_permit']);
};
