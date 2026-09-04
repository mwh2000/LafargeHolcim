<?php

/**
 * Generates collision-free license/permit numbers. Two concurrent requests
 * both computing MAX(existing number) + 1 in PHP is what let simultaneous
 * submissions collide on the same number; this instead claims a number with
 * a single atomic UPDATE ... LAST_INSERT_ID(expr) statement, which takes an
 * exclusive row lock in MySQL/InnoDB and serializes concurrent claims on the
 * same counter_key.
 *
 * Call this inside the caller's existing beginTransaction()/commit() block so
 * the claimed row stays locked (and the number is released back on rollback)
 * for the lifetime of the surrounding insert.
 */
class LicenseNumberGenerator
{
    public static function next(PDO $db, string $counterKey, string $prefix): string
    {
        $db->prepare("INSERT IGNORE INTO license_number_counters (counter_key, next_number) VALUES (?, 1)")
            ->execute([$counterKey]);

        $db->prepare("UPDATE license_number_counters SET next_number = LAST_INSERT_ID(next_number) + 1 WHERE counter_key = ?")
            ->execute([$counterKey]);

        $number = (int)$db->lastInsertId();
        return $prefix . $number;
    }
}
