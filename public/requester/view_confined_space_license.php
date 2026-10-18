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
                    <div id="editStaffModal" class="hidden no-print fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
                        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
                            <div class="p-4 border-b flex justify-between items-center bg-gray-50 rounded-t-lg">
                                <button type="button" onclick="closeEditStaffModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                <h3 class="text-lg font-bold text-[#0b6f76]">تعديل طاقم العمل</h3>
                            </div>
                            <div class="p-6 overflow-y-auto flex-1 bg-gray-50/50" id="edit-staff-groups-container"></div>
                            <div class="px-6 py-2 bg-gray-50/50 text-right">
                                <button type="button" id="editAddGroupBtn" class="flex items-center gap-2 text-sm text-[#0b6f76] font-medium hover:underline w-fit mr-0 ml-auto" dir="rtl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    إضافة مجموعة
                                </button>
                            </div>
                            <div class="p-4 border-t flex justify-end gap-3 bg-gray-50 rounded-b-lg">
                                <button type="button" id="saveStaffBtn" class="px-6 py-2 bg-[#0b6f76] text-white rounded-md hover:bg-[#085a60] transition font-medium">حفظ التغييرات</button>
                                <button type="button" onclick="closeEditStaffModal()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition font-medium">إلغاء</button>
                            </div>
                        </div>
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
                currentPermitData = p;
                const isCreator = Number(p.created_by) === VIEWER_ID;
                const canManagePermit = [3, 5, 7].includes(VIEWER_ROLE);
                const canManageStaff = isCreator || canManagePermit;
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
                        if ((currentPermitData.staff_groups || []).some(group => Number(group.is_done) !== 1)) return Swal.fire({
                            icon: 'error',
                            title: 'تعذر الإغلاق',
                            text: 'لا يمكن إغلاق الرخصة حتى يتم إكمال العمل لجميع المجموعات'
                        });
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
                html += section('المعلومات الأساسية', `<div class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl">${info('رقم أمر العمل (WO)',p.wo)}${info('اسم طالب الرخصة',p.company_name)}${info('القسم',p.location)}${info('الموقع الدقيق',p.supervisor)}${info('المعدة المستخدمة',Array.isArray(p.equipment_used) ? p.equipment_used.join('، ') : p.equipment_used)}${info('نوع الصيانة',p.maintenance_type)}${info('تاريخ الإصدار',p.issuing_date_time)}${info('تاريخ ووقت بدء العمل',p.task_start_datetime)}${finishingTimeValue}${info('تم الإنشاء بواسطة',p.creator_name)}</div>${p.details ? `<div class="mt-4 pt-4 border-t text-sm text-right" dir="rtl"><span class="text-gray-500 block mb-1">التفاصيل:</span><div class="p-3 bg-gray-50 rounded border text-gray-800 whitespace-pre-wrap">${safe(p.details)}</div></div>` : ''}`);
                const addPermits = p.additional_permits.length ? p.additional_permits.map(row => `<tr><td>${safe(row.permit_name)}</td><td>${safe(row.permit_number)}</td></tr>`).join('') : '<tr><td colspan="2">لا توجد تصاريح إضافية</td></tr>';
                html += section('التصاريح الإضافية المطلوبة', `<div class="overflow-x-auto" dir="rtl"><table class="w-full border-collapse text-sm text-right"><thead><tr class="bg-gray-50"><th class="p-2 border">اسم التصريح</th><th class="p-2 border">رقم التصريح</th></tr></thead><tbody>${addPermits}</tbody></table></div><div class="mt-4 pt-4 border-t text-sm text-right" dir="rtl"><span class="text-gray-500 block mb-1">وصف العمل:</span><div class="p-3 bg-gray-50 rounded border text-gray-800 whitespace-pre-wrap">${safe(p.work_description)}</div></div>`);
                html += section('إجراءات السيطرة', `<div class="space-y-2 text-sm text-right" dir="rtl">${p.control_measures.map((item,index) => `<div class="flex flex-col sm:flex-row justify-between p-3 border rounded-md bg-gray-50 gap-2"><span class="text-gray-700 flex-1">${index + 1}. ${safe(item.measure_text)}</span><span class="font-bold text-[#0b6f76]">${safe(item.status)}</span><div class="flex flex-wrap gap-2">${(item.images || []).map(image => `<a href="../../public/${safe(image.image_path)}" target="_blank" rel="noopener"><img src="../../public/${safe(image.image_path)}" alt="صورة تقييم المخاطر" class="h-12 w-12 object-cover rounded border"></a>`).join('')}</div></div>`).join('')}</div>`);
                const entrantRows = p.entrants.map(item => `<tr><td class="p-2 border"><div>${safe(item.person_name)}</div>${item.person_signature ? `<img src="../../public/${safe(item.person_signature)}" alt="توقيع ${safe(item.person_name)}" class="mt-1 h-10 max-w-24 object-contain">` : ''}</td><td class="p-2 border">${Number(item.medically_fit) ? '✓' : '—'}</td><td class="p-2 border">${Number(item.authorized_to_enter) ? '✓' : '—'}</td><td class="p-2 border"><div>${safe(item.added_by_name)}</div>${item.added_by_signature ? `<img src="../../public/${safe(item.added_by_signature)}" alt="توقيع ${safe(item.added_by_name)}" class="mt-1 h-10 max-w-24 object-contain">` : ''}</td></tr>`).join('');
                const entrantEditor = canManagePermit && isOpen ? `<form id="addEntrantForm" class="no-print mb-4 flex flex-wrap items-end gap-3 rounded border border-gray-200 bg-gray-50 p-3"><label class="min-w-56 flex-1 text-sm">اسم الشخص<input name="person_name" required class="mt-1 w-full rounded border border-gray-300 p-2"></label><label class="flex items-center gap-2 text-sm"><input name="medically_fit" type="checkbox" checked class="h-4 w-4">لائق صحياً</label><label class="flex items-center gap-2 text-sm"><input name="authorized_to_enter" type="checkbox" checked class="h-4 w-4">مصرح له بالدخول</label><button class="rounded bg-teal-800 px-4 py-2 text-white" type="submit">إضافة الشخص</button></form>` : '';
                html += section('أسماء الأشخاص الذين سيدخلون إلى المكان المغلق', `${entrantEditor}<div class="overflow-x-auto" dir="rtl"><table class="w-full border-collapse text-sm text-right"><thead><tr class="bg-gray-50"><th class="p-2 border">الاسم / التوقيع</th><th class="p-2 border">لائق صحياً</th><th class="p-2 border">مصرح له بالدخول</th><th class="p-2 border">أضيف بواسطة / التوقيع</th></tr></thead><tbody>${entrantRows}</tbody></table></div>`);
                html += `<section id="staff-card" class="permit-section bg-white p-6 rounded-lg shadow-md"><div class="flex justify-between items-center mb-4 border-b pb-2">${canManageStaff ? `<button id="editStaffBtn" type="button" class="no-print text-sm flex items-center gap-1 text-[#0b6f76] hover:text-[#085a60] font-medium transition-colors bg-[#0b6f76]/10 px-3 py-1.5 rounded-md hover:bg-[#0b6f76]/20"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>تعديل</button>` : '<span></span>'}<h2 class="text-lg font-bold text-[#0b6f76] text-right" dir="rtl">طاقم العمل</h2></div><div id="val-staff" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-right" dir="rtl"></div></section>`;
                html += section('التهوية والاتصالات', `<dl class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl">${info('رقم نموذج وحدة التهوية',p.ventilation_unit_no)}${info('طريقة التهوية تحافظ على الحدود المقبولة',Number(p.ventilation_within_limits) ? 'نعم' : 'لا')}${info('وسائل الاتصالات',p.communications.map(row => row.communication_method).join('، '))}</dl>`);
                html += section('خطة الطوارئ', `<dl class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl">${signedInfo('المسؤول عن تنفيذ خطة الطوارئ',p.emergency_responsible,p.emergency_responsible_signature)}${info('المعدات المطلوبة',p.rescue_equipment.map(row => row.equipment_name).join('، '))}${info('معدات الإنقاذ متوفرة قرب موقع العمل',yesNo(p.rescue_equipment_available))}</dl>`);
                const gasRows = p.gas_measurements.map(row => `<tr>${['measurement_time','oxygen_percent','lel_uel_percent','co_ppm','h2s_ppm'].map(key => `<td class="border p-2">${safe(row[key])}</td>`).join('')}<td class="border p-2"><div>${safe(row.added_by_name)}</div>${row.added_by_signature ? `<img src="../../public/${safe(row.added_by_signature)}" alt="توقيع ${safe(row.added_by_name)}" class="mx-auto mt-1 h-10 max-w-24 object-contain">` : '<span class="text-xs text-slate-500">لا يوجد توقيع مسجل</span>'}</td></tr>`).join('');
                html += section('قياس الغازات', `<div id="gasEntryControls" class="no-print mb-4 flex flex-col gap-3"></div><div class="overflow-x-auto" dir="rtl"><table class="min-w-[900px] w-full border-collapse text-center text-sm"><thead class="bg-gray-50"><tr><th class="border p-2">الوقت</th><th class="border p-2">الأوكسجين O₂<br>19.5% إلى 23.5%</th><th class="border p-2">LEL/UEL<br>أقل من 10%</th><th class="border p-2">CO<br>أقل من 5000 PPM</th><th class="border p-2">H₂S<br>أقل من 10 PPM</th><th class="border p-2">أضيفت بواسطة / التوقيع</th></tr></thead><tbody>${gasRows}</tbody></table></div><dl class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right mt-4" dir="rtl">${info('رقم وموديل الجهاز',p.gas_device_model)}${signedInfo('الشخص المؤهل للقياس',p.gas_qualified_person,p.gas_qualified_person_signature)}${info('معايرة الجهاز',yesNo(p.gas_device_calibrated))}${info('مراقبة مستمرة',yesNo(p.continuous_monitoring))}${info('هل مراقب الفتحة مطلوب؟',yesNo(p.hatch_watcher_required))}${p.hatch_watcher_required === 'نعم' ? info('اسم مراقب الفتحة',p.hatch_watcher_name) : ''}</dl>${canManagePermit && isOpen ? `<form id="hatchWatcherForm" class="no-print mt-4 flex flex-wrap items-end gap-3 rounded border border-gray-200 bg-gray-50 p-3 text-sm" dir="rtl"><fieldset><legend class="mb-1 font-medium">هل مراقب الفتحة مطلوب؟</legend><div class="flex gap-4"><label class="flex items-center gap-1"><input type="radio" name="hatch_watcher_required" value="نعم" ${p.hatch_watcher_required === 'نعم' ? 'checked' : ''}> نعم</label><label class="flex items-center gap-1"><input type="radio" name="hatch_watcher_required" value="لا" ${p.hatch_watcher_required === 'لا' ? 'checked' : ''}> لا</label></div></fieldset><label id="hatchWatcherNameEdit" class="min-w-56 flex-1 ${p.hatch_watcher_required === 'نعم' ? '' : 'hidden'}">اسم مراقب الفتحة<input name="hatch_watcher_name" value="${p.hatch_watcher_name ? safe(p.hatch_watcher_name) : ''}" class="mt-1 w-full rounded border border-gray-300 p-2"></label><button type="submit" class="rounded bg-teal-800 px-4 py-2 text-white">حفظ مراقب الفتحة</button></form>` : ''}<div class="mt-4 p-3 bg-gray-50 rounded border text-sm leading-7 text-right" dir="rtl"><p>أ) على مدير الوحدة أن يأذن رسمياً بالعمل في الأماكن المحصورة عالية الخطورة، مثل: الصوامع، صهاريج التخزين، المجاري الجوفية والحفريات أعمق من 2 م.</p><p>ب) يجب ألا تصدر تصاريح الدخول لأكثر من مناوبة واحدة. إذا تعذر إكمال مدة المهمة / الأنشطة خلال المناوبة، فيجب إصدار تصريح دخول جديد في بداية التحول التالي.</p></div>`);
                const issuerSignature = p.creator_signature ? `<img src="../../public/${safe(p.creator_signature)}" alt="توقيع ${safe(p.issuer_name)}" class="mt-2 h-12 max-w-40 object-contain">` : '';
                html += section('الشخص المخول بإصدار هذا التصريح', `<dl class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl"><div><span class="text-gray-500">الاسم:</span><div class="font-medium text-gray-800">${safe(p.issuer_name)}</div>${issuerSignature}</div>${info('التعهد',Number(p.issuer_declaration) ? 'أقر بأنه قد تمت المراجعة لكل المتطلبات الرئيسية الآمنة لدخول المكان المغلق' : '—')}</dl>`);
                html += section('إسناد الرخصة', `<dl class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl">${info('المسند إليه',p.assigned_to_name)}</dl>`);
                content.innerHTML = html;
                renderStaff(p, canManageStaff && isOpen && !isNotActive, canManageStaff ? (!isOpen ? 'الرخصة مغلقة، لا يمكن تحديث حالة المجموعات.' : (isNotActive ? 'الرخصة غير فعالة لأن وقت انتهائها قد مضى. عدّل وقت الانتهاء أولاً لتتمكن من تحديث حالة المجموعات.' : '')) : '');
                if (canManageStaff) document.getElementById('editStaffBtn').addEventListener('click', openEditStaffModal);
                const editFinishingTimeButton = document.getElementById('editFinishingTimeButton');
                if (editFinishingTimeButton) {
                    editFinishingTimeButton.addEventListener('click', () => {
                        document.getElementById('newFinishingTime').value = String(p.finishing_time || '').replace(' ', 'T').slice(0, 16);
                        const modal = document.getElementById('finishingTimeEditModal');
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    });
                }
                const hatchWatcherForm = document.getElementById('hatchWatcherForm');
                if (hatchWatcherForm) {
                    hatchWatcherForm.querySelectorAll('[name="hatch_watcher_required"]').forEach(input => input.addEventListener('change', () => {
                        document.getElementById('hatchWatcherNameEdit').classList.toggle('hidden', input.value !== 'نعم');
                    }));
                    hatchWatcherForm.addEventListener('submit', async event => {
                        event.preventDefault();
                        const formData = new FormData(hatchWatcherForm);
                        const response = await fetch('../../api/requester/confined_space_permit.php?action=updateHatchWatcher', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json', 'Authorization': `Bearer ${TOKEN}`},
                            body: JSON.stringify({
                                permit_id: PERMIT_ID,
                                hatch_watcher_required: formData.get('hatch_watcher_required'),
                                hatch_watcher_name: formData.get('hatch_watcher_name')
                            })
                        });
                        const saved = await response.json();
                        if (!response.ok || !saved.success) return Swal.fire({icon: 'error', title: 'تعذر الحفظ', text: saved.message});
                        window.location.reload();
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

        let currentPermitData = null;
        const STAFF_API = '../../api/requester/confined_space_permit.php';

        function showStaffLockedReason(reason) {
            Swal.fire({icon: 'info', title: 'غير متاح حالياً', text: reason});
        }

        function renderStaff(data, canToggle, lockedReason = '') {
            const staffContainer = document.getElementById('val-staff');
            staffContainer.innerHTML = '';
            if (!data.staff || !data.staff.length) {
                staffContainer.innerHTML = '<p class="text-gray-400 italic text-sm">لا يوجد طاقم عمل مسجل</p>';
                return;
            }
            const groupsMap = {};
            data.staff.forEach(member => {
                const key = member.group_id;
                if (!groupsMap[key]) groupsMap[key] = {
                    name: member.group_name || 'طاقم العمل',
                    members: [],
                    id: member.group_id,
                    is_done: Number(member.group_is_done) === 1
                };
                groupsMap[key].members.push(member.name);
            });
            Object.values(groupsMap).forEach(g => {
                const groupDiv = document.createElement('div');
                groupDiv.setAttribute('data-group-id', g.id);
                groupDiv.className = 'bg-gray-50 border border-gray-100 p-3 rounded-md print:p-1 print:mb-2 break-inside-avoid';
                if (g.is_done) groupDiv.classList.add('bg-green-50', 'border-green-300');

                const headerRow = document.createElement('div');
                headerRow.className = 'flex justify-between items-center mb-2';
                headerRow.setAttribute('dir', 'rtl');
                const gTitle = document.createElement('h4');
                gTitle.className = 'text-sm font-bold text-[#0b6f76] mb-0 print:text-[9pt]';
                gTitle.textContent = g.name;
                const leftContainer = document.createElement('div');
                if (canToggle) {
                    if (!g.is_done) leftContainer.classList.add('no-print');
                    leftContainer.innerHTML = `
                        <label class="flex items-center gap-3 cursor-pointer" dir="rtl">
                            <input type="checkbox" onchange="onGroupDoneChange(${g.id}, this)" class="no-print group-done-cb w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500" ${g.is_done ? 'checked' : ''}>
                            <span class="text-sm font-medium text-gray-700">تم اكمال العمل</span>
                        </label>`;
                } else {
                    leftContainer.innerHTML = `
                        <div class="flex items-center gap-2 ${lockedReason ? 'cursor-not-allowed opacity-60' : ''}" ${lockedReason ? `title="${safe(lockedReason)}"` : ''}>
                            <input type="checkbox" class="w-4 h-4 text-green-600 border-gray-300 rounded" onclick="return false;" ${g.is_done ? 'checked' : ''}>
                            <span class="text-sm text-gray-600">تم اكمال العمل</span>
                        </div>`;
                    if (lockedReason) leftContainer.firstElementChild.addEventListener('click', () => showStaffLockedReason(lockedReason));
                }
                headerRow.append(gTitle, leftContainer);
                groupDiv.appendChild(headerRow);

                const membersDiv = document.createElement('div');
                membersDiv.className = 'flex flex-wrap gap-2';
                g.members.forEach(name => {
                    const span = document.createElement('span');
                    span.className = 'px-3 py-1 bg-white border border-gray-100 rounded text-xs text-gray-700 font-medium print:text-[8pt] print:px-1.5 print:py-0.5';
                    span.textContent = name;
                    membersDiv.appendChild(span);
                });
                groupDiv.appendChild(membersDiv);
                staffContainer.appendChild(groupDiv);
            });
        }

        async function onGroupDoneChange(groupId, el) {
            const checked = el.checked;
            try {
                const res = await fetch(`${STAFF_API}?action=toggleGroupDone`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Authorization': `Bearer ${TOKEN}`},
                    body: JSON.stringify({permit_id: PERMIT_ID, group_id: groupId, is_done: checked ? 1 : 0})
                });
                const result = await res.json();
                if (!result.success) {
                    el.checked = !checked;
                    return Swal.fire('خطأ', result.message || 'لم يتم التحديث', 'error');
                }
                const group = (currentPermitData.staff_groups || []).find(g => Number(g.id) === Number(groupId));
                if (group) group.is_done = checked ? 1 : 0;
                const groupDiv = el.closest('[data-group-id]');
                groupDiv.classList.toggle('bg-green-50', checked);
                groupDiv.classList.toggle('border-green-300', checked);
                groupDiv.classList.toggle('bg-gray-50', !checked);
            } catch (e) {
                el.checked = !checked;
                Swal.fire('خطأ', 'حدث خطأ غير متوقع', 'error');
            }
        }

        // --- Edit Staff Logic ---
        function openEditStaffModal() {
            const container = document.getElementById('edit-staff-groups-container');
            container.innerHTML = '';
            const groups = {};
            (currentPermitData.staff || []).forEach(member => {
                if (!groups[member.group_id]) groups[member.group_id] = {name: member.group_name || '', members: []};
                groups[member.group_id].members.push(member.name);
            });
            const list = Object.values(groups);
            if (list.length) list.forEach(group => addEditGroup(group.name, group.members));
            else addEditGroup('', ['']);
            document.getElementById('editStaffModal').classList.remove('hidden');
        }

        function closeEditStaffModal() {
            document.getElementById('editStaffModal').classList.add('hidden');
        }

        function createEditStaffInput(name = '') {
            return `
                <div class="edit-staff-entry flex gap-2 mb-2">
                    <input type="text" placeholder="ادخل اسم الفرد هنا" value="${name ? safe(name) : ''}" class="flex-1 px-4 py-2 border rounded-md focus:ring-[#0b6f76] edit-staff-name-input">
                    <button type="button" class="remove-edit-staff px-3 py-2 bg-red-100 text-red-600 rounded-md hover:bg-red-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>`;
        }

        function addEditGroup(groupName = '', members = ['']) {
            const groupDiv = document.createElement('div');
            groupDiv.className = 'edit-staff-group-card bg-white border border-gray-200 p-5 rounded-lg shadow-sm relative mb-4';
            groupDiv.innerHTML = `
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-2" dir="rtl">
                    <div class="flex-1">
                        <label class="block text-sm font-bold text-[#0b6f76] mb-1">اسم المجموعة</label>
                        <input type="text" placeholder="مثال: Group A أو فريق الصيانة" value="${groupName ? safe(groupName) : ''}" class="w-full md:w-3/4 px-4 py-2 border border-gray-300 rounded-md focus:ring-[#0b6f76] edit-group-name-input">
                    </div>
                    <button type="button" class="remove-edit-group text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-full transition-colors mr-2" title="حذف المجموعة">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
                <div class="edit-staff-members-container" dir="rtl">
                    <label class="block text-sm font-medium text-gray-700 mb-3">أفراد المجموعة</label>
                    ${members.map(createEditStaffInput).join('')}
                </div>
                <button type="button" class="add-staff-to-edit-group-btn mt-4 flex items-center gap-2 text-sm text-[#0b6f76] font-bold hover:underline" dir="rtl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    إضافة فرد للمجموعة
                </button>`;
            document.getElementById('edit-staff-groups-container').appendChild(groupDiv);
            updateEditStaffRemoveButtons(groupDiv);
        }

        function updateEditStaffRemoveButtons(groupCard) {
            const entries = groupCard.querySelectorAll('.edit-staff-entry');
            entries.forEach(entry => entry.querySelector('.remove-edit-staff').classList.toggle('hidden', entries.length === 1));
        }

        document.getElementById('editAddGroupBtn').addEventListener('click', () => addEditGroup());

        document.getElementById('edit-staff-groups-container').addEventListener('click', e => {
            if (e.target.closest('.add-staff-to-edit-group-btn')) {
                const groupCard = e.target.closest('.edit-staff-group-card');
                groupCard.querySelector('.edit-staff-members-container').insertAdjacentHTML('beforeend', createEditStaffInput());
                updateEditStaffRemoveButtons(groupCard);
            }
            if (e.target.closest('.remove-edit-staff')) {
                const entry = e.target.closest('.edit-staff-entry');
                const groupCard = entry.closest('.edit-staff-group-card');
                entry.remove();
                updateEditStaffRemoveButtons(groupCard);
            }
            if (e.target.closest('.remove-edit-group')) e.target.closest('.edit-staff-group-card').remove();
        });

        document.getElementById('saveStaffBtn').addEventListener('click', async () => {
            const groups = document.querySelectorAll('.edit-staff-group-card');
            const staff_groups = [];
            let isValid = true;
            groups.forEach(groupCard => {
                const groupName = groupCard.querySelector('.edit-group-name-input').value.trim();
                const members = Array.from(groupCard.querySelectorAll('.edit-staff-name-input')).map(input => input.value.trim()).filter(val => val !== '');
                if (!groupName || members.length === 0) isValid = false;
                else staff_groups.push({group_name: groupName, members});
            });
            if (groups.length > 0 && !isValid) return Swal.fire('خطأ', 'يرجى ملء أسماء المجموعات وأفرادها.', 'error');

            const saveBtn = document.getElementById('saveStaffBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'جاري الحفظ...';
            try {
                const res = await fetch(`${STAFF_API}?action=updateStaffGroups`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Authorization': `Bearer ${TOKEN}`},
                    body: JSON.stringify({permit_id: PERMIT_ID, staff_groups})
                });
                const result = await res.json();
                if (result.success) Swal.fire('نجاح', result.message, 'success').then(() => location.reload());
                else Swal.fire('خطأ', result.message, 'error');
            } catch (e) {
                Swal.fire('خطأ', 'حدث خطأ غير متوقع', 'error');
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'حفظ التغييرات';
            }
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