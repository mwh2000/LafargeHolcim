<?php
require_once '../../core/Database.php';
require_once '../../config/config.php';
require_once __DIR__ . '../../partials/sidebar.php';
require_once __DIR__ . '../../partials/navbar.php';
require_once '../helpers/authCheck.php';

$permitId = $_GET['id'] ?? null;
if (!$permitId) {
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
    <title>تفاصيل رخصة العمل الساخن | View Hot Work Permit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @media print {
            body { background: white !important; }
            .dashboard-container { background: white !important; margin: 0 !important; padding: 0 !important; }
            .sidebar, nav, .navbar, #sidebar, .no-print, button { display: none !important; }
            main { padding: 0 !important; margin: 0 !important; width: 100% !important; }
            .sm\:ml-64 { margin-left: 0 !important; width: 100% !important; }

            /* Table Header repeating trick */
            .print-table { width: 100%; border-collapse: collapse; }
            .print-header-space { height: 100px; }
            .print-header-container { display: table-header-group; }
            
            /* Grids for space saving */
            .grid-cols-1.md\:grid-cols-2 { display: grid !important; grid-template-columns: repeat(2, 1fr) !important; gap: 0.5rem !important; }
            
            #val-control-measures, #val-performers-check {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 0.25rem !important;
            }
            .space-y-2 > * + * { margin-top: 0 !important; }

            #val-approvals {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 0.25rem !important;
            }

            /* Table compression */
            td, th { padding: 0.2rem !important; font-size: 9pt !important; }
            
            /* Print Header Styling */
            .print-header { border-bottom: 2px solid #0b6f76; margin-bottom: 0.5rem; padding-bottom: 0.25rem; text-align: center; width: 100%; }
            .print-header img { height: 40px; margin: 0 auto 5px; display: block; }
            .print-header h1 { font-size: 14pt; color: #0b6f76; font-weight: bold; }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <?php renderNavbar('تفاصيل رخصة العمل الساخن'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('hot_work'); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-4 md:p-8">
                <div class="max-w-4xl mx-auto">
                    <div class="flex flex-col md:flex-row justify-between items-end md:items-center gap-4 mb-6 no-print">
                        <h1 class="text-xl md:text-2xl font-semibold text-gray-700 text-right order-1 md:order-2">تفاصيل رخصة العمل الساخن (Hot Work Permit)</h1>
                        <button onclick="window.print()" class="flex items-center gap-2 bg-white border border-[#0b6f76] text-[#0b6f76] px-4 py-2 rounded-md hover:bg-[#0b6f76] hover:text-white transition shadow-sm order-2 md:order-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>طباعة / PDF</span>
                        </button>
                    </div>

                    <table class="print-table">
                        <thead class="print-header-container">
                            <tr>
                                <td>
                                    <!-- Print Header (Visible only in print) -->
                                    <div class="print-header hidden print:block">
                                        <img src="../../public/images/logo.png" alt="Logo" class="mx-auto h-12 mb-2">
                                        <h1 class="text-xl font-bold text-[#0b6f76] text-center pb-2 mb-4">Hot Work Permit - رخصة العمل الساخن</h1>
                                    </div>
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div id="loading" class="text-center py-10">
                                        <p class="text-gray-500">جاري تحميل البيانات...</p>
                                    </div>

                                    <div id="content" class="hidden space-y-6">
                        <!-- القسم الأول: المعلومات الأساسية -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">المعلومات الأساسية</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl">
                                <div><span class="text-gray-500">رقم الرخصة:</span> <span id="val-permit-no" class="font-medium text-gray-800"></span></div>
                                <div><span class="text-gray-500">تاريخ الإصدار:</span> <span id="val-issuing-date" class="font-medium text-gray-800"></span></div>
                                <div><span class="text-gray-500">الشركة:</span> <span id="val-company" class="font-medium text-gray-800"></span></div>
                                <div><span class="text-gray-500">الموقع:</span> <span id="val-location" class="font-medium text-gray-800"></span></div>
                                <div><span class="text-gray-500">المشرف:</span> <span id="val-supervisor" class="font-medium text-gray-800"></span></div>
                                <div><span class="text-gray-500">المعدة المستخدمة:</span> <span id="val-equipment" class="font-medium text-gray-800"></span></div>
                                <div><span class="text-gray-500">تاريخ ووقت البدء:</span> <span id="val-start" class="font-medium text-blue-600"></span></div>
                                <div><span class="text-gray-500">وقت الانتهاء المتوقع:</span> <span id="val-finish" class="font-medium text-green-600"></span></div>
                                <div><span class="text-gray-500">تم الإنشاء بواسطة:</span> <span id="val-creator" class="font-medium text-gray-800"></span></div>
                                <div><span class="text-gray-500">مسند إلى (Assigned To):</span> <span id="val-assigned" class="font-bold text-[#0b6f76]"></span></div>
                            </div>
                        </div>

                        <!-- القسم الثاني: التصاريح الإضافية -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">التصاريح الإضافية المرفقة</h2>
                            <div class="overflow-x-auto" dir="rtl">
                                <table class="w-full text-right border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="p-2 border">اسم التصريح</th>
                                            <th class="p-2 border">رقم التصريح</th>
                                            <th class="p-2 border">وصف العمل</th>
                                        </tr>
                                    </thead>
                                    <tbody id="val-additional-permits">
                                        <!-- Data will be injected here -->
                                    </tbody>
                                </table>
                            </div>
                            <p id="no-additional-permits" class="text-sm text-gray-500 hidden mt-2">لا توجد تصاريح إضافية محددة.</p>
                        </div>

                        <!-- القسم الثالث: إجراءات السيطرة -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">إجراءات السيطرة (Control Measures)</h2>
                            <div class="space-y-2 text-sm text-right" dir="rtl" id="val-control-measures">
                                <!-- Data will be injected here -->
                            </div>
                        </div>

                        <!-- القسم الرابع: منفذي الأعمال -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">منفذي الأعمال الساخنة (Performers Check)</h2>
                            <div class="space-y-2 text-sm text-right" dir="rtl" id="val-performers-check">
                                <!-- Data will be injected here -->
                            </div>
                        </div>

                        <!-- القسم الخامس: المطابقة والموافقة -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">المطابقة والموافقة (Approvals)</h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-right" dir="rtl" id="val-approvals">
                                <!-- Data will be injected here -->
                            </div>
                        </div>

                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script>
        const PERMIT_ID = "<?= $permitId ?>";
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
        const API_BASE = "../../api/requester/hot_work_permit.php";

        document.addEventListener('DOMContentLoaded', async () => {
            await loadPermitData();
        });

        async function loadPermitData() {
            try {
                const res = await fetch(`${API_BASE}?action=show&id=${PERMIT_ID}`, {
                    headers: { 'Authorization': `Bearer ${TOKEN}` }
                });
                const result = await res.json();

                if (result.success) {
                    displayData(result.data);
                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('content').classList.remove('hidden');
                } else {
                    Swal.fire('خطأ', result.message || 'فشل في تحميل بيانات الرخصة', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('خطأ', 'حدث خطأ في الاتصال بالسيرفر', 'error');
            }
        }

        function displayData(data) {
            // Basic Info
            document.getElementById('val-permit-no').textContent = data.permit_no || '-';
            document.getElementById('val-issuing-date').textContent = data.issuing_date_time || '-';
            document.getElementById('val-company').textContent = data.company_name || '-';
            document.getElementById('val-location').textContent = data.location || '-';
            document.getElementById('val-supervisor').textContent = data.supervisor || '-';
            document.getElementById('val-equipment').textContent = data.equipment_used || '-';
            document.getElementById('val-start').textContent = data.task_start_datetime ? data.task_start_datetime.replace('T', ' ') : '-';
            document.getElementById('val-finish').textContent = data.finishing_time || '-';
            document.getElementById('val-creator').textContent = data.creator_name || '-';
            document.getElementById('val-assigned').textContent = data.assigned_to_name || '-';

            // Additional Permits
            const apContainer = document.getElementById('val-additional-permits');
            if (data.additional_permits && data.additional_permits.length > 0) {
                data.additional_permits.forEach(ap => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50 transition-colors';
                    tr.innerHTML = `
                        <td class="p-2 border text-gray-700">${ap.permit_name}</td>
                        <td class="p-2 border font-medium">${ap.permit_number || '-'}</td>
                        <td class="p-2 border text-gray-600">${ap.work_description || '-'}</td>
                    `;
                    apContainer.appendChild(tr);
                });
            } else {
                document.getElementById('no-additional-permits').classList.remove('hidden');
                apContainer.parentElement.classList.add('hidden');
            }

            // Control Measures
            const cmContainer = document.getElementById('val-control-measures');
            if (data.control_measures && data.control_measures.length > 0) {
                data.control_measures.forEach((cm, index) => {
                    const div = document.createElement('div');
                    div.className = 'flex flex-col sm:flex-row justify-between p-3 border rounded-md bg-gray-50 gap-2';
                    
                    let statusColor = 'text-gray-600';
                    if (cm.status === 'نعم') statusColor = 'text-[#0b6f76] font-bold';
                    else if (cm.status === 'كلا') statusColor = 'text-red-600 font-bold';

                    div.innerHTML = `
                        <span class="text-gray-700 flex-1">${index + 1}. ${cm.measure_text}</span>
                        <span class="${statusColor} w-20 text-right">${cm.status}</span>
                    `;
                    cmContainer.appendChild(div);
                });
            } else {
                cmContainer.innerHTML = '<p class="text-gray-500">لا توجد بيانات</p>';
            }

            // Performers Check
            const pcContainer = document.getElementById('val-performers-check');
            if (data.performers_check && data.performers_check.length > 0) {
                data.performers_check.forEach((pc, index) => {
                    const div = document.createElement('div');
                    div.className = 'flex flex-col sm:flex-row justify-between p-3 border rounded-md bg-gray-50 gap-2';
                    
                    let answerColor = 'text-gray-600';
                    if (pc.answer === 'نعم') answerColor = 'text-[#0b6f76] font-bold';
                    else if (pc.answer === 'كلا') answerColor = 'text-red-600 font-bold';

                    div.innerHTML = `
                        <span class="text-gray-700 flex-1">${index + 1}. ${pc.question_text}</span>
                        <span class="${answerColor} w-20 text-right">${pc.answer}</span>
                    `;
                    pcContainer.appendChild(div);
                });
            } else {
                pcContainer.innerHTML = '<p class="text-gray-500">لا توجد بيانات</p>';
            }

            // Approvals
            const appContainer = document.getElementById('val-approvals');
            if (data.approvals && data.approvals.length > 0) {
                data.approvals.forEach(app => {
                    const div = document.createElement('div');
                    div.className = 'p-4 border rounded-md bg-gray-50';
                    
                    let isApproved = app.approval_status.includes('Approved');
                    let statusParts = app.approval_status.split(' - ');
                    let name = statusParts[0] || 'N/A';
                    
                    div.innerHTML = `
                        <div class="text-gray-500 text-xs mb-1">${app.role_name}</div>
                        <div class="font-bold text-gray-800 mb-2">${name}</div>
                        <div class="flex items-center gap-2">
                            ${isApproved 
                                ? '<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> <span class="text-green-600 text-sm font-medium">تمت الموافقة</span>' 
                                : '<svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> <span class="text-yellow-600 text-sm font-medium">قيد الانتظار / غير مكتمل</span>'}
                        </div>
                    `;
                    appContainer.appendChild(div);
                });
            } else {
                appContainer.innerHTML = '<p class="text-gray-500 col-span-2">لا توجد بيانات</p>';
            }
        }
    </script>
</body>
</html>
