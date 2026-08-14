<?php
require_once '../../core/Database.php';
require_once '../../config/config.php';
require_once __DIR__ . '../../partials/sidebar.php';
require_once __DIR__ . '../../partials/navbar.php';
require_once '../helpers/authCheck.php';
$officials = require 'officials_list.php';


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

if ($lastNo) {
    if (preg_match('/(\d+)$/', $lastNo, $matches)) {
        $nextNoValue = (int)$matches[1] + 1;
    }
}
$nextLicenseNo = 'OHSM-PTW-00' . $nextNoValue;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KCML / SLV | رخصة عزل الطاقة Energy Isolation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <style>
        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        .step-indicator.active {
            color: #0b6f76;
            font-weight: bold;
            border-bottom: 2px solid #0b6f76;
        }

        /* Disabled button styles */
        #nextBtn:disabled,
        #submitBtn:disabled {
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
                    <h1 class="text-2xl font-semibold text-gray-700 mb-6">رخصة عزل الطاقة Energy Isolation</h1>

                    <!-- Progress Bar -->
                    <div class="flex justify-between mb-8 border-b pb-2 text-[10px] md:text-sm">
                        <div class="step-indicator active px-2 transition-all duration-300" data-step="1">طلب الرخصة</div>
                        <div class="step-indicator px-2 transition-all duration-300" data-step="2">الطاقة</div>
                        <div class="step-indicator px-2 transition-all duration-300" data-step="3">المعدات</div>
                        <div class="step-indicator px-2 transition-all duration-300" data-step="4">طاقم العمل</div>
                        <div class="step-indicator px-2 transition-all duration-300" data-step="5">السلامة</div>
                        <div class="step-indicator px-2 transition-all duration-300" data-step="6">اسم العازل</div>
                        <div class="step-indicator px-2 transition-all duration-300" data-step="7">الموافقات</div>
                        <div class="step-indicator px-2 transition-all duration-300" data-step="8">المسؤول</div>
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
                                    <input type="datetime-local" name="date" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">الموقع الدقيق</label>
                                    <input type="text" name="exact_location" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">القسم</label>
                                    <select name="equipment_section_id" id="equipment_section_id" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                        <option value="">اختر القسم</option>
                                        <?php foreach ($sections as $section): ?>
                                            <option value="<?= $section['id'] ?>"><?= $section['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-smf font-medium text-green-700 mb-1">السبب</label>
                                    <select name="reason" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                        <option value="صيانة وقائية">صيانة وقائية</option>
                                        <option value="طارئة">طارئة</option>
                                        <option value="اطفاء مبرمج SD">اطفاء مبرمج SD</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">تاريخ ووقت انتهاء الرخصة</label>
                                    <input type="datetime-local" id="license_expiry" name="license_expiry" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76] mb-2">
                                    <p class="text-xs text-gray-500">سيتم اعتبار الرخصة غير نشطة تلقائياً عند تجاوز وقت الانتهاء.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">تصريح العمل</label>
                                    <select name="work_permit" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                        <option value="عزل بسيط">عزل بسيط</option>
                                        <option value="عزل مركب">عزل مركب</option>
                                        <option value="عزل عن بعد">عزل عن بعد</option>
                                        <option value="عزل VCS">عزل VCS</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">اسم المعدة</label>
                                    <input type="text" name="equipment_name" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>

                                <div class="flex items-center mt-6">
                                    <input type="checkbox" name="execution_exceeds_shift_time" id="exceeds" class="mr-2">
                                    <label for="exceeds" class="text-sm font-medium text-gray-700">التنفيذ يتجاوز وقت المناوبة</label>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">اسم الطالب للعزل</label>
                                    <input type="text" name="requester_name" placeholder="ادخل اسم الطالب" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">القسم الطالب للعزل</label>
                                    <input type="text" name="requester_section" placeholder="ادخل القسم الطالب" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
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
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-medium text-[#0b6f76]">المعدات المراد عزلها</h2>
                                <button type="button" id="addNewEquipmentBtn" class="text-sm px-3 py-1.5 bg-[#0b6f76] text-white rounded-md hover:bg-opacity-90 transition">
                                    + إضافة معدة جديدة
                                </button>
                            </div>

                            <!-- Search Bar -->
                            <div class="mb-4 relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </span>
                                <input type="text" id="equipment-search" placeholder="بحث عن اسم المعدة..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-[#0b6f76] focus:border-[#0b6f76]">
                            </div>

                            <div id="equipment-container" class="space-y-4">
                                <p class="text-sm text-gray-500 italic">يرجى اختيار القسم في الخطوة الأولى أولاً.</p>
                            </div>

                            <!-- Pagination Container -->
                            <div id="equipment-pagination" class="mt-6 flex justify-center gap-2"></div>
                        </div>

                        <!-- Step 4: Crew Selection -->
                        <div class="step-content" data-step="4">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">طاقم العمل</h2>
                            <div id="staff-groups-container" class="space-y-6">
                                <!-- Groups will be dynamically added here -->
                            </div>
                            <button type="button" id="addGroupBtn" class="mt-4 flex items-center gap-2 text-sm text-[#0b6f76] font-medium hover:underline">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                إضافة مجموعة
                            </button>
                        </div>

                        <!-- Step 5: Safety Officer Selection -->
                        <div class="step-content" data-step="5">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">السلامة</h2>
                            <p>تمت المراجعة من قبل قسم السلامة
                                <span>✔️</span>
                            </p>
                        </div>

                        <!-- Step 6: Isolation Official Selection -->
                        <div class="step-content" data-step="6">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">اسم العازل</h2>
                            <div>
                                <label class="block text-sm font-medium text-green-700 mb-2">اختر العازل</label>
                                <select id="official_selection" name="official_name" class="w-full">
                                    <option value="">اختر العازل...</option>
                                    <?php foreach ($officials as $off): ?>
                                        <option value="<?= htmlspecialchars($off['name']) ?>" data-department="<?= htmlspecialchars($off['department']) ?>">
                                            <?= htmlspecialchars($off['name']) ?> (<?= htmlspecialchars($off['department']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="official_department" id="official_department">
                            </div>
                        </div>

                        <!-- Step 7: Approvals (included from separate file) -->
                        <div class="step-content" data-step="7">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">الموافقات على عزل المعدة</h2>
                            <?php include __DIR__ . '/approvals_options.php'; ?>
                        </div>

                        <!-- Step 7: Area Manager Selection -->
                        <div class="step-content" data-step="8">
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
                            <button type="submit" id="submitBtn" class="px-6 py-2 bg-green-600 text-white rounded-md ml-auto hidden">تم العزل</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let currentStep = 1;
            const totalSteps = 8;
            const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";

            // Equipment search and pagination state
            let equipmentPage = 1;
            let equipmentSearch = '';
            let selectedEquipments = new Map(); // stores id -> {id, name}

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

            // Initialize TomSelect for Official
            let officialSelect = new TomSelect('#official_selection', {
                persist: false,
                create: false,
                placeholder: 'اختر مسؤول العزل...',
                onChange: (value) => {
                    const select = document.getElementById('official_selection');
                    const selectedOption = Array.from(select.options).find(opt => opt.value === value);
                    if (selectedOption) {
                        document.getElementById('official_department').value = selectedOption.dataset.department;
                    }
                    updateButtonStates();
                }
            });


            const licenseExpiryInput = document.getElementById('license_expiry');
            licenseExpiryInput.addEventListener('input', updateButtonStates);

            // Staff dynamic groups logic
            const staffGroupsContainer = document.getElementById('staff-groups-container');
            const addGroupBtn = document.getElementById('addGroupBtn');

            function createStaffInput() {
                return `
                    <div class="staff-entry flex gap-2 mb-2 animate-slide-in">
                        <input type="text" placeholder="ادخل اسم الفرد هنا" class="flex-1 px-4 py-2 border rounded-md focus:ring-[#0b6f76] staff-name-input">
                        <button type="button" class="remove-staff px-3 py-2 bg-red-100 text-red-600 rounded-md hover:bg-red-200 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                `;
            }

            function addGroup() {
                const groupDiv = document.createElement('div');
                groupDiv.className = 'staff-group-card bg-white border border-gray-200 p-5 rounded-lg shadow-sm relative mb-4';
                groupDiv.innerHTML = `
                    <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-2" dir="rtl">
                        <div class="flex-1">
                             <label class="block text-sm font-bold text-[#0b6f76] mb-1">اسم المجموعة</label>
                             <input type="text" placeholder="مثال: Group A أو فريق الصيانة" class="w-full md:w-3/4 px-4 py-2 border border-gray-300 rounded-md focus:ring-[#0b6f76] group-name-input" required>
                        </div>
                        <button type="button" class="remove-group text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-full transition-colors mr-2" title="حذف المجموعة">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                    <div class="staff-members-container" dir="rtl">
                        <label class="block text-sm font-medium text-gray-700 mb-3">أفراد المجموعة</label>
                        ${createStaffInput()}
                    </div>
                    <button type="button" class="add-staff-to-group-btn mt-4 flex items-center gap-2 text-sm text-[#0b6f76] font-bold hover:underline" dir="rtl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        إضافة فرد للمجموعة
                    </button>
                `;
                staffGroupsContainer.appendChild(groupDiv);
                updateStaffRemoveButtons(groupDiv);
                updateButtonStates();
            }

            addGroupBtn.addEventListener('click', addGroup);

            staffGroupsContainer.addEventListener('click', (e) => {
                // Add staff member to group
                if (e.target.closest('.add-staff-to-group-btn')) {
                    const groupCard = e.target.closest('.staff-group-card');
                    const membersContainer = groupCard.querySelector('.staff-members-container');
                    membersContainer.insertAdjacentHTML('beforeend', createStaffInput());
                    updateStaffRemoveButtons(groupCard);
                    updateButtonStates();
                }

                // Remove staff member from group
                if (e.target.closest('.remove-staff')) {
                    const entry = e.target.closest('.staff-entry');
                    const groupCard = entry.closest('.staff-group-card');
                    entry.remove();
                    updateStaffRemoveButtons(groupCard);
                    updateButtonStates();
                }

                // Remove entire group
                if (e.target.closest('.remove-group')) {
                    e.target.closest('.staff-group-card').remove();
                    updateButtonStates();
                }
            });

            staffGroupsContainer.addEventListener('input', (e) => {
                if (e.target.classList.contains('staff-name-input') || e.target.classList.contains('group-name-input')) {
                    updateButtonStates();
                }
            });

            function updateStaffRemoveButtons(groupCard) {
                if (!groupCard) return;
                const entries = groupCard.querySelectorAll('.staff-entry');
                entries.forEach((entry, index) => {
                    const removeBtn = entry.querySelector('.remove-staff');
                    if (entries.length === 1) {
                        removeBtn.classList.add('hidden');
                    } else {
                        removeBtn.classList.remove('hidden');
                    }
                });
            }

            // Initialize with one empty group
            addGroup();

            const equipmentSearchInput = document.getElementById('equipment-search');
            let searchTimeout = null;

            const addNewEquipmentBtn = document.getElementById('addNewEquipmentBtn');
            addNewEquipmentBtn.addEventListener('click', async () => {
                const sectionId = document.getElementById('equipment_section_id').value;
                if (!sectionId) {
                    Swal.fire('تنبيه', 'يرجى اختيار القسم أولاً من الخطوة الأولى.', 'warning');
                    return;
                }

                const {
                    value: formValues
                } = await Swal.fire({
                    title: 'إضافة معدة جديدة',
                    html: '<input id="swal-eq-name" class="swal2-input" placeholder="اسم المعدة">' +
                        '<input id="swal-eq-image" type="file" accept="image/*" class="swal2-file">',
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'حفظ',
                    cancelButtonText: 'إلغاء',
                    confirmButtonColor: '#0b6f76',
                    preConfirm: () => {
                        const name = document.getElementById('swal-eq-name').value.trim();
                        const imageFile = document.getElementById('swal-eq-image').files[0];
                        if (!name) {
                            Swal.showValidationMessage('يرجى إدخال اسم المعدة');
                            return false;
                        }
                        return {
                            name,
                            imageFile
                        };
                    }
                });

                if (!formValues) return;

                const fd = new FormData();
                fd.append('name', formValues.name);
                fd.append('section_id', sectionId);
                if (formValues.imageFile) fd.append('image', formValues.imageFile);

                try {
                    const res = await fetch('../../api/admin/equipments.php?action=create', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${TOKEN}`
                        },
                        body: fd
                    });
                    const result = await res.json();
                    if (result.success) {
                        Swal.fire('نجاح', 'تمت إضافة المعدة بنجاح', 'success');
                        equipmentSearch = '';
                        equipmentSearchInput.value = '';
                        equipmentPage = 1;
                        await loadEquipments();
                    } else {
                        Swal.fire('خطأ', result.message || 'حدث خطأ ما', 'error');
                    }
                } catch (e) {
                    Swal.fire('خطأ', 'فشل الاتصال بالخادم', 'error');
                }
            });

            equipmentSearchInput.addEventListener('input', (e) => {
                equipmentSearch = e.target.value;
                equipmentPage = 1;
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadEquipments();
                }, 500);
            });

            function updateStaffRemoveButtonsOld() {
                // Not used anymore
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
                    if (selectedEquipments.size === 0) return false;
                } else if (step === 4) {
                    const groups = document.querySelectorAll('.staff-group-card');
                    if (groups.length === 0) return false;

                    for (let group of groups) {
                        const groupName = group.querySelector('.group-name-input').value.trim();
                        if (!groupName) return false;

                        const names = Array.from(group.querySelectorAll('.staff-name-input'))
                            .map(input => input.value.trim())
                            .filter(val => val !== '');
                        if (names.length === 0) return false;
                    }
                } else if (step === 6) {
                    if (!officialSelect.getValue()) return false;
                } else if (step === 7) {
                    // Require all approval checkboxes to be selected (UI-only)
                    const approvalsChecked = currentContent.querySelectorAll('input[name="approvals[]"]:checked').length;
                    if (approvalsChecked !== 5) return false;
                } else if (step === 8) {
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
                if (currentStep >= 7) {
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
                const paginationContainer = document.getElementById('equipment-pagination');

                if (!sectionId) {
                    container.innerHTML = '<p class="text-red-500">يرجى العودة للخطوة الأولى واختيار القسم.</p>';
                    paginationContainer.innerHTML = '';
                    lastSectionId = null;
                    return;
                }

                // If section changed, reset page, search and selected equipments
                if (sectionId !== lastSectionId) {
                    equipmentPage = 1;
                    equipmentSearch = '';
                    equipmentSearchInput.value = '';
                    selectedEquipments.clear();
                    lastSectionId = sectionId;
                }

                container.innerHTML = '<p class="text-center py-4">جاري تحميل المعدات...</p>';
                try {
                    const res = await fetch(`../../api/requester/energy_insulation.php?action=getEquipmentsBySection&section_id=${sectionId}&search=${encodeURIComponent(equipmentSearch)}&page=${equipmentPage}`, {
                        headers: {
                            'Authorization': `Bearer ${TOKEN}`
                        }
                    });
                    const result = await res.json();

                    if (result.success) {
                        const {
                            equipments,
                            total_pages,
                            page
                        } = result.data;
                        container.innerHTML = '';

                        if (equipments.length === 0) {
                            container.innerHTML = '<p class="text-yellow-600 text-center py-4">لم يتم العثور على معدات.</p>';
                            paginationContainer.innerHTML = '';
                            return;
                        }

                        equipments.forEach(eq => {
                            const div = document.createElement('div');
                            div.className = 'flex items-center gap-4 p-3 border rounded-md bg-white hover:bg-gray-50 transition-colors cursor-pointer';

                            const isChecked = selectedEquipments.has(eq.id.toString());
                            const imageHtml = eq.image ?
                                `<img src="../../public/${eq.image}" class="w-16 h-16 object-cover rounded border shadow-sm" alt="${eq.name}">` :
                                `<div class="w-16 h-16 bg-gray-100 rounded border flex items-center justify-center text-[10px] text-gray-400">No Image</div>`;

                            div.innerHTML = `
                                <input type="checkbox" value="${eq.id}" class="eq-checkbox h-5 w-5 text-[#0b6f76] rounded" ${isChecked ? 'checked' : ''}>
                                <div class="flex-1">
                                    <span class="text-sm font-semibold text-gray-800">${eq.name}</span>
                                </div>
                                ${imageHtml}
                            `;

                            // Checkbox change logic
                            const cb = div.querySelector('.eq-checkbox');
                            const toggleSelection = () => {
                                if (cb.checked) {
                                    selectedEquipments.set(eq.id.toString(), {
                                        id: eq.id,
                                        name: eq.name
                                    });
                                } else {
                                    selectedEquipments.delete(eq.id.toString());
                                }
                                updateButtonStates();
                            };

                            cb.addEventListener('change', (e) => {
                                e.stopPropagation();
                                toggleSelection();
                            });

                            div.addEventListener('click', (e) => {
                                if (e.target.tagName !== 'INPUT') {
                                    cb.checked = !cb.checked;
                                    toggleSelection();
                                }
                            });

                            container.appendChild(div);
                        });

                        // Render Pagination
                        renderPagination(total_pages, page);
                    }
                } catch (e) {
                    container.innerHTML = '<p class="text-red-500 text-center py-4">فشل في تحميل المعدات.</p>';
                }
            }

            function renderPagination(totalPages, currentPage) {
                const paginationContainer = document.getElementById('equipment-pagination');
                paginationContainer.innerHTML = '';

                if (totalPages <= 1) return;

                const createBtn = (label, page, isActive = false, isDisabled = false) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = label;
                    btn.className = `px-3 py-1 text-sm border rounded hover:bg-gray-100 ${isActive ? 'bg-[#0b6f76] text-white border-[#0b6f76] hover:bg-[#0b6f76]' : 'text-gray-600'} ${isDisabled ? 'opacity-50 cursor-not-allowed' : ''}`;
                    if (!isDisabled && !isActive) {
                        btn.addEventListener('click', () => {
                            equipmentPage = page;
                            loadEquipments();
                        });
                    }
                    return btn;
                };

                // Prev Button
                paginationContainer.appendChild(createBtn('السابق', currentPage - 1, false, currentPage === 1));

                // Page Numbers
                for (let i = 1; i <= totalPages; i++) {
                    if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                        paginationContainer.appendChild(createBtn(i, i, i === currentPage));
                    } else if (i === currentPage - 2 || i === currentPage + 2) {
                        const span = document.createElement('span');
                        span.textContent = '...';
                        span.className = 'px-2';
                        paginationContainer.appendChild(span);
                    }
                }

                // Next Button
                paginationContainer.appendChild(createBtn('التالي', currentPage + 1, false, currentPage === totalPages));
            }

            async function loadEligibleUsers() {
                if (managerSelect.options.length > 0) return; // Already loaded

                try {
                    const res = await fetch('../../api/requester/energy_insulation.php?action=getEligibleUsers', {
                        headers: {
                            'Authorization': `Bearer ${TOKEN}`
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        data.data.forEach(user => {
                            managerSelect.addOption({
                                value: user.id,
                                text: user.name
                            });
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
                    equipment_no: '<?= $nextLicenseNo ?>',
                    equipment_section_id: formData.get('equipment_section_id'),
                    date: formData.get('date'),
                    reason: formData.get('reason'),
                    license_expiry: document.getElementById('license_expiry').value,
                    work_permit: formData.get('work_permit'),
                    exact_location: formData.get('exact_location'),
                    requester_name: formData.get('requester_name'),
                    requester_section: formData.get('requester_section'),
                    execution_exceeds_shift_time: formData.get('execution_exceeds_shift_time') ? 1 : 0,
                    energy_types: [],
                    equipments: [],
                    staff_groups: [],
                    area_manager_id: managerSelect.getValue(),
                    official_name: officialSelect.getValue(),
                    official_department: document.getElementById('official_department').value
                };

                // Get Staff Groups
                document.querySelectorAll('.staff-group-card').forEach(groupCard => {
                    const groupName = groupCard.querySelector('.group-name-input').value.trim();
                    const members = Array.from(groupCard.querySelectorAll('.staff-name-input'))
                        .map(input => input.value.trim())
                        .filter(val => val !== '');

                    if (groupName && members.length > 0) {
                        data.staff_groups.push({
                            group_name: groupName,
                            members: members
                        });
                    }
                });

                // Get Energy Types
                document.querySelectorAll('input[name="energy_types[]"]:checked').forEach(cb => {
                    data.energy_types.push(cb.value);
                });

                // Get Equipments from selectedEquipments Map
                selectedEquipments.forEach((eq, id) => {
                    data.equipments.push({
                        id: id,
                        no: eq.name
                    });
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
                    submitBtn.textContent = 'جاري تأكيد العزل...';

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
                    submitBtn.textContent = 'تم العزل';
                }
            });
        });
    </script>
</body>

</html>