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
            body {
                background: white !important;
                font-size: 9pt;
                margin: 0;
                padding: 0.2in;
            }

            .dashboard-container {
                background: white !important;
                min-height: auto !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                flex: none !important;
            }

            .flex-1 {
                flex: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                overflow: visible !important;
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

            .sidebar,
            nav,
            .navbar,
            #sidebar,
            .no-print,
            button,
            select,
            #am-approval-section,
            #io-review-section,
            #sl-review-section {
                display: none !important;
            }

            .bg-white {
                border: 1px solid #e5e7eb !important;
                shadow: none !important;
                box-shadow: none !important;
                margin-bottom: 0.25rem !important;
                padding: 0.5rem !important;
            }

            .shadow-md,
            .shadow-lg {
                box-shadow: none !important;
            }

            #content {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                transform: none !important;
            }

            .print-table-header {
                display: table-header-group !important;
            }

            .print-header {
                display: block !important;
                border-bottom: 2px solid #0b6f76;
                margin-bottom: 0.5rem;
                padding-bottom: 0.25rem;
                text-align: center;
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

            .max-w-4xl {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
            }

            h2 {
                font-size: 11pt !important;
                margin-bottom: 0.2rem !important;
                padding-bottom: 0.2rem !important;
            }

            .grid {
                gap: 0.25rem !important;
            }

            .grid-cols-1.md\:grid-cols-2 {
                grid-template-columns: repeat(2, 1fr) !important;
            }

            .space-y-6>*+* {
                margin-top: 0.25rem !important;
            }

            #val-equipments {
                grid-template-cols: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.25rem !important;
            }

            #val-equipments img {
                height: 30px !important;
                width: 30px !important;
            }

            #val-equipments div p {
                font-size: 8pt !important;
            }

            .print-only {
                display: block !important;
            }
        }

        .print-only {
            display: none;
        }
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
                            <button id="pdfDownloadBtn" onclick="window.print()" class="hidden flex items-center gap-2 bg-white border border-red-500 text-red-600 px-3 py-1.5 md:px-4 md:py-2 rounded-md hover:bg-red-50 transition text-sm md:text-base font-medium shadow-sm">
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

                    <!-- Print Header (Repeats on each page) -->
                    <table class="w-full">
                        <thead class="print-table-header hidden">
                            <tr>
                                <th>
                                    <div class="print-header">
                                        <img src="../../public/images/logo.png" alt="Logo">
                                        <h1>رخصة عزل الطاقة - Energy Insulation License</h1>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
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
                                                <div><span class="text-gray-500">مرخص العزل:</span> <span id="val-created-by" class="font-medium"></span></div>
                                                <div><span class="text-gray-500">طالب العزل:</span> <span id="val-requester" class="font-medium font-bold text-blue-600"></span></div>
                                                <div><span class="text-gray-500">مسؤول المنطقة:</span> <span id="val-am" class="font-medium"></span></div>
                                                <div><span class="text-gray-500">اسم العازل:</span> <span id="val-official" class="font-medium text-green-700"></span></div>
                                                <div id="am-approved-container" class="hidden"><span class="text-gray-500">تاريخ تأكيد العزل:</span> <span id="val-am-approved-at" class="font-medium text-blue-600"></span></div>

                                            </div>
                                        </div>

                                        <!-- Energy Types Card -->
                                        <div class="bg-white p-6 rounded-lg shadow-md">
                                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">أنواع الطاقة المعزولة</h2>
                                            <div class="flex justify-between items-center mb-3">
                                                <div id="val-energy-types" class="flex flex-wrap gap-2 justify-start" dir="rtl"></div>
                                                <div class="no-print">
                                                    <button id="addEnergyTypesBtn" class="hidden bg-white border px-3 py-1 rounded-md text-sm text-[#0b6f76]">إضافة</button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Equipments Card -->
                                        <div class="bg-white p-6 rounded-lg shadow-md">
                                            <h2 class="text-lg font-bold text-[#0b6f76] mb-4 border-b pb-2 text-right" dir="rtl">اسم المعدة المراد عزلها</h2>
                                            <div class="flex justify-between items-center mb-3">
                                                <div id="val-equipments" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-start" dir="rtl"></div>
                                                <div class="no-print">
                                                    <button id="addEquipmentsBtn" class="hidden bg-white border px-3 py-1 rounded-md text-sm text-[#0b6f76]">إضافة</button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Staff Card -->
                                        <div id="staff-card" class="bg-white p-6 rounded-lg shadow-md">
                                            <div class="flex justify-between items-center mb-4 border-b pb-2">
                                                <button id="editStaffBtn" class="hidden no-print text-sm flex items-center gap-1 text-[#0b6f76] hover:text-[#085a60] font-medium transition-colors bg-[#0b6f76]/10 px-3 py-1.5 rounded-md hover:bg-[#0b6f76]/20">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    تعديل
                                                </button>
                                                <h2 class="text-lg font-bold text-[#0b6f76] text-right" dir="rtl">طاقم العمل</h2>
                                            </div>
                                            <div id="val-staff" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-right" dir="rtl"></div>
                                        </div>

                                        <!-- Isolation Done Info Card -->
                                        <div id="isolation-done-card" class="hidden bg-white p-6 rounded-lg shadow-md border-t-4 border-green-600 mt-6">
                                            <h2 class="text-lg font-bold text-green-700 mb-4 border-b pb-2 text-right" dir="rtl">تم العزل</h2>
                                            <div class="space-y-4 text-right" dir="rtl">
                                                <p class="text-sm text-gray-700 font-medium">تمت المصادقة على العزل</p>
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-[10px] text-gray-400 flex">بواسطة مرخص العزل ✔
                                                    </span>
                                                    <span id="val-iso-done-by" class="text-sm font-bold text-green-800"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Isolation Removed Info Card -->



                                        <!-- AM Done Section -->
                                        <div id="am-action-section" class="hidden bg-yellow-50 p-6 rounded-lg shadow-md border border-yellow-200">
                                            <h2 class="text-lg font-bold text-yellow-800 mb-4 text-right" dir="rtl">إجراءات مسؤول العزل</h2>
                                            <div class="space-y-4 text-right" dir="rtl">
                                                <p class="text-sm text-yellow-700">تمت المصادقة على العزل</p>
                                                <button id="amDoneBtn" class="w-full bg-[#0b6f76] text-white py-3 rounded-md hover:bg-[#085a60] transition font-bold text-lg">Done / تم العزل</button>
                                            </div>
                                        </div>

                                        <!-- Requester Removal Section -->
                                        <div id="requester-action-section" class="hidden bg-green-50 p-6 rounded-lg shadow-md border border-green-200">
                                            <h2 class="text-lg font-bold text-green-800 mb-4 text-right" dir="rtl">طلب رفع العزل - <?= $userName ?></h2>
                                            <div class="space-y-4 text-right" dir="rtl">
                                                <p class="text-sm text-green-700">تم الانتهاء من العمل على المعده وتم ازالة كافة الاقفال الشخصية الخاصه للمجموعه وتم تنصيب كافة الواقيات وتنظيف المكان</p>

                                                <label class="flex items-center gap-3 justify-start cursor-pointer group" dir="rtl">
                                                    <input type="checkbox" id="permitWorkCb" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                                    <span class="text-sm font-medium text-gray-700 group-hover:text-green-700 transition-colors">اطلب رفع العزل بعد ان تم التأكيد ان جميع العاملين خارج المعدة</span>
                                                </label>

                                                <label class="flex items-center gap-3 justify-start cursor-pointer group" dir="rtl">
                                                    <input type="checkbox" id="removeSectionLockCb" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                                    <span class="text-sm font-medium text-gray-700 group-hover:text-green-700 transition-colors">اطلب ازالة قفل القسم من صندوق العزل</span>
                                                </label>

                                                <button id="removeIsolationBtn" disabled class="w-full bg-green-600 text-white py-3 rounded-md hover:bg-green-700 transition font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                                    رفع العزل
                                                </button>
                                            </div>
                                        </div>


                                        <!-- Print Completion Confirmation (Visible in PDF when completed) -->
                                        <div id="print-completion-card" class="hidden bg-white border border-gray-200 rounded-lg p-3 mt-2">
                                            <div class="text-right" dir="rtl">
                                                <h2 class="text-lg font-bold text-[#0b6f76] mb-1 text-right" dir="rtl">طلب رفع العزل - <span id="val-remover-name"></span></h2>
                                                <p class="text-sm text-[#0b6f76] mb-2">تم الانتهاء من العمل على المعده وتم ازالة كافة الاقفال الشخصية الخاصه للمجموعه وتم تنصيب كافة الواقيات وتنظيف المكان</p>

                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-3 justify-start" dir="rtl">
                                                        <input type="checkbox" class="w-4 h-4 text-[#0b6f76] border-gray-300 rounded" onclick="return false;" checked>
                                                        <span class="text-sm font-medium text-gray-700">اطلب رفع العزل بعد ان تم التأكيد ان جميع العاملين خارج المعدة</span>
                                                    </div>

                                                    <div class="flex items-center gap-3 justify-start" dir="rtl">
                                                        <input type="checkbox" class="w-4 h-4 text-[#0b6f76] border-gray-300 rounded" onclick="return false;" checked>
                                                        <span class="text-sm font-medium text-gray-700">اطلب ازالة قفل القسم من صندوق العزل</span>
                                                    </div>
                                                </div>

                                                <p class="text-sm font-bold text-gray-700 italic mt-4 text-xs">تمت المصادقة على رفع العزل بواسطة: <span id="val-remover-confirm-name"></span></p>
                                                <div id="end-at-container" class="hidden"><span class="text-gray-500 text-xs">تاريخ رفع العزل:</span> <span id="val-end-at" class="font-medium text-xs text-green-600"></span></div>
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
        // Add/Remove Energy Types & Equipments - Area Manager actions
        function openAddEnergyModal() {
            const modal = document.getElementById('addEnergyModal');
            const list = document.getElementById('add-energy-list');
            list.innerHTML = '<p class="text-center py-4">جاري التحميل...</p>';
            modal.classList.remove('hidden');

            fetch(`${API_BASE}?action=getEnergyTypes`, {
                    headers: {
                        'Authorization': `Bearer ${TOKEN}`
                    }
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) throw new Error('فشل في جلب الأنواع');
                    const types = res.data;
                    list.innerHTML = '';
                    types.forEach(t => {
                        const id = t.id;
                        const already = (currentLicenseData.energy_types || []).some(et => et.id == id);
                        const row = document.createElement('div');
                        row.className = 'flex items-center justify-between p-2 border-b';
                        row.innerHTML = `<label class="flex items-center gap-2"><input type="checkbox" value="${id}" ${already ? 'disabled' : ''}> <span>${t.name}</span></label>`;
                        list.appendChild(row);
                    });
                }).catch(e => {
                    list.innerHTML = '<p class="text-red-500">خطأ في التحميل</p>';
                });
        }

        function closeAddEnergyModal() {
            document.getElementById('addEnergyModal').classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const submitAddEnergyBtn = document.getElementById('submitAddEnergyBtn');
            if (submitAddEnergyBtn) submitAddEnergyBtn.addEventListener('click', async () => {
                const checkboxes = Array.from(document.querySelectorAll('#add-energy-list input[type="checkbox"]:not(:disabled):checked'));
                if (checkboxes.length === 0) {
                    Swal.fire('تنبيه', 'اختر نوع طاقة واحد على الأقل', 'warning');
                    return;
                }
                const ids = checkboxes.map(cb => parseInt(cb.value));
                try {
                    const res = await fetch(`${API_BASE}?action=addEnergyTypes`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${TOKEN}`
                        },
                        body: JSON.stringify({
                            license_id: LICENSE_ID,
                            energy_type_ids: ids
                        })
                    });
                    const result = await res.json();
                    if (result.success) {
                        Swal.fire('نجاح', result.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('خطأ', result.message || 'فشل', 'error');
                    }
                } catch (e) {
                    Swal.fire('خطأ', 'حدث خطأ', 'error');
                }
            });

            const submitAddEquipmentsBtn = document.getElementById('submitAddEquipmentsBtn');
            if (submitAddEquipmentsBtn) submitAddEquipmentsBtn.addEventListener('click', async () => {
                const checkboxes = Array.from(document.querySelectorAll('#add-equipments-list input[type="checkbox"]:not(:disabled):checked'));
                if (checkboxes.length === 0) {
                    Swal.fire('تنبيه', 'اختر معدة واحدة على الأقل', 'warning');
                    return;
                }
                const ids = checkboxes.map(cb => parseInt(cb.value));
                try {
                    const res = await fetch(`${API_BASE}?action=addEquipments`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${TOKEN}`
                        },
                        body: JSON.stringify({
                            license_id: LICENSE_ID,
                            equipment_ids: ids
                        })
                    });
                    const result = await res.json();
                    if (result.success) {
                        Swal.fire('نجاح', result.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('خطأ', result.message || 'فشل', 'error');
                    }
                } catch (e) {
                    Swal.fire('خطأ', 'حدث خطأ', 'error');
                }
            });
        });

        async function removeEnergyType(energyTypeId) {
            const ok = await Swal.fire({
                title: 'تأكيد',
                text: 'هل تريد حذف نوع الطاقة؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم'
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(`${API_BASE}?action=removeEnergyType`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${TOKEN}`
                    },
                    body: JSON.stringify({
                        license_id: LICENSE_ID,
                        energy_type_id: energyTypeId
                    })
                });
                const result = await res.json();
                if (result.success) {
                    Swal.fire('نجاح', result.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('خطأ', result.message || 'فشل', 'error');
                }
            } catch (e) {
                Swal.fire('خطأ', 'حدث خطأ', 'error');
            }
        }

        function openAddEquipmentsModal() {
            const modal = document.getElementById('addEquipmentsModal');
            const list = document.getElementById('add-equipments-list');
            list.innerHTML = '<p class="text-center py-4">جاري التحميل...</p>';
            modal.classList.remove('hidden');

            const sectionId = currentLicenseData.equipment_section_id;
            fetch(`${API_BASE}?action=getEquipmentsBySection&section_id=${sectionId}&limit=200`, {
                    headers: {
                        'Authorization': `Bearer ${TOKEN}`
                    }
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) throw new Error('فشل في جلب المعدات');
                    const items = res.data.equipments || [];
                    list.innerHTML = '';
                    items.forEach(it => {
                        const already = (currentLicenseData.equipments || []).some(eq => eq.id == it.id);
                        const row = document.createElement('div');
                        row.className = 'flex items-center justify-between p-2 border-b';
                        row.innerHTML = `<label class="flex items-center gap-2"><input type="checkbox" value="${it.id}" ${already ? 'disabled' : ''}> <span>${it.name}</span></label>`;
                        list.appendChild(row);
                    });
                }).catch(e => {
                    list.innerHTML = '<p class="text-red-500">خطأ في التحميل</p>';
                });
        }

        function closeAddEquipmentsModal() {
            document.getElementById('addEquipmentsModal').classList.add('hidden');
        }

        document.getElementById('submitAddEquipmentsBtn').addEventListener('click', async () => {
            const checkboxes = Array.from(document.querySelectorAll('#add-equipments-list input[type="checkbox"]:not(:disabled):checked'));
            if (checkboxes.length === 0) {
                Swal.fire('تنبيه', 'اختر معدة واحدة على الأقل', 'warning');
                return;
            }
            const ids = checkboxes.map(cb => parseInt(cb.value));
            try {
                const res = await fetch(`${API_BASE}?action=addEquipments`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${TOKEN}`
                    },
                    body: JSON.stringify({
                        license_id: LICENSE_ID,
                        equipment_ids: ids
                    })
                });
                const result = await res.json();
                if (result.success) {
                    Swal.fire('نجاح', result.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('خطأ', result.message || 'فشل', 'error');
                }
            } catch (e) {
                Swal.fire('خطأ', 'حدث خطأ', 'error');
            }
        });

        async function removeEquipment(equipmentId) {
            const ok = await Swal.fire({
                title: 'تأكيد',
                text: 'هل تريد حذف المعدة من هذه الرخصة؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم'
            });
            if (!ok.isConfirmed) return;
            try {
                const res = await fetch(`${API_BASE}?action=removeEquipment`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${TOKEN}`
                    },
                    body: JSON.stringify({
                        license_id: LICENSE_ID,
                        equipment_id: equipmentId
                    })
                });
                const result = await res.json();
                if (result.success) {
                    Swal.fire('نجاح', result.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('خطأ', result.message || 'فشل', 'error');
                }
            } catch (e) {
                Swal.fire('خطأ', 'حدث خطأ', 'error');
            }
        }
    </script>

    <!-- Add Energy Types Modal -->
    <div id="addEnergyModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">
            <div class="p-4 border-b flex justify-between items-center bg-gray-50 rounded-t-lg">
                <h3 class="text-lg font-bold text-[#0b6f76]">إضافة أنواع طاقة</h3>
                <button type="button" onclick="closeAddEnergyModal()" class="text-gray-400 hover:text-red-500">✕</button>
            </div>
            <div class="p-4 overflow-y-auto" id="add-energy-list"></div>
            <div class="p-4 border-t flex justify-end gap-3 bg-gray-50 rounded-b-lg">
                <button id="submitAddEnergyBtn" class="px-4 py-2 bg-[#0b6f76] text-white rounded-md">إضافة</button>
                <button type="button" onclick="closeAddEnergyModal()" class="px-4 py-2 bg-gray-200 rounded-md">إلغاء</button>
            </div>
        </div>
    </div>

    <!-- Add Equipments Modal -->
    <div id="addEquipmentsModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
            <div class="p-4 border-b flex justify-between items-center bg-gray-50 rounded-t-lg">
                <h3 class="text-lg font-bold text-[#0b6f76]">إضافة معدات</h3>
                <button type="button" onclick="closeAddEquipmentsModal()" class="text-gray-400 hover:text-red-500">✕</button>
            </div>
            <div class="p-4 overflow-y-auto" id="add-equipments-list"></div>
            <div class="p-4 border-t flex justify-end gap-3 bg-gray-50 rounded-b-lg">
                <button id="submitAddEquipmentsBtn" class="px-4 py-2 bg-[#0b6f76] text-white rounded-md">إضافة</button>
                <button type="button" onclick="closeAddEquipmentsModal()" class="px-4 py-2 bg-gray-200 rounded-md">إلغاء</button>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div id="editStaffModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="p-4 border-b flex justify-between items-center bg-gray-50 rounded-t-lg">
                <button type="button" onclick="closeEditStaffModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <h3 class="text-lg font-bold text-[#0b6f76]">تعديل طاقم العمل</h3>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto flex-1 bg-gray-50/50" id="edit-staff-groups-container">
                <!-- Dynamic groups go here -->
            </div>
            <div class="px-6 py-2 bg-gray-50/50 text-right">
                <button type="button" id="editAddGroupBtn" class="flex items-center gap-2 text-sm text-[#0b6f76] font-medium hover:underline w-fit mr-0 ml-auto" dir="rtl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    إضافة مجموعة
                </button>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 border-t flex justify-end gap-3 bg-gray-50 rounded-b-lg">
                <button type="button" id="saveStaffBtn" class="px-6 py-2 bg-[#0b6f76] text-white rounded-md hover:bg-[#085a60] transition font-medium">حفظ التغييرات</button>
                <button type="button" onclick="closeEditStaffModal()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition font-medium">إلغاء</button>
            </div>
        </div>
    </div>

    <script>
        const LICENSE_ID = "<?= $licenseId ?>";
        const CURRENT_USER_ID = "<?= $userId ?>";
        const CURRENT_USER_ROLE = "<?= $userRole ?>";
        const CURRENT_USER_NAME = "<?= $userName ?>";
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
        const API_BASE = "../../api/requester/energy_insulation.php";

        let currentLicenseData = null;
        let officerSelect, shiftLeaderSelect;

        document.addEventListener('DOMContentLoaded', async () => {
            await loadLicenseData();

            // Handle work permit checkbox
            const permitCb = document.getElementById('permitWorkCb');
            const removeSectionLockCb = document.getElementById('removeSectionLockCb');
            const removeBtn = document.getElementById('removeIsolationBtn');
            if (permitCb && removeSectionLockCb && removeBtn) {
                const updateBtnState = () => {
                    removeBtn.disabled = !(permitCb.checked && removeSectionLockCb.checked);
                };
                permitCb.addEventListener('change', updateBtnState);
                removeSectionLockCb.addEventListener('change', updateBtnState);
            }
        });

        async function loadLicenseData() {
            try {
                const res = await fetch(`${API_BASE}?action=show&id=${LICENSE_ID}`, {
                    headers: {
                        'Authorization': `Bearer ${TOKEN}`
                    }
                });
                const result = await res.json();

                if (result.success) {
                    currentLicenseData = result.data;
                    const data = result.data;
                    displayData(data);

                    // Show AM actions if pending and user is the assigned AM
                    if (data.status === 'pending' && data.area_manager_id == CURRENT_USER_ID) {
                        document.getElementById('am-action-section').classList.remove('hidden');
                    }

                    // Show add/remove controls to assigned area manager
                    if (data.area_manager_id == CURRENT_USER_ID) {
                        const addEnergyBtn = document.getElementById('addEnergyTypesBtn');
                        const addEquipBtn = document.getElementById('addEquipmentsBtn');
                        if (addEnergyBtn) addEnergyBtn.classList.remove('hidden');
                        if (addEquipBtn) addEquipBtn.classList.remove('hidden');
                        if (addEnergyBtn) addEnergyBtn.addEventListener('click', openAddEnergyModal);
                        if (addEquipBtn) addEquipBtn.addEventListener('click', openAddEquipmentsModal);
                    }

                    // Show Requester actions if active_isolation and user is creator
                    const requesterSection = document.getElementById('requester-action-section');
                    if (requesterSection) {
                        if (data.status === 'active_isolation' && data.created_by == CURRENT_USER_ID) {
                            // show on screen but hide from print/PDF until isolation is actually removed
                            requesterSection.classList.remove('hidden');
                            requesterSection.classList.add('no-print');
                        } else {
                            requesterSection.classList.add('hidden');
                            requesterSection.classList.remove('no-print');
                        }
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
            document.getElementById('val-created-by').textContent = data.creator_name;
            document.getElementById('val-requester').textContent = data.requester_name;
            document.getElementById('val-remover-confirm-name').textContent = data.requester_name;
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
                statusByEl.textContent = `بواسطة: ${data.creator_name || 'N/A'}`;
            } else if (data.status === 'active_isolation' || data.status === 'completed' || data.status === 'rejected') {
                statusByEl.textContent = `بواسطة: ${data.creator_name || 'N/A'}`;
            } else if (data.status === 'approved_by_am') {
                statusByEl.textContent = `بواسطة: ${data.creator_name || 'N/A'}`;
            }

            // Energy Types
            const energyContainer = document.getElementById('val-energy-types');
            energyContainer.innerHTML = '';
            data.energy_types.forEach(et => {
                const span = document.createElement('span');
                span.className = 'px-3 py-1 bg-gray-100 border rounded-full text-xs flex items-center gap-2';
                span.setAttribute('data-energy-id', et.id);
                span.innerHTML = `<span>${et.name}</span>`;
                // if current user is area manager allow delete
                if (data.area_manager_id == CURRENT_USER_ID) {
                    const del = document.createElement('button');
                    del.className = 'text-red-500 text-sm no-print';
                    del.title = 'حذف';
                    del.innerHTML = '✕';
                    del.addEventListener('click', (e) => {
                        e.stopPropagation();
                        removeEnergyType(et.id);
                    });
                    span.appendChild(del);
                }
                energyContainer.appendChild(span);
            });

            // Equipments
            const eqContainer = document.getElementById('val-equipments');
            data.equipments.forEach(eq => {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-3 p-2 border rounded-md bg-gray-50 justify-between';
                const left = document.createElement('div');
                left.className = 'flex items-center gap-3';
                const img = eq.image ?
                    `<img src="../../public/${eq.image}" class="w-12 h-12 object-cover rounded">` :
                    `<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-[10px] text-gray-400">No Image</div>`;
                left.innerHTML = `
                    ${img}
                    <div>
                        <p class="text-xs font-bold">${eq.name}</p>
                        <p class="text-[10px] text-gray-500">Ref: ${eq.equipment_no}</p>
                    </div>
                `;
                div.appendChild(left);
                if (data.area_manager_id == CURRENT_USER_ID) {
                    const actions = document.createElement('div');
                    actions.className = 'no-print';
                    const delBtn = document.createElement('button');
                    delBtn.className = 'text-red-500 px-2 py-1 rounded-md border';
                    delBtn.textContent = 'حذف';
                    delBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        removeEquipment(eq.id);
                    });
                    actions.appendChild(delBtn);
                    div.appendChild(actions);
                }
                eqContainer.appendChild(div);
            });

            // Staff
            const staffContainer = document.getElementById('val-staff');
            staffContainer.innerHTML = '';
            if (data.staff && data.staff.length > 0) {
                // Build groups map with metadata (id, is_done)
                const groupsMap = {};
                data.staff.forEach(member => {
                    const gName = member.group_name || 'طاقم العمل';
                    if (!groupsMap[gName]) groupsMap[gName] = {
                        members: [],
                        id: member.group_id || null,
                        is_done: member.group_is_done ? 1 : 0
                    };
                    groupsMap[gName].members.push(member.name);
                });

                Object.keys(groupsMap).forEach(groupName => {
                    const g = groupsMap[groupName];
                    const groupDiv = document.createElement('div');
                    groupDiv.setAttribute('data-group-id', g.id || '');
                    groupDiv.className = 'bg-gray-50 border border-gray-100 p-3 rounded-md print:p-1 print:mb-2 break-inside-avoid';
                    //if checked, add green border
                    if (g.is_done) {
                        groupDiv.classList.add('bg-green-50', 'border-green-300');
                    }

                    // Header row: group name (right) and checkbox+label (left)
                    const headerRow = document.createElement('div');
                    headerRow.className = 'flex justify-between items-center mb-2';
                    headerRow.setAttribute('dir', 'rtl');

                    const gTitle = document.createElement('h4');
                    gTitle.className = 'text-sm font-bold text-[#0b6f76] mb-0 print:text-[9pt]';
                    gTitle.textContent = groupName;

                    const leftContainer = document.createElement('div');

                    // Show interactive checkbox only to license creator
                    if (CURRENT_USER_ID == data.created_by && g.id) {
                        // if not checked, add .no-print to hide from print/PDF until checked
                        if (!g.is_done) {
                            leftContainer.classList.add('no-print');
                        }
                        leftContainer.innerHTML = `
                            <label class="flex items-center gap-3 cursor-pointer" dir="rtl">
                                <input type="checkbox" onchange="onGroupDoneChange(${g.id}, this)" class="no-print group-done-cb w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500" ${g.is_done ? 'checked' : ''}>
                                <span class="text-sm font-medium text-gray-700">تم اكمال العمل</span>
                            </label>
                        `;
                    } else {
                        // read-only view
                        leftContainer.innerHTML = `
                            <div class="flex items-center gap-2">
                                <input type="checkbox" class="w-4 h-4 text-green-600 border-gray-300 rounded" onclick="return false;" ${g.is_done ? 'checked' : ''}>
                                <span class="text-sm text-gray-600">تم اكمال العمل</span>
                            </div>
                        `;
                    }

                    headerRow.appendChild(gTitle);
                    headerRow.appendChild(leftContainer);
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
            } else {
                staffContainer.innerHTML = '<p class="text-gray-400 italic text-sm">لا يوجد طاقم عمل مسجل</p>';
            }

            // Isolation Done Card
            if (data.status === 'active_isolation' || data.status === 'completed') {
                const isoDoneCard = document.getElementById('isolation-done-card');
                if (isoDoneCard) {
                    isoDoneCard.classList.remove('hidden');
                    document.getElementById('val-iso-done-by').textContent = data.creator_name || 'N/A';
                }
            }

            // Isolation Removed Card
            if (data.status === 'completed' && CURRENT_USER_ROLE != '3') {
                const isoRemovedCard = document.getElementById('isolation-removed-card');
                if (isoRemovedCard) {
                    isoRemovedCard.classList.remove('hidden');
                    document.getElementById('val-iso-removed-by').textContent = data.creator_name || 'N/A';
                }
            }

            // Print Completion Section (only show when fully completed)
            const pcCard = document.getElementById('print-completion-card');
            if (data.status === 'completed' && CURRENT_USER_ROLE != '3') {
                if (pcCard) {
                    pcCard.classList.remove('hidden');
                    document.getElementById('val-remover-name').textContent = data.creator_name;
                }
            } else {
                if (pcCard) pcCard.classList.add('hidden');
            }

            // Show PDF download button only after isolation is removed (completed) or when pending (before isolation is done)
            if (data.status === 'completed' || data.status === 'active_isolation') {
                document.getElementById('pdfDownloadBtn').classList.remove('hidden');
            }

            // Edit Staff Button Logic
            if (CURRENT_USER_ID == data.created_by) {
                document.getElementById('editStaffBtn').classList.remove('hidden');
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
                submitAction('amDone', {
                    license_id: LICENSE_ID
                });
            }
        });

        // Requester Removal Button Logic
        document.getElementById('removeIsolationBtn').addEventListener('click', async () => {
            const result = await Swal.fire({
                title: 'تأكيد رفع العزل',
                text: 'تم رفع العزل وارجاع الطاقات الى حالة التشغيل من قبل العازل',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، تأكيد رفع العزل',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#059669'
            });

            if (result.isConfirmed) {
                submitAction('removeIsolation', {
                    license_id: LICENSE_ID
                });
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

        // Toggle group is_done via API
        async function onGroupDoneChange(groupId, el) {
            if (!groupId) return;
            const checked = el.checked;
            try {
                const res = await fetch(`${API_BASE}?action=toggleGroupDone`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${TOKEN}`
                    },
                    body: JSON.stringify({
                        group_id: groupId,
                        license_id: LICENSE_ID,
                        is_done: checked ? 1 : 0
                    })
                });
                const result = await res.json();
                if (!result.success) {
                    el.checked = !checked;
                    Swal.fire('خطأ', result.message || 'لم يتم التحديث', 'error');
                } else {
                    // update data-group-id bg color based on new is_done value
                    const groupDiv = el.closest('[data-group-id]');
                    if (groupDiv) {
                        if (checked) {
                            groupDiv.classList.add('bg-green-50', 'border-green-300');
                            groupDiv.classList.remove('bg-gray-50');
                        } else {
                            groupDiv.classList.add('bg-gray-50');
                            groupDiv.classList.remove('bg-green-50', 'border-green-300');
                        }
                    }
                }
            } catch (e) {
                el.checked = !checked;
                Swal.fire('خطأ', 'حدث خطأ غير متوقع', 'error');
            }
        }

        // --- Edit Staff Logic ---
        function openEditStaffModal() {
            const container = document.getElementById('edit-staff-groups-container');
            container.innerHTML = '';

            if (currentLicenseData && currentLicenseData.staff && currentLicenseData.staff.length > 0) {
                // Group the existing staff by group_name
                const groups = {};
                currentLicenseData.staff.forEach(member => {
                    const gName = member.group_name || 'مجموعة بدون اسم';
                    if (!groups[gName]) groups[gName] = [];
                    groups[gName].push(member.name);
                });

                Object.keys(groups).forEach(groupName => {
                    addEditGroup(groupName, groups[groupName]);
                });
            } else {
                addEditGroup('', ['']); // Empty group with one empty member
            }

            document.getElementById('editStaffModal').classList.remove('hidden');
        }

        function closeEditStaffModal() {
            document.getElementById('editStaffModal').classList.add('hidden');
        }

        document.getElementById('editStaffBtn').addEventListener('click', openEditStaffModal);

        function createEditStaffInput(name = '') {
            return `
                <div class="edit-staff-entry flex gap-2 mb-2">
                    <input type="text" placeholder="ادخل اسم الفرد هنا" value="${name}" class="flex-1 px-4 py-2 border rounded-md focus:ring-[#0b6f76] edit-staff-name-input">
                    <button type="button" class="remove-edit-staff px-3 py-2 bg-red-100 text-red-600 rounded-md hover:bg-red-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            `;
        }

        function addEditGroup(groupName = '', members = ['']) {
            const container = document.getElementById('edit-staff-groups-container');
            const groupDiv = document.createElement('div');
            groupDiv.className = 'edit-staff-group-card bg-white border border-gray-200 p-5 rounded-lg shadow-sm relative mb-4';

            let membersHtml = '';
            members.forEach(name => {
                membersHtml += createEditStaffInput(name);
            });

            groupDiv.innerHTML = `
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-2" dir="rtl">
                    <div class="flex-1">
                            <label class="block text-sm font-bold text-[#0b6f76] mb-1">اسم المجموعة</label>
                            <input type="text" placeholder="مثال: Group A أو فريق الصيانة" value="${groupName}" class="w-full md:w-3/4 px-4 py-2 border border-gray-300 rounded-md focus:ring-[#0b6f76] edit-group-name-input" required>
                    </div>
                    <button type="button" class="remove-edit-group text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-full transition-colors mr-2" title="حذف المجموعة">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
                <div class="edit-staff-members-container" dir="rtl">
                    <label class="block text-sm font-medium text-gray-700 mb-3">أفراد المجموعة</label>
                    ${membersHtml}
                </div>
                <button type="button" class="add-staff-to-edit-group-btn mt-4 flex items-center gap-2 text-sm text-[#0b6f76] font-bold hover:underline" dir="rtl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    إضافة فرد للمجموعة
                </button>
            `;
            container.appendChild(groupDiv);
            updateEditStaffRemoveButtons(groupDiv);
        }

        document.getElementById('editAddGroupBtn').addEventListener('click', () => addEditGroup());

        document.getElementById('edit-staff-groups-container').addEventListener('click', (e) => {
            // Add member
            if (e.target.closest('.add-staff-to-edit-group-btn')) {
                const groupCard = e.target.closest('.edit-staff-group-card');
                const membersContainer = groupCard.querySelector('.edit-staff-members-container');
                membersContainer.insertAdjacentHTML('beforeend', createEditStaffInput());
                updateEditStaffRemoveButtons(groupCard);
            }

            // Remove member
            if (e.target.closest('.remove-edit-staff')) {
                const entry = e.target.closest('.edit-staff-entry');
                const groupCard = entry.closest('.edit-staff-group-card');
                entry.remove();
                updateEditStaffRemoveButtons(groupCard);
            }

            // Remove group
            if (e.target.closest('.remove-edit-group')) {
                e.target.closest('.edit-staff-group-card').remove();
            }
        });

        function updateEditStaffRemoveButtons(groupCard) {
            if (!groupCard) return;
            const entries = groupCard.querySelectorAll('.edit-staff-entry');
            entries.forEach((entry) => {
                const removeBtn = entry.querySelector('.remove-edit-staff');
                if (entries.length === 1) {
                    removeBtn.classList.add('hidden');
                } else {
                    removeBtn.classList.remove('hidden');
                }
            });
        }

        // Save Edit Staff
        document.getElementById('saveStaffBtn').addEventListener('click', async () => {
            const groups = document.querySelectorAll('.edit-staff-group-card');
            const staff_groups = [];

            let isValid = true;

            groups.forEach(groupCard => {
                const groupName = groupCard.querySelector('.edit-group-name-input').value.trim();
                const members = Array.from(groupCard.querySelectorAll('.edit-staff-name-input'))
                    .map(input => input.value.trim())
                    .filter(val => val !== '');

                if (!groupName || members.length === 0) {
                    isValid = false;
                } else {
                    staff_groups.push({
                        group_name: groupName,
                        members: members
                    });
                }
            });

            if (groups.length > 0 && !isValid) {
                Swal.fire('خطأ', 'يرجى ملء أسماء المجموعات وأفرادها.', 'error');
                return;
            }

            const saveBtn = document.getElementById('saveStaffBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'جاري الحفظ...';

            try {
                const res = await fetch(`${API_BASE}?action=updateStaffGroups`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${TOKEN}`
                    },
                    body: JSON.stringify({
                        license_id: LICENSE_ID,
                        staff_groups: staff_groups
                    })
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
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'حفظ التغييرات';
            }
        });
    </script>
</body>

</html>