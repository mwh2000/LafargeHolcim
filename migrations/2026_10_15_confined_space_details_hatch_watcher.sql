ALTER TABLE confined_space_permit
    ADD COLUMN details TEXT NULL DEFAULT NULL AFTER finishing_time,
    ADD COLUMN hatch_watcher_required VARCHAR(10) NULL DEFAULT NULL AFTER continuous_monitoring,
    ADD COLUMN hatch_watcher_name VARCHAR(255) NULL DEFAULT NULL AFTER hatch_watcher_required;
