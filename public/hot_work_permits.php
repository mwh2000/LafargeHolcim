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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <title>KCML / SLV | Hot Work Permits List</title>
</head>

<body class="bg-gray-50">

    <?php renderNavbar('Hot Work Permits'); ?>

    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('all_permits'); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">

                <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold text-gray-700">Hot Work Permits List</h1>
                        <span id="statusFilterBadge" class="hidden px-3 py-1 rounded-full text-xs font-bold"></span>
                    </div>

                    <div class="flex flex-wrap gap-2 items-center bg-white p-3 rounded-md shadow-sm">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">Permit No:</label>
                            <input type="text" id="filterPermitNo" placeholder="Search..." class="border px-2 py-1 rounded-md text-sm">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">Location:</label>
                            <select id="filterLocation" class="border px-2 py-1 rounded-md text-sm">
                                <option value="">All</option>
                                <option value="كسارة">كسارة</option>
                                <option value="طحونة مواد">طحونة مواد</option>
                                <option value="الافران">الافران</option>
                                <option value="طواحين الاسمنت">طواحين الاسمنت</option>
                                <option value="التعبئة">التعبئة</option>
                                <option value="محطة الاساله">محطة الاساله</option>
                                <option value="اخرى">اخرى</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">From:</label>
                            <input type="date" id="filterFromDate" class="border px-2 py-1 rounded-md text-sm">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">To:</label>
                            <input type="date" id="filterToDate" class="border px-2 py-1 rounded-md text-sm">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">Status:</label>
                            <select id="statusFilter" class="border px-2 py-1 rounded-md text-sm">
                                <option value="">All</option>
                                <option value="open">Open</option>
                                <option value="not_active">Not Active</option>
                                <option value="close">Close</option>
                            </select>
                        </div>
                        <button id="applyFiltersBtn" class="bg-[#0b6f76] text-white px-4 py-1.5 rounded-md text-sm hover:bg-[#085a60] transition">Apply</button>
                        <button id="resetFilters" class="hidden text-gray-500 hover:text-gray-700 px-4 py-1.5 text-sm font-medium">Clear</button>
                    </div>
                </div>

                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">Permit No</th>
                                <th class="px-6 py-3">Issuing Date</th>
                                <th class="px-6 py-3">Finishing Time</th>
                                <th class="px-6 py-3">Requester Name</th>
                                <th class="px-6 py-3">Location</th>
                                <th class="px-6 py-3">Issuer</th>
                                <th class="px-6 py-3">Assigned To</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="permitsTableBody">
                            <tr>
                                <td colspan="8" class="text-center py-4 text-gray-500">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Component -->
                <div id="pagination" class="flex justify-between items-center bg-white p-4 mt-4 rounded-lg shadow-sm">
                    <span id="pageInfo" class="text-sm text-gray-500">Page 1 of 1</span>
                    <div class="flex gap-2">
                        <button id="prevPage" disabled class="px-4 py-2 border rounded-md text-sm hover:bg-gray-50 disabled:opacity-50 transition">Previous</button>
                        <button id="nextPage" disabled class="px-4 py-2 border rounded-md text-sm hover:bg-gray-50 disabled:opacity-50 transition">Next</button>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
        const API_URL = "../api/requester/hot_work_permit.php";

        let currentPage = 1;
        const PAGE_LIMIT = 10;

        async function fetchPermits(page = 1) {
            currentPage = page;
            const params = new URLSearchParams(window.location.search);
            params.set("action", "getAll");
            params.set("page", currentPage);
            params.set("limit", PAGE_LIMIT);

            try {
                const response = await fetch(`${API_URL}?${params.toString()}`, {
                    headers: {
                        "Authorization": `Bearer ${TOKEN}`
                    }
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                renderPermits(result.data.permits);
                updatePaginationControls(result.data);
            } catch (error) {
                document.getElementById('permitsTableBody').innerHTML = `
                    <tr><td colspan="8" class="text-center py-4 text-red-500">${error.message}</td></tr>`;
            }
        }

        function updatePaginationControls(data) {
            const {
                page,
                total_pages
            } = data;
            document.getElementById('pageInfo').textContent = `Page ${page} of ${total_pages || 1}`;

            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');

            prevBtn.disabled = page <= 1;
            nextBtn.disabled = page >= total_pages;

            prevBtn.onclick = () => fetchPermits(page - 1);
            nextBtn.onclick = () => fetchPermits(page + 1);
        }

        function renderPermits(permits) {
            const tbody = document.getElementById('permitsTableBody');
            tbody.innerHTML = '';

            if (!permits || !permits.length) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-gray-400">No permits found</td></tr>';
                return;
            }

            const now = new Date();
            permits.forEach(p => {
                const isClosed = !!p.done_at && (!p.finishing_time || new Date(p.done_at) <= new Date(p.finishing_time));
                const isNotActive = !isClosed && p.finishing_time && new Date(p.finishing_time) < now;
                const statusChip = isClosed ?
                    `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700">Close</span>` :
                    (isNotActive ?
                        `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">Not Active</span>` :
                        `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">Open</span>`);

                tbody.innerHTML += `
                    <tr class="border-b hover:bg-gray-50 transition ${isNotActive ? 'bg-red-50/30' : ''}">
                    <td class="px-6 py-4 font-bold text-gray-900">${p.permit_no}</td>
                    <td class="px-6 py-4">${p.issuing_date_time || '-'}</td>
                    <td class="px-6 py-4">${p.finishing_time || '-'}</td>
                    <td class="px-6 py-4">${p.company_name}</td>
                    <td class="px-6 py-4">${p.location}</td>
                    <td class="px-6 py-4">${p.creator_name || '-'}</td>
                    <td class="px-6 py-4">${p.assigned_to_name || '-'}</td>
                    
                    <td class="px-6 py-4 whitespace-nowrap">${statusChip}</td>
                    
                    <td class="px-6 py-4 text-right">
                        <a href="requester/view_hot_work_license.php?id=${p.id}"
                        class="text-[#0b6f76] hover:underline font-medium">
                            View
                        </a>
                    </td>
                </tr>
                `;
            });
        }

        document.getElementById('filterPermitNo').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') document.getElementById('applyFiltersBtn').click();
        });

        document.getElementById('applyFiltersBtn').addEventListener('click', () => {
            const params = new URLSearchParams(window.location.search);
            const permitNo = document.getElementById('filterPermitNo').value.trim();
            const location = document.getElementById('filterLocation').value;
            const fromDate = document.getElementById('filterFromDate').value;
            const toDate = document.getElementById('filterToDate').value;
            const status = document.getElementById('statusFilter').value;

            if (permitNo) params.set('permit_no', permitNo);
            else params.delete('permit_no');
            if (location) params.set('location', location);
            else params.delete('location');
            if (fromDate) params.set('from_date', fromDate);
            else params.delete('from_date');
            if (toDate) params.set('to_date', toDate);
            else params.delete('to_date');
            if (status) params.set('status', status);
            else params.delete('status');

            window.location.search = params.toString();
        });

        document.getElementById('resetFilters').addEventListener('click', () => {
            window.location.href = 'hot_work_permits.php';
        });

        document.addEventListener("DOMContentLoaded", () => {
            const params = new URLSearchParams(window.location.search);
            const permitNo = params.get('permit_no');
            const location = params.get('location');
            const fromDate = params.get('from_date');
            const toDate = params.get('to_date');
            const status = params.get('status');

            if (permitNo) document.getElementById('filterPermitNo').value = permitNo;
            if (location) document.getElementById('filterLocation').value = location;
            if (fromDate) document.getElementById('filterFromDate').value = fromDate;
            if (toDate) document.getElementById('filterToDate').value = toDate;
            if (status) document.getElementById('statusFilter').value = status;

            if (permitNo || location || fromDate || toDate || status) {
                document.getElementById('resetFilters').classList.remove('hidden');
            }

            if (status) {
                const badge = document.getElementById('statusFilterBadge');
                badge.classList.remove('hidden');
                if (status === 'open') {
                    badge.textContent = 'Open';
                    badge.classList.add('bg-blue-100', 'text-blue-700');
                } else if (status === 'not_active') {
                    badge.textContent = 'Not Active';
                    badge.classList.add('bg-red-100', 'text-red-700');
                } else if (status === 'close') {
                    badge.textContent = 'Close';
                    badge.classList.add('bg-green-100', 'text-green-700');
                }
            }

            fetchPermits();
        });
    </script>

</body>

</html>