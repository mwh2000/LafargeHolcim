<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/navbar.php';
require_once __DIR__ . '/../helpers/authCheck.php';
$permitId = (int)($_GET['id'] ?? 0);
$viewerData = json_decode($_COOKIE['user_data'] ?? '{}', true);
$viewerId = (int)($viewerData['id'] ?? 0);
$viewerRole = (int)($viewerData['role_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض رخصة دخول الأماكن المغلقة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
        }

        @media print {

            .no-print,
            #sidebar,
            nav,
            .navbar {
                display: none !important
            }

            main {
                padding: 0 !important;
                margin: 0 !important
            }

            .sm\:ml-64 {
                margin: 0 !important
            }

            .permit-section {
                break-inside: avoid
            }

            .print-header {
                display: block !important;
                border-bottom: 2px solid #0b6f76;
                margin-bottom: .5rem;
                padding-bottom: .25rem;
                text-align: center;
            }

            .print-header img {
                height: 40px;
                margin: 0 auto 5px;
            }

            @media print {
                #finishingTimeEditModal {
                    display: none !important;
                }
            }
        }
    </style>
</head>

<body class="bg-slate-50">
    <?php renderNavbar('رخصة دخول الأماكن المغلقة'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]"><?php renderSidebar('confined_space'); ?>
        <div class="flex-1 flex flex-col sm:ml-64">
            <main class="flex-1 p-4 md:p-8">
                <div class="w-full mx-auto">
                    <div class="no-print flex flex-col md:flex-row justify-between items-end md:items-center gap-4 mb-6">
                        <h1 class="text-xl md:text-2xl font-semibold text-gray-700 text-right">تفاصيل رخصة دخول الأماكن المغلقة</h1>
                        <div class="flex items-center gap-2">
                            <a href="../confined_space_permits.php" class="rounded border border-[#0b6f76] bg-white px-4 py-2 text-[#0b6f76]">رجوع إلى قائمة الرخص</a>
                            <button id="printPermitButton" onclick="window.print()" class="hidden rounded border border-[#0b6f76] bg-white px-4 py-2 text-[#0b6f76]">طباعة / PDF</button>
                        </div>
                    </div>
                    <div class="print-header hidden print:block">
                        <img src="../../public/images/logo.png" alt="Logo" class="mx-auto h-10 mb-2">
                        <h1 class="text-xl font-bold text-[#0b6f76] pb-2">رخصة دخول الأماكن المغلقة</h1>
                    </div>
                    <div id="permitActions" class="no-print flex flex-wrap gap-2 mb-4"></div>
                    <div id="permitContent" class="space-y-6">
                        <p class="bg-white p-8 text-center text-slate-600">جاري تحميل الرخصة...</p>
                    </div>
                    <div id="finishingTimeEditModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 p-4 no-print">
                        <form id="finishingTimeEditForm" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                            <h2 class="mb-4 text-lg font-bold text-gray-800">تعديل وقت انتهاء الرخصة</h2>
                            <label class="block text-sm font-medium text-gray-700">تاريخ ووقت الانتهاء الجديد
                                <input id="newFinishingTime" type="datetime-local" required class="mt-2 w-full rounded border border-gray-300 p-2">
                            </label>
                            <div class="mt-5 flex justify-end gap-2">
                                <button type="button" id="cancelFinishingTimeEdit" class="rounded border border-gray-300 px-4 py-2 text-gray-700">إلغاء</button>
                                <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-white">حفظ</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        const TOKEN = <?= json_encode($_COOKIE['token'] ?? '') ?>;
        const PERMIT_ID = <?= $permitId ?>;
        const VIEWER_ID = <?= $viewerId ?>;
        const VIEWER_ROLE = <?= $viewerRole ?>;
        const API_URL = `../../api/requester/confined_space_permit.php?action=show&id=${PERMIT_ID}`;
        const content = document.getElementById('permitContent');
        const safe = value => String(value ?? '—').replace(/[&<>"']/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        } [c]));
        const section = (title, body) => `<section class="permit-section bg-white p-6 rounded-lg shadow-md"><h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">${title}</h2>${body}</section>`;
        const info = (label, value) => `<div><span class="text-gray-500">${label}:</span> <span class="font-medium text-gray-800">${safe(value)}</span></div>`;
        const signedInfo = (label, value, signature) => `<div><span class="text-gray-500">${label}:</span> <span class="font-medium text-gray-800">${safe(value)}</span>${signature ? `<img src="../../public/${safe(signature)}" alt="توقيع ${safe(value)}" class="mt-1 h-10 max-w-24 object-contain">` : ''}</div>`;
        const yesNo = value => value === 'نعم' ? 'نعم' : value === 'لا' ? 'لا' : '—';

        fetch(API_URL, {
                headers: {
                    'Authorization': `Bearer ${TOKEN}`
                }
            })
            .then(response => response.json())
            .then(async result => {
                if (!result.success) throw new Error(result.message || 'تعذر تحميل الرخصة');
                const p = result.data;
                const isCreator = Number(p.created_by) === VIEWER_ID;
                const canManagePermit = [3, 5, 7].includes(VIEWER_ROLE);
                const isOpen = p.status !== 'closed';
                const isNotActive = isOpen && p.finishing_time && new Date(p.finishing_time.replace(' ', 'T')) < new Date();
                document.getElementById('printPermitButton').classList.toggle('hidden', isOpen);
                const actions = document.getElementById('permitActions');
                if (isCreator && isOpen) {
                    actions.innerHTML = `${!isNotActive ? `<a class="rounded bg-teal-800 px-4 py-2 text-white" href="add_confined_space_license.php?id=${PERMIT_ID}">تعديل الرخصة</a>` : ''}<button id="closePermit" class="rounded border border-red-700 px-4 py-2 text-red-700">إغلاق الرخصة</button>`;
                    document.getElementById('closePermit').addEventListener('click', async () => {
                        const confirm = await Swal.fire({
                            icon: 'warning',
                            title: 'إغلاق الرخصة؟',
                            text: 'لن تتمكن من تعديلها أو إضافة قراءات جديدة بعد الإغلاق.',
                            showCancelButton: true,
                            confirmButtonText: 'إغلاق',
                            cancelButtonText: 'إلغاء'
                        });
                        if (!confirm.isConfirmed) return;
                        const response = await fetch('../../api/requester/confined_space_permit.php?action=close', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${TOKEN}`
                            },
                            body: JSON.stringify({
                                permit_id: PERMIT_ID
                            })
                        });
                        const closeResult = await response.json();
                        if (!response.ok || !closeResult.success) return Swal.fire({
                            icon: 'error',
                            title: 'تعذر الإغلاق',
                            text: closeResult.message
                        });
                        window.location.reload();
                    });
                }
                if (canManagePermit && isOpen) {
                    actions.insertAdjacentHTML('beforeend', '<button id="showGasEntry" class="rounded border border-teal-800 px-4 py-2 text-teal-800">إضافة قراءة غازات</button>');
                    actions.insertAdjacentHTML('beforeend', `<form id="addGasForm" class="hidden w-full grid grid-cols-1 sm:grid-cols-5 gap-2 rounded border bg-white p-3"><input name="measurement_time" type="datetime-local" required class="rounded border p-2"><input name="oxygen_percent" type="number" step="any" required placeholder="O₂ %" class="rounded border p-2"><input name="lel_uel_percent" type="number" step="any" required placeholder="LEL/UEL %" class="rounded border p-2"><input name="co_ppm" type="number" step="any" required placeholder="CO PPM" class="rounded border p-2"><input name="h2s_ppm" type="number" step="any" required placeholder="H₂S PPM" class="rounded border p-2"><div id="gasSigner" class="sm:col-span-5 text-sm"></div><button class="rounded bg-teal-800 p-2 text-white sm:col-span-5" type="submit">حفظ القراءة</button></form>`);
                    document.getElementById('showGasEntry').addEventListener('click', () => document.getElementById('addGasForm').classList.toggle('hidden'));
                    const newTime = document.querySelector('#addGasForm [name="measurement_time"]');
                    newTime.value = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16);
                    const signerResponse = await fetch('../../api/requester/confined_space_permit.php?action=getGasSigner', {
                        headers: {
                            'Authorization': `Bearer ${TOKEN}`
                        }
                    });
                    const signerResult = await signerResponse.json();
                    if (!signerResponse.ok || !signerResult.success) throw new Error(signerResult.message || 'تعذر تحميل بيانات الموقّع');
                    const gasSigner = signerResult.data;
                    const signerContainer = document.getElementById('gasSigner');
                    signerContainer.textContent = `مضاف القراءة: ${gasSigner.name}`;
                    if (gasSigner.signature) {
                        signerContainer.insertAdjacentHTML('beforeend', `<img src="../../public/${safe(gasSigner.signature)}" alt="توقيع ${safe(gasSigner.name)}" class="mt-2 h-12 max-w-40 object-contain">`);
                    } else {
                        signerContainer.insertAdjacentHTML('beforeend', '<p class="mt-2 text-slate-500">لا يوجد توقيع محفوظ لهذا المستخدم.</p>');
                    }
                    document.getElementById('addGasForm').addEventListener('submit', async event => {
                        event.preventDefault();
                        const values = Object.fromEntries(new FormData(event.currentTarget));
                        const response = await fetch('../../api/requester/confined_space_permit.php?action=addGasMeasurement', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${TOKEN}`
                            },
                            body: JSON.stringify({
                                permit_id: PERMIT_ID,
                                measurement: values
                            })
                        });
                        const saved = await response.json();
                        if (!response.ok || !saved.success) return Swal.fire({
                            icon: 'error',
                            title: 'تعذرت إضافة القراءة',
                            text: saved.message
                        });
                        window.location.reload();
                    });
                }
                const statusLabel = !isOpen ? 'مغلقة' : (isNotActive ? 'غير فعالة' : 'مفتوحة');
                const statusClass = !isOpen ? 'bg-green-100 text-green-700' : (isNotActive ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700');
                let html = `<div class="flex flex-wrap items-center justify-between gap-3 mb-4 border-b border-gray-200 pb-3"><h2 class="text-lg font-bold text-[#0b6f76]">${safe(p.permit_no)}</h2><span class="px-2 py-1 rounded-full text-xs font-bold ${statusClass}">${statusLabel}</span></div>`;
                const finishingTimeEdit = canManagePermit && isNotActive ? `<button type="button" id="editFinishingTimeButton" class="no-print mr-2 inline-flex items-center text-teal-800" title="تعديل تاريخ الانتهاء" aria-label="تعديل تاريخ الانتهاء"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002-2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>` : '';
                const finishingTimeMeta = p.finishing_time_updated_at ? `<small class="mt-1 block text-xs text-gray-500">عُدّل بواسطة ${safe(p.finishing_time_updated_by_name)} بتاريخ ${safe(p.finishing_time_updated_at)}</small>` : '';
                const finishingHistory = (p.finishing_time_history || []).map(item => `<div class="text-xs text-gray-500">${safe(item.previous_finishing_time)} ← ${safe(item.new_finishing_time)} | ${safe(item.changed_by_name)} | ${safe(item.changed_at)}</div>`).join('');
                const finishingTimeClass = isNotActive ? 'font-bold text-red-700' : 'font-medium text-gray-800';
                const finishingTimeValue = `<div><span class="text-gray-500">وقت انتهاء الرخصة:</span><span class="${finishingTimeClass}"> ${safe(p.finishing_time)}</span>${finishingTimeEdit}${finishingTimeMeta}${finishingHistory ? `<div class="mt-1 space-y-1">${finishingHistory}</div>` : ''}</div>`;
                html += section('المعلومات الأساسية', `<div class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl">${info('رقم أمر العمل (WO)',p.wo)}${info('اسم طالب الرخصة',p.company_name)}${info('القسم',p.location)}${info('الموقع الدقيق',p.supervisor)}${info('المعدة المستخدمة',Array.isArray(p.equipment_used) ? p.equipment_used.join('، ') : p.equipment_used)}${info('نوع الصيانة',p.maintenance_type)}${info('تاريخ الإصدار',p.issuing_date_time)}${info('تاريخ ووقت بدء العمل',p.task_start_datetime)}${finishingTimeValue}${info('تم الإنشاء بواسطة',p.creator_name)}</div>`);
                const addPermits = p.additional_permits.length ? p.additional_permits.map(row => `<tr><td>${safe(row.permit_name)}</td><td>${safe(row.permit_number)}</td></tr>`).join('') : '<tr><td colspan="2">لا توجد تصاريح إضافية</td></tr>';
                html += section('التصاريح الإضافية المطلوبة', `<div class="overflow-x-auto" dir="rtl"><table class="w-full border-collapse text-sm text-right"><thead><tr class="bg-gray-50"><th class="p-2 border">اسم التصريح</th><th class="p-2 border">رقم التصريح</th></tr></thead><tbody>${addPermits}</tbody></table></div><div class="mt-4 pt-4 border-t text-sm text-right" dir="rtl"><span class="text-gray-500 block mb-1">وصف العمل:</span><div class="p-3 bg-gray-50 rounded border text-gray-800 whitespace-pre-wrap">${safe(p.work_description)}</div></div>`);
                html += section('إجراءات السيطرة', `<div class="space-y-2 text-sm text-right" dir="rtl">${p.control_measures.map((item,index) => `<div class="flex flex-col sm:flex-row justify-between p-3 border rounded-md bg-gray-50 gap-2"><span class="text-gray-700 flex-1">${index + 1}. ${safe(item.measure_text)}</span><span class="font-bold text-[#0b6f76]">${safe(item.status)}</span><div class="flex flex-wrap gap-2">${(item.images || []).map(image => `<a href="../../public/${safe(image.image_path)}" target="_blank" rel="noopener"><img src="../../public/${safe(image.image_path)}" alt="صورة تقييم المخاطر" class="h-12 w-12 object-cover rounded border"></a>`).join('')}</div></div>`).join('')}</div>`);
                const entrantRows = p.entrants.map(item => `<tr><td class="p-2 border"><div>${safe(item.person_name)}</div>${item.person_signature ? `<img src="../../public/${safe(item.person_signature)}" alt="توقيع ${safe(item.person_name)}" class="mt-1 h-10 max-w-24 object-contain">` : ''}</td><td class="p-2 border">${Number(item.medically_fit) ? '✓' : '—'}</td><td class="p-2 border">${Number(item.authorized_to_enter) ? '✓' : '—'}</td><td class="p-2 border"><div>${safe(item.added_by_name)}</div>${item.added_by_signature ? `<img src="../../public/${safe(item.added_by_signature)}" alt="توقيع ${safe(item.added_by_name)}" class="mt-1 h-10 max-w-24 object-contain">` : ''}</td></tr>`).join('');
                const entrantEditor = canManagePermit && isOpen ? `<form id="addEntrantForm" class="no-print mb-4 flex flex-wrap items-end gap-3 rounded border border-gray-200 bg-gray-50 p-3"><label class="min-w-56 flex-1 text-sm">اسم الشخص<input name="person_name" required class="mt-1 w-full rounded border border-gray-300 p-2"></label><label class="flex items-center gap-2 text-sm"><input name="medically_fit" type="checkbox" checked class="h-4 w-4">لائق صحياً</label><label class="flex items-center gap-2 text-sm"><input name="authorized_to_enter" type="checkbox" checked class="h-4 w-4">مصرح له بالدخول</label><button class="rounded bg-teal-800 px-4 py-2 text-white" type="submit">إضافة الشخص</button></form>` : '';
                html += section('أسماء الأشخاص الذين سيدخلون إلى المكان المغلق', `${entrantEditor}<div class="overflow-x-auto" dir="rtl"><table class="w-full border-collapse text-sm text-right"><thead><tr class="bg-gray-50"><th class="p-2 border">الاسم / التوقيع</th><th class="p-2 border">لائق صحياً</th><th class="p-2 border">مصرح له بالدخول</th><th class="p-2 border">أضيف بواسطة / التوقيع</th></tr></thead><tbody>${entrantRows}</tbody></table></div>`);
                html += section('التهوية والاتصالات', `<dl class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl">${info('رقم نموذج وحدة التهوية',p.ventilation_unit_no)}${info('طريقة التهوية تحافظ على الحدود المقبولة',Number(p.ventilation_within_limits) ? 'نعم' : 'لا')}${info('وسائل الاتصالات',p.communications.map(row => row.communication_method).join('، '))}</dl>`);
                html += section('خطة الطوارئ', `<dl class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl">${signedInfo('المسؤول عن تنفيذ خطة الطوارئ',p.emergency_responsible,p.emergency_responsible_signature)}${info('المعدات المطلوبة',p.rescue_equipment.map(row => row.equipment_name).join('، '))}${info('معدات الإنقاذ متوفرة قرب موقع العمل',yesNo(p.rescue_equipment_available))}</dl>`);
                const gasRows = p.gas_measurements.map(row => `<tr>${['measurement_time','oxygen_percent','lel_uel_percent','co_ppm','h2s_ppm'].map(key => `<td class="border p-2">${safe(row[key])}</td>`).join('')}<td class="border p-2"><div>${safe(row.added_by_name)}</div>${row.added_by_signature ? `<img src="../../public/${safe(row.added_by_signature)}" alt="توقيع ${safe(row.added_by_name)}" class="mx-auto mt-1 h-10 max-w-24 object-contain">` : '<span class="text-xs text-slate-500">لا يوجد توقيع مسجل</span>'}</td></tr>`).join('');
                html += section('قياس الغازات', `<div id="gasEntryControls" class="no-print mb-4 flex flex-col gap-3"></div><div class="overflow-x-auto" dir="rtl"><table class="min-w-[900px] w-full border-collapse text-center text-sm"><thead class="bg-gray-50"><tr><th class="border p-2">الوقت</th><th class="border p-2">الأوكسجين O₂<br>19.5% إلى 23.5%</th><th class="border p-2">LEL/UEL<br>أقل من 10%</th><th class="border p-2">CO<br>أقل من 5000 PPM</th><th class="border p-2">H₂S<br>أقل من 10 PPM</th><th class="border p-2">أضيفت بواسطة / التوقيع</th></tr></thead><tbody>${gasRows}</tbody></table></div><dl class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right mt-4" dir="rtl">${info('رقم وموديل الجهاز',p.gas_device_model)}${signedInfo('الشخص المؤهل للقياس',p.gas_qualified_person,p.gas_qualified_person_signature)}${info('معايرة الجهاز',yesNo(p.gas_device_calibrated))}${info('مراقبة مستمرة',yesNo(p.continuous_monitoring))}</dl><div class="mt-4 p-3 bg-gray-50 rounded border text-sm leading-7 text-right" dir="rtl"><p>أ) على مدير الوحدة أن يأذن رسمياً بالعمل في الأماكن المحصورة عالية الخطورة، مثل: الصوامع، صهاريج التخزين، المجاري الجوفية والحفريات أعمق من 2 م.</p><p>ب) يجب ألا تصدر تصاريح الدخول لأكثر من مناوبة واحدة. إذا تعذر إكمال مدة المهمة / الأنشطة خلال المناوبة، فيجب إصدار تصريح دخول جديد في بداية التحول التالي.</p></div>`);
                const issuerSignature = p.creator_signature ? `<img src="../../public/${safe(p.creator_signature)}" alt="توقيع ${safe(p.issuer_name)}" class="mt-2 h-12 max-w-40 object-contain">` : '';
                html += section('الشخص المخول بإصدار هذا التصريح', `<dl class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl"><div><span class="text-gray-500">الاسم:</span><div class="font-medium text-gray-800">${safe(p.issuer_name)}</div>${issuerSignature}</div>${info('التعهد',Number(p.issuer_declaration) ? 'أقر بأنه قد تمت المراجعة لكل المتطلبات الرئيسية الآمنة لدخول المكان المغلق' : '—')}</dl>`);
                html += section('إسناد الرخصة', `<dl class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl">${info('المسند إليه',p.assigned_to_name)}</dl>`);
                content.innerHTML = html;
                const editFinishingTimeButton = document.getElementById('editFinishingTimeButton');
                if (editFinishingTimeButton) {
                    editFinishingTimeButton.addEventListener('click', () => {
                        document.getElementById('newFinishingTime').value = String(p.finishing_time || '').replace(' ', 'T').slice(0, 16);
                        const modal = document.getElementById('finishingTimeEditModal');
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    });
                }
                const addEntrantForm = document.getElementById('addEntrantForm');
                if (addEntrantForm) {
                    addEntrantForm.addEventListener('submit', async event => {
                        event.preventDefault();
                        const formData = new FormData(addEntrantForm);
                        const response = await fetch('../../api/requester/confined_space_permit.php?action=addEntrant', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${TOKEN}`
                            },
                            body: JSON.stringify({
                                permit_id: PERMIT_ID,
                                person_name: formData.get('person_name'),
                                medically_fit: formData.has('medically_fit'),
                                authorized_to_enter: formData.has('authorized_to_enter')
                            })
                        });
                        const added = await response.json();
                        if (!response.ok || !added.success) return Swal.fire({
                            icon: 'error',
                            title: 'تعذرت إضافة الشخص',
                            text: added.message
                        });
                        window.location.reload();
                    });
                }
                if (canManagePermit && isOpen) {
                    const gasEntryControls = document.getElementById('gasEntryControls');
                    gasEntryControls.append(document.getElementById('showGasEntry'), document.getElementById('addGasForm'));
                }
            })
            .catch(error => {
                content.innerHTML = `<p class="bg-white p-8 text-center text-red-700">${safe(error.message)}</p>`;
            });

        document.getElementById('cancelFinishingTimeEdit').addEventListener('click', () => {
            const modal = document.getElementById('finishingTimeEditModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });

        document.getElementById('finishingTimeEditForm').addEventListener('submit', async event => {
            event.preventDefault();
            const finishingTime = document.getElementById('newFinishingTime').value;
            if (!finishingTime || new Date(finishingTime) <= new Date()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'وقت غير صالح',
                    text: 'اختر وقت انتهاء في المستقبل'
                });
                return;
            }
            const submitButton = event.currentTarget.querySelector('[type="submit"]');
            submitButton.disabled = true;
            try {
                const response = await fetch('../../api/requester/confined_space_permit.php?action=updateFinishingTime', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${TOKEN}`
                    },
                    body: JSON.stringify({
                        permit_id: PERMIT_ID,
                        finishing_time: finishingTime
                    })
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'تعذر تحديث وقت الانتهاء');
                window.location.reload();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'تعذر التحديث',
                    text: error.message
                });
                submitButton.disabled = false;
            }
        });
    </script>
</body>

</html>