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
        }
    </style>
</head>

<body class="bg-slate-50">
    <?php renderNavbar('رخصة دخول الأماكن المغلقة'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]"><?php renderSidebar('confined_space'); ?>
        <div class="flex-1 flex flex-col sm:ml-64">
            <main class="flex-1 p-4 md:p-8">
                <div class="max-w-5xl mx-auto">
                    <div class="no-print flex justify-between items-center gap-3 mb-5"><a href="../confined_space_permits.php" class="text-teal-800 underline">رجوع إلى قائمة الرخص</a><button onclick="window.print()" class="rounded border border-teal-800 bg-white px-4 py-2 text-teal-800">طباعة / PDF</button></div>
                    <div id="permitActions" class="no-print mb-4 flex flex-wrap gap-2"></div>
                    <div id="permitContent" class="space-y-4">
                        <p class="bg-white p-8 text-center text-slate-600">جاري تحميل الرخصة...</p>
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
        const section = (title, body) => `<section class="permit-section bg-white border border-slate-200 rounded-md p-5"><h2 class="border-r-4 border-teal-700 pr-3 text-lg font-bold mb-4">${title}</h2>${body}</section>`;
        const info = (label, value) => `<div><dt class="text-xs text-slate-500">${label}</dt><dd class="mt-1 font-medium">${safe(value)}</dd></div>`;
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
                const isOpen = p.status !== 'closed';
                const actions = document.getElementById('permitActions');
                if (isCreator && isOpen) {
                    actions.innerHTML = `<a class="rounded bg-teal-800 px-4 py-2 text-white" href="add_confined_space_license.php?id=${PERMIT_ID}">تعديل الرخصة</a><button id="closePermit" class="rounded border border-red-700 px-4 py-2 text-red-700">إغلاق الرخصة</button>`;
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
                if (VIEWER_ROLE === 7 && isOpen) {
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
                const statusLabel = isOpen ? 'مفتوحة' : 'مغلقة';
                let html = `<header class="flex flex-wrap items-center justify-between gap-3 bg-[#10253b] text-white rounded-md p-5"><div><p class="text-sm">رخصة دخول الأماكن المغلقة</p><h1 class="text-2xl font-bold mt-1">${safe(p.permit_no)}</h1></div><span class="rounded border border-white px-3 py-1">${statusLabel}</span></header>`;
                html += section('المعلومات الأساسية', `<dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">${info('رقم أمر العمل (WO)',p.wo)}${info('اسم طالب الرخصة',p.company_name)}${info('القسم',p.location)}${info('الموقع الدقيق',p.supervisor)}${info('نوع الصيانة',p.maintenance_type)}${info('تاريخ ووقت إصدار الرخصة',p.task_start_datetime)}${info('وقت انتهاء الرخصة',p.finishing_time)}${info('المعدة المستخدمة',Array.isArray(p.equipment_used) ? p.equipment_used.join('، ') : p.equipment_used)}${info('تاريخ الإنشاء',p.issuing_date_time)}${info('المسند إليه',p.assigned_to_name)}</dl>`);
                const addPermits = p.additional_permits.length ? p.additional_permits.map(row => `<tr><td>${safe(row.permit_name)}</td><td>${safe(row.permit_number)}</td></tr>`).join('') : '<tr><td colspan="2">لا توجد تصاريح إضافية</td></tr>';
                html += section('التصاريح الإضافية المطلوبة', `<div class="overflow-x-auto"><table class="w-full text-right"><thead><tr><th class="p-2">التصريح</th><th class="p-2">رقم التصريح</th></tr></thead><tbody>${addPermits}</tbody></table></div><p class="mt-4"><b>وصف العمل:</b><br>${safe(p.work_description)}</p>`);
                html += section('إجراءات السيطرة', `<div class="space-y-4">${p.control_measures.map((item,index) => `<div class="border-b pb-3"><div class="flex justify-between gap-4"><span>${index + 1}. ${safe(item.measure_text)}</span><b>${safe(item.status)}</b></div><div class="mt-3 flex flex-wrap gap-3">${(item.images || []).map(image => `<a href="../../public/${safe(image.image_path)}" target="_blank" rel="noopener"><img src="../../public/${safe(image.image_path)}" alt="صورة تقييم المخاطر" class="h-24 w-24 rounded border object-cover"></a>`).join('')}</div></div>`).join('')}</div>`);
                html += section('أسماء الأشخاص الذين سيدخلون إلى المكان المغلق', `<div class="overflow-x-auto"><table class="w-full text-right"><thead><tr><th class="p-2">الاسم</th><th class="p-2">لائق صحياً</th><th class="p-2">مصرح له بالدخول</th></tr></thead><tbody>${p.entrants.map(item => `<tr><td class="p-2 border-t">${safe(item.person_name)}</td><td class="p-2 border-t">${Number(item.medically_fit) ? '✓' : '—'}</td><td class="p-2 border-t">${Number(item.authorized_to_enter) ? '✓' : '—'}</td></tr>`).join('')}</tbody></table></div>`);
                html += section('التهوية والاتصالات', `<dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">${info('رقم نموذج وحدة التهوية',p.ventilation_unit_no)}${info('طريقة التهوية تحافظ على الحدود المقبولة',Number(p.ventilation_within_limits) ? 'نعم' : 'لا')}${info('وسائل الاتصالات',p.communications.map(row => row.communication_method).join('، '))}</dl>`);
                html += section('خطة الطوارئ', `<dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">${info('المسؤول عن تنفيذ خطة الطوارئ',p.emergency_responsible)}${info('المعدات المطلوبة',p.rescue_equipment.map(row => row.equipment_name).join('، '))}${info('معدات الإنقاذ متوفرة قرب موقع العمل',yesNo(p.rescue_equipment_available))}</dl>`);
                const gasRows = p.gas_measurements.map(row => `<tr>${['measurement_time','oxygen_percent','lel_uel_percent','co_ppm','h2s_ppm'].map(key => `<td class="border p-2">${safe(row[key])}</td>`).join('')}<td class="border p-2"><div>${safe(row.added_by_name)}</div>${row.added_by_signature ? `<img src="../../public/${safe(row.added_by_signature)}" alt="توقيع ${safe(row.added_by_name)}" class="mx-auto mt-1 h-10 max-w-24 object-contain">` : '<span class="text-xs text-slate-500">لا يوجد توقيع مسجل</span>'}</td></tr>`).join('');
                html += section('قياس الغازات', `<div class="overflow-x-auto"><table class="min-w-[900px] w-full text-center text-sm"><thead class="bg-[#c9dcef]"><tr><th class="border p-2">الوقت</th><th class="border p-2">الأوكسجين O₂<br>19.5% إلى 23.5%</th><th class="border p-2">LEL/UEL<br>أقل من 10%</th><th class="border p-2">CO<br>أقل من 5000 PPM</th><th class="border p-2">H₂S<br>أقل من 10 PPM</th><th class="border p-2">أضيفت بواسطة / التوقيع</th></tr></thead><tbody>${gasRows}</tbody></table></div><dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">${info('رقم وموديل الجهاز',p.gas_device_model)}${info('الشخص المؤهل للقياس',p.gas_qualified_person)}${info('معايرة الجهاز',yesNo(p.gas_device_calibrated))}${info('مراقبة مستمرة',yesNo(p.continuous_monitoring))}</dl><div class="mt-4 rounded bg-amber-50 p-4 leading-7"><p>أ) على مدير الوحدة أن يأذن رسمياً بالعمل في الأماكن المحصورة عالية الخطورة، مثل: الصوامع، صهاريج التخزين، المجاري الجوفية والحفريات أعمق من 2 م.</p><p>ب) يجب ألا تصدر تصاريح الدخول لأكثر من مناوبة واحدة. إذا تعذر إكمال مدة المهمة / الأنشطة خلال المناوبة، فيجب إصدار تصريح دخول جديد في بداية التحول التالي.</p></div>`);
                html += section('الشخص المخول بإصدار هذا التصريح', `<dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">${info('الاسم',p.issuer_name)}${info('التعهد',Number(p.issuer_declaration) ? 'أقر بأنه قد تمت المراجعة لكل المتطلبات الرئيسية الآمنة لدخول المكان المغلق' : '—')}</dl>`);
                content.innerHTML = html;
            })
            .catch(error => {
                content.innerHTML = `<p class="bg-white p-8 text-center text-red-700">${safe(error.message)}</p>`;
            });
    </script>
</body>

</html>