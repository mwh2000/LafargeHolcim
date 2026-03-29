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
</head>
<body class="bg-gray-50">
    <?php renderNavbar('تفاصيل رخصة عزل الطاقة'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('energy_isolation'); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="max-w-4xl mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <span id="license-status" class="px-3 py-1 rounded-full text-sm font-medium"></span>
                        <h1 class="text-2xl font-semibold text-gray-700">تفاصيل رخصة عزل الطاقة</h1>
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
                                <?php if (in_array($userRole, [7, 8])): ?>
                                    <div><span class="text-gray-500">القسم:</span> <span id="val-section" class="font-medium"></span></div>
                                <?php endif; ?>
                                <div><span class="text-gray-500">السبب:</span> <span id="val-reason" class="font-medium"></span></div>
                                <div><span class="text-gray-500">تصريح العمل:</span> <span id="val-permit" class="font-medium"></span></div>
                                <div><span class="text-gray-500">طالب العزل:</span> <span id="val-requester" class="font-medium font-bold text-blue-600"></span></div>
                                <div><span class="text-gray-500">مسؤول المنطقة:</span> <span id="val-am" class="font-medium"></span></div>
                                <div id="end-at-container" class="hidden"><span class="text-gray-500">تاريخ الانتهاء:</span> <span id="val-end-at" class="font-medium text-green-600"></span></div>
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

                        <!-- Approval Section (For AM) -->
                        <div id="am-approval-section" class="hidden bg-yellow-50 p-6 rounded-lg shadow-md border border-yellow-200">
                            <h2 class="text-lg font-bold text-yellow-800 mb-4 text-right" dir="rtl">إجراءات مسؤول المنطقة</h2>
                            <div class="space-y-4 text-right" dir="rtl">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">تعيين مسؤول العزل (Isolation Officer)</label>
                                    <select id="officer_selection" class="w-full"></select>
                                </div>
                                <div class="flex gap-4">
                                    <button id="approveBtn" class="flex-1 bg-green-600 text-white py-2 rounded-md hover:bg-green-700 transition">تأكيد وتعيين مسؤول العزل</button>
                                    <button id="rejectBtn" class="flex-1 bg-red-600 text-white py-2 rounded-md hover:bg-red-700 transition">رفض الرخصة</button>
                                </div>
                            </div>
                        </div>

                        <!-- Status Details (If rejected or approved) -->
                        <div id="rejection-card" class="hidden bg-red-50 p-6 rounded-lg shadow-md border border-red-200">
                            <h2 class="text-lg font-bold text-red-800 mb-2 text-right" dir="rtl">سبب الرفض</h2>
                            <p id="val-reject-reason" class="text-red-700 italic text-right" dir="rtl"></p>
                        </div>
                        
                        <div id="approval-card" class="hidden bg-green-50 p-6 rounded-lg shadow-md border border-green-200">
                            <h2 class="text-lg font-bold text-green-800 mb-2 text-right" dir="rtl">تفاصيل الاعتماد</h2>
                            <p class="text-green-700 text-right" dir="rtl">مسؤول العزل المعين: <span id="val-io" class="font-bold"></span></p>
                        </div>

                        <!-- IO Review Section -->
                        <div id="io-review-section" class="hidden bg-blue-50 p-6 rounded-lg shadow-md border border-blue-200">
                            <h2 class="text-lg font-bold text-blue-800 mb-4 text-right" dir="rtl">مراجعة رخصة العزل</h2>
                            <p class="text-sm text-blue-600 mb-4 text-right" dir="rtl">يرجى مراجعة المعدات وأنواع الطاقة واختيار مصدر الرخصه او مسؤول الوجبة للتأكيد.</p>
                            
                            <div class="space-y-4 text-right" dir="rtl">
                                <div>
                                    <select id="shift_leader_selection" class="w-full"></select>
                                </div>
                                <button id="ioConfirmBtn" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition">تأكيد المراجعة والتحويل للمرحلة التالية</button>
                            </div>
                        </div>

                        <!-- Shift Leader Review Section -->
                        <div id="sl-review-section" class="hidden bg-purple-50 p-6 rounded-lg shadow-md border border-purple-200">
                            <h2 class="text-lg font-bold text-purple-800 mb-4 text-right" dir="rtl">التأكيد النهائي (مصدر الرخصة او مسؤول الوجبة)</h2>
                            <p class="text-sm text-purple-600 mb-4 text-right" dir="rtl">يرجى مراجعة المعدات وأنواع الطاقة لإتمام إجراءات الرخصة.</p>
                            
                            <div class="space-y-4 text-right" dir="rtl">
                                <button id="slConfirmBtn" class="w-full bg-purple-600 text-white py-2 rounded-md hover:bg-purple-700 transition">تأكيد نهائي وإكمال الرخصة</button>
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
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
        const API_BASE = "../../api/requester/energy_insulation.php";

        let officerSelect, shiftLeaderSelect;

        document.addEventListener('DOMContentLoaded', async () => {
            await loadLicenseData();
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
                        document.getElementById('am-approval-section').classList.remove('hidden');
                        initOfficerSelect();
                    }

                    // Show IO actions if approved_by_am and user is the assigned IO
                    if (data.status === 'approved_by_am' && data.isolation_officer_id == CURRENT_USER_ID) {
                        document.getElementById('io-review-section').classList.remove('hidden');
                        
                        // User wants to see ONLY equipments and energy types, except for roles 7 & 8
                        if (CURRENT_USER_ROLE != 7 && CURRENT_USER_ROLE != 8) {
                            document.querySelector('[id="val-no"]').closest('.bg-white').classList.add('hidden'); // Hide Basic Info
                            document.getElementById('rejection-card').classList.add('hidden');
                            document.getElementById('approval-card').classList.add('hidden');
                            document.getElementById('staff-card').classList.add('hidden');
                        }
                        
                        initShiftLeaderSelect();
                    }

                    // Show SL actions if reviewed_by_io and user is the assigned SL
                    if (data.status === 'reviewed_by_io' && data.shift_leader_id == CURRENT_USER_ID) {
                        document.getElementById('sl-review-section').classList.remove('hidden');
                        
                        // User wants to see ONLY equipments and energy types, except for roles 7 & 8
                        if (CURRENT_USER_ROLE != 7 && CURRENT_USER_ROLE != 8) {
                            document.querySelector('[id="val-no"]').closest('.bg-white').classList.add('hidden'); // Hide Basic Info
                            document.getElementById('rejection-card').classList.add('hidden');
                            document.getElementById('approval-card').classList.add('hidden');
                            document.getElementById('staff-card').classList.add('hidden');
                        }
                    }

                    if (data.status === 'rejected') {
                        document.getElementById('rejection-card').classList.remove('hidden');
                        document.getElementById('val-reject-reason').textContent = data.reject_reason || 'لا يوجد سبب محدد';
                    }

                    if (data.status === 'approved_by_am') {
                        document.getElementById('approval-card').classList.remove('hidden');
                        document.getElementById('val-io').textContent = data.isolation_officer_name || 'N/A';
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

            if (data.end_at) {
                document.getElementById('end-at-container').classList.remove('hidden');
                document.getElementById('val-end-at').textContent = data.end_at;
            }

            const statusEl = document.getElementById('license-status');
            statusEl.textContent = getStatusText(data.status);
            statusEl.className = `px-3 py-1 rounded-full text-sm font-medium ${getStatusClass(data.status)}`;

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
        }

        function getStatusText(status) {
            const map = {
                'pending': 'قيد الانتظار',
                'approved_by_am': 'معتمدة من مسؤول المنطقة',
                'rejected': 'مرفوضة',
                'completed': 'مكتملة'
            };
            return map[status] || status;
        }

        function getStatusClass(status) {
            const map = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'approved_by_am': 'bg-blue-100 text-blue-800',
                'rejected': 'bg-red-100 text-red-800',
                'completed': 'bg-green-100 text-green-800'
            };
            return map[status] || 'bg-gray-100 text-gray-800';
        }

        async function initOfficerSelect() {
            officerSelect = new TomSelect('#officer_selection', {
                persist: false,
                create: false,
                placeholder: 'اختر مسؤول العزل (Role 8)...'
            });

            try {
                const res = await fetch(`${API_BASE}?action=getIsolationOfficers`, {
                    headers: { 'Authorization': `Bearer ${TOKEN}` }
                });
                const result = await res.json();
                if (result.success) {
                    result.data.forEach(user => {
                        officerSelect.addOption({ value: user.id, text: user.name });
                    });
                    officerSelect.refreshOptions(false);
                }
            } catch (e) {
                console.error('Failed to load officers');
            }
        }

        async function initShiftLeaderSelect() {
            shiftLeaderSelect = new TomSelect('#shift_leader_selection', {
                persist: false,
                create: false,
                placeholder: 'اختيار مصدر الرخصه او مسؤول الوجبة'
            });

            try {
                const res = await fetch(`${API_BASE}?action=getShiftLeaders`, {
                    headers: { 'Authorization': `Bearer ${TOKEN}` }
                });
                const result = await res.json();
                if (result.success) {
                    result.data.forEach(user => {
                        shiftLeaderSelect.addOption({ value: user.id, text: user.name });
                    });
                    shiftLeaderSelect.refreshOptions(false);
                }
            } catch (e) {
                console.error('Failed to load shift leaders');
            }
        }

        // IO Confirm Button Logic
        document.getElementById('ioConfirmBtn').addEventListener('click', async () => {
            const slId = shiftLeaderSelect.getValue();
            if (!slId) {
                Swal.fire('تنبيه', 'يرجى اختيار المستخدم التالي أولاً', 'warning');
                return;
            }

            const result = await Swal.fire({
                title: 'تأكيد المراجعة',
                text: 'تم عزل جميع المعدات اعلاه ووضع مفاتيح المعدات المعزولة في صندوق العزل',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، تأكيد',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#0b6f76'
            });

            if (result.isConfirmed) {
                submitAction('confirmByIsolationOfficer', { license_id: LICENSE_ID, shift_leader_id: slId });
            }
        });

        // SL Confirm Button Logic
        document.getElementById('slConfirmBtn').addEventListener('click', async () => {
            const result = await Swal.fire({
                title: 'تأكيد نهائي',
                text: 'التأكد من اجراء فحص تشغيل المعدة للتأكد من عزلها من الموقع قبل البدء بالعمل',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، إتمام',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#7c3aed'
            });

            if (result.isConfirmed) {
                submitAction('confirmByShiftLeader', { license_id: LICENSE_ID });
            }
        });

        // Approve Button Logic
        document.getElementById('approveBtn').addEventListener('click', async () => {
            const officerId = officerSelect.getValue();
            if (!officerId) {
                Swal.fire('تنبيه', 'يرجى اختيار مسؤول العزل أولاً', 'warning');
                return;
            }

            const result = await Swal.fire({
                title: 'تأكيد الاعتماد',
                text: 'هل أنت متأكد من تعيين هذا المسؤول؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، تأكيد',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#0b6f76'
            });

            if (result.isConfirmed) {
                submitAction('updateIsolationOfficer', { license_id: LICENSE_ID, officer_id: officerId });
            }
        });

        // Reject Button Logic
        document.getElementById('rejectBtn').addEventListener('click', async () => {
            const { value: reason } = await Swal.fire({
                title: 'رفض الرخصة',
                input: 'textarea',
                inputLabel: 'سبب الرفض',
                inputPlaceholder: 'اكتب سبب الرفض هنا...',
                inputAttributes: { 'aria-label': 'اكتب سبب الرفض هنا' },
                showCancelButton: true,
                confirmButtonText: 'تأكيد الرفض',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#dc2626',
                inputValidator: (value) => {
                    if (!value) {
                        return 'يجب كتابة سبب الرفض'
                    }
                }
            });

            if (reason) {
                submitAction('reject', { license_id: LICENSE_ID, reason: reason });
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
