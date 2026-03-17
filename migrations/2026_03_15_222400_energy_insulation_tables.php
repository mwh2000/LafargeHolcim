<?php

return function (PDO $pdo) {
    $sql = "
        CREATE TABLE IF NOT EXISTS `energy_insulation_license` (
            `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `equipment_name` varchar(255) DEFAULT NULL,
            `equipment_no` varchar(255) DEFAULT NULL,
            `date` timestamp NULL DEFAULT NULL,
            `reason` varchar(255) DEFAULT NULL,
            `license_expiry` varchar(255) DEFAULT NULL,
            `execution_exceeds_shift_time` int DEFAULT NULL,
            `exact_location` int DEFAULT NULL,
            `created_by` bigint UNSIGNED DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `area_manager_id` bigint UNSIGNED DEFAULT NULL,
            `isolation_officer_id` bigint UNSIGNED DEFAULT NULL,
            `shift_leader_id` bigint UNSIGNED DEFAULT NULL,
            `shift_leader_confirmation` tinyint(1) DEFAULT NULL,
            `status` varchar(255) DEFAULT NULL,
            `reject_reason` varchar(255) DEFAULT NULL,
            `work_permit` varchar(255) DEFAULT NULL,
            `equipment_section_id` int DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

        CREATE TABLE IF NOT EXISTS `energy_insulation_energy_types` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `license_id` bigint UNSIGNED NOT NULL,
            `energy_type_id` int NOT NULL,
            CONSTRAINT `fk_ei_energy_license` FOREIGN KEY (`license_id`) REFERENCES `energy_insulation_license` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

        CREATE TABLE IF NOT EXISTS `energy_insulation_equipments` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `license_id` bigint UNSIGNED NOT NULL,
            `equipment_id` int NOT NULL,
            `equipment_no` varchar(255) DEFAULT NULL,
            CONSTRAINT `fk_ei_equip_license` FOREIGN KEY (`license_id`) REFERENCES `energy_insulation_license` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

        CREATE TABLE IF NOT EXISTS `energy_insulation_staff` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `license_id` bigint UNSIGNED NOT NULL,
            `user_id` bigint UNSIGNED NOT NULL,
            CONSTRAINT `fk_ei_staff_license` FOREIGN KEY (`license_id`) REFERENCES `energy_insulation_license` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ";
    $pdo->exec($sql);
    echo "✅ Energy insulation tables created\n";
};
