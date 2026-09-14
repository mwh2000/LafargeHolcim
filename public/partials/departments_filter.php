<?php
$departments = [
    "Dispatch",
    "Process",
    "Mechanical",
    "Methods",
    "HR",
    "Electric",
    "Packing and CM's",
    "Canten",
    "Quality",
    "Production",
    "Quarry",
    "Dispatch",
    "Service- fire fighting",
    "Warehouses",
    "L&D",
    "Project",
    "Plant service",
    "Quarry & Crusher",
    "Shift Management",
    "Utility",
    "Packing",
    "Environment",
    "Operation",
    "Health",
    "Safety",
    "Energy",
    "Clinker",
    "Warehouse&ME"
];
$departments = array_unique($departments);
?>
<select id="department" multiple class="multi-select w-full px-4 py-2 border rounded-md">
    <?php foreach ($departments as $dept): ?>
        <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
    <?php endforeach; ?>
</select>