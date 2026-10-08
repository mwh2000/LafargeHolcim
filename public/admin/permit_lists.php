<?php
require_once '../../core/Database.php';
require_once '../../config/config.php';
require_once '../../controllers/PermitListController.php';

require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/navbar.php';

require_once '../helpers/authCheck.php';

$listKey = $_GET['list'] ?? 'confined_space_equipment';
if (!isset(PermitListController::LISTS[$listKey])) $listKey = 'confined_space_equipment';
$list = PermitListController::LISTS[$listKey];
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <title>LafargeHolcim | <?= htmlspecialchars($list['label']) ?></title>
</head>

<body class="bg-gray-50">
    <?php renderNavbar('Permit Lists'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%] flex">
        <?php renderSidebar('permit_list_' . $listKey); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12" dir="rtl">
                <div class="flex flex-wrap gap-2 mb-6">
                    <?php foreach (PermitListController::LISTS as $key => $item): ?>
                        <a href="?list=<?= $key ?>"
                            class="px-4 py-2 rounded-lg text-sm border <?= $key === $listKey ? 'bg-[#0b6f76] text-white border-[#0b6f76]' : 'bg-white text-[#0b6f76] border-[#0b6f76]' ?>">
                            <?= htmlspecialchars($item['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700"><?= htmlspecialchars($list['label']) ?></h1>
                    <button type="button" id="addItemButton"
                        class="px-5 py-2 bg-[#0b6f76] text-white text-sm font-medium rounded-lg hover:bg-opacity-80 transition">
                        + إضافة
                    </button>
                </div>

                <div class="bg-white p-4 rounded-lg shadow mb-6">
                    <input type="text" id="searchInput" placeholder="بحث بالاسم"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full sm:w-1/3 outline-none focus:border-[#0b6f76]">
                </div>

                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm text-right text-gray-600">
                        <thead class="bg-gray-100 text-gray-700 text-xs">
                            <tr>
                                <th class="px-6 py-3">#</th>
                                <th class="px-6 py-3">الاسم</th>
                                <?php if ($list['has_inspection_date']): ?>
                                    <th class="px-6 py-3">تاريخ الفحص</th>
                                    <th class="px-6 py-3">الحالة</th>
                                <?php endif; ?>
                                <th class="px-6 py-3 text-left">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-gray-500">جاري التحميل...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <div id="itemModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 p-4" dir="rtl">
        <form id="itemForm" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl space-y-4">
            <h2 id="itemModalTitle" class="text-lg font-bold text-gray-800"></h2>
            <label class="block text-sm font-medium text-gray-700">الاسم
                <input name="name" required class="mt-2 w-full rounded border border-gray-300 p-2">
            </label>
            <?php if ($list['has_inspection_date']): ?>
                <label class="block text-sm font-medium text-gray-700">تاريخ آخر فحص
                    <input name="inspection_date" type="date" class="mt-2 w-full rounded border border-gray-300 p-2">
                    <span class="mt-1 block text-xs text-gray-500">ينتهي الفحص بعد 18 شهراً. اتركه فارغاً إذا لم يكن هناك تاريخ فحص.</span>
                </label>
            <?php endif; ?>
            <div class="flex justify-end gap-2">
                <button type="button" id="cancelItemButton" class="rounded border border-gray-300 px-4 py-2 text-gray-700">إلغاء</button>
                <button type="submit" class="rounded bg-[#0b6f76] px-4 py-2 text-white">حفظ</button>
            </div>
        </form>
    </div>

    <script>
        const LIST_KEY = <?= json_encode($listKey) ?>;
        const HAS_INSPECTION_DATE = <?= json_encode($list['has_inspection_date']) ?>;
        const API = action => `../../api/admin/permit_lists.php?list=${LIST_KEY}&action=${action}`;
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
        const modal = document.getElementById('itemModal');
        const form = document.getElementById('itemForm');
        let items = [];
        let editingId = null;

        const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        } [c]));

        async function request(url, options = {}) {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Authorization': `Bearer ${TOKEN}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            const data = await response.json();
            if (!data.success) throw new Error(data.message);
            return data;
        }

        function isExpired(date) {
            if (!date) return false;
            const expiry = new Date(`${date}T00:00:00`);
            expiry.setMonth(expiry.getMonth() + 18);
            return new Date() > expiry;
        }

        async function fetchItems() {
            const search = document.getElementById('searchInput').value.trim();
            const tableBody = document.getElementById('itemsTableBody');
            try {
                const data = await request(`${API('all')}&search=${encodeURIComponent(search)}`);
                items = data.data?.items || [];
                if (!items.length) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">لا توجد عناصر</td></tr>';
                    return;
                }
                tableBody.innerHTML = items.map((item, index) => {
                    const inspection = HAS_INSPECTION_DATE ? `<td class="px-6 py-4">${escapeHtml(item.inspection_date || '—')}</td><td class="px-6 py-4">${isExpired(item.inspection_date) ? '<span class="text-red-600 font-medium">منتهي الفحص</span>' : '<span class="text-green-700">ساري</span>'}</td>` : '';
                    return `<tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 text-gray-900">${index + 1}</td>
                        <td class="px-6 py-4">${escapeHtml(item.name)}</td>
                        ${inspection}
                        <td class="px-6 py-4 text-left">
                            <button type="button" onclick="openModal(${item.id})" class="text-blue-600 hover:text-blue-900 ml-3">تعديل</button>
                            <button type="button" onclick="deleteItem(${item.id})" class="text-red-600 hover:text-red-900">حذف</button>
                        </td>
                    </tr>`;
                }).join('');
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-red-500 py-4">${escapeHtml(error.message)}</td></tr>`;
            }
        }

        function openModal(id = null) {
            editingId = id;
            const item = items.find(row => Number(row.id) === id) || {};
            document.getElementById('itemModalTitle').textContent = id ? 'تعديل' : 'إضافة جديد';
            form.elements.name.value = item.name || '';
            if (HAS_INSPECTION_DATE) form.elements.inspection_date.value = item.inspection_date || '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            form.elements.name.focus();
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function deleteItem(id) {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'سيتم حذف هذا العنصر نهائياً',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'نعم، احذف',
                cancelButtonText: 'إلغاء'
            }).then(async result => {
                if (!result.isConfirmed) return;
                try {
                    await request(`${API('delete')}&id=${id}`, {
                        method: 'DELETE'
                    });
                    Swal.fire('تم الحذف', '', 'success');
                    fetchItems();
                } catch (error) {
                    Swal.fire('خطأ', error.message, 'error');
                }
            });
        }

        form.addEventListener('submit', async event => {
            event.preventDefault();
            const payload = Object.fromEntries(new FormData(form));
            try {
                await request(editingId ? `${API('update')}&id=${editingId}` : API('create'), {
                    method: editingId ? 'PUT' : 'POST',
                    body: JSON.stringify(payload)
                });
                closeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'تم الحفظ',
                    timer: 1200,
                    showConfirmButton: false
                });
                fetchItems();
            } catch (error) {
                Swal.fire('خطأ', error.message, 'error');
            }
        });

        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(fetchItems, 400);
        });
        document.getElementById('addItemButton').addEventListener('click', () => openModal());
        document.getElementById('cancelItemButton').addEventListener('click', closeModal);
        document.addEventListener('DOMContentLoaded', fetchItems);
    </script>
</body>

</html>
