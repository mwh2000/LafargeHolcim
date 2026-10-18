<?php

return function (PDO $pdo) {
    foreach (array_filter(array_map('trim', explode(';', file_get_contents(__DIR__ . '/2026_10_18_confined_space_staff.sql')))) as $statement) {
        $pdo->exec($statement);
    }
};
