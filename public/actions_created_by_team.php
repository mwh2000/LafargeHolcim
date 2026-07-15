<?php
require_once '../core/Database.php';
require_once '../config/config.php';

require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/partials/navbar.php';

require_once 'helpers/authCheck.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>
        LafargeHolcim | Actions
    </title>
</head>

<body class="bg-gray-50">

    <!-- ✅ Layout -->
    <?php renderNavbar('Actions', '/public/notifications.php'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('actions_created_by_team'); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700">Actions Created by Team</h1>

                    <div class="flex flex-col sm:flex-row gap-3 mb-6">
                        <select id="statusFilter" class="border px-4 py-2 rounded-md text-sm">
                            <option value="">All Status</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                            <option value="overdue">Overdue</option>
                        </select>

                        <input type="date" id="fromDate" class="border px-4 py-2 rounded-md text-sm">
                        <input type="date" id="toDate" class="border px-4 py-2 rounded-md text-sm">
                    </div>

                </div>

                <!-- ✅ Users Table -->
                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">Action</th>
                                <th class="px-6 py-3">Created by</th>
                                <th class="px-6 py-3">Due Date</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="actionsTableBody">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-gray-500">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Container -->
                <div id="paginationContainer" class="flex items-center justify-between bg-white px-4 py-3 sm:px-6 border-t mt-4 rounded-lg shadow-sm">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <button id="prevMobile" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</button>
                        <button id="nextMobile" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</button>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span id="pageStart" class="font-medium">0</span> to <span id="pageEnd" class="font-medium">0</span> of <span id="totalResults" class="font-medium">0</span> results
                            </p>
                        </div>
                        <div>
                            <nav id="paginationNav" class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                <!-- Pagination buttons will be inserted here -->
                            </nav>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        /* ================= AUTH ================= */
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
        const user_id = "<?= $_COOKIE['user_id'] ?? '' ?>";
        const BASE_API = `../api/actions.php?action=created_by_team&user_id=${user_id}`;

        /* ================= ELEMENTS ================= */
        const statusFilter = document.getElementById('statusFilter');
        const fromDate = document.getElementById('fromDate');
        const toDate = document.getElementById('toDate');
        const tableBody = document.getElementById('actionsTableBody');

        /* ================= PAGINATION STATE ================= */
        let currentPage = 1;
        const rowsPerPage = 15;
        let totalRecords = 0;

        /* ================= FETCH ================= */
        async function fetchActions(filters = {}) {

            const params = new URLSearchParams();

            if (filters.status) params.append('status', filters.status);
            if (filters.from_date) params.append('from_date', filters.from_date);
            if (filters.to_date) params.append('to_date', filters.to_date);
            params.append('page', currentPage);
            params.append('limit', rowsPerPage);

            const finalUrl = `${BASE_API}&${params.toString()}`;

            tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-gray-400">Loading...</td>
            </tr>
        `;

            try {
                const response = await fetch(finalUrl, {
                    headers: {
                        "Authorization": `Bearer ${TOKEN}`,
                        "Accept": "application/json"
                    }
                });

                const data = await response.json();
                if (!data.success) throw new Error(data.message);

                totalRecords = data.data?.total || 0;
                renderActions(data.data?.actions || []);
                renderPagination();

            } catch (error) {
                tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-red-500">
                        ${error.message}
                    </td>
                </tr>`;
            }
        }

        /* ================= RENDER ================= */
        function renderActions(actions) {

            tableBody.innerHTML = "";

            if (!actions.length) {
                tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-gray-500">
                        No actions found
                    </td>
                </tr>`;
                document.getElementById('paginationContainer').classList.add('hidden');
                return;
            }
            document.getElementById('paginationContainer').classList.remove('hidden');

            const today = new Date();

            actions.forEach(action => {

                let statusText = action.status;
                const expiryDate = new Date(action.expiry_date);

                // ✅ Overdue logic
                if (action.status === 'open' && today > expiryDate) {
                    statusText = 'Overdue';
                }

                tableBody.innerHTML += `
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4">${action.action}</td>
                    <td class="px-6 py-4">${action.created_by_name}</td>
                    <td class="px-6 py-4">${action.expiry_date}</td>
                    <td class="px-6 py-4 font-semibold">
                        ${statusText}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="action.php?id=${action.id}"
                           class="text-blue-600 hover:text-blue-900">
                            View
                        </a>
                    </td>
                </tr>`;
            });
        }

        /* ================= PAGINATION RENDER ================= */
        function renderPagination() {
            const totalPages = Math.ceil(totalRecords / rowsPerPage);
            const nav = document.getElementById('paginationNav');
            const pageStart = document.getElementById('pageStart');
            const pageEnd = document.getElementById('pageEnd');
            const totalResults = document.getElementById('totalResults');

            pageStart.textContent = totalRecords === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1;
            pageEnd.textContent = Math.min(currentPage * rowsPerPage, totalRecords);
            totalResults.textContent = totalRecords;

            let html = "";

            html += `
                <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}
                    class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="sr-only">Previous</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01.02 1.06L8.832 10l3.978 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                    </svg>
                </button>
            `;

            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, startPage + 4);
            const adjustedStart = Math.max(1, endPage - 4);

            for (let i = adjustedStart; i <= endPage; i++) {
                html += `
                    <button onclick="changePage(${i})"
                        class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ${i === currentPage ? 'z-10 bg-[#0b6f76] text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0'}">
                        ${i}
                    </button>
                `;
            }

            html += `
                <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}
                    class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="sr-only">Next</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01-.02-1.06L11.168 10 7.19 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                </button>
            `;

            nav.innerHTML = html;

            document.getElementById('prevMobile').onclick = () => changePage(currentPage - 1);
            document.getElementById('prevMobile').disabled = currentPage === 1;
            document.getElementById('nextMobile').onclick = () => changePage(currentPage + 1);
            document.getElementById('nextMobile').disabled = currentPage === totalPages || totalPages === 0;
        }

        function changePage(page) {
            const totalPages = Math.ceil(totalRecords / rowsPerPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            fetchActions(getFilters());
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        /* ================= FILTERS ================= */
        function getFilters() {
            return {
                status: statusFilter.value,
                from_date: fromDate.value,
                to_date: toDate.value
            };
        }

        statusFilter.addEventListener('change', () => {
            currentPage = 1;
            fetchActions(getFilters());
        });

        fromDate.addEventListener('change', () => {
            currentPage = 1;
            fetchActions(getFilters());
        });

        toDate.addEventListener('change', () => {
            currentPage = 1;
            fetchActions(getFilters());
        });

        /* ================= INIT ================= */
        document.addEventListener("DOMContentLoaded", () => {
            fetchActions();
        });
    </script>




</body>

</html>
