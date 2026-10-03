<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/navbar.php';
require_once __DIR__ . '/../helpers/authCheck.php';
$permitId = (int)($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض رخصة دخول الأماكن المغلقة</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            .then(result => {
                if (!result.success) throw new Error(result.message || 'تعذر تحميل الرخصة');
                const p = result.data;
                let html = `<header class="bg-[#10253b] text-white rounded-md p-5"><p class="text-sm">رخصة دخول الأماكن المغلقة</p><h1 class="text-2xl font-bold mt-1">${safe(p.permit_no)}</h1></header>`;
                html += section('المعلومات الأساسية', `<dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">${info('رقم أمر العمل (WO)',p.wo)}${info('اسم طالب الرخصة',p.company_name)}${info('القسم',p.location)}${info('الموقع الدقيق',p.supervisor)}${info('نوع الصيانة',p.maintenance_type)}${info('تاريخ ووقت إصدار الرخصة',p.task_start_datetime)}${info('وقت انتهاء الرخصة',p.finishing_time)}${info('المعدة المستخدمة',Array.isArray(p.equipment_used) ? p.equipment_used.join('، ') : p.equipment_used)}${info('تاريخ الإنشاء',p.issuing_date_time)}</dl>`);
                const addPermits = p.additional_permits.length ? p.additional_permits.map(row => `<tr><td>${safe(row.permit_name)}</td><td>${safe(row.permit_number)}</td></tr>`).join('') : '<tr><td colspan="2">لا توجد تصاريح إضافية</td></tr>';
                html += section('التصاريح الإضافية المطلوبة', `<div class="overflow-x-auto"><table class="w-full text-right"><thead><tr><th class="p-2">التصريح</th><th class="p-2">رقم التصريح</th></tr></thead><tbody>${addPermits}</tbody></table></div><p class="mt-4"><b>وصف العمل:</b><br>${safe(p.work_description)}</p>`);
                html += section('إجراءات السيطرة', `<ul class="space-y-2">${p.control_measures.map((item,index) => `<li class="flex justify-between gap-4 border-b py-2"><span>${index + 1}. ${safe(item.measure_text)}</span><b>${safe(item.status)}</b></li>`).join('')}</ul>`);
                html += section('أسماء الأشخاص الذين سيدخلون إلى المكان المغلق', `<div class="overflow-x-auto"><table class="w-full text-right"><thead><tr><th class="p-2">الاسم</th><th class="p-2">لائق صحياً</th><th class="p-2">مصرح له بالدخول</th></tr></thead><tbody>${p.entrants.map(item => `<tr><td class="p-2 border-t">${safe(item.person_name)}</td><td class="p-2 border-t">${Number(item.medically_fit) ? '✓' : '—'}</td><td class="p-2 border-t">${Number(item.authorized_to_enter) ? '✓' : '—'}</td></tr>`).join('')}</tbody></table></div>`);
                html += section('التهوية والاتصالات', `<dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">${info('رقم نموذج وحدة التهوية',p.ventilation_unit_no)}${info('طريقة التهوية تحافظ على الحدود المقبولة',Number(p.ventilation_within_limits) ? 'نعم' : 'لا')}${info('وسائل الاتصالات',p.communications.map(row => row.communication_method).join('، '))}</dl>`);
                html += section('خطة الطوارئ', `<dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">${info('المسؤول عن تنفيذ خطة الطوارئ',p.emergency_responsible)}${info('المعدات المطلوبة',p.rescue_equipment.map(row => row.equipment_name).join('، '))}${info('معدات الإنقاذ متوفرة قرب موقع العمل',yesNo(p.rescue_equipment_available))}${info('تتطلب سيطرة على حركة السير',yesNo(p.traffic_control_required))}</dl>`);
                const gasRows = p.gas_measurements.map(row => `<tr>${['measurement_time','oxygen_percent','lel_uel_percent','co_ppm','h2s_ppm'].map(key => `<td class="border p-2">${safe(row[key])}</td>`).join('')}</tr>`).join('');
                html += section('قياس الغازات', `<div class="overflow-x-auto"><table class="min-w-[700px] w-full text-center text-sm"><thead class="bg-[#c9dcef]"><tr><th class="border p-2">الوقت</th><th class="border p-2">O₂ %</th><th class="border p-2">LEL/UEL %</th><th class="border p-2">CO PPM</th><th class="border p-2">H₂S PPM</th></tr></thead><tbody>${gasRows}</tbody></table></div><dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">${info('رقم وموديل الجهاز',p.gas_device_model)}${info('الشخص المؤهل للقياس',p.gas_qualified_person)}${info('معايرة الجهاز',yesNo(p.gas_device_calibrated))}${info('مراقبة مستمرة',yesNo(p.continuous_monitoring))}</dl><div class="mt-4 rounded bg-amber-50 p-4 leading-7"><p>أ) على مدير الوحدة أن يأذن رسمياً بالعمل في الأماكن المحصورة عالية الخطورة، مثل: الصوامع، صهاريج التخزين، المجاري الجوفية والحفريات أعمق من 2 م.</p><p>ب) يجب ألا تصدر تصاريح الدخول لأكثر من مناوبة واحدة. إذا تعذر إكمال مدة المهمة / الأنشطة خلال المناوبة، فيجب إصدار تصريح دخول جديد في بداية التحول التالي.</p></div>`);
                html += section('الشخص المخول بإصدار هذا التصريح', `<dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">${info('الاسم',p.issuer_name)}${info('مؤهل ومتدرب','✓')}${info('لائق صحياً','✓')}${info('التعهد','أقر بأنه قد تمت المراجعة لكل المتطلبات الرئيسية الآمنة لدخول المكان المغلق')}</dl>`);
                content.innerHTML = html;
            })
            .catch(error => {
                content.innerHTML = `<p class="bg-white p-8 text-center text-red-700">${safe(error.message)}</p>`;
            });
    </script>
</body>

</html>