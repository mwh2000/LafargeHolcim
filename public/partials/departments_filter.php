<?php
$departments = [
    "Qualty", "Method", "process", "quary", "Dispatch", "Service", "قسم التجهيز", "Warehouses", "Mechanecal", "L&D", "Project", "Project & Improvement", "Plant service", "Hfo&Dispatch", "Quarry & Crusher", "Shift Management", "CM Management", "Clinker", "Utility", "Electrical", "Service- fire fighting", "Packing and CM's", "Packing", "Plant Management", "Maintenance Mangar", "Enviroment", "Production", "Operation", "Health", "Crusher", "Energy", "Warehouse&ME"
];
$departments = array_unique($departments);
?>
<select id="department" multiple class="multi-select w-full px-4 py-2 border rounded-md">
    <?php foreach ($departments as $dept): ?>
        <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
    <?php endforeach; ?>
</select>
