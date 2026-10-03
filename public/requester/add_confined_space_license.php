<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/navbar.php';
require_once __DIR__ . '/../helpers/authCheck.php';

$userData = json_decode($_COOKIE['user_data'] ?? '{}', true);
$currentUserName = $userData['name'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رخصة دخول الأماكن المغلقة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
        }

        .section-title {
            border-right: 4px solid #0b6f76;
            padding-right: .75rem;
        }

        .form-field {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: .375rem;
            padding: .625rem .75rem;
        }

        .form-field:focus {
            outline: 2px solid #0b6f76;
            outline-offset: 1px;
        }

        .gas-table {
            min-width: 760px;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        .step-indicator.active {
            color: #0b6f76;
            font-weight: 700;
            border-bottom: 2px solid #0b6f76;
        }
    </style>
</head>

<body class="bg-slate-50">
    <?php renderNavbar('رخصة دخول الأماكن المغلقة'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('confined_space'); ?>
        <div class="flex-1 flex flex-col sm:ml-64">
            <main class="flex-1 p-4 md:p-8 md:pl-12">
                <div class="max-w-5xl mx-auto">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                        <div>
                            <p class="text-sm text-teal-800 font-semibold">تصريح دخول آمن</p>
                            <h1 class="text-2xl font-bold text-slate-800">رخصة دخول الأماكن المغلقة</h1>
                        </div>
                        <a href="../confined_space_permits.php" class="text-sm text-teal-800 underline">قائمة الرخص</a>
                    </div>

                    <div id="stepIndicators" class="flex justify-between mb-6 border-b pb-2 text-[10px] sm:text-sm overflow-x-auto whitespace-nowrap gap-2">
                        <button type="button" class="step-indicator active px-2 py-2 shrink-0" data-step="1">المعلومات الأساسية</button>
                        <button type="button" class="step-indicator px-2 py-2 shrink-0" data-step="2">تصاريح إضافية</button>
                        <button type="button" class="step-indicator px-2 py-2 shrink-0" data-step="3">إجراءات السيطرة</button>
                        <button type="button" class="step-indicator px-2 py-2 shrink-0" data-step="4">أسماء الداخلين</button>
                        <button type="button" class="step-indicator px-2 py-2 shrink-0" data-step="5">التهوية والاتصالات</button>
                        <button type="button" class="step-indicator px-2 py-2 shrink-0" data-step="6">خطة الطوارئ</button>
                        <button type="button" class="step-indicator px-2 py-2 shrink-0" data-step="7">قياس الغازات</button>
                        <button type="button" class="step-indicator px-2 py-2 shrink-0" data-step="8">مخول إصدار التصريح</button>
                    </div>

                    <form id="confinedSpaceForm" class="space-y-5" novalidate>
                        <section class="step-content active bg-white border border-slate-200 rounded-md p-5 md:p-6" data-step="1">
                            <h2 class="section-title text-lg font-bold text-slate-800 mb-5">القسم الأول: المعلومات الأساسية</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="text-sm font-medium">رقم الرخصة<input class="form-field mt-1 bg-slate-100" value="يصدر تلقائياً عند الحفظ" readonly></label>
                                <label class="text-sm font-medium">رقم أمر العمل (WO)<input name="wo" required class="form-field mt-1"></label>
                                <label class="text-sm font-medium">اسم طالب الرخصة<input name="company_name" required class="form-field mt-1"></label>
                                <label class="text-sm font-medium">القسم
                                    <select name="location" required class="form-field mt-1">
                                        <option value="">اختر القسم</option>
                                        <option>كسارة</option>
                                        <option>طحونة مواد</option>
                                        <option>الأفران</option>
                                        <option>طواحين الاسمنت</option>
                                        <option>التعبئة</option>
                                        <option>محطة الاسالة</option>
                                        <option>أخرى</option>
                                    </select>
                                </label>
                                <label class="text-sm font-medium">الموقع الدقيق<input name="supervisor" required class="form-field mt-1"></label>
                                <label class="text-sm font-medium">المعدة المستخدمة
                                    <select name="equipment_used[]" multiple class="form-field mt-1 min-h-28">
                                        <option value="كوسره">كوسره</option>
                                        <option value="ماكنة لحام">ماكنة لحام</option>
                                        <option value="اوكسي استيلين">اوكسي استيلين</option>
                                    </select>
                                </label>
                                <label class="text-sm font-medium">نوع الصيانة
                                    <select name="maintenance_type" required class="form-field mt-1">
                                        <option value="طارئة">طارئة</option>
                                        <option value="مخطط">مخطط</option>
                                        <option value="pm">pm</option>
                                    </select>
                                </label>
                                <label class="text-sm font-medium">تاريخ ووقت إصدار الرخصة<input name="task_start_datetime" type="datetime-local" required class="form-field mt-1"></label>
                                <label class="text-sm font-medium">وقت انتهاء الرخصة<input name="finishing_time" type="datetime-local" required class="form-field mt-1"></label>
                            </div>
                        </section>

                        <section class="step-content bg-white border border-slate-200 rounded-md p-5 md:p-6" data-step="2">
                            <h2 class="section-title text-lg font-bold text-slate-800 mb-5">القسم الثاني: التصاريح الإضافية المطلوبة</h2>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-right border-collapse">
                                    <thead>
                                        <tr class="bg-slate-100">
                                            <th class="p-3 border">التصريح</th>
                                            <th class="p-3 border">رقم التصريح</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ([['energy_isolation', 'Energy Isolation (LOTOTO)'], ['work_at_height', 'Work at Height'], ['hot_work', 'Hot work permit'], ['other_permit', 'Other Permit']] as [$id, $label]): ?>
                                            <tr>
                                                <td class="p-3 border"><label class="flex items-center gap-2"><input type="checkbox" name="additional_permits_selected[]" value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" class="h-4 w-4 accent-teal-700"><span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span></label><input type="hidden" name="permit_name_<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"></td>
                                                <td class="p-2 border"><input name="permit_no_<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" class="form-field" placeholder="رقم التصريح"></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <label class="block text-sm font-medium mt-5">وصف العمل<textarea name="work_description" required rows="3" class="form-field mt-1"></textarea></label>
                        </section>

                        <section class="step-content bg-white border border-slate-200 rounded-md p-5 md:p-6" data-step="3">
                            <h2 class="section-title text-lg font-bold text-slate-800 mb-5">القسم الثالث: إجراءات السيطرة</h2>
                            <div class="space-y-3">
                                <?php foreach (
                                    [
                                        'هل تم إعداد تقييم للمخاطر قبل الشروع بالدخول؟',
                                        'هل تم استبعاد المخاطر أو هي تحت السيطرة بشكل مناسب؟',
                                        'هل من الممكن أداء العمل بدون الدخول إلى المنطقة المغلقة؟'
                                    ] as $index => $question
                                ): ?>
                                    <fieldset class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                                        <legend class="text-sm"><?= htmlspecialchars($question, ENT_QUOTES, 'UTF-8') ?></legend>
                                        <div class="flex gap-4 shrink-0"><label class="flex items-center gap-1"><input type="radio" name="control_<?= $index ?>" value="نعم" required> نعم</label><label class="flex items-center gap-1"><input type="radio" name="control_<?= $index ?>" value="لا"> لا</label></div>
                                    </fieldset>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="step-content bg-white border border-slate-200 rounded-md p-5 md:p-6" data-step="4">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                <h2 class="section-title text-lg font-bold text-slate-800">أسماء الأشخاص الذين سيدخلون إلى المكان المغلق</h2>
                                <button type="button" id="addEntrant" class="rounded bg-teal-800 px-3 py-2 text-sm text-white">+ إضافة اسم</button>
                            </div>
                            <div id="entrantsList" class="space-y-3"></div>
                        </section>

                        <section class="step-content bg-white border border-slate-200 rounded-md p-5 md:p-6" data-step="5">
                            <h2 class="section-title text-lg font-bold text-slate-800 mb-5">التهوية</h2>
                            <label class="block text-sm font-medium max-w-xl">رقم نموذج وحدة التهوية<input name="ventilation_unit_no" class="form-field mt-1"></label>
                            <label class="flex items-center gap-2 mt-4"><input type="checkbox" name="ventilation_within_limits" value="1" class="h-4 w-4 accent-teal-700"><span>هل طريقة التهوية تحافظ على الحدود المقبولة؟</span></label>
                            <fieldset class="mt-6">
                                <legend class="font-semibold mb-3">الاتصالات (تحديد وسيلة الاتصالات)</legend>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <?php foreach (['مصباح إنارة', 'لاسلكي', 'تواصل بصري', 'إشارة حبل شد', 'تحذير يدوي', 'أخرى'] as $method): ?>
                                        <label class="flex items-center gap-2"><input type="checkbox" name="communications[]" value="<?= htmlspecialchars($method, ENT_QUOTES, 'UTF-8') ?>" class="h-4 w-4 accent-teal-700"><span><?= htmlspecialchars($method, ENT_QUOTES, 'UTF-8') ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        </section>

                        <section class="step-content bg-white border border-slate-200 rounded-md p-5 md:p-6" data-step="6">
                            <h2 class="section-title text-lg font-bold text-slate-800 mb-5">خطة الطوارئ</h2>
                            <label class="block text-sm font-medium max-w-xl">المسؤول عن تنفيذ خطة الطوارئ<input name="emergency_responsible" class="form-field mt-1"></label>
                            <fieldset class="mt-5">
                                <legend class="font-semibold mb-3">ما هي المعدات المطلوبة في حالة الطوارئ؟</legend>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <?php foreach (['رافعة ميكانيكية', 'استخدام ما يناسب من خطة العمل على المرتفعات', 'جهاز هواء SCBA ذاتي التوريد مع نقالة', 'حامل ثلاثي', 'رافعة كهربائية', 'نقالة', 'أخرى'] as $equipment): ?>
                                        <label class="flex items-center gap-2"><input type="checkbox" name="rescue_equipment[]" value="<?= htmlspecialchars($equipment, ENT_QUOTES, 'UTF-8') ?>" class="h-4 w-4 accent-teal-700"><span><?= htmlspecialchars($equipment, ENT_QUOTES, 'UTF-8') ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">
                                <label class="text-sm font-medium">هل معدات الإنقاذ متوفرة قرب موقع العمل؟<select name="rescue_equipment_available" class="form-field mt-1">
                                        <option value="">اختر</option>
                                        <option>نعم</option>
                                        <option>لا</option>
                                    </select></label>
                                <label class="text-sm font-medium">هل تتطلب سيطرة على حركة السير حول المنطقة؟<select name="traffic_control_required" class="form-field mt-1">
                                        <option value="">اختر</option>
                                        <option>نعم</option>
                                        <option>لا</option>
                                    </select></label>
                            </div>
                        </section>

                        <section class="step-content bg-white border border-slate-200 rounded-md p-5 md:p-6" data-step="7">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                <div>
                                    <h2 class="section-title text-lg font-bold text-slate-800">قياس الغازات</h2>
                                    <p class="text-sm text-slate-600 mt-2">أدخل القراءات اليدوية بترتيب الوقت، الأوكسجين، LEL/UEL، CO، ثم H₂S.</p>
                                </div>
                                <button type="button" id="addGasRow" class="rounded bg-teal-800 px-3 py-2 text-sm text-white">+ إضافة قراءة</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="gas-table w-full text-sm border-collapse text-center">
                                    <thead class="bg-[#c9dcef]">
                                        <tr>
                                            <th class="p-2 border">الوقت</th>
                                            <th class="p-2 border">الأوكسجين O₂<br>19.5% إلى 23.5%</th>
                                            <th class="p-2 border">الغازات القابلة للانفجار LEL/UEL<br>أقل من 10%</th>
                                            <th class="p-2 border">أول أكسيد الكربون CO<br>أقل من 5000 PPM</th>
                                            <th class="p-2 border">كبريت الهيدروجين H₂S<br>أقل من 10 PPM</th>
                                            <th class="p-2 border">إجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody id="gasRows"></tbody>
                                </table>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                                <label class="text-sm font-medium">رقم وموديل الجهاز<input name="gas_device_model" class="form-field mt-1"></label>
                                <label class="text-sm font-medium">اسم الشخص المؤهل للقياس<input name="gas_qualified_person" class="form-field mt-1"></label>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                <?php foreach (['gas_device_calibrated' => 'معايرة الجهاز', 'continuous_monitoring' => 'مراقبة مستمرة'] as $field => $label): ?>
                                    <fieldset>
                                        <legend class="text-sm font-medium mb-2"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></legend>
                                        <div class="flex gap-5"><label class="flex items-center gap-1"><input type="radio" name="<?= $field ?>" value="نعم"> نعم</label><label class="flex items-center gap-1"><input type="radio" name="<?= $field ?>" value="لا"> لا</label></div>
                                    </fieldset>
                                <?php endforeach; ?>
                            </div>
                            <aside class="mt-5 rounded border-r-4 border-amber-500 bg-amber-50 p-4 text-sm leading-7 text-slate-800">
                                <p>أ) على مدير الوحدة أن يأذن رسمياً بالعمل في الأماكن المحصورة عالية الخطورة، مثل: الصوامع، صهاريج التخزين، المجاري الجوفية والحفريات أعمق من 2 م.</p>
                                <p>ب) يجب ألا تصدر تصاريح الدخول لأكثر من مناوبة واحدة. إذا تعذر إكمال مدة المهمة / الأنشطة خلال المناوبة، فيجب إصدار تصريح دخول جديد في بداية التحول التالي.</p>
                            </aside>
                        </section>

                        <section class="step-content bg-white border border-slate-200 rounded-md p-5 md:p-6" data-step="8">
                            <h2 class="section-title text-lg font-bold text-slate-800 mb-5">الشخص المخول بإصدار هذا التصريح</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="text-sm font-medium">اسم الشخص<input name="issuer_name" value="<?= htmlspecialchars($currentUserName, ENT_QUOTES, 'UTF-8') ?>" readonly class="form-field mt-1 bg-slate-100"></label>
                                <div class="flex flex-col justify-center gap-3"><label class="flex items-center gap-2"><input type="checkbox" checked disabled class="h-4 w-4 accent-teal-700"><span>مؤهل ومتدرب</span></label><label class="flex items-center gap-2"><input type="checkbox" checked disabled class="h-4 w-4 accent-teal-700"><span>لائق صحياً</span></label></div>
                            </div>
                            <label class="flex items-start gap-2 mt-5 rounded bg-slate-50 p-4"><input type="checkbox" name="issuer_declaration" value="1" required class="mt-1 h-4 w-4 accent-teal-700"><span>أقر بأنه قد تمت المراجعة لكل المتطلبات الرئيسية الآمنة لدخول المكان المغلق.</span></label>
                        </section>

                        <div class="mt-8 flex justify-between gap-3 border-t pt-4">
                            <button type="button" id="prevStep" class="hidden rounded bg-slate-200 px-5 py-2.5 text-slate-700">السابق</button>
                            <button type="button" id="nextStep" class="ml-auto rounded bg-teal-800 px-6 py-2.5 text-white disabled:cursor-not-allowed disabled:bg-slate-300">التالي</button>
                            <button id="submitPermit" class="hidden rounded bg-green-700 px-6 py-2.5 font-semibold text-white disabled:opacity-50" type="submit">إصدار الرخصة</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <script>
        const TOKEN = <?= json_encode($_COOKIE['token'] ?? '') ?>;
        const CURRENT_USER_NAME = <?= json_encode($currentUserName, JSON_UNESCAPED_UNICODE) ?>;
        const API_URL = '../../api/requester/confined_space_permit.php';
        const entrantsList = document.getElementById('entrantsList');
        const gasRows = document.getElementById('gasRows');
        const equipmentSelect = new TomSelect('[name="equipment_used[]"]', {
            persist: false,
            create: false,
            maxItems: null,
            plugins: ['remove_button'],
            placeholder: 'اختر المعدة المستخدمة...'
        });

        function addEntrant(name = '', fit = true, authorized = true) {
            const row = document.createElement('div');
            row.className = 'grid grid-cols-1 lg:grid-cols-[minmax(180px,1fr)_auto_auto_auto] items-center gap-3 rounded border border-slate-200 p-3';
            row.innerHTML = `<input name="entrant_name[]" value="${escapeHtml(name)}" required class="form-field" placeholder="اسم الشخص"><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="entrant_fit[]" value="1" ${fit ? 'checked' : ''} class="h-4 w-4 accent-teal-700">لائق صحياً</label><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="entrant_authorized[]" value="1" ${authorized ? 'checked' : ''} class="h-4 w-4 accent-teal-700">مصرح له بالدخول</label><button type="button" class="remove-row text-sm text-red-700">حذف</button>`;
            entrantsList.appendChild(row);
            row.querySelector('.remove-row').addEventListener('click', () => row.remove());
        }

        function addGasRow(values = []) {
            const row = document.createElement('tr');
            row.innerHTML = [
                ['measurement_time', 'الوقت'],
                ['oxygen_percent', 'O₂ %'],
                ['lel_uel_percent', 'LEL/UEL %'],
                ['co_ppm', 'CO PPM'],
                ['h2s_ppm', 'H₂S PPM']
            ].map(([name, placeholder], index) => `<td class="p-1 border"><input name="${name}[]" value="${escapeHtml(values[index] || '')}" class="form-field text-center min-w-24" placeholder="${placeholder}"></td>`).join('') + '<td class="p-1 border"><button type="button" class="remove-row px-2 text-red-700" aria-label="حذف القراءة">حذف</button></td>';
            gasRows.appendChild(row);
            row.querySelector('.remove-row').addEventListener('click', () => row.remove());
        }

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            } [character]));
        }

        document.getElementById('addEntrant').addEventListener('click', () => addEntrant());
        document.getElementById('addGasRow').addEventListener('click', () => addGasRow());
        addEntrant();
        addGasRow();

        const totalSteps = 8;
        let currentStep = 1;
        let highestStepReached = 1;
        const indicators = [...document.querySelectorAll('.step-indicator')];
        const stepContents = [...document.querySelectorAll('.step-content')];
        const previousButton = document.getElementById('prevStep');
        const nextButton = document.getElementById('nextStep');
        const submitButton = document.getElementById('submitPermit');

        function isStepValid(step) {
            const section = document.querySelector(`.step-content[data-step="${step}"]`);
            if (!section) return false;
            const requiredFields = section.querySelectorAll('input[required], select[required], textarea[required]');
            if ([...requiredFields].some(field => !String(field.value).trim())) return false;

            if (step === 1) return document.querySelectorAll('[name="equipment_used[]"] option:checked').length > 0;
            if (step === 2) {
                return section.querySelectorAll('[name="additional_permits_selected[]"]:checked').length > 0 &&
                    !!section.querySelector('[name="work_description"]').value.trim();
            }
            if (step === 3) return [0, 1, 2].every(index => section.querySelector(`[name="control_${index}"]:checked`));
            if (step === 4) {
                const names = [...entrantsList.querySelectorAll('[name="entrant_name[]"]')];
                return names.length > 0 && names.every(input => input.value.trim());
            }
            if (step === 6) return !!section.querySelector('[name="rescue_equipment_available"]').value &&
                !!section.querySelector('[name="traffic_control_required"]').value;
            if (step === 7) return !!section.querySelector('[name="gas_device_model"]').value.trim() &&
                !!section.querySelector('[name="gas_qualified_person"]').value.trim() &&
                !!section.querySelector('[name="gas_device_calibrated"]:checked') &&
                !!section.querySelector('[name="continuous_monitoring"]:checked');
            if (step === 8) return section.querySelector('[name="issuer_declaration"]').checked;
            return true;
        }

        function updateTabStates() {
            const valid = isStepValid(currentStep);
            nextButton.disabled = !valid;
            submitButton.disabled = !valid;
            if (valid && currentStep >= highestStepReached) highestStepReached = Math.min(totalSteps, currentStep + 1);
            if (!valid && currentStep <= highestStepReached) highestStepReached = currentStep;
            indicators.forEach(indicator => {
                const step = Number(indicator.dataset.step);
                indicator.disabled = step > highestStepReached;
                indicator.classList.toggle('cursor-pointer', !indicator.disabled);
                indicator.classList.toggle('opacity-50', indicator.disabled);
            });
        }

        function showStep(step) {
            currentStep = step;
            stepContents.forEach(section => section.classList.toggle('active', Number(section.dataset.step) === step));
            indicators.forEach(indicator => indicator.classList.toggle('active', Number(indicator.dataset.step) === step));
            previousButton.classList.toggle('hidden', step === 1);
            nextButton.classList.toggle('hidden', step === totalSteps);
            submitButton.classList.toggle('hidden', step !== totalSteps);
            updateTabStates();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        document.getElementById('confinedSpaceForm').addEventListener('input', updateTabStates);
        document.getElementById('confinedSpaceForm').addEventListener('change', updateTabStates);
        indicators.forEach(indicator => indicator.addEventListener('click', () => {
            const targetStep = Number(indicator.dataset.step);
            if (targetStep <= highestStepReached) showStep(targetStep);
        }));
        previousButton.addEventListener('click', () => showStep(currentStep - 1));
        nextButton.addEventListener('click', () => {
            if (isStepValid(currentStep)) showStep(currentStep + 1);
            else Swal.fire({
                icon: 'warning',
                title: 'أكمل بيانات القسم',
                text: 'تحقق من الحقول المطلوبة قبل الانتقال إلى الخطوة التالية'
            });
        });

        document.getElementById('confinedSpaceForm').addEventListener('submit', async event => {
            event.preventDefault();
            const invalidStep = Array.from({
                length: totalSteps
            }, (_, index) => index + 1).find(step => !isStepValid(step));
            if (invalidStep) {
                highestStepReached = Math.max(highestStepReached, invalidStep);
                showStep(invalidStep);
                Swal.fire({
                    icon: 'warning',
                    title: 'بيانات غير مكتملة',
                    text: 'أكمل الحقول المطلوبة في جميع الخطوات قبل إصدار الرخصة'
                });
                return;
            }
            const form = event.currentTarget;
            const fd = new FormData(form);
            const data = {
                wo: fd.get('wo'),
                company_name: fd.get('company_name'),
                location: fd.get('location'),
                supervisor: fd.get('supervisor'),
                equipment_used: fd.getAll('equipment_used[]'),
                maintenance_type: fd.get('maintenance_type'),
                task_start_datetime: fd.get('task_start_datetime'),
                finishing_time: fd.get('finishing_time'),
                work_description: fd.get('work_description'),
                ventilation_unit_no: fd.get('ventilation_unit_no'),
                ventilation_within_limits: fd.has('ventilation_within_limits'),
                emergency_responsible: fd.get('emergency_responsible'),
                rescue_equipment_available: fd.get('rescue_equipment_available'),
                traffic_control_required: fd.get('traffic_control_required'),
                gas_device_model: fd.get('gas_device_model'),
                gas_qualified_person: fd.get('gas_qualified_person'),
                gas_device_calibrated: fd.get('gas_device_calibrated'),
                continuous_monitoring: fd.get('continuous_monitoring'),
                issuer_declaration: fd.has('issuer_declaration'),
                issuer_name: CURRENT_USER_NAME,
                additional_permits: [],
                control_measures: [],
                entrants: [],
                communications: fd.getAll('communications[]'),
                rescue_equipment: fd.getAll('rescue_equipment[]'),
                gas_measurements: []
            };
            fd.getAll('additional_permits_selected[]').forEach(id => data.additional_permits.push({
                permit_name: fd.get(`permit_name_${id}`),
                permit_number: fd.get(`permit_no_${id}`)
            }));
            for (let index = 0; index < 3; index++) data.control_measures.push({
                text: <?= json_encode(['هل تم إعداد تقييم للمخاطر قبل الشروع بالدخول؟', 'هل تم استبعاد المخاطر أو هي تحت السيطرة بشكل مناسب؟', 'هل من الممكن أداء العمل بدون الدخول إلى المنطقة المغلقة؟'], JSON_UNESCAPED_UNICODE) ?>[index],
                answer: fd.get(`control_${index}`)
            });
            entrantsList.querySelectorAll(':scope > div').forEach(row => data.entrants.push({
                name: row.querySelector('[name="entrant_name[]"]').value,
                medically_fit: row.querySelector('[name="entrant_fit[]"]').checked,
                authorized_to_enter: row.querySelector('[name="entrant_authorized[]"]').checked
            }));
            if (!data.entrants.length || !fd.getAll('additional_permits_selected[]').length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'معلومات ناقصة',
                    text: 'أضف شخصاً واحداً على الأقل وحدد تصريحاً إضافياً واحداً'
                });
                return;
            }
            const measurementColumns = ['measurement_time[]', 'oxygen_percent[]', 'lel_uel_percent[]', 'co_ppm[]', 'h2s_ppm[]'].map(key => fd.getAll(key));
            for (let index = 0; index < measurementColumns[0].length; index++) data.gas_measurements.push({
                measurement_time: measurementColumns[0][index],
                oxygen_percent: measurementColumns[1][index],
                lel_uel_percent: measurementColumns[2][index],
                co_ppm: measurementColumns[3][index],
                h2s_ppm: measurementColumns[4][index]
            });

            const button = submitButton;
            button.disabled = true;
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${TOKEN}`
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'تعذر إصدار الرخصة');
                await Swal.fire({
                    icon: 'success',
                    title: 'تم إصدار الرخصة',
                    text: result.permit_no,
                    confirmButtonColor: '#0b6f76'
                });
                window.location.href = `view_confined_space_license.php?id=${result.id}`;
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'تعذر الحفظ',
                    text: error.message,
                    confirmButtonColor: '#0b6f76'
                });
                button.disabled = false;
            }
        });
    </script>
</body>

</html>