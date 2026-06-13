<?php
require_once '../../core/Database.php';
require_once '../../config/config.php';
require_once __DIR__ . '../../partials/sidebar.php';
require_once __DIR__ . '../../partials/navbar.php';
require_once '../helpers/authCheck.php';

// Fetch options from partials
$additionalPermits = require '../partials/hot_work/additional_permits.php';
$controlMeasures = require '../partials/hot_work/control_measures.php';
$performersCheck = require '../partials/hot_work/performers_check.php';

// Fetch database connection
$config = require '../../config/config.php';
$db = new Database($config['db']);
$pdo = $db->getConnection();

// Auto-generate license number (permit_no)
$lastNoStmt = $pdo->query("SELECT permit_no FROM hot_work_permit ORDER BY id DESC LIMIT 1");
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
    <title>KCML / SLV | رخصة العمل الساخن Hot Work Permit</title>
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

        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php renderNavbar('رخصة العمل الساخن'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('hot_work'); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-4 md:p-8 md:pl-12">
                <div class="max-w-4xl mx-auto">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
                        <h1 class="text-xl md:text-2xl font-semibold text-gray-700">رخصة العمل الساخن (Hot Work Permit)</h1>
                        <span class="text-sm text-red-600 md:text-base">رقم الإطفاء ( 07806444440 )</span>
                    </div>
                    <p class="text-lg text-red-700 mb-6">الاعمال الساخنة تشمل اعمال اللحام والجلغ والقطع</p>

                    <!-- Progress Bar -->
                    <div class="flex justify-between mb-8 border-b pb-2 text-[9px] md:text-sm overflow-x-auto no-scrollbar whitespace-nowrap gap-2">
                        <div class="step-indicator active px-2 transition-all duration-300 flex-shrink-0" data-step="1">المعلومات الأساسية</div>
                        <div class="step-indicator px-2 transition-all duration-300 flex-shrink-0" data-step="2">تصاريح إضافية</div>
                        <div class="step-indicator px-2 transition-all duration-300 flex-shrink-0" data-step="3">إجراءات السيطرة</div>
                        <div class="step-indicator px-2 transition-all duration-300 flex-shrink-0" data-step="4">منفذي الأعمال</div>
                        <div class="step-indicator px-2 transition-all duration-300 flex-shrink-0" data-step="5">المطابقة والموافقة</div>
                        <div class="step-indicator px-2 transition-all duration-300 flex-shrink-0" data-step="6">إسناد الرخصة</div>
                    </div>

                    <form id="hotWorkForm" class="bg-white p-6 rounded-lg shadow-md">

                        <!-- Step 1: Basic Information -->
                        <div class="step-content active" data-step="1">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">القسم الأول: المعلومات الأساسية</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">رقم الرخصة</label>
                                    <input type="text" name="permit_no" value="<?= $nextLicenseNo ?>" readonly class="w-full px-4 py-2 border rounded-md bg-gray-100 cursor-not-allowed focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">رقم أمر العمل (WO)</label>
                                    <input type="text" name="wo" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">اسم طالب الرخصه</label>
                                    <input type="text" name="company_name" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">القسم</label>
                                    <select id="location" name="location" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]"></select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">رخصة عمل حرجة</label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" id="is_critical" name="is_critical" value="1" class="w-5 h-5 text-[#0b6f76] rounded">
                                        <span class="text-sm">رخصة عمل حرجة</span>
                                    </label>
                                </div>
                                <div id="critical_manager_container" style="display:none;">
                                    <label class="block text-sm font-medium text-green-700 mb-1">موافقة قسم السلامة</label>
                                    <select id="critical_manager_id" name="critical_manager_id" class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]"></select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">الموقع الدقيق</label>
                                    <input type="text" name="supervisor" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">المعدة المستخدمة</label>
                                    <input type="text" name="equipment_used" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">نوع الصيانة</label>
                                    <select id="maintenance_type" name="maintenance_type" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                        <option selected value="طارئة">طارئة</option>
                                        <option value="العمل على المرتفعات">العمل على المرتفعات</option>
                                        <option value="مخطط">مخطط</option>
                                        <option value="pm">pm</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">تاريخ اصدار الرخصه</label>
                                    <input type="datetime-local" name="task_start_datetime" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">وقت انتهاء الرخصه</label>
                                    <input type="datetime-local" name="finishing_time" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">تاريخ انشاء الرخصة</label>
                                    <input type="date" name="creation_date" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Additional Permits -->
                        <div class="step-content" data-step="2">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">القسم الثاني: التصاريح الإضافية المطلوبة</h2>
                            <div class="overflow-x-auto mb-6">
                                <table class="w-full text-right border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="p-2 border">التصريح</th>
                                            <th class="p-2 border">رقم التصريح</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($additionalPermits as $permit): ?>
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="p-2 border">
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" name="additional_permits_selected[]" value="<?= $permit['id'] ?>" class="w-4 h-4 text-[#0b6f76] rounded">
                                                        <span class="text-sm"><?= $permit['label_ar'] ?></span>
                                                    </label>
                                                    <input type="hidden" name="permit_name_<?= $permit['id'] ?>" value="<?= $permit['label_ar'] ?>">
                                                </td>
                                                <td class="p-2 border">
                                                    <input type="text" name="permit_no_<?= $permit['id'] ?>" placeholder="رقم التصريح" class="w-full px-2 py-1 border rounded focus:ring-1 focus:ring-[#0b6f76] outline-none">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="border-t pt-4">
                                <label class="block text-sm font-medium text-green-700 mb-2">وصف العمل (Work Description)</label>
                                <textarea name="work_description" rows="3" required placeholder="يرجى كتابة وصف العمل هنا..." class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76] outline-none"></textarea>
                            </div>
                        </div>

                        <!-- Step 3: Control Measures -->
                        <div class="step-content" data-step="3">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">القسم الثالث: إجراءات السيطرة</h2>
                            <div class="space-y-2">
                                <?php foreach ($controlMeasures as $index => $measure): ?>
                                    <div class="p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                                            <span class="text-sm text-gray-700 flex-1"><?= ($index + 1) . ". " . $measure ?></span>
                                            <input type="hidden" name="control_measure_text_<?= $index ?>" value="<?= htmlspecialchars($measure) ?>">
                                            <div class="flex gap-3">
                                                <label class="flex items-center gap-1 cursor-pointer">
                                                    <input type="radio" name="control_measure_status_<?= $index ?>" value="نعم" class="w-4 h-4 text-[#0b6f76]">
                                                    <span class="text-xs">نعم</span>
                                                </label>
                                                <label class="flex items-center gap-1 cursor-pointer">
                                                    <input type="radio" name="control_measure_status_<?= $index ?>" value="كلا" class="w-4 h-4 text-red-600">
                                                    <span class="text-xs">كلا</span>
                                                </label>
                                                <label class="flex items-center gap-1 cursor-pointer">
                                                    <input type="radio" name="control_measure_status_<?= $index ?>" value="غير متاح" class="w-4 h-4 text-gray-500">
                                                    <span class="text-xs">غير متاح</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Step 4: Performers Check -->
                        <div class="step-content" data-step="4">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">القسم الرابع: منفذي الأعمال الساخنة</h2>
                            <div class="space-y-2">
                                <?php foreach ($performersCheck as $index => $check): ?>
                                    <div class="p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                                            <span class="text-sm text-gray-700 flex-1"><?= ($index + 1) . ". " . $check ?></span>
                                            <input type="hidden" name="performer_check_text_<?= $index ?>" value="<?= htmlspecialchars($check) ?>">
                                            <div class="flex gap-3">
                                                <label class="flex items-center gap-1 cursor-pointer">
                                                    <input type="radio" name="performer_check_answer_<?= $index ?>" value="نعم" class="w-4 h-4 text-[#0b6f76]">
                                                    <span class="text-xs">نعم</span>
                                                </label>
                                                <label class="flex items-center gap-1 cursor-pointer">
                                                    <input type="radio" name="performer_check_answer_<?= $index ?>" value="كلا" class="w-4 h-4 text-red-600">
                                                    <span class="text-xs">كلا</span>
                                                </label>
                                                <label class="flex items-center gap-1 cursor-pointer">
                                                    <input type="radio" name="performer_check_answer_<?= $index ?>" value="غير متاح" class="w-4 h-4 text-gray-500">
                                                    <span class="text-xs">غير متاح</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Step 5: Approvals -->
                        <div class="step-content" data-step="5">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">القسم الخامس: المطابقة والموافقة</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php
                                $userData = json_decode($_COOKIE['user_data'] ?? '{}', true);
                                $currentUserName = $userData['name'] ?? '';
                                $roles = [
                                    ['id' => 'welding', 'label' => 'اللحام (Welding Name)'],
                                    ['id' => 'supervisor', 'label' => 'المشرف (Supervisor)'],
                                    ['id' => 'fire_sentry', 'label' => 'مراقب النار (Fire Sentry)'],
                                    ['id' => 'ptw_issuer', 'label' => 'مخول التصريح (PTW Issuer)']
                                ];
                                foreach ($roles as $role):
                                    $isIssuer = ($role['id'] === 'ptw_issuer');
                                ?>
                                    <div class="p-4 border rounded-lg bg-gray-50">
                                        <label class="block text-sm font-medium text-green-700 mb-1"><?= $role['label'] ?></label>
                                        <input type="text"
                                            name="approval_name_<?= $role['id'] ?>"
                                            value="<?= $isIssuer ? $currentUserName : '' ?>"
                                            <?= $isIssuer ? 'readonly' : '' ?>
                                            placeholder="الاسم الكامل"
                                            class="w-full px-3 py-2 border rounded-md mb-2 focus:ring-[#0b6f76] outline-none <?= $isIssuer ? 'bg-gray-100 cursor-not-allowed' : '' ?>">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="approval_status_<?= $role['id'] ?>" value="1" class="w-5 h-5 text-[#0b6f76] rounded">
                                            <span class="text-xs font-medium text-gray-700">تمت المطابقة والموافقة</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Step 6: Assigned To -->
                        <div class="step-content" data-step="6">
                            <h2 class="text-xl font-medium mb-4 text-[#0b6f76]">القسم السادس: إسناد الرخصة</h2>
                            <div class="max-w-md mx-auto">
                                <label class="block text-sm font-medium text-green-700 mb-2">اختر الشخص المسؤول (Assigned To)</label>
                                <select id="assigned_to" name="assigned_to" required class="w-full"></select>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="mt-8 flex justify-between border-t pt-4">
                            <button type="button" id="prevBtn" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hidden">السابق</button>
                            <button type="button" id="nextBtn" disabled class="px-6 py-2 bg-[#0b6f76] text-white rounded-md ml-auto disabled:bg-gray-300 disabled:cursor-not-allowed">التالي</button>
                            <button type="submit" id="submitBtn" disabled class="px-6 py-2 bg-green-600 text-white rounded-md ml-auto hidden disabled:bg-gray-300 disabled:cursor-not-allowed">إرسال الرخصة</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            let currentStep = 1;
            let highestStepReached = 1;
            const totalSteps = 6;
            const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";

            const group1 = [
                'كسارة', 'طحونة مواد', 'الافران', 'طواحين الاسمنت', 'التعبئة', 'محطة الاساله', 'اخرى'
            ];
            const group2 = [
                'خزانات الوقود', 'محطة تفريغ النفط', 'انابيب نقل الوقود', 'مشعل الرئيسي', 'المشعل الثانوي', 'مخازن الاصباغ والمواد الكيمياوية', 'خزانات الغاز'
            ];

            const form = document.getElementById('hotWorkForm');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtn = document.getElementById('submitBtn');
            const indicators = document.querySelectorAll('.step-indicator');
            const contents = document.querySelectorAll('.step-content');

            // Initialize TomSelect for Assignee
            let assigneeSelect = new TomSelect('#assigned_to', {
                persist: false,
                create: false,
                placeholder: 'اختر المسؤول...',
                onChange: () => updateButtonStates()
            });

            // Critical manager select (TomSelect)
            let criticalManagerSelect = new TomSelect('#critical_manager_id', {
                persist: false,
                create: false,
                placeholder: 'اختر موافقة القسم...'
            });

            const isCriticalCheckbox = document.getElementById('is_critical');
            const criticalManagerContainer = document.getElementById('critical_manager_container');
            const locationSelect = document.getElementById('location');

            let resumeMode = false;

            function setPointerEventsForStep1(enabled) {
                document.querySelectorAll('.step-content[data-step="1"] select').forEach(el => {
                    if (enabled) {
                        el.style.pointerEvents = '';
                        el.style.background = '';
                    } else {
                        el.style.pointerEvents = 'none';
                        el.style.background = '#f3f4f6';
                    }
                });
            }

            function disableNonStep1Controls(disable) {
                document.querySelectorAll('.step-content').forEach(c => {
                    if (c.dataset.step !== '1') {
                        c.querySelectorAll('input,select,textarea').forEach(el => {
                            el.disabled = disable;
                        });
                    }
                });
            }

            function populateLocation(isCritical) {
                const list = isCritical ? group2 : group1;
                locationSelect.innerHTML = '';
                list.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item;
                    opt.textContent = item;
                    locationSelect.appendChild(opt);
                });
            }

            // initial populate (normal)
            populateLocation(false);

            isCriticalCheckbox.addEventListener('change', async (e) => {
                const checked = e.target.checked;
                populateLocation(checked);
                document.getElementById('critical_manager_id').required = checked;
                if (checked) {
                    criticalManagerContainer.style.display = 'block';
                    if (!resumeMode) {
                        // hide steps 2-6 and disable their controls so validation won't block
                        document.querySelectorAll('.step-content').forEach(c => {
                            if (c.dataset.step !== '1') c.style.display = 'none';
                        });
                        disableNonStep1Controls(true);
                        nextBtn.textContent = 'إرسال الرخصة الحرجة';
                        // load managers
                        try {
                            const res = await fetch('../../api/requester/hot_work_permit.php?action=getManagers', {
                                headers: {
                                    'Authorization': `Bearer ${TOKEN}`
                                }
                            });
                            const data = await res.json();
                            if (data.success) {
                                criticalManagerSelect.clearOptions();
                                data.data.forEach(m => criticalManagerSelect.addOption({
                                    value: m.id,
                                    text: m.name
                                }));
                                criticalManagerSelect.refreshOptions(false);
                            }
                        } catch (err) {
                            console.error('Failed to load managers', err);
                        }
                    } else {
                        // resume mode: keep steps visible and enabled
                        document.querySelectorAll('.step-content').forEach(c => c.style.display = '');
                        disableNonStep1Controls(false);
                        nextBtn.textContent = 'التالي';
                    }
                } else {
                    criticalManagerContainer.style.display = 'none';
                    document.querySelectorAll('.step-content').forEach(c => c.style.display = '');
                    disableNonStep1Controls(false);
                    nextBtn.textContent = 'التالي';
                }
            });

            // If editing/resuming (id param), load permit and prepare resume mode
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('id');
            if (editId) {
                try {
                    const res = await fetch(`../../api/requester/hot_work_permit.php?action=show&id=${editId}`, {
                        headers: {
                            'Authorization': `Bearer ${TOKEN}`
                        }
                    });
                    const jr = await res.json();
                    if (jr.success) {
                        const p = jr.data;
                        // If critical and pending_creator, enable resume mode
                        if (p.is_critical && p.critical_status === 'pending_creator') {
                            // enable resume mode: indicate critical but allow steps 2-6 to be edited
                            resumeMode = true;
                            isCriticalCheckbox.checked = true;
                            populateLocation(true);
                            criticalManagerContainer.style.display = 'block';

                            // lock step 1 inputs (readonly) but keep them enabled for submission
                            document.querySelectorAll('.step-content[data-step="1"] input, .step-content[data-step="1"] textarea').forEach(el => {
                                el.readOnly = true;
                            });
                            // for selects, prevent pointer events but keep enabled so their values are submitted
                            setPointerEventsForStep1(false);

                            // populate basic fields
                            document.querySelector('input[name="permit_no"]').value = p.permit_no || '';
                            document.querySelector('input[name="wo"]').value = p.wo || '';
                            document.querySelector('input[name="company_name"]').value = p.company_name || '';
                            document.getElementById('location').value = p.location || '';
                            document.querySelector('input[name="supervisor"]').value = p.supervisor || '';
                            document.querySelector('input[name="equipment_used"]').value = p.equipment_used || '';
                            document.getElementById('maintenance_type').value = p.maintenance_type || 'طارئة';
                            if (p.task_start_datetime) document.querySelector('input[name="task_start_datetime"]').value = p.task_start_datetime.replace(' ', 'T');
                            if (p.finishing_time) document.querySelector('input[name="finishing_time"]').value = p.finishing_time.replace(' ', 'T');
                            if (p.creation_date) document.querySelector('input[name="creation_date"]').value = p.creation_date;

                            // show steps 2-6 and ensure their controls are enabled
                            document.querySelectorAll('.step-content').forEach(c => c.style.display = '');
                            disableNonStep1Controls(false);
                            currentStep = 2;
                            updateUI();
                        }
                    }
                } catch (e) {
                    console.error('Failed to load permit for edit', e);
                }
            }

            function checkStepValidity(step) {
                const currentContent = document.querySelector(`.step-content[data-step="${step}"]`);

                if (step === 1) {
                    const requiredFields = currentContent.querySelectorAll('input[required], select[required], textarea[required]');
                    for (let field of requiredFields) {
                        if (!field.value.trim()) return false;
                    }
                } else if (step === 2) {
                    const checkedCount = currentContent.querySelectorAll('input[name="additional_permits_selected[]"]:checked').length;
                    const workDesc = currentContent.querySelector('textarea[name="work_description"]').value.trim();
                    if (checkedCount === 0 || !workDesc) return false;
                } else if (step === 3) {
                    // Must answer ALL control measures
                    const questionsCount = currentContent.querySelectorAll('.p-3.border.rounded-lg').length;
                    const radioChecked = currentContent.querySelectorAll('input[type="radio"]:checked').length;
                    if (radioChecked < questionsCount) return false;
                } else if (step === 4) {
                    // Must answer ALL performer checks
                    const questionsCount = currentContent.querySelectorAll('.p-3.border.rounded-lg').length;
                    const radioChecked = currentContent.querySelectorAll('input[type="radio"]:checked').length;
                    if (radioChecked < questionsCount) return false;
                } else if (step === 5) {
                    // Assuming all 4 names are mandatory as section is mandatory
                    const names = currentContent.querySelectorAll('input[type="text"]');
                    for (let input of names) {
                        if (!input.value.trim()) return false;
                    }
                } else if (step === 6) {
                    if (!assigneeSelect.getValue()) return false;
                }

                return true;
            }

            function updateButtonStates() {
                const isValid = checkStepValidity(currentStep);
                nextBtn.disabled = !isValid;
                submitBtn.disabled = !isValid;

                // Update highestStepReached based on current step's validity
                if (isCriticalCheckbox.checked && !resumeMode) {
                    highestStepReached = 1;
                } else if (isValid) {
                    if (currentStep >= highestStepReached) {
                        highestStepReached = currentStep + 1;
                    }
                } else {
                    // If current step becomes invalid, we can't go beyond it
                    if (currentStep <= highestStepReached) {
                        highestStepReached = currentStep;
                    }
                }
                updateIndicatorStyles();
            }

            function updateIndicatorStyles() {
                indicators.forEach(indicator => {
                    const step = parseInt(indicator.dataset.step);
                    // A step is clickable if it's less than or equal to highestStepReached
                    // BUT step 1 is always clickable.
                    if (step === 1 || step <= highestStepReached) {
                        indicator.classList.add('cursor-pointer');
                        indicator.style.opacity = '1';
                    } else {
                        indicator.classList.remove('cursor-pointer');
                        indicator.style.opacity = '0.5';
                    }
                });
            }

            // Listen for any input changes in the form
            form.addEventListener('input', () => updateButtonStates());
            form.addEventListener('change', () => updateButtonStates());

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

                if (currentStep === 6) {
                    loadAssignees().then(() => updateButtonStates());
                } else {
                    updateButtonStates();
                }
                updateIndicatorStyles();
            }

            nextBtn.addEventListener('click', () => {
                if (isCriticalCheckbox.checked && !resumeMode) {
                    // submit immediately for critical permits (only if not resuming)
                    form.requestSubmit();
                    return;
                }
                if (checkStepValidity(currentStep)) {
                    currentStep++;
                    updateUI();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });

            prevBtn.addEventListener('click', () => {
                currentStep--;
                updateUI();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            indicators.forEach(indicator => {
                indicator.addEventListener('click', () => {
                    const step = parseInt(indicator.dataset.step);
                    // Allow navigation only if the step is clickable
                    if (step === 1 || step <= highestStepReached) {
                        currentStep = step;
                        updateUI();
                    }
                });
            });

            async function loadAssignees() {
                if (assigneeSelect.options.length > 0) return;

                try {
                    const res = await fetch('../../api/requester/hot_work_permit.php?action=getAssignees', {
                        headers: {
                            'Authorization': `Bearer ${TOKEN}`
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        data.data.forEach(user => {
                            assigneeSelect.addOption({
                                value: user.id,
                                text: user.name
                            });
                        });
                        assigneeSelect.refreshOptions(false);
                    }
                } catch (e) {
                    console.error('Failed to load assignees');
                }
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(form);

                // If critical (and not resuming), send minimal payload to create a critical permit
                if (isCriticalCheckbox.checked && !resumeMode) {
                    const payload = {
                        permit_no: formData.get('permit_no'),
                        wo: formData.get('wo'),
                        company_name: formData.get('company_name'),
                        location: formData.get('location'),
                        supervisor: formData.get('supervisor'),
                        equipment_used: formData.get('equipment_used'),
                        maintenance_type: formData.get('maintenance_type'),
                        task_start_datetime: formData.get('task_start_datetime'),
                        finishing_time: formData.get('finishing_time'),
                        creation_date: formData.get('creation_date'),
                        is_critical: 1,
                        critical_manager_id: criticalManagerSelect.getValue(),
                        critical_status: 'pending_manager'
                    };

                    try {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'جاري الإرسال...';

                        const res = await fetch('../../api/requester/hot_work_permit.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${TOKEN}`
                            },
                            body: JSON.stringify(payload)
                        });
                        const result = await res.json();
                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: result.message,
                                confirmButtonText: 'حسناً',
                                confirmButtonColor: '#0b6f76'
                            }).then(() => {
                                const redirectId = result.id || editId;
                                window.location.href = `view_hot_work_license.php?id=${redirectId}`;
                            });
                        } else {
                            throw new Error(result.message);
                        }
                    } catch (err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: err.message || 'حدث خطأ ما',
                            confirmButtonText: 'حسناً',
                            confirmButtonColor: '#0b6f76'
                        });
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'إرسال الرخصة';
                    }

                    return;
                }

                const data = {
                    permit_no: formData.get('permit_no'),
                    wo: formData.get('wo'),
                    company_name: formData.get('company_name'),
                    location: formData.get('location'),
                    supervisor: formData.get('supervisor'),
                    equipment_used: formData.get('equipment_used'),
                    maintenance_type: formData.get('maintenance_type'),
                    task_start_datetime: formData.get('task_start_datetime'),
                    finishing_time: formData.get('finishing_time'),
                    creation_date: formData.get('creation_date'),
                    assigned_to: assigneeSelect.getValue(),
                    work_description: formData.get('work_description'),
                    additional_permits: [],
                    control_measures: [],
                    performers_check: [],
                    approvals: []
                };

                // Collection of Additional Permits
                const selectedPermits = formData.getAll('additional_permits_selected[]');
                selectedPermits.forEach(id => {
                    data.additional_permits.push({
                        permit_name: formData.get('permit_name_' + id),
                        permit_number: formData.get('permit_no_' + id)
                    });
                });

                // Collection of Control Measures
                <?php foreach ($controlMeasures as $index => $measure): ?>
                    data.control_measures.push({
                        text: formData.get('control_measure_text_<?= $index ?>'),
                        status: formData.get('control_measure_status_<?= $index ?>') || 'غير متاح'
                    });
                <?php endforeach; ?>

                // Collection of Performers Check
                <?php foreach ($performersCheck as $index => $check): ?>
                    data.performers_check.push({
                        text: formData.get('performer_check_text_<?= $index ?>'),
                        answer: formData.get('performer_check_answer_<?= $index ?>') || 'غير متاح'
                    });
                <?php endforeach; ?>

                // Collection of Approvals
                const roles = ['welding', 'supervisor', 'fire_sentry', 'ptw_issuer'];
                const roleLabels = {
                    'welding': 'Welding Name',
                    'supervisor': 'Supervisor',
                    'fire_sentry': 'Fire Sentry',
                    'ptw_issuer': 'PTW Issuer'
                };
                roles.forEach(role => {
                    data.approvals.push({
                        role: roleLabels[role],
                        name: formData.get('approval_name_' + role),
                        approved: formData.get('approval_status_' + role) === '1'
                    });
                });

                try {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'جاري الإرسال...';

                    // Determine API endpoint and payload based on mode
                    let url = '../../api/requester/hot_work_permit.php';
                    let payload = data;

                    if (resumeMode) {
                        // When completing a critical permit, use complete action
                        url += '?action=complete';
                        payload = {
                            ...data,
                            permit_id: editId
                        };
                    }

                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${TOKEN}`
                        },
                        body: JSON.stringify(payload)
                    });
                    const result = await res.json();
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'تم بنجاح',
                            text: result.message,
                            confirmButtonText: 'حسناً',
                            confirmButtonColor: '#0b6f76'
                        }).then(() => {
                            window.location.href = `view_hot_work_license.php?id=${result.id}`;
                        });
                    } else {
                        throw new Error(result.message);
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: err.message || 'حدث خطأ ما',
                        confirmButtonText: 'حسناً',
                        confirmButtonColor: '#0b6f76'
                    });
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'إرسال الرخصة';
                }
            });
        });
    </script>
</body>

</html>