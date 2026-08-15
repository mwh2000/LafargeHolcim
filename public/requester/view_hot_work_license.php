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
            body {
                background: white !important;
            }

            .dashboard-container {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .sidebar,
            nav,
            .navbar,
            #sidebar,
            .no-print,
            button {
                display: none !important;
            }

            main {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            .sm\:ml-64 {
                margin-left: 0 !important;
                width: 100% !important;
            }

            /* Hide empty sections in print */
            .bg-white {
                break-inside: avoid;
            }

            .bg-white[style*="display: none"] {
                display: none !important;
            }

            /* Table Header repeating trick */
            .print-table {
                width: 100%;
                border-collapse: collapse;
            }

            .print-header-space {
                height: 100px;
            }

            .print-header-container {
                display: table-header-group;
            }

            /* Grids for space saving */
            .grid-cols-1.md\:grid-cols-2 {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 0.5rem !important;
            }

            #val-control-measures,
            #val-performers-check {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 0.25rem !important;
            }

            .space-y-2>*+* {
                margin-top: 0 !important;
            }

            #val-approvals {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 0.25rem !important;
            }

            #safety_approval_info>.grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 0.5rem !important;
            }

            /* Table compression */
            td,
            th {
                padding: 0.2rem !important;
                font-size: 9pt !important;
            }

            /* Print Header Styling */
            .print-header {
                border-bottom: 2px solid #0b6f76;
                margin-bottom: 0.5rem;
                padding-bottom: 0.25rem;
                text-align: center;
                width: 100%;
            }

            .print-header img {
                height: 40px;
                margin: 0 auto 5px;
                display: block;
            }

            .print-header h1 {
                font-size: 14pt;
                color: #0b6f76;
                font-weight: bold;
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <?php renderNavbar('تفاصيل رخصة العمل الساخن'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('hot_work'); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-4 md:p-8 w-full">
                <div class="w-full mx-auto">
                    <div class="flex flex-col md:flex-row justify-between items-end md:items-center gap-4 mb-6 no-print">
                        <h1 class="text-xl md:text-2xl font-semibold text-gray-700 text-right order-1 md:order-2">تفاصيل رخصة العمل الساخن (Hot Work Permit)</h1>
                        <button id="print-btn" onclick="window.print()" class="hidden flex items-center gap-2 bg-white border border-[#0b6f76] text-[#0b6f76] px-4 py-2 rounded-md hover:bg-[#0b6f76] hover:text-white transition shadow-sm order-2 md:order-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>طباعة / PDF</span>
                        </button>
                    </div>

                    <table class="print-table w-full bg-transparent border-collapse">
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
                                        <!-- Critical status and actions -->
                                        <div id="critical_status_container" class="bg-white p-4 rounded-lg shadow-md hidden text-right no-print">
                                            <div class="inline-flex flex-wrap items-center justify-end gap-4">
                                                <div id="critical_status_badge" class="font-bold"></div>
                                                <div id="critical_actions" class="flex gap-2 start"></div>
                                            </div>
                                        </div>

                                        <!-- Safety review status and actions (normal permits only) -->
                                        <div id="safety_status_container" class="bg-white p-4 rounded-lg shadow-md hidden text-right no-print">
                                            <div class="inline-flex flex-wrap items-center justify-end gap-4 w-full">
                                                <div id="safety_status_badge" class="font-bold px-3 py-1 rounded-full text-sm" dir="rtl"></div>
                                                <div id="safety_actions" class="flex gap-2 start"></div>
                                            </div>
                                            <div id="safety_comment_container" class="hidden mt-3 p-3 bg-red-50 border border-red-200 rounded-md text-right" dir="rtl">
                                                <span class="text-red-700 text-sm font-medium">سبب الرفض:</span>
                                                <p id="safety_comment_text" class="text-red-800 text-sm mt-1"></p>
                                            </div>
                                        </div>
                                        <div id="safety_approval_info" class="hidden mt-4 p-4 bg-gray-50 border border-gray-200 rounded-md text-right" dir="rtl">
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                                <div>
                                                    <span class="text-gray-500">اسم السلامة الموافق:</span>
                                                    <div id="val-safety-approver-name" class="font-medium text-gray-800"></div>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">وقت الموافقة:</span>
                                                    <div id="val-safety-approval-time" class="font-medium text-gray-800"></div>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">توقيع الموافق:</span>
                                                    <div class="mt-2">
                                                        <img id="val-safety-approver-signature" src="" alt="توقيع الموافق" class="hidden h-16 object-contain p-1" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- القسم الأول: المعلومات الأساسية -->
                                        <div class="bg-white p-6 rounded-lg shadow-md">
                                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">المعلومات الأساسية</h2>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm text-right" dir="rtl">
                                                <div><span class="text-gray-500">رقم الرخصة:</span> <span id="val-permit-no" class="font-medium text-gray-800"></span></div>
                                                <div><span class="text-gray-500">تاريخ الإصدار:</span> <span id="val-issuing-date" class="font-medium text-gray-800"></span></div>
                                                <div><span class="text-gray-500">اسم طالب الرخصه:</span> <span id="val-company" class="font-medium text-gray-800"></span></div>
                                                <div><span class="text-gray-500">القسم:</span> <span id="val-location" class="font-medium text-gray-800"></span></div>
                                                <div><span class="text-gray-500">الموقع الدقيق:</span> <span id="val-supervisor" class="font-medium text-gray-800"></span></div>
                                                <div><span class="text-gray-500">المعدة المستخدمة:</span> <span id="val-equipment" class="font-medium text-gray-800"></span></div>
                                                <div><span class="text-gray-500">تاريخ اصدار الرخصه:</span> <span id="val-start" class="font-medium text-blue-600"></span></div>
                                                <div class="block">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="text-gray-500">وقت انتهاء الرخصه:</span>
                                                        <span id="val-finish" class="font-medium text-green-600"></span>
                                                        <span id="open-badge" class="hidden px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700">Open</span>
                                                        <span id="not-active-badge" class="hidden px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">Not Active</span>
                                                        <button id="edit-finish-btn" class="hidden no-print p-1 text-gray-400 hover:text-[#0b6f76] transition rounded" title="تعديل وقت الانتهاء">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <span class="text-gray-500" id="updated-by"></span>
                                                </div>
                                                <div><span class="text-gray-500">تم الإنشاء بواسطة:</span> <span id="val-creator" class="font-medium text-gray-800"></span></div>
                                                <div><span class="text-gray-500">مسند إلى (Assigned To):</span> <span id="val-assigned" class="font-bold text-[#0b6f76]"></span></div>
                                                <!-- <div><span class="text-gray-500" id="critical_manager_info">موافقة قسم سلامة:</span> <span id="critical_manager_name" class="font-bold text-[#0b6f76]"></span></div>
                                                <div><span class="text-gray-500" id="critical_supervisor_info">موافقة مدير المصنع:</span> <span id="critical_supervisor_name" class="font-bold text-[#0b6f76]"></span></div> -->
                                                <div><span class="text-gray-500">رقم أمر العمل (WO):</span> <span id="val-wo" class="font-medium text-gray-800"></span></div>
                                                <div><span class="text-gray-500">نوع الرخصة:</span> <span id="val-permit-type" class="font-medium text-gray-800"></span></div>
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
                                                        </tr>
                                                    </thead>
                                                    <tbody id="val-additional-permits">
                                                        <!-- Data will be injected here -->
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="mt-4 pt-4 border-t">
                                                <span class="text-gray-500 block mb-1 text-right" dir="rtl">وصف العمل (Work Description):</span>
                                                <div id="val-work-description" class="p-3 bg-gray-50 rounded border text-gray-800 whitespace-pre-wrap text-right" dir="rtl"></div>
                                            </div>
                                            <p id="no-additional-permits" class="text-sm text-gray-500 hidden mt-2">لا توجد تصاريح إضافية محددة.</p>
                                        </div>

                                        <!-- القسم الثالث: إجراءات السيطرة -->
                                        <div class="bg-white p-6 rounded-lg shadow-md">
                                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">إجراءات السيطرة</h2>
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

    <!-- Edit Finishing Time Modal -->
    <div id="editFinishModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center flex">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 text-right">تعديل وقت انتهاء الرخصة</h3>
            <input type="datetime-local" id="newFinishingTime" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm mb-4 focus:ring-[#0b6f76] focus:border-[#0b6f76]">
            <div class="flex gap-2 justify-end">
                <button onclick="document.getElementById('editFinishModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border rounded-md">إلغاء</button>
                <button onclick="saveFinishingTime()" class="px-4 py-2 bg-[#0b6f76] text-white text-sm rounded-md hover:bg-[#085a60]">حفظ</button>
            </div>
        </div>
    </div>

    <script>
        const PERMIT_ID = "<?= $permitId ?>";
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
        const USER_ID = <?= (int)$userId ?>;
        const USER_ROLE = <?= (int)$userRole ?>;
        const API_BASE = "../../api/requester/hot_work_permit.php";

        function setActionButtonLoading(button, isLoading, loadingText = 'جاري المعالجة...') {
            if (!button) return;
            const originalText = button.dataset.originalText || button.textContent;
            button.dataset.originalText = originalText;
            button.disabled = isLoading;
            button.classList.toggle('opacity-70', isLoading);
            button.classList.toggle('cursor-not-allowed', isLoading);
            button.classList.toggle('pointer-events-none', isLoading);
            button.textContent = isLoading ? loadingText : originalText;
        }

        async function requestJson(url, options = {}) {
            const response = await fetch(url, options);
            const text = await response.text();
            let data = null;
            try {
                data = text ? JSON.parse(text) : null;
            } catch (e) {
                data = {
                    success: false,
                    message: text || 'استجابة غير صالحة من الخادم'
                };
            }
            return {
                response,
                data
            };
        }

        document.addEventListener('DOMContentLoaded', async () => {
            await loadPermitData();
        });

        async function loadPermitData() {
            try {
                const res = await fetch(`${API_BASE}?action=show&id=${PERMIT_ID}`, {
                    headers: {
                        'Authorization': `Bearer ${TOKEN}`
                    }
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
            document.getElementById('val-equipment').textContent = Array.isArray(data.equipment_used) ? (data.equipment_used.join('، ') || '-') : (data.equipment_used || '-');
            document.getElementById('val-start').textContent = data.task_start_datetime ? data.task_start_datetime.replace('T', ' ') : '-';
            document.getElementById('val-finish').textContent = data.finishing_time ? data.finishing_time.replace('T', ' ') : '-';

            // Show "Open" or "Not Active" badge based on finishing time
            const printBtn = document.getElementById('print-btn');
            const openBadge = document.getElementById('open-badge');
            const notActiveBadge = document.getElementById('not-active-badge');

            const isClosed = !!data.done_at && (!data.finishing_time || new Date(data.done_at) <= new Date(data.finishing_time));
            const isOpen = !isClosed && (!data.finishing_time || new Date(data.finishing_time) >= new Date());
            if (isClosed) {
                openBadge.textContent = 'Close';
                openBadge.classList.remove('hidden');
                openBadge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700';
            } else if (isOpen) {
                openBadge.textContent = 'Open';
                openBadge.classList.remove('hidden');
                openBadge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700';
            } else {
                notActiveBadge.classList.remove('hidden');
            }

            if (isOpen || data.safety_status === 'approved' || isClosed) {
                printBtn.classList.remove('hidden');
            }

            if (data.finishing_time_updated_by) {
                document.getElementById('updated-by').textContent = `تم التحديث بواسطة: ${data.finishing_time_updated_by }`;
            }

            // Show edit finishing time button only for creator
            if (data.created_by == USER_ID) {
                const editFinishBtn = document.getElementById('edit-finish-btn');
                editFinishBtn.classList.remove('hidden');
                editFinishBtn.addEventListener('click', () => openEditFinishingTimeModal(data.finishing_time));
            }

            document.getElementById('val-work-description').textContent = data.work_description || '-';
            document.getElementById('val-creator').textContent = data.creator_name || '-';
            document.getElementById('val-assigned').textContent = data.assigned_to_name || '-';

            // Safety approval details
            if (data.safety_status === 'approved') {
                document.getElementById('safety_approval_info').classList.remove('hidden');
                document.getElementById('val-safety-approver-name').textContent = data.safety_reviewer_name || '-';
                document.getElementById('val-safety-approval-time').textContent = data.safety_reviewed_at ? data.safety_reviewed_at.replace('T', ' ') : '-';
                if (data.safety_reviewer_signature) {
                    const sig = document.getElementById('val-safety-approver-signature');
                    sig.src = `../../public/${data.safety_reviewer_signature}`;
                    sig.classList.remove('hidden');
                }
            }

            document.getElementById('val-permit-type').textContent = (parseInt(data.is_critical) === 1) ? 'رخصة العمل الساخن (الحرجة)' : 'رخصة العمل الساخن (عاديه)';
            if (data.WO) {
                document.getElementById('val-wo').textContent = data.WO;
            }

            // Critical status handling
            const isCritical = parseInt(data.is_critical || 0) === 1;
            if (isCritical) {
                document.getElementById('critical_status_container').classList.remove('hidden');
                const status = data.critical_status || 'pending_manager';
                let badgeText = '';
                if (status === 'pending_manager') badgeText = 'بأنتظار موافقة مدير المصنع';
                else if (status === 'pending_creator') badgeText = 'تمت الموافقة للعمل (جاهز للعمل)';
                else if (status === 'completed') badgeText = 'مكتمل';
                else badgeText = status;

                const badge = document.getElementById('critical_status_badge');
                badge.textContent = badgeText;

                const criticalActions = document.getElementById('critical_actions');
                criticalActions.innerHTML = '';

                const canAct = [3, 5, 7].includes(USER_ROLE);
                if (data.safety_status === 'approved' && status === 'pending_manager' && (USER_ID === parseInt(data.critical_manager_id || 0) || canAct)) {
                    const btn = document.createElement('button');
                    btn.className = 'px-3 py-2 bg-[#0b6f76] text-white rounded';
                    btn.textContent = 'موافقة مدير المصنع';
                    criticalActions.appendChild(btn);
                    btn.addEventListener('click', async () => {
                        setActionButtonLoading(btn, true);
                        try {
                            const {
                                data: jr
                            } = await requestJson(API_BASE + '?action=approveManager', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Authorization': `Bearer ${TOKEN}`
                                },
                                body: JSON.stringify({
                                    permit_id: PERMIT_ID
                                })
                            });
                            if (jr?.success) Swal.fire('تم', jr.message, 'success').then(() => location.reload());
                            else Swal.fire('خطأ', jr?.message || 'فشل الطلب', 'error');
                        } catch (e) {
                            Swal.fire('خطأ', 'فشل الطلب', 'error');
                        } finally {
                            setActionButtonLoading(btn, false);
                        }
                    });
                }

                if (status === 'pending_creator' && (USER_ID === parseInt(data.created_by || 0) || canAct)) {
                    const btn = document.createElement('button');
                    btn.className = 'px-3 py-2 bg-[#0b6f76] text-white rounded';
                    btn.textContent = 'إكمال خطوات الرخصة';
                    btn.addEventListener('click', () => {
                        window.location.href = `add_hot_work_license.php?id=${PERMIT_ID}`;
                    });
                    criticalActions.appendChild(btn);
                }
            }

            // Safety review status handling
            const safetyStatus = data.safety_status || 'pending';
            document.getElementById('safety_status_container').classList.remove('hidden');

            const badge = document.getElementById('safety_status_badge');
            if (safetyStatus === 'pending') {
                badge.textContent = 'بانتظار موافقة قسم السلامة' + (data.safety_reviewer_name ? ` (${data.safety_reviewer_name})` : '') + 'او shiftleader';
                badge.className = 'font-bold px-3 py-1 rounded-full text-sm bg-yellow-100 text-yellow-800';
            } else if (safetyStatus === 'approved') {
                badge.textContent = 'تمت الموافقة من قبل قسم السلامة' + (data.safety_reviewer_name ? ` (${data.safety_reviewer_name})` : '');
                badge.className = 'font-bold px-3 py-1 rounded-full text-sm bg-green-100 text-green-800';
            } else if (safetyStatus === 'rejected') {
                badge.textContent = 'مرفوضة من قبل قسم السلامة' + (data.safety_reviewer_name ? ` (${data.safety_reviewer_name})` : '');
                badge.className = 'font-bold px-3 py-1 rounded-full text-sm bg-red-100 text-red-800';
            }

            if (safetyStatus === 'rejected' && data.safety_comment) {
                document.getElementById('safety_comment_container').classList.remove('hidden');
                document.getElementById('safety_comment_text').textContent = data.safety_comment;
            }

            const safetyActions = document.getElementById('safety_actions');
            safetyActions.innerHTML = '';

            if (safetyStatus === 'pending' && USER_ID === parseInt(data.safety_reviewer_id || 0)) {
                let managerSelect = null;
                if (isCritical) {
                    managerSelect = document.createElement('select');
                    managerSelect.className = 'px-3 py-2 border rounded';
                    managerSelect.innerHTML = '<option value="">اختر مدير المصنع...</option>';
                    safetyActions.appendChild(managerSelect);

                    fetch(`${API_BASE}?action=getManagers`, {
                            headers: {
                                'Authorization': `Bearer ${TOKEN}`
                            }
                        })
                        .then(r => r.json())
                        .then(j => {
                            if (j.success) {
                                j.data.forEach(m => {
                                    const opt = document.createElement('option');
                                    opt.value = m.id;
                                    opt.textContent = m.name;
                                    managerSelect.appendChild(opt);
                                });
                            }
                        })
                        .catch(() => {});
                }

                const approveBtn = document.createElement('button');
                approveBtn.className = 'px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition';
                approveBtn.textContent = 'موافقة';
                safetyActions.appendChild(approveBtn);

                const rejectBtn = document.createElement('button');
                rejectBtn.className = 'px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition';
                rejectBtn.textContent = 'مراجعة الرخصة';
                safetyActions.appendChild(rejectBtn);

                approveBtn.addEventListener('click', async () => {
                    if (isCritical && !managerSelect?.value) {
                        Swal.fire('خطأ', 'اختر مدير المصنع قبل الموافقة', 'error');
                        return;
                    }
                    const result = await Swal.fire({
                        title: 'تأكيد الموافقة',
                        text: 'هل أنت متأكد من الموافقة على رخصة العمل الساخن؟',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'نعم، موافقة',
                        cancelButtonText: 'إلغاء',
                        confirmButtonColor: '#059669'
                    });
                    if (!result.isConfirmed) return;
                    setActionButtonLoading(approveBtn, true, 'جاري الموافقة...');
                    setActionButtonLoading(rejectBtn, true, 'جاري المعالجة...');
                    if (managerSelect) managerSelect.disabled = true;
                    try {
                        const {
                            data: jr
                        } = await requestJson(API_BASE + '?action=approveSafety', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${TOKEN}`
                            },
                            body: JSON.stringify({
                                permit_id: PERMIT_ID,
                                critical_manager_id: isCritical ? managerSelect?.value : null
                            })
                        });
                        if (jr?.success) Swal.fire('تم', jr.message, 'success').then(() => location.reload());
                        else Swal.fire('خطأ', jr?.message || 'فشل الطلب', 'error');
                    } catch (e) {
                        Swal.fire('خطأ', 'فشل الطلب', 'error');
                    } finally {
                        setActionButtonLoading(approveBtn, false);
                        setActionButtonLoading(rejectBtn, false);
                        if (managerSelect) managerSelect.disabled = false;
                    }
                });

                rejectBtn.addEventListener('click', async () => {
                    const result = await Swal.fire({
                        title: 'مراجعة الرخصة',
                        input: 'textarea',
                        inputLabel: 'سبب عدم قبول الرخصة',
                        inputPlaceholder: 'اكتب سبب الرفض هنا...',
                        inputAttributes: {
                            'dir': 'rtl'
                        },
                        showCancelButton: true,
                        confirmButtonText: 'رفض الرخصة',
                        cancelButtonText: 'إلغاء',
                        confirmButtonColor: '#dc2626',
                        inputValidator: (value) => {
                            if (!value || !value.trim()) return 'سبب الرفض مطلوب';
                        }
                    });
                    if (!result.isConfirmed) return;
                    setActionButtonLoading(approveBtn, true, 'جاري المعالجة...');
                    setActionButtonLoading(rejectBtn, true, 'جاري الرفض...');
                    if (managerSelect) managerSelect.disabled = true;
                    try {
                        const {
                            data: jr
                        } = await requestJson(API_BASE + '?action=rejectSafety', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${TOKEN}`
                            },
                            body: JSON.stringify({
                                permit_id: PERMIT_ID,
                                comment: result.value
                            })
                        });
                        if (jr?.success) Swal.fire('تم', jr.message, 'success').then(() => location.reload());
                        else Swal.fire('خطأ', jr?.message || 'فشل الطلب', 'error');
                    } catch (e) {
                        Swal.fire('خطأ', 'فشل الطلب', 'error');
                    } finally {
                        setActionButtonLoading(approveBtn, false);
                        setActionButtonLoading(rejectBtn, false);
                        if (managerSelect) managerSelect.disabled = false;
                    }
                });
            }

            if (safetyStatus === 'rejected' && USER_ID === parseInt(data.created_by || 0)) {
                const editBtn = document.createElement('button');
                editBtn.className = 'px-3 py-2 bg-[#0b6f76] text-white rounded hover:bg-[#085a60] transition';
                editBtn.textContent = 'تعديل الرخصة';
                editBtn.addEventListener('click', () => {
                    window.location.href = `add_hot_work_license.php?id=${PERMIT_ID}`;
                });
                safetyActions.appendChild(editBtn);
            }

            if (safetyStatus === 'approved' && USER_ID === parseInt(data.created_by || 0) && !data.done_at && (!data.finishing_time || new Date(data.finishing_time) >= new Date())) {
                const doneBtn = document.createElement('button');
                doneBtn.className = 'px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition';
                doneBtn.textContent = 'اغلاق الرخصة';
                doneBtn.addEventListener('click', async () => {
                    const result = await Swal.fire({
                        title: 'تأكيد الإغلاق',
                        text: 'هل تريد إغلاق هذه الرخصة الآن؟',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'نعم، إغلاق',
                        cancelButtonText: 'إلغاء',
                        confirmButtonColor: '#10b981'
                    });

                    if (!result.isConfirmed) return;

                    try {
                        const {
                            data: jr
                        } = await requestJson(API_BASE + '?action=markPermitDone', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${TOKEN}`
                            },
                            body: JSON.stringify({
                                permit_id: PERMIT_ID
                            })
                        });

                        if (jr?.success) {
                            Swal.fire('تم', jr.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('خطأ', jr?.message || 'فشل إغلاق الرخصة', 'error');
                        }
                    } catch (e) {
                        Swal.fire('خطأ', 'فشل إغلاق الرخصة', 'error');
                    }
                });
                safetyActions.appendChild(doneBtn);
            }

            // Additional Permits
            const apContainer = document.getElementById('val-additional-permits');
            const apSection = apContainer.closest('.bg-white');
            if (data.additional_permits && data.additional_permits.length > 0) {
                apSection.style.display = 'block';
                data.additional_permits.forEach(ap => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50 transition-colors';
                    tr.innerHTML = `
                        <td class="p-2 border text-gray-700">${ap.permit_name}</td>
                        <td class="p-2 border font-medium">${ap.permit_number || '-'}</td>
                    `;
                    apContainer.appendChild(tr);
                });
            } else {
                apSection.style.display = 'none';
            }

            // Control Measures
            const cmContainer = document.getElementById('val-control-measures');
            const cmSection = cmContainer.closest('.bg-white');
            if (data.control_measures && data.control_measures.length > 0) {
                cmSection.style.display = 'block';
                data.control_measures.forEach((cm, index) => {
                    const div = document.createElement('div');
                    div.className = 'flex flex-col sm:flex-row justify-between p-3 border rounded-md bg-gray-50 gap-2';

                    let answerColor = 'text-gray-600';
                    if (cm.status === 'نعم') answerColor = 'text-[#0b6f76] font-bold';
                    else if (cm.status === 'كلا') answerColor = 'text-red-600 font-bold';

                    div.innerHTML = `
                        <span class="text-gray-700 flex-1">${index + 1}. ${cm.measure_text}</span>
                        <span class="${answerColor} w-20 text-right">${cm.status}</span>
                    `;
                    cmContainer.appendChild(div);
                });
            } else {
                cmSection.style.display = 'none';
            }

            // Performers Check
            const pcContainer = document.getElementById('val-performers-check');
            const pcSection = pcContainer.closest('.bg-white');
            if (data.performers_check && data.performers_check.length > 0) {
                pcSection.style.display = 'block';
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
                pcSection.style.display = 'none';
            }

            // Approvals
            const appContainer = document.getElementById('val-approvals');
            const appSection = appContainer.closest('.bg-white');
            if (data.approvals && data.approvals.length > 0) {
                appSection.style.display = 'block';
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
                appSection.style.display = 'none';
            }
        }

        function openEditFinishingTimeModal(currentTime) {
            const input = document.getElementById('newFinishingTime');
            if (currentTime) {
                input.value = currentTime.replace(' ', 'T').substring(0, 16);
            }
            document.getElementById('editFinishModal').classList.remove('hidden');
        }

        async function saveFinishingTime() {
            const newTime = document.getElementById('newFinishingTime').value;
            if (!newTime) {
                Swal.fire('تنبيه', 'يرجى تحديد وقت الانتهاء', 'warning');
                return;
            }
            const saveBtn = document.querySelector('#editFinishModal button:last-child');
            setActionButtonLoading(saveBtn, true, 'جاري الحفظ...');
            try {
                const {
                    data: result
                } = await requestJson(`${API_BASE}?action=updateFinishingTime`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${TOKEN}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        permit_id: PERMIT_ID,
                        finishing_time: newTime
                    })
                });
                if (!result?.success) throw new Error(result?.message || 'حدث خطأ في الاتصال');
                document.getElementById('editFinishModal').classList.add('hidden');
                Swal.fire('تم', 'تم تحديث وقت الانتهاء بنجاح', 'success').then(() => loadPermitData());
            } catch (e) {
                Swal.fire('خطأ', e.message || 'حدث خطأ في الاتصال', 'error');
            } finally {
                setActionButtonLoading(saveBtn, false);
            }
        }
    </script>
</body>

</html>