<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/helpers/authCheck.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رخص دخول الأماكن المغلقة</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <?php renderNavbar('رخص دخول الأماكن المغلقة'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('confined_space'); ?>
        <div class="flex-1 flex flex-col sm:ml-64">
            <main class="flex-1 p-4 md:p-8 md:pl-12">
                <div class="max-w-6xl mx-auto">
                    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-gray-800">رخص دخول الأماكن المغلقة</h1>
                            <span id="statusFilterBadge" class="hidden px-3 py-1 rounded-full text-xs font-bold"></span>
                        </div>
                        <a href="requester/add_confined_space_license.php" class="rounded bg-teal-800 px-4 py-2 text-white">+ إصدار رخصة</a>
                    </div>
                    <div class="flex flex-wrap gap-2 items-center bg-white p-3 rounded-md shadow-sm mb-4" dir="rtl">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">رقم الرخصة:</label>
                            <input type="text" id="filterPermitNo" placeholder="بحث..." class="border px-2 py-1 rounded-md text-sm">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">القسم:</label>
                            <select id="filterLocation" class="border px-2 py-1 rounded-md text-sm">
                                <option value="">الكل</option>
                                <option>كسارة</option>
                                <option>طحونة مواد</option>
                                <option>الأفران</option>
                                <option>طواحين الاسمنت</option>
                                <option>التعبئة</option>
                                <option>محطة الاسالة</option>
                                <option>أخرى</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">من:</label>
                            <input type="date" id="filterFromDate" class="border px-2 py-1 rounded-md text-sm">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">إلى:</label>
                            <input type="date" id="filterToDate" class="border px-2 py-1 rounded-md text-sm">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">الحالة:</label>
                            <select id="statusFilter" class="border px-2 py-1 rounded-md text-sm">
                                <option value="">الكل</option>
                                <option value="open">مفتوحة</option>
                                <option value="not_active">غير فعالة</option>
                                <option value="close">مغلقة</option>
                            </select>
                        </div>
                        <button id="applyFiltersBtn" class="bg-[#0b6f76] text-white px-4 py-1.5 rounded-md text-sm hover:bg-[#085a60] transition">تطبيق</button>
                        <button id="resetFilters" class="hidden text-gray-500 hover:text-gray-700 px-4 py-1.5 text-sm font-medium">مسح</button>
                    </div>
                    <div class="overflow-x-auto bg-white border border-slate-200 rounded-md">
                        <table class="w-full min-w-[800px] text-sm text-right">
                            <thead class="bg-slate-100 text-slate-700">
                                <tr>
                                    <th class="px-4 py-3">رقم الرخصة</th>
                                    <th class="px-4 py-3">تاريخ الإصدار</th>
                                    <th class="px-4 py-3">أمر العمل</th>
                                    <th class="px-4 py-3">طالب الرخصة</th>
                                    <th class="px-4 py-3">القسم</th>
                                    <th class="px-4 py-3">المخول بالإصدار</th>
                                    <th class="px-4 py-3">الحالة</th>
                                    <th class="px-4 py-3">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody id="permitRows">
                                <tr>
                                    <td colspan="8" class="p-6 text-center text-slate-500">جاري تحميل الرخص...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        const TOKEN = <?= json_encode($_COOKIE['token'] ?? '') ?>;
        const FILTERS = ['permit_no', 'location', 'from_date', 'to_date', 'status'];
        const FILTER_INPUTS = {
            permit_no: 'filterPermitNo',
            location: 'filterLocation',
            from_date: 'filterFromDate',
            to_date: 'filterToDate',
            status: 'statusFilter'
        };
        const STATUS_BADGES = {
            open: ['مفتوحة', 'bg-blue-100', 'text-blue-700'],
            not_active: ['غير فعالة', 'bg-red-100', 'text-red-700'],
            close: ['مغلقة', 'bg-green-100', 'text-green-700']
        };
        const urlParams = new URLSearchParams(window.location.search);
        const apiParams = new URLSearchParams({
            action: 'getAll'
        });
        FILTERS.forEach(key => {
            const value = urlParams.get(key);
            if (!value) return;
            apiParams.set(key, value);
            document.getElementById(FILTER_INPUTS[key]).value = value;
        });
        if (FILTERS.some(key => urlParams.get(key))) document.getElementById('resetFilters').classList.remove('hidden');
        const badgeConfig = STATUS_BADGES[urlParams.get('status')];
        if (badgeConfig) {
            const badge = document.getElementById('statusFilterBadge');
            const [text, ...classes] = badgeConfig;
            badge.textContent = text;
            badge.classList.remove('hidden');
            badge.classList.add(...classes);
        }
        document.getElementById('applyFiltersBtn').addEventListener('click', () => {
            const params = new URLSearchParams();
            FILTERS.forEach(key => {
                const value = document.getElementById(FILTER_INPUTS[key]).value.trim();
                if (value) params.set(key, value);
            });
            window.location.search = params.toString();
        });
        document.getElementById('filterPermitNo').addEventListener('keydown', event => {
            if (event.key === 'Enter') document.getElementById('applyFiltersBtn').click();
        });
        document.getElementById('resetFilters').addEventListener('click', () => {
            window.location.href = 'confined_space_permits.php';
        });
        const API_URL = `../api/requester/confined_space_permit.php?${apiParams.toString()}`;
        const tbody = document.getElementById('permitRows');

        function cell(value) {
            const td = document.createElement('td');
            td.className = 'px-4 py-3 border-t';
            td.textContent = value || '—';
            return td;
        }
        fetch(API_URL, {
                headers: {
                    'Authorization': `Bearer ${TOKEN}`
                }
            })
            .then(response => response.json())
            .then(result => {
                tbody.replaceChildren();
                if (!result.success) throw new Error(result.message || 'تعذر تحميل الرخص');
                if (!result.data.length) {
                    tbody.innerHTML = '<tr><td colspan="8" class="p-6 text-center text-slate-500">لا توجد رخص مسجلة</td></tr>';
                    return;
                }
                result.data.forEach(permit => {
                    const row = document.createElement('tr');
                    const isInactive = permit.status !== 'closed' && permit.finishing_time && new Date(permit.finishing_time.replace(' ', 'T')) < new Date();
                    const permitStatus = permit.status === 'closed' ? 'مغلقة' : (isInactive ? 'غير فعالة' : 'مفتوحة');
                    row.append(cell(permit.permit_no), cell(permit.issuing_date_time), cell(permit.wo), cell(permit.company_name), cell(permit.location), cell(permit.issuer_name), cell(permitStatus));
                    const action = document.createElement('td');
                    action.className = 'px-4 py-3 border-t';
                    const link = document.createElement('a');
                    link.href = `requester/view_confined_space_license.php?id=${encodeURIComponent(permit.id)}`;
                    link.textContent = 'عرض';
                    link.className = 'font-semibold text-teal-800 underline';
                    action.append(link);
                    row.append(action);
                    tbody.append(row);
                });
            })
            .catch(error => {
                tbody.innerHTML = `<tr><td colspan="8" class="p-6 text-center text-red-700">${String(error.message).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}</td></tr>`;
            });
    </script>
</body>

</html>