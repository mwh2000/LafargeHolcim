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
                                    <label class="block text-sm font-medium text-green-700 mb-1">اسم طالب الرخصه</label>
                                    <input type="text" name="company_name" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">القسم</label>
                                    <input type="text" name="location" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
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
                                    <label class="block text-sm font-medium text-green-700 mb-1">تاريخ اصدار الرخصه</label>
                                    <input type="datetime-local" name="task_start_datetime" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-green-700 mb-1">وقت انتهاء الرخصه</label>
                                    <input type="text" name="finishing_time" placeholder="مثال: 04:00 PM" required class="w-full px-4 py-2 border border-black rounded-md focus:ring-[#0b6f76]">
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
        document.addEventListener('DOMContentLoaded', () => {
            let currentStep = 1;
            let highestStepReached = 1;
            const totalSteps = 6;
            const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";

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

            function checkStepValidity(step) {
                const currentContent = document.querySelector(`.step-content[data-step="${step}"]`);

                if (step === 1) {
                    const requiredInputs = currentContent.querySelectorAll('input[required]');
                    for (let input of requiredInputs) {
                        if (!input.value.trim()) return false;
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
                if (isValid) {
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
                const data = {
                    permit_no: formData.get('permit_no'),
                    company_name: formData.get('company_name'),
                    location: formData.get('location'),
                    supervisor: formData.get('supervisor'),
                    equipment_used: formData.get('equipment_used'),
                    task_start_datetime: formData.get('task_start_datetime'),
                    finishing_time: formData.get('finishing_time'),
                    assigned_to: formData.get('assigned_to'),
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

                    const res = await fetch('../../api/requester/hot_work_permit.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${TOKEN}`
                        },
                        body: JSON.stringify(data)
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