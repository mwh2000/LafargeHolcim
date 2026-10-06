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
                        <h1 class="text-2xl font-bold text-gray-800">رخص دخول الأماكن المغلقة</h1>
                        <a href="requester/add_confined_space_license.php" class="rounded bg-teal-800 px-4 py-2 text-white">+ إصدار رخصة</a>
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
        const API_URL = '../api/requester/confined_space_permit.php?action=getAll';
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