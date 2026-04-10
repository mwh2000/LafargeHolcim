<?php
require_once '../../core/Database.php';
require_once '../../config/config.php';
require_once __DIR__ . '../../partials/sidebar.php';
require_once __DIR__ . '../../partials/navbar.php';
require_once '../helpers/authCheck.php';

$licenseId = $_GET['id'] ?? null;
if (!$licenseId) {
    header('Location: ../dashboard.php');
    exit;
}

$userData = json_decode($_COOKIE['user_data'] ?? '{}', true);
$userId = $userData['id'] ?? 0;
$userRole = $userData['role_id'] ?? 0;
$userName = $userData['name'] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View License | تفاصيل الرخصة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <style>
        /* Print Styles */
        @media print {
            body { background: white !important; font-size: 9pt; margin: 0; padding: 0.2in; }
            .dashboard-container { background: white !important; min-height: auto !important; padding: 0 !important; margin: 0 !important; width: 100% !important; flex: none !important; }
            .flex-1 { flex: none !important; margin: 0 !important; padding: 0 !important; width: 100% !important; overflow: visible !important; }
            main { padding: 0 !important; margin: 0 !important; width: 100% !important; }
            .sm\:ml-64 { margin-left: 0 !important; width: 100% !important; }
            .sidebar, nav, .navbar, #sidebar, .no-print, button, select, #am-approval-section, #io-review-section, #sl-review-section { display: none !important; }
            .bg-white { border: 1px solid #e5e7eb !important; shadow: none !important; box-shadow: none !important; margin-bottom: 0.25rem !important; padding: 0.5rem !important; }
            .shadow-md, .shadow-lg { box-shadow: none !important; }
            #content { display: block !important; visibility: visible !important; opacity: 1 !important; transform: none !important; }
            .print-header { display: block !important; border-bottom: 2px solid #0b6f76; margin-bottom: 0.5rem; padding-bottom: 0.25rem; text-align: center; }
            .print-header img { height: 40px; margin: 0 auto 5px; display: block; }
            .print-header h1 { font-size: 14pt; color: #0b6f76; font-weight: bold; }
            .max-w-4xl { max-width: 100% !important; width: 100% !important; margin: 0 !important; }
            h2 { font-size: 11pt !important; margin-bottom: 0.2rem !important; padding-bottom: 0.2rem !important; }
            .grid { gap: 0.25rem !important; }
            .grid-cols-1.md\:grid-cols-2 { grid-template-columns: repeat(2, 1fr) !important; }
            .space-y-6 > * + * { margin-top: 0.25rem !important; }
            #val-equipments { grid-template-cols: repeat(2, minmax(0, 1fr)) !important; gap: 0.25rem !important; }
            #val-equipments img { height: 30px !important; width: 30px !important; }
            #val-equipments div p { font-size: 8pt !important; }
            .print-only { display: block !important; }
        }
        .print-only { display: none; }
    </style>
</head>
<body class="bg-gray-50">
    <?php renderNavbar('تفاصيل رخصة عزل الطاقة'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('energy_isolation'); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="max-w-4xl mx-auto">
                    <div class="flex flex-col md:flex-row justify-between items-end md:items-center gap-4 mb-6 no-print">
                        <h1 class="text-xl md:text-2xl font-semibold text-gray-700 text-right order-1 md:order-2">تفاصيل رخصة عزل الطاقة</h1>
                        
                        <div class="flex flex-wrap items-center gap-3 order-2 md:order-1">
                            <button onclick="window.print()" class="flex items-center gap-2 bg-white border border-red-500 text-red-600 px-3 py-1.5 md:px-4 md:py-2 rounded-md hover:bg-red-50 transition text-sm md:text-base font-medium shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>تحميل PDF</span>
                            </button>

                            <div class="text-right">
                                <span id="license-status" class="px-3 py-1 rounded-full text-[10px] md:text-sm font-medium"></span>
                                <p id="status-by" class="text-[9px] md:text-[10px] text-gray-400 mt-1"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Print Header (Hidden on screen) -->
                    <div class="print-header hidden">
                        <img src="../../public/images/logo.png" alt="Logo">
                        <h1>رخصة عزل الطاقة - Energy Insulation License</h1>
                    </div>

                    <div id="loading" class="text-center py-10">
                        <p class="text-gray-500">جاري تحميل البيانات...</p>
                    </div>

                    <div id="content" class="hidden space-y-6">
                        <!-- Basic Info Card -->
                        <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-[#0b6f76]">
                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">معلومات عامة</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl">
                                <div><span class="text-gray-500">رقم الرخصة:</span> <span id="val-no" class="font-medium"></span></div>
                                <div><span class="text-gray-500">التاريخ:</span> <span id="val-date" class="font-medium"></span></div>
                                <div><span class="text-gray-500">الموقع:</span> <span id="val-location" class="font-medium"></span></div>
                                <div><span class="text-gray-500">اسم المعدة:</span> <span id="val-eq-name" class="font-medium"></span></div>
                                <div><span class="text-gray-500">القسم:</span> <span id="val-section" class="font-medium"></span></div>
                                <div><span class="text-gray-500">السبب:</span> <span id="val-reason" class="font-medium"></span></div>
                                <div><span class="text-gray-500">تصريح العمل:</span> <span id="val-permit" class="font-medium"></span></div>
                                <div><span class="text-gray-500">طالب العزل:</span> <span id="val-requester" class="font-medium font-bold text-blue-600"></span></div>
                                <div><span class="text-gray-500">مسؤول المنطقة:</span> <span id="val-am" class="font-medium"></span></div>
                                <div><span class="text-gray-500">اسم العازل:</span> <span id="val-official" class="font-medium text-green-700"></span></div>
                                <div id="am-approved-container" class="hidden"><span class="text-gray-500">تاريخ تأكيد العزل:</span> <span id="val-am-approved-at" class="font-medium text-blue-600"></span></div>
                                <div id="end-at-container" class="hidden"><span class="text-gray-500">تاريخ فك العزل:</span> <span id="val-end-at" class="font-medium text-green-600"></span></div>
                            </div>
                        </div>

                        <!-- Energy Types Card -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">أنواع الطاقة المعزولة</h2>
                            <div id="val-energy-types" class="flex flex-wrap gap-2 justify-start" dir="rtl"></div>
                        </div>

                        <!-- Equipments Card -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">اسم المعدة المراد عزلها</h2>
                            <div id="val-equipments" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-start" dir="rtl"></div>
                        </div>

                        <!-- Staff Card -->
                        <div id="staff-card" class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">طاقم العمل</h2>
                            <div id="val-staff" class="flex flex-wrap gap-2 justify-start" dir="rtl"></div>
                        </div>



                        <!-- AM Done Section -->
                        <div id="am-action-section" class="hidden bg-yellow-50 p-6 rounded-lg shadow-md border border-yellow-200">
                            <h2 class="text-lg font-bold text-yellow-800 mb-4 text-right" dir="rtl">إجراءات مسؤول المنطقة</h2>
                            <div class="space-y-4 text-right" dir="rtl">
                                <p class="text-sm text-yellow-700">الرجاء التأكد من العزل قبل الضغط على Done.</p>
                                <button id="amDoneBtn" class="w-full bg-[#0b6f76] text-white py-3 rounded-md hover:bg-[#085a60] transition font-bold text-lg">Done / تم العزل</button>
                            </div>
                        </div>

                        <!-- Requester Removal Section -->
                        <div id="requester-action-section" class="hidden bg-green-50 p-6 rounded-lg shadow-md border border-green-200">
                            <h2 class="text-lg font-bold text-green-800 mb-4 text-right" dir="rtl">فك العزل - <?= $userName ?></h2>
                            <div class="space-y-4 text-right" dir="rtl">
                                <p class="text-sm text-green-700">المصادقه على الترخيص للعمل بعد التأكد من تجربة التشغيل للمعده من الموقع.</p>
                                
                                <label class="flex items-center gap-3 justify-start cursor-pointer group" dir="rtl">
                                    <input type="checkbox" id="permitWorkCb" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                    <span class="text-sm font-medium text-gray-700 group-hover:text-green-700 transition-colors">يسمح بالعمل</span>
                                </label>

                                <button id="removeIsolationBtn" disabled class="w-full bg-green-600 text-white py-3 rounded-md hover:bg-green-700 transition font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                    فك العزل
                                </button>
                            </div>
                        </div>


                        <!-- Print Completion Confirmation (Visible in PDF when completed) -->
                        <div id="print-completion-card" class="hidden print-only bg-white p-6 rounded-lg shadow-md border-t-4 border-green-600 mt-6">
                            <div class="text-right" dir="rtl">
                                <p class="text-sm font-bold text-green-800 italic mb-2">المصادقه على الترخيص للعمل بعد التأكد من تجربة التشغيل للمعده من الموقع</p>
                                <p class="text-sm text-gray-700">تم فك العزل بواسطة: <span id="val-remover-name" class="font-bold border-b border-gray-400 pb-0.5"></span></p>
                            </div>
                        </div>

                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const LICENSE_ID = "<?= $licenseId ?>";
        const CURRENT_USER_ID = "<?= $userId ?>";
        const CURRENT_USER_ROLE = "<?= $userRole ?>";
        const CURRENT_USER_NAME = "<?= $userName ?>";
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
        const API_BASE = "../../api/requester/energy_insulation.php";

        let officerSelect, shiftLeaderSelect;

        document.addEventListener('DOMContentLoaded', async () => {
            await loadLicenseData();

            // Handle work permit checkbox
            const permitCb = document.getElementById('permitWorkCb');
            const removeBtn = document.getElementById('removeIsolationBtn');
            if (permitCb && removeBtn) {
                permitCb.addEventListener('change', (e) => {
                    removeBtn.disabled = !e.target.checked;
                });
            }
        });

        async function loadLicenseData() {
            try {
                const res = await fetch(`${API_BASE}?action=show&id=${LICENSE_ID}`, {
                    headers: { 'Authorization': `Bearer ${TOKEN}` }
                });
                const result = await res.json();

                if (result.success) {
                    const data = result.data;
                    displayData(data);
                    
                    // Show AM actions if pending and user is the assigned AM
                    if (data.status === 'pending' && data.area_manager_id == CURRENT_USER_ID) {
                        document.getElementById('am-action-section').classList.remove('hidden');
                    }

                    // Show Requester actions if active_isolation and user is creator
                    if (data.status === 'active_isolation' && data.created_by == CURRENT_USER_ID) {
                        document.getElementById('requester-action-section').classList.remove('hidden');
                    }

                    if (data.am_approved_at) {
                        document.getElementById('am-approved-container').classList.remove('hidden');
                        document.getElementById('val-am-approved-at').textContent = data.am_approved_at;
                    }

                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('content').classList.remove('hidden');
                } else {
                    Swal.fire('خطأ', 'فشل في تحميل بيانات الرخصة', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('خطأ', 'حدث خطأ في الاتصال', 'error');
            }
        }

        function displayData(data) {
            document.getElementById('val-no').textContent = data.equipment_no;
            document.getElementById('val-date').textContent = data.date;
            document.getElementById('val-location').textContent = data.exact_location;
            document.getElementById('val-eq-name').textContent = data.equipment_name;
            const sectionEl = document.getElementById('val-section');
            if (sectionEl) sectionEl.textContent = data.section_name;
            document.getElementById('val-reason').textContent = data.reason;
            document.getElementById('val-permit').textContent = data.work_permit;
            document.getElementById('val-requester').textContent = data.requester_name;
            document.getElementById('val-am').textContent = data.area_manager_name;
            document.getElementById('val-official').textContent = `${data.official_name} (${data.official_department})`;

            const ioNameEl = document.getElementById('val-io-name');
            if (ioNameEl) ioNameEl.textContent = data.isolation_officer_name || 'لم يتم التعيين بعد';

            const slNameEl = document.getElementById('val-sl-name');
            if (slNameEl) slNameEl.textContent = data.shift_leader_name || 'لم يتم التأكيد بعد';

            if (data.end_at) {
                document.getElementById('end-at-container').classList.remove('hidden');
                document.getElementById('val-end-at').textContent = data.end_at;
            }

            const statusEl = document.getElementById('license-status');
            const statusByEl = document.getElementById('status-by');
            statusEl.textContent = getStatusText(data.status);
            statusEl.className = `px-3 py-1 rounded-full text-sm font-medium ${getStatusClass(data.status)}`;

            // Display who approved/signed this status
            statusByEl.textContent = '';
            if (data.status === 'pending') {
                statusByEl.textContent = `بواسطة: ${data.requester_name || 'N/A'}`;
            } else if (data.status === 'active_isolation' || data.status === 'completed' || data.status === 'rejected') {
                statusByEl.textContent = `بواسطة: ${data.area_manager_name || 'N/A'}`;
            } else if (data.status === 'approved_by_am') {
                statusByEl.textContent = `بواسطة: ${data.area_manager_name || 'N/A'}`;
            }

            // Energy Types
            const energyContainer = document.getElementById('val-energy-types');
            data.energy_types.forEach(et => {
                const span = document.createElement('span');
                span.className = 'px-3 py-1 bg-gray-100 border rounded-full text-xs';
                span.textContent = et.name;
                energyContainer.appendChild(span);
            });

            // Equipments
            const eqContainer = document.getElementById('val-equipments');
            data.equipments.forEach(eq => {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-3 p-2 border rounded-md bg-gray-50';
                const img = eq.image 
                    ? `<img src="../../public/${eq.image}" class="w-12 h-12 object-cover rounded">` 
                    : `<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-[10px] text-gray-400">No Image</div>`;
                div.innerHTML = `
                    ${img}
                    <div>
                        <p class="text-xs font-bold">${eq.name}</p>
                        <p class="text-[10px] text-gray-500">Ref: ${eq.equipment_no}</p>
                    </div>
                `;
                eqContainer.appendChild(div);
            });

            // Staff
            const staffContainer = document.getElementById('val-staff');
            if (data.staff && data.staff.length > 0) {
                data.staff.forEach(member => {
                    const span = document.createElement('span');
                    span.className = 'px-4 py-2 bg-blue-50 border border-blue-100 rounded-md text-sm text-blue-800 font-medium';
                    span.textContent = member.name;
                    staffContainer.appendChild(span);
                });
            } else {
                staffContainer.innerHTML = '<p class="text-gray-400 italic text-sm">لا يوجد طاقم عمل مسجل</p>';
            }

            // Print Completion Section
            if (data.status === 'completed') {
                const pcCard = document.getElementById('print-completion-card');
                if (pcCard) {
                    pcCard.classList.remove('hidden');
                    document.getElementById('val-remover-name').textContent = data.requester_name;
                }
            }
        }

        function getStatusText(status) {
            const map = {
                'pending': 'بانتظار الموافقة',
                'active_isolation': 'تم العزل - Isolation Active',
                'rejected': 'مرفوضة',
                'completed': 'مكتملة - Isolation Removed'
            };
            return map[status] || status;
        }

        function getStatusClass(status) {
            const map = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'active_isolation': 'bg-blue-100 text-blue-800',
                'rejected': 'bg-red-100 text-red-800',
                'completed': 'bg-green-100 text-green-800'
            };
            return map[status] || 'bg-gray-100 text-gray-800';
        }

        // AM Done Button Logic
        document.getElementById('amDoneBtn').addEventListener('click', async () => {
            const result = await Swal.fire({
                title: 'تأكيد العزل',
                text: 'هل تم التأكد من عزل جميع الطاقات المطلوبة؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، Done',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#0b6f76'
            });

            if (result.isConfirmed) {
                submitAction('amDone', { license_id: LICENSE_ID });
            }
        });

        // Requester Removal Button Logic
        document.getElementById('removeIsolationBtn').addEventListener('click', async () => {
            const result = await Swal.fire({
                title: 'تأكيد فك العزل',
                text: 'تم فك العزل وارجاع الطاقات الى حالة التشغيل من قبل العازل',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، تأكيد فك العزل',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#059669'
            });

            if (result.isConfirmed) {
                submitAction('removeIsolation', { license_id: LICENSE_ID });
            }
        });

        async function submitAction(action, data) {
            try {
                const res = await fetch(`${API_BASE}?action=${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${TOKEN}`
                    },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    Swal.fire('نجاح', result.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('خطأ', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('خطأ', 'حدث خطأ غير متوقع', 'error');
            }
        }
    </script>
</body>
</html>
