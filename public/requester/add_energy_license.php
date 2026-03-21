<?php
require_once '../../core/Database.php';
require_once '../../config/config.php';
require_once __DIR__ . '../../partials/sidebar.php';
require_once __DIR__ . '../../partials/navbar.php';
require_once '../helpers/authCheck.php';

// Fetch sections and energy types for the form
$config = require '../../config/config.php';
$db = new Database($config['db']);
$pdo = $db->getConnection();

$sectionStmt = $pdo->query("SELECT id, name FROM equipment_sections ORDER BY name");
$sections = $sectionStmt->fetchAll(PDO::FETCH_ASSOC);

$energyStmt = $pdo->query("SELECT id, name FROM energy_types ORDER BY name");
$energyTypes = $energyStmt->fetchAll(PDO::FETCH_ASSOC);

// Get user data from cookie
$userData = json_decode($_COOKIE['user_data'] ?? '{}', true);
$userName = $userData['name'] ?? 'N/A';
$userDepartment = $userData['department'] ?? 'N/A';

// Auto-generate license number (equipment_no)
$lastNoStmt = $pdo->query("SELECT equipment_no FROM energy_insulation_license ORDER BY id DESC LIMIT 1");
$lastNo = $lastNoStmt->fetchColumn();
$nextNoValue = 1;
if ($lastNo && is_numeric($lastNo)) {
    $nextNoValue = (int)$lastNo + 1;
}
$nextLicenseNo = str_pad($nextNoValue, 3, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KCML / SLV | رخصة عزل الطاقة  Energy Isolation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <style>
        .step-content { display: none; }
        .step-content.active { display: block; }
        .step-indicator.active { color: #0b6f76; font-weight: bold; border-bottom: 2px solid #0b6f76; }
        
        /* Disabled button styles */
        #nextBtn:disabled, #submitBtn:disabled {
            background-color: #d1d5db !important;
            color: #9ca3af !important;
            cursor: not-allowed;
            opacity: 0.7;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php renderNavbar('رخصة عزل الطاقة'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('energy_isolation'); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="max-w-4xl mx-auto">
                    <h1 class="text-2xl font-semibold text-gray-700 mb-6">رخصة عزل الطاقة  Energy Isolation</h1>

                    <!-- Progress Bar -->
                    <div class="flex justify-between mb-8 border-b pb-2">
                        <div class="step-indicator active px-2 transition-all duration-300" data-step="1">طلب الرخصة</div>
                        <div class="step-indicator px-2 transition-all duration-300" data-step="2">الطاقة</div>
                        <div class="step-indicator px-2 transition-all duration-300" data-step="3">المعدات</div>
                        <div class="step-indicator px-2 transition-all duration-300" data-step="4">طاقم العمل</div>
                        <div class="step-indicator px-2 transition-all duration-300" data-step="5">السلامة</div>
                        <div class="step-indicator px-2 transition-all duration-300" data-step="6">المسؤول</div>
                    </div>

                    <form id="licenseForm" class="bg-white p-6 rounded-lg shadow-md">
                        
                        <!-- Step 1: Basic Information -->
                        <div class="step-content active" data-step="1">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">إنشاء رخصة</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">رقم الرخصة</label>
                                    <input type="text" name="equipment_no" value="<?= $nextLicenseNo ?>" readonly required class="w-full px-4 py-2 border rounded-md bg-gray-100 cursor-not-allowed focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">التاريخ</label>
                                    <input type="datetime-local" name="date" required class="w-full px-4 py-2 border rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">الموقع الدقيق</label>
                                    <input type="text" name="exact_location" required class="w-full px-4 py-2 border rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">قسم المعدات</label>
                                    <select name="equipment_section_id" id="equipment_section_id" required class="w-full px-4 py-2 border rounded-md focus:ring-[#0b6f76]">
                                        <option value="">اختر القسم</option>
                                        <?php foreach ($sections as $section): ?>
                                            <option value="<?= $section['id'] ?>"><?= $section['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">السبب</label>
                                    <select name="reason" required class="w-full px-4 py-2 border rounded-md focus:ring-[#0b6f76]">
                                        <option value="صيانة وقائية">صيانة وقائية</option>
                                        <option value="طارئة">طارئة</option>
                                        <option value="اطفاء مبرمج SD">اطفاء مبرمج SD</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">تاريخ انتهاء الرخصة</label>
                                    <select name="license_expiry" required class="w-full px-4 py-2 border rounded-md focus:ring-[#0b6f76]">
                                        <option value="">اختر</option>
                                        <option selected value="بعد 12 ساعة">بعد 12 ساعة</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">تصريح العمل</label>
                                    <select name="work_permit" required class="w-full px-4 py-2 border rounded-md focus:ring-[#0b6f76]">
                                        <option value="عزل بسيط">عزل بسيط</option>
                                        <option value="عزل مركب">عزل مركب</option>
                                        <option value="عزل عن بعد">عزل عن بعد</option>
                                        <option value="عزل VCS">عزل VCS</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">اسم المعدة</label>
                                    <input type="text" name="equipment_name" required class="w-full px-4 py-2 border rounded-md focus:ring-[#0b6f76]">
                                </div>
                                
                                <div class="flex items-center mt-6">
                                    <input type="checkbox" name="execution_exceeds_shift_time" id="exceeds" class="mr-2">
                                    <label for="exceeds" class="text-sm font-medium text-gray-700">التنفيذ يتجاوز وقت المناوبة</label>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">اسم الطالب للعزل</label>
                                    <input type="text" value="<?= htmlspecialchars($userName) ?>" readonly class="w-full px-4 py-2 border rounded-md bg-gray-100 cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">القسم الطالب للعزل</label>
                                    <input type="text" value="<?= htmlspecialchars($userDepartment) ?>" readonly class="w-full px-4 py-2 border rounded-md bg-gray-100 cursor-not-allowed">
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Energy Type Selection -->
                        <div class="step-content" data-step="2">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">أنواع الطاقة المراد عزلها</h2>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($energyTypes as $type): ?>
                                    <label class="flex items-center p-3 border rounded-md hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="energy_types[]" value="<?= $type['id'] ?>" class="mr-3">
                                        <span class="text-sm"><?= $type['name'] ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Step 3: Equipment Selection -->
                        <div class="step-content" data-step="3">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">المعدات المراد عزلها</h2>
                            <div id="equipment-container" class="space-y-4">
                                <p class="text-sm text-gray-500 italic">يرجى اختيار القسم في الخطوة الأولى أولاً.</p>
                            </div>
                        </div>

                        <!-- Step 4: Crew Selection -->
                        <div class="step-content" data-step="4">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">طاقم العمل</h2>
                            <div id="staff-container" class="space-y-3">
                                <label class="block text-sm font-medium text-green-700 mb-2">أسماء طاقم العمل</label>
                                <div class="staff-entry flex gap-2">
                                    <input type="text" name="staff_names[]" placeholder="ادخل الاسم هنا" class="flex-1 px-4 py-2 border rounded-md focus:ring-[#0b6f76] staff-name-input">
                                    <button type="button" class="remove-staff px-3 py-2 bg-red-100 text-red-600 rounded-md hover:bg-red-200 transition-colors hidden">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button type="button" id="addStaffBtn" class="mt-4 flex items-center gap-2 text-sm text-[#0b6f76] font-medium hover:underline">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                إضافة اسم آخر
                            </button>
                        </div>

                        <!-- Step 5: Safety Officer Selection -->
                        <div class="step-content" data-step="5">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">السلامة</h2>
                            <p>تمت المراجعة من قبل قسم السلامة.</p>
                        </div>

                        <!-- Step 6: Area Manager Selection -->
                        <div class="step-content" data-step="6">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">مسؤول المنطقة</h2>
                            <div>
                                <label class="block text-sm font-medium text-green-700 mb-2">اختر مسؤول المنطقة</label>
                                <select id="manager_selection" name="area_manager_id" class="w-full"></select>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="mt-8 flex justify-between border-t pt-4">
                            <button type="button" id="prevBtn" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hidden">السابق</button>
                            <button type="button" id="nextBtn" class="px-6 py-2 bg-[#0b6f76] text-white rounded-md ml-auto">التالي</button>
                            <button type="submit" id="submitBtn" class="px-6 py-2 bg-green-600 text-white rounded-md ml-auto hidden">إرسال الرخصة</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let currentStep = 1;
            const totalSteps = 6;
            const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";

            const form = document.getElementById('licenseForm');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtn = document.getElementById('submitBtn');
            const indicators = document.querySelectorAll('.step-indicator');
            const contents = document.querySelectorAll('.step-content');

            // Initialize TomSelect for Manager
            let managerSelect = new TomSelect('#manager_selection', { 
                persist: false, 
                create: false, 
                placeholder: 'اختر المسؤول...',
                onChange: () => updateButtonStates()
            });

            // Staff dynamic inputs logic
            const staffContainer = document.getElementById('staff-container');
            const addStaffBtn = document.getElementById('addStaffBtn');

            addStaffBtn.addEventListener('click', () => {
                const newEntry = document.createElement('div');
                newEntry.className = 'staff-entry flex gap-2 animate-slide-in';
                newEntry.innerHTML = `
                    <input type="text" name="staff_names[]" placeholder="ادخل الاسم هنا" class="flex-1 px-4 py-2 border rounded-md focus:ring-[#0b6f76] staff-name-input">
                    <button type="button" class="remove-staff px-3 py-2 bg-red-100 text-red-600 rounded-md hover:bg-red-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                `;
                staffContainer.appendChild(newEntry);
                updateStaffRemoveButtons();
                updateButtonStates();
            });

            staffContainer.addEventListener('click', (e) => {
                if (e.target.closest('.remove-staff')) {
                    const entry = e.target.closest('.staff-entry');
                    entry.remove();
                    updateStaffRemoveButtons();
                    updateButtonStates();
                }
            });

            staffContainer.addEventListener('input', (e) => {
                if (e.target.classList.contains('staff-name-input')) {
                    updateButtonStates();
                }
            });

            function updateStaffRemoveButtons() {
                const entries = staffContainer.querySelectorAll('.staff-entry');
                entries.forEach((entry, index) => {
                    const removeBtn = entry.querySelector('.remove-staff');
                    if (entries.length === 1) {
                        removeBtn.classList.add('hidden');
                    } else {
                        removeBtn.classList.remove('hidden');
                    }
                });
            }

            function checkStepValidity(step) {
                const currentContent = document.querySelector(`.step-content[data-step="${step}"]`);
                
                if (step === 1) {
                    const requiredInputs = currentContent.querySelectorAll('input[required], select[required]');
                    for (let input of requiredInputs) {
                        if (!input.value.trim()) return false;
                    }
                } else if (step === 2) {
                    const energyChecked = currentContent.querySelectorAll('input[name="energy_types[]"]:checked').length > 0;
                    if (!energyChecked) return false;
                } else if (step === 3) {
                    const equipmentChecked = currentContent.querySelectorAll('.eq-checkbox:checked');
                    if (equipmentChecked.length === 0) return false;
                    
                    // Check if checked equipments have a number (Optional now, but we keep the check if we use hidden input)
                    for (let cb of equipmentChecked) {
                        const eqId = cb.value;
                        const eqNoInput = document.querySelector(`input[name="equipment_nos[${eqId}]"]`);
                        if (!eqNoInput || !eqNoInput.value.trim()) return false;
                    }
                } else if (step === 4) {
                    const names = Array.from(document.querySelectorAll('.staff-name-input'))
                                       .map(input => input.value.trim())
                                       .filter(val => val !== '');
                    if (names.length === 0) return false;
                } else if (step === 6) {
                    if (!managerSelect.getValue()) return false;
                }
                
                return true;
            }

            function updateButtonStates() {
                const isValid = checkStepValidity(currentStep);
                nextBtn.disabled = !isValid;
                submitBtn.disabled = !isValid;
            }

            // Listen for any input changes in the form
            form.addEventListener('input', (e) => {
                updateButtonStates();
            });

            form.addEventListener('change', (e) => {
                updateButtonStates();
            });

            updateUI();

            function updateUI() {
                contents.forEach(content => {
                    content.classList.toggle('active', parseInt(content.dataset.step) === currentStep);
                });
                indicators.forEach(indicator => {
                    indicator.classList.toggle('active', parseInt(indicator.dataset.step) === currentStep);
                });

                prevBtn.classList.toggle('hidden', currentStep === 1);
                nextBtn.classList.toggle('hidden', currentStep === totalSteps);
                submitBtn.classList.toggle('hidden', currentStep !== totalSteps);

                if (currentStep === 3) {
                    loadEquipments().then(() => updateButtonStates());
                }
                if (currentStep === 4 || currentStep === 5) {
                    loadEligibleUsers().then(() => updateButtonStates());
                }
                
                updateButtonStates();
            }

            nextBtn.addEventListener('click', () => {
                if (validateStep(currentStep)) {
                    currentStep++;
                    updateUI();
                }
            });

            prevBtn.addEventListener('click', () => {
                currentStep--;
                updateUI();
            });

            function validateStep(step) {
                return checkStepValidity(step);
            }

            let lastSectionId = null;
            async function loadEquipments() {
                const sectionId = document.getElementById('equipment_section_id').value;
                const container = document.getElementById('equipment-container');
                
                if (!sectionId) {
                    container.innerHTML = '<p class="text-red-500">يرجى العودة للخطوة الأولى واختيار القسم.</p>';
                    lastSectionId = null;
                    return;
                }

                // If section hasn't changed, don't reload and wipe user choices
                if (sectionId === lastSectionId) {
                    return;
                }

                container.innerHTML = '<p>جاري تحميل المعدات...</p>';
                try {
                    const res = await fetch(`../../api/requester/energy_insulation.php?action=getEquipmentsBySection&section_id=${sectionId}`, {
                        headers: { 'Authorization': `Bearer ${TOKEN}` }
                    });
                    const data = await res.json();
                    if (data.success) {
                        container.innerHTML = '';
                        if (data.data.length === 0) {
                            container.innerHTML = '<p class="text-yellow-600">لم يتم العثور على معدات لهذا القسم.</p>';
                            lastSectionId = sectionId;
                            return;
                        }
                        data.data.forEach(eq => {
                            const div = document.createElement('div');
                            div.className = 'flex items-center gap-4 p-3 border rounded-md bg-white hover:bg-gray-50 transition-colors cursor-pointer';
                            
                            const imageHtml = eq.image 
                                ? `<img src="../../public/${eq.image}" class="w-20 h-20 object-cover rounded border shadow-sm" alt="${eq.name}">` 
                                : `<div class="w-20 h-20 bg-gray-100 rounded border flex items-center justify-center text-xs text-gray-400">No Image</div>`;

                            div.innerHTML = `
                                <input type="checkbox" name="equipment_ids[]" value="${eq.id}" class="eq-checkbox h-5 w-5 text-[#0b6f76]">
                                <div class="flex-1">
                                    <span class="text-sm font-semibold text-gray-800">${eq.name}</span>
                                </div>
                                ${imageHtml}
                                <input type="hidden" name="equipment_nos[${eq.id}]" value="${eq.name}">
                            `;
                            
                            // Make the whole div clickable to toggle checkbox
                            div.addEventListener('click', (e) => {
                                if (e.target.tagName !== 'INPUT') {
                                    const cb = div.querySelector('.eq-checkbox');
                                    cb.checked = !cb.checked;
                                    updateButtonStates();
                                }
                            });

                            container.appendChild(div);
                        });
                        lastSectionId = sectionId;
                    }
                } catch (e) {
                    container.innerHTML = '<p class="text-red-500">فشل في تحميل المعدات.</p>';
                }
            }

            async function loadEligibleUsers() {
                if (managerSelect.options.length > 0) return; // Already loaded

                try {
                    const res = await fetch('../../api/requester/energy_insulation.php?action=getEligibleUsers', {
                        headers: { 'Authorization': `Bearer ${TOKEN}` }
                    });
                    const data = await res.json();
                    if (data.success) {
                        data.data.forEach(user => {
                            managerSelect.addOption({ value: user.id, text: user.name });
                        });
                        managerSelect.refreshOptions(false);
                    }
                } catch (e) {
                    console.error('Failed to load users');
                }
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(form);
                const data = {
                    equipment_name: formData.get('equipment_name'),
                    equipment_no: <?= $nextLicenseNo ?>,
                    equipment_section_id: formData.get('equipment_section_id'),
                    date: formData.get('date'),
                    reason: formData.get('reason'),
                    license_expiry: formData.get('license_expiry'),
                    work_permit: formData.get('work_permit'),
                    exact_location: formData.get('exact_location'),
                    execution_exceeds_shift_time: formData.get('execution_exceeds_shift_time') ? 1 : 0,
                    energy_types: [],
                    equipments: [],
                    staff: Array.from(document.querySelectorAll('.staff-name-input'))
                                .map(input => input.value.trim())
                                .filter(val => val !== ''),
                    area_manager_id: managerSelect.getValue()
                };

                // Get Energy Types
                document.querySelectorAll('input[name="energy_types[]"]:checked').forEach(cb => {
                    data.energy_types.push(cb.value);
                });

                // Get Equipments
                document.querySelectorAll('.eq-checkbox:checked').forEach(cb => {
                    const eqId = cb.value;
                    const eqNo = document.querySelector(`input[name="equipment_nos[${eqId}]"]`).value;
                    data.equipments.push({ id: eqId, no: eqNo });
                });

                if (data.energy_types.length === 0) {
                    Swal.fire('خطأ', 'يرجى اختيار نوع طاقة واحد على الأقل.', 'error');
                    return;
                }

                if (data.equipments.length === 0) {
                    Swal.fire('خطأ', 'يرجى اختيار معدة واحدة على الأقل.', 'error');
                    return;
                }

                try {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'جاري الإرسال...';

                    const res = await fetch('../../api/requester/energy_insulation.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${TOKEN}`
                        },
                        body: JSON.stringify(data)
                    });
                    const result = await res.json();
                    if (result.success) {
                        Swal.fire('نجاح', 'تم إنشاء الرخصة بنجاح!', 'success').then(() => {
                            window.location.href = '../dashboard.php';
                        });
                    } else {
                        throw new Error(result.message);
                    }
                } catch (err) {
                    Swal.fire('خطأ', err.message || 'حدث خطأ ما', 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'إرسال الرخصة';
                }
            });
        });
    </script>
</body>
</html>
