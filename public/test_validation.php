<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/EnergyInsulationController.php';

$config = require __DIR__ . '/../config/config.php';
$database = new Database($config['db']);
$db = $database->getConnection();

header('Content-Type: text/plain; charset=UTF-8');

echo "Starting verification tests for Lift Isolation validation...\n\n";

try {
    $db->beginTransaction();

    // 1. Create a dummy energy insulation license
    $stmt = $db->prepare("
        INSERT INTO energy_insulation_license (
            equipment_name, equipment_no, date, reason, license_expiry, 
            execution_exceeds_shift_time, work_permit, equipment_section_id, 
            created_by, requester_name, requester_section, status, exact_location
        ) VALUES ('Test Equip', '123', NOW(), 'Test Reason', NOW(), 0, 'Permit-123', 1, 1, 'Requester Name', 'Section', 'active_isolation', 'Location')
    ");
    $stmt->execute();
    $licenseId = $db->lastInsertId();
    echo "Created mock license ID: $licenseId\n";

    // 2. Insert group 1 (completed)
    $stmt = $db->prepare("INSERT INTO energy_insulation_staff_group (name, license_id, is_done) VALUES ('Group A', ?, 1)");
    $stmt->execute([$licenseId]);
    $groupAId = $db->lastInsertId();
    echo "Added Group A (is_done = 1), ID: $groupAId\n";

    // 3. Insert group 2 (incomplete)
    $stmt = $db->prepare("INSERT INTO energy_insulation_staff_group (name, license_id, is_done) VALUES ('Group B', ?, 0)");
    $stmt->execute([$licenseId]);
    $groupBId = $db->lastInsertId();
    echo "Added Group B (is_done = 0), ID: $groupBId\n";

    // 4. Instantiate Controller
    $controller = new EnergyInsulationController($db);

    // 5. Test 1: Try to remove isolation when Group B is not done
    echo "Test 1: Attempting to lift isolation while Group B is incomplete...\n";
    $resJson = $controller->removeIsolationAction($licenseId, 1);
    $res = json_decode($resJson, true);

    if (isset($res['success']) && $res['success'] === false) {
        echo "✅ Test 1 Passed: Server correctly blocked the action.\n";
        echo "Response Message: " . $res['message'] . "\n";
        if ($res['message'] === 'لا يمكن رفع العزل حتى يتم إكمال العمل لجميع المجموعات') {
            echo "✅ Test 1 Message check Passed!\n";
        } else {
            echo "❌ Test 1 Message check Failed!\n";
        }
    } else {
        echo "❌ Test 1 Failed: Server allowed lifting isolation despite incomplete groups.\n";
    }

    // 6. Update Group B to is_done = 1
    $stmt = $db->prepare("UPDATE energy_insulation_staff_group SET is_done = 1 WHERE id = ?");
    $stmt->execute([$groupBId]);
    echo "Updated Group B to is_done = 1.\n";

    // 7. Test 2: Try to remove isolation when all groups are done
    echo "Test 2: Attempting to lift isolation when all groups are complete...\n";
    $resJson = $controller->removeIsolationAction($licenseId, 1);
    $res = json_decode($resJson, true);

    if (isset($res['success']) && $res['success'] === true) {
        echo "✅ Test 2 Passed: Server successfully processed the action.\n";
        echo "Response Message: " . $res['message'] . "\n";
    } else {
        echo "❌ Test 2 Failed: Server blocked the action even though all groups were complete.\n";
        echo "Response Message: " . ($res['message'] ?? 'Unknown error') . "\n";
    }

    // 8. Test 3: Expired license must not allow lifting isolation.
    $db->prepare("UPDATE energy_insulation_license SET license_expiry = DATE_SUB(NOW(), INTERVAL 1 HOUR), status = 'active_isolation' WHERE id = ?")->execute([$licenseId]);
    echo "Test 3: Attempting to lift isolation after expiry time...\n";
    $resJson = $controller->removeIsolationAction($licenseId, 1);
    $res = json_decode($resJson, true);

    if (isset($res['success']) && $res['success'] === false && stripos($res['message'] ?? '', 'انتهى') !== false) {
        echo "✅ Test 3 Passed: Expired license was blocked correctly.\n";
    } else {
        echo "❌ Test 3 Failed: Expired license was incorrectly allowed or message mismatch.\n";
        echo "Response Message: " . ($res['message'] ?? 'Unknown error') . "\n";
    }

    $db->rollBack();
    echo "\nVerification tests completed successfully. Database changes rolled back.\n";
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Error during tests: " . $e->getMessage() . "\n";
}
