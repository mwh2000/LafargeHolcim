ALTER TABLE confined_space_permit
    ADD COLUMN finishing_time_updated_at DATETIME NULL DEFAULT NULL,
    ADD COLUMN finishing_time_updated_by BIGINT UNSIGNED NULL DEFAULT NULL;
