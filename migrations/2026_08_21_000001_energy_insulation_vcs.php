<?php

return function (PDO $pdo) {
    $check = $pdo->query("SHOW COLUMNS FROM energy_insulation_license LIKE 'is_vcs_isolation'");
    if ($check->fetch()) {
        echo "✅ energy_insulation_license.is_vcs_isolation already exists\n";
        return;
    }

    $pdo->exec("ALTER TABLE energy_insulation_license ADD COLUMN is_vcs_isolation TINYINT(1) NOT NULL DEFAULT 0 AFTER work_permit");
    echo "✅ Added energy_insulation_license.is_vcs_isolation\n";
};
