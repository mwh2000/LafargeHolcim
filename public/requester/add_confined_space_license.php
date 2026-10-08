<?php
require_once __DIR__ . '/../../core/Database.php';
$config = require __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/navbar.php';
require_once __DIR__ . '/../helpers/authCheck.php';
require_once __DIR__ . '/../../controllers/PermitListController.php';

$userData = json_decode($_COOKIE['user_data'] ?? '{}', true);
$currentUserName = $userData['name'] ?? '';
$database = new Database($config['db']);
$pdo = $database->getConnection();
$counterStmt = $pdo->prepare('SELECT next_number FROM license_number_counters WHERE counter_key = ?');
$counterStmt->execute(['confined_space_permit']);
$nextPermitSerial = (int)($counterStmt->fetchColumn() ?: 1);
$nextPermitNo = 'OHSM-PTW-C.S-' . str_pad((string)$nextPermitSerial, 3, '0', STR_PAD_LEFT);
$editPermitId = (int)($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">

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

        .gas-value-invalid {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
            color: #b91c1c !important;
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
                        <button type="button" class="step-indicator px-2 py-2 shrink-0" data-step="9">إسناد الرخصة</button>
                    </div>

                    <form id="confinedSpaceForm" class="space-y-5" data-permit-id="<?= $editPermitId ?>" novalidate>
                        <section class="step-content active bg-white border border-slate-200 rounded-md p-5 md:p-6" data-step="1">
                            <h2 class="section-title text-lg font-bold text-slate-800 mb-5">القسم الأول: المعلومات الأساسية</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="text-sm font-medium">رقم الرخصة<input name="permit_no_display" class="form-field mt-1 bg-slate-100" value="<?= htmlspecialchars($nextPermitNo, ENT_QUOTES, 'UTF-8') ?>" readonly></label>
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
                                        <?php foreach ((new PermitListController($pdo, 'confined_space_equipment'))->getOptions() as $equipmentOption): ?>
                                            <option value="<?= htmlspecialchars($equipmentOption['name']) ?>"><?= htmlspecialchars($equipmentOption['name']) ?></option>
                                        <?php endforeach; ?>
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
                            <fieldset class="space-y-4">
                                <legend class="text-sm font-medium">هل تم إعداد تقييم للمخاطر قبل الشروع بالدخول؟</legend>
                                <div class="flex gap-4"><label class="flex items-center gap-1"><input type="radio" name="control_0" value="نعم" required> نعم</label><label class="flex items-center gap-1"><input type="radio" name="control_0" value="لا"> لا</label></div>
                                <label class="inline-flex cursor-pointer items-center rounded border border-teal-800 px-3 py-2 text-sm text-teal-800">
                                    اختيار صور تقييم المخاطر
                                    <input id="controlImagesInput" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">
                                </label>
                                <p class="text-xs text-slate-500">يمكنك اختيار عدة صور من نافذة الاختيار نفسها.</p>
                                <div id="controlImagesPreview" class="flex flex-wrap gap-3"></div>
                            </fieldset>
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
                            <label class="block text-sm font-medium max-w-xl">رقم نموذج وحدة التهوية<select name="ventilation_unit_no" class="form-field mt-1">
                                    <option value="ميكانيكية">ميكانيكية</option>
                                    <option value="SCABA">SCABA</option>
                                    <option value=" خارجي مزود  هواء"> خارجي مزود هواء</option>
                                    <option value="اخرى">اخرى</option>
                                </select></label>
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
                            <label class="block text-sm font-medium max-w-xl">المسؤول عن تنفيذ خطة الطوارئ
                                <select name="emergency_responsible" class="form-field mt-1">
                                    <option value="">اختر المسؤول</option>
                                    <?php foreach (
                                        [
                                            'Isam Ghanim Ali Abdullah Al-Asadi',
                                            'Abbas Hasan Hussain Hasan Al-Najiem',
                                            'Abbas Aliwi Obaied Basha Al-Oaidi',
                                            'Hasan Mahmoud Khudair Abbas Al-Nemawi',
                                            'Asaad Jabbar Hadi Humairi',
                                            'Wissam Jaber Najim Abd  Baki',
                                            'Akil Abbas Mohammed Abbas Al-Asadi',
                                            'Ahmed Mlouh Wajar Mansi Al- Hussain',
                                            'Hachum Soultan Sadkhan Abdullah Al-Rashid',
                                            'Hashim Obaied Kurdi Faisal ',
                                            'Ihsan Oraibi Yaqoub Hamad Al-Asadi',
                                            'Mahmoud Kamll Alwan Abd Al-Asadi'
                                        ] as $emergencyResponsible
                                    ): ?>
                                        <option value="<?= htmlspecialchars($emergencyResponsible, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($emergencyResponsible, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <fieldset class="mt-5">
                                <legend class="font-semibold mb-3">ما هي المعدات المطلوبة في حالة الطوارئ؟</legend>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <?php foreach (['رافعة ميكانيكية', 'استخدام ما يناسب من خطة العمل على المرتفعات', 'جهاز هواء SCBA ذاتي التوريد مع نقالة', 'حامل ثلاثي', 'رافعة كهربائية', 'نقالة', 'الاسعاف 07806444441', 'الاطفاء 07806444440', 'أخرى'] as $equipment): ?>
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
                                <?php foreach (['gas_device_calibrated' => 'هل تم اجراء معايرة للجهاز', 'continuous_monitoring' => 'مراقبة مستمرة'] as $field => $label): ?>
                                    <fieldset>
                                        <legend class="text-sm font-medium mb-2"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></legend>
                                        <div class="flex gap-5"><label class="flex items-center gap-1"><input type="radio" name="<?= $field ?>" value="نعم"> نعم</label><label class="flex items-center gap-1"><input type="radio" name="<?= $field ?>" value="لا"> لا</label></div>
                                    </fieldset>
                                <?php endforeach; ?>
                            </div>
                            <aside class="mt-5 rounded border-r-4 border-amber-500 bg-amber-50 p-4 text-sm leading-7 text-slate-800">
                                <p>أ) على مدير الوحدة أن يأذن رسمياً بالعمل في الأماكن المحصورة عالية الخطورة، مثل: الصوامع، صهاريج التخزين، المجاري الجوفية والحفريات أعمق من 2 م.</p>
                                <p>ب)يتم تجديد تصريح العمل في الاعمال التي تتجاوز وجبة واحده.</p>
                            </aside>
                        </section>

                        <section class="step-content bg-white border border-slate-200 rounded-md p-5 md:p-6" data-step="8">
                            <h2 class="section-title text-lg font-bold text-slate-800 mb-5">الشخص المخول بإصدار هذا التصريح</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="text-sm font-medium">اسم الشخص<input name="issuer_name" value="<?= htmlspecialchars($currentUserName, ENT_QUOTES, 'UTF-8') ?>" readonly class="form-field mt-1 bg-slate-100"></label>
                            </div>
                            <label class="flex items-start gap-2 mt-5 rounded bg-slate-50 p-4"><input type="checkbox" name="issuer_declaration" value="1" required class="mt-1 h-4 w-4 accent-teal-700"><span>أقر بأنه قد تمت المراجعة لكل المتطلبات الرئيسية الآمنة لدخول المكان المغلق.</span></label>
                        </section>

                        <section class="step-content bg-white border border-slate-200 rounded-md p-5 md:p-6" data-step="9">
                            <h2 class="section-title text-lg font-bold text-slate-800 mb-5">إسناد الرخصة</h2>
                            <label class="block text-sm font-medium max-w-xl">إسناد إلى مستخدم
                                <select name="assigned_to" id="assignedTo" class="form-field mt-1">
                                    <option value="">بدون إسناد</option>
                                </select>
                            </label>
                            <p class="mt-3 text-sm text-slate-600">عند الإصدار أو تغيير الإسناد، سيصل إشعار وبريد إلكتروني للمستخدم المحدد.</p>
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
        const controlImagesPreview = document.getElementById('controlImagesPreview');
        const controlImagesInput = document.getElementById('controlImagesInput');
        const selectedControlImages = [];
        const permitForm = document.getElementById('confinedSpaceForm');
        const permitId = Number(permitForm.dataset.permitId || 0);
        let originalPermit = null;
        const equipmentSelect = new TomSelect('[name="equipment_used[]"]', {
            persist: false,
            create: false,
            maxItems: null,
            plugins: ['remove_button'],
            placeholder: 'اختر المعدة المستخدمة...'
        });

        function addEntrant(name = '', fit = true, authorized = true, id = '') {
            const row = document.createElement('div');
            row.dataset.entrantId = id;
            row.className = 'grid grid-cols-1 lg:grid-cols-[minmax(180px,1fr)_auto_auto_auto] items-center gap-3 rounded border border-slate-200 p-3';
            row.innerHTML = `<input name="entrant_name[]" value="${escapeHtml(name)}" required class="form-field" placeholder="اسم الشخص"><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="entrant_fit[]" value="1" ${fit ? 'checked' : ''} class="h-4 w-4 accent-teal-700">لائق صحياً</label><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="entrant_authorized[]" value="1" ${authorized ? 'checked' : ''} class="h-4 w-4 accent-teal-700">مصرح له بالدخول</label><button type="button" class="remove-row text-sm text-red-700">حذف</button>`;
            entrantsList.appendChild(row);
            row.querySelector('.remove-row').addEventListener('click', () => row.remove());
        }

        function addGasRow(values = [], measurementId = null) {
            const row = document.createElement('tr');
            const now = new Date();
            const localTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
            row.innerHTML = [
                ['measurement_time', 'الوقت', 'datetime-local'],
                ['oxygen_percent', 'O₂ %', 'number'],
                ['lel_uel_percent', 'LEL/UEL %', 'number'],
                ['co_ppm', 'CO PPM', 'number'],
                ['h2s_ppm', 'H₂S PPM', 'number']
            ].map(([name, placeholder, type], index) => {
                const value = name === 'measurement_time' ? (values[index] || localTime) : (values[index] || '');
                const numericAttributes = type === 'number' ? 'step="any" inputmode="decimal"' : '';
                return `<td class="p-1 border"><input type="${type}" name="${name}[]" value="${escapeHtml(value)}" ${numericAttributes} class="form-field gas-input text-center min-w-24" data-gas="${name}" placeholder="${placeholder}"></td>`;
            }).join('') + '<td class="p-1 border"><button type="button" class="remove-row px-2 text-red-700" aria-label="حذف القراءة">حذف</button></td>';
            if (measurementId) row.dataset.measurementId = measurementId;
            gasRows.appendChild(row);
            row.querySelector('.remove-row').addEventListener('click', () => {
                row.remove();
                validateGasRows();
                updateTabStates();
            });
            row.querySelectorAll('.gas-input').forEach(input => input.addEventListener('input', validateGasRows));
            validateGasRows();
        }

        function renderControlImagePreviews() {
            controlImagesPreview.replaceChildren();
            selectedControlImages.forEach((selectedImage, index) => {
                const item = document.createElement('div');
                item.className = 'relative';
                const image = document.createElement('img');
                image.src = selectedImage.previewUrl || `../../public/${selectedImage.path}`;
                image.alt = selectedImage.file?.name || selectedImage.path || 'صورة تقييم المخاطر';
                image.className = 'h-20 w-20 rounded border object-cover';
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = '×';
                remove.setAttribute('aria-label', 'حذف الصورة');
                remove.className = 'absolute -top-2 -left-2 h-6 w-6 rounded-full bg-red-700 text-white';
                remove.addEventListener('click', () => {
                    if (selectedImage.previewUrl) URL.revokeObjectURL(selectedImage.previewUrl);
                    selectedControlImages.splice(index, 1);
                    renderControlImagePreviews();
                    updateTabStates();
                });
                item.append(image, remove);
                controlImagesPreview.appendChild(item);
            });
        }

        controlImagesInput.addEventListener('change', () => {
            const files = [...controlImagesInput.files];
            const oversized = files.find(file => file.size > 8 * 1024 * 1024);
            if (oversized) {
                Swal.fire({
                    icon: 'warning',
                    title: 'حجم الصورة كبير',
                    text: 'الحد الأعلى لحجم الصورة الواحدة 8 ميغابايت'
                });
                controlImagesInput.value = '';
                return;
            }
            files.forEach(file => selectedControlImages.push({
                file,
                previewUrl: URL.createObjectURL(file)
            }));
            controlImagesInput.value = '';
            renderControlImagePreviews();
            updateTabStates();
        });

        function gasValueIsValid(input) {
            if (input.dataset.gas === 'measurement_time') return !!input.value;
            if (input.value.trim() === '') return false;
            const value = Number(input.value);
            if (!Number.isFinite(value)) return false;
            if (input.dataset.gas === 'oxygen_percent') return value >= 19.5 && value <= 23.5;
            if (input.dataset.gas === 'lel_uel_percent') return value >= 0 && value < 10;
            if (input.dataset.gas === 'co_ppm') return value >= 0 && value < 5000;
            if (input.dataset.gas === 'h2s_ppm') return value >= 0 && value < 10;
            return false;
        }

        function validateGasRows() {
            let allValid = gasRows.querySelectorAll('tr').length > 0;
            gasRows.querySelectorAll('tr').forEach(row => {
                row.querySelectorAll('.gas-input').forEach(input => {
                    const valid = gasValueIsValid(input);
                    input.classList.toggle('gas-value-invalid', !valid && input.value.trim() !== '');
                    input.setAttribute('aria-invalid', valid ? 'false' : 'true');
                    if (!valid) allValid = false;
                });
            });
            return allValid;
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

        const totalSteps = 9;
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
            if (step === 3) return !!section.querySelector('[name="control_0"]:checked') &&
                selectedControlImages.length > 0;
            if (step === 4) {
                const names = [...entrantsList.querySelectorAll('[name="entrant_name[]"]')];
                return names.length > 0 && names.every(input => input.value.trim());
            }
            if (step === 6) return !!section.querySelector('[name="rescue_equipment_available"]').value;
            if (step === 7) return validateGasRows() && !!section.querySelector('[name="gas_device_model"]').value.trim() &&
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

        permitForm.addEventListener('input', updateTabStates);
        permitForm.addEventListener('change', updateTabStates);
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
        updateTabStates();

        async function loadAssignees(selectedId = '') {
            const select = document.getElementById('assignedTo');
            const response = await fetch(`${API_URL}?action=getAssignees`, {
                headers: {
                    'Authorization': `Bearer ${TOKEN}`
                }
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.message || 'تعذر تحميل قائمة المستخدمين');
            result.data.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name;
                select.appendChild(option);
            });
            select.value = selectedId ? String(selectedId) : '';
        }

        function setChecked(name, value) {
            const input = [...permitForm.querySelectorAll(`[name="${name}"]`)].find(item => item.value === String(value));
            if (input) input.checked = true;
        }

        function populatePermit(permit) {
            originalPermit = permit;
            ['wo', 'company_name', 'supervisor', 'maintenance_type', 'task_start_datetime', 'finishing_time', 'work_description', 'ventilation_unit_no', 'emergency_responsible', 'rescue_equipment_available', 'gas_device_model', 'gas_qualified_person'].forEach(name => {
                const field = permitForm.elements.namedItem(name);
                if (field) field.value = (permit[name] || '').replace?.(' ', 'T') && ['task_start_datetime', 'finishing_time'].includes(name) ? permit[name].replace(' ', 'T') : (permit[name] || '');
            });
            permitForm.elements.namedItem('location').value = permit.location || '';
            document.getElementById('assignedTo').value = permit.assigned_to ? String(permit.assigned_to) : '';
            permitForm.elements.namedItem('ventilation_within_limits').checked = Number(permit.ventilation_within_limits) === 1;
            permitForm.elements.namedItem('issuer_declaration').checked = Number(permit.issuer_declaration) === 1;
            const equipment = Array.isArray(permit.equipment_used) ? permit.equipment_used : [];
            equipment.forEach(name => equipmentSelect.addOption({value: name, text: name}));
            equipmentSelect.setValue(equipment, true);
            ['gas_device_calibrated', 'continuous_monitoring'].forEach(name => setChecked(name, permit[name]));
            (permit.additional_permits || []).forEach(item => {
                const row = [...permitForm.querySelectorAll('[name="permit_name_' + item.permit_name + '"]')];
                const hidden = [...permitForm.querySelectorAll('input[type="hidden"][name^="permit_name_"]')].find(input => input.value === item.permit_name);
                if (hidden) {
                    const key = hidden.name.replace('permit_name_', '');
                    permitForm.querySelector(`[name="additional_permits_selected[]"][value="${key}"]`).checked = true;
                    permitForm.elements.namedItem(`permit_no_${key}`).value = item.permit_number || '';
                }
            });
            const control = (permit.control_measures || [])[0];
            if (control) setChecked('control_0', control.status);
            selectedControlImages.splice(0, selectedControlImages.length, ...((control?.images || []).map(image => ({
                path: image.image_path
            }))));
            renderControlImagePreviews();
            entrantsList.replaceChildren();
            (permit.entrants || []).forEach(item => addEntrant(item.person_name, Number(item.medically_fit) === 1, Number(item.authorized_to_enter) === 1, item.id));
            (permit.communications || []).forEach(item => setChecked('communications[]', item.communication_method));
            (permit.rescue_equipment || []).forEach(item => setChecked('rescue_equipment[]', item.equipment_name));
            gasRows.replaceChildren();
            (permit.gas_measurements || []).forEach(item => addGasRow([String(item.measurement_time || '').replace(' ', 'T').slice(0, 16), item.oxygen_percent, item.lel_uel_percent, item.co_ppm, item.h2s_ppm], item.id));
            document.querySelector('input[name="permit_no_display"]').value = permit.permit_no;
            document.querySelector('h1').textContent = `تعديل رخصة دخول الأماكن المغلقة ${permit.permit_no}`;
            document.getElementById('submitPermit').textContent = 'حفظ التعديلات';
        }

        permitForm.addEventListener('submit', async event => {
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
            data.assigned_to = fd.get('assigned_to') || null;
            fd.getAll('additional_permits_selected[]').forEach(id => data.additional_permits.push({
                permit_name: fd.get(`permit_name_${id}`),
                permit_number: fd.get(`permit_no_${id}`)
            }));
            data.control_measures.push({
                text: 'هل تم إعداد تقييم للمخاطر قبل الشروع بالدخول؟',
                answer: fd.get('control_0')
            });
            entrantsList.querySelectorAll(':scope > div').forEach(row => data.entrants.push({
                id: row.dataset.entrantId || null,
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
                h2s_ppm: measurementColumns[4][index],
                id: gasRows.querySelectorAll('tr')[index]?.dataset.measurementId || null
            });

            const button = submitButton;
            button.disabled = true;
            try {
                const imageFormData = new FormData();
                selectedControlImages.filter(image => image.file).forEach(image => imageFormData.append('images[]', image.file));
                data.control_images = selectedControlImages.filter(image => image.path).map(image => image.path);
                if (selectedControlImages.some(image => image.file)) {
                    const imageResponse = await fetch(`${API_URL}?action=uploadControlImages`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${TOKEN}`
                        },
                        body: imageFormData
                    });
                    const imageResult = await imageResponse.json();
                    if (!imageResponse.ok || !imageResult.success) throw new Error(imageResult.message || 'تعذر رفع صور تقييم المخاطر');
                    data.control_images.push(...imageResult.data.paths);
                }
                const response = await fetch(permitId ? `${API_URL}?action=update` : API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${TOKEN}`
                    },
                    body: JSON.stringify(permitId ? {
                        ...data,
                        permit_id: permitId
                    } : data)
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'تعذر إصدار الرخصة');
                await Swal.fire({
                    icon: 'success',
                    title: permitId ? 'تم تحديث الرخصة' : 'تم إصدار الرخصة',
                    text: result.permit_no,
                    confirmButtonColor: '#0b6f76'
                });
                window.location.href = `view_confined_space_license.php?id=${result.id || permitId}`;
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

        loadAssignees().then(async () => {
            if (permitId) {
                const response = await fetch(`${API_URL}?action=show&id=${permitId}`, {
                    headers: {
                        'Authorization': `Bearer ${TOKEN}`
                    }
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message || 'تعذر تحميل الرخصة للتعديل');
                if (Number(result.data.created_by) !== Number(<?= (int)($userData['id'] ?? 0) ?>)) throw new Error('تعديل الرخصة متاح لمنشئها فقط');
                populatePermit(result.data);
            }
            updateTabStates();
        }).catch(error => Swal.fire({
            icon: 'error',
            title: 'تعذر تحميل النموذج',
            text: error.message
        }));
    </script>
</body>

</html>