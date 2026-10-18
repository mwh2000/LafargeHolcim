CREATE TABLE IF NOT EXISTS confined_space_staff_group (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    confined_space_permit_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_done TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_confined_space_staff_group_permit
        FOREIGN KEY (confined_space_permit_id) REFERENCES confined_space_permit (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS confined_space_staff (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    confined_space_permit_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    CONSTRAINT fk_confined_space_staff_permit
        FOREIGN KEY (confined_space_permit_id) REFERENCES confined_space_permit (id) ON DELETE CASCADE,
    CONSTRAINT fk_confined_space_staff_group
        FOREIGN KEY (group_id) REFERENCES confined_space_staff_group (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
