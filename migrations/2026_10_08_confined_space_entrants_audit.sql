ALTER TABLE confined_space_entrants
    ADD COLUMN added_by BIGINT UNSIGNED NULL DEFAULT NULL,
    ADD COLUMN added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS confined_space_finishing_time_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    confined_space_permit_id BIGINT UNSIGNED NOT NULL,
    previous_finishing_time DATETIME NOT NULL,
    new_finishing_time DATETIME NOT NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_confined_space_finishing_time_permit
        FOREIGN KEY (confined_space_permit_id) REFERENCES confined_space_permit (id) ON DELETE CASCADE,
    INDEX idx_confined_space_finishing_history (confined_space_permit_id, changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
