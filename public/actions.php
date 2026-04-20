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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <title>LafargeHolcim | Actions</title>
</head>

<body class="bg-gray-50">

    <?php renderNavbar('Actions', '/public/notifications.php'); ?>

    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('dashboard'); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">

                <div class="flex gap-2">
                    <select id="statusFilter" class="border px-4 py-2 rounded-md text-sm">
                        <option value="">All Status</option>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                        <option value="overdue">Overdue</option>
                    </select>

                    <button id="exportExcel"
                        class="bg-green-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700">
                        Download Excel
                    </button>
                </div>


                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3 cursor-pointer hover:bg-gray-200 transition-colors group" onclick="toggleSort('action')">
                                    <div class="flex items-center">
                                        Action
                                        <span class="ml-1 opacity-0 group-hover:opacity-100 sort-icon-action">↕</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 cursor-pointer hover:bg-gray-200 transition-colors group" onclick="toggleSort('created_by_name')">
                                    <div class="flex items-center">
                                        Created by
                                        <span class="ml-1 opacity-0 group-hover:opacity-100 sort-icon-created_by_name">↕</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 cursor-pointer hover:bg-gray-200 transition-colors group" onclick="toggleSort('group')">
                                    <div class="flex items-center">
                                        By Group
                                        <span class="ml-1 opacity-0 group-hover:opacity-100 sort-icon-group">↕</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 cursor-pointer hover:bg-gray-200 transition-colors group" onclick="toggleSort('assigned_user_name')">
                                    <div class="flex items-center">
                                        Assigned to
                                        <span class="ml-1 opacity-0 group-hover:opacity-100 sort-icon-assigned_user_name">↕</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 cursor-pointer hover:bg-gray-200 transition-colors group" onclick="toggleSort('assigned_user_group')">
                                    <div class="flex items-center">
                                        To Group
                                        <span class="ml-1 opacity-0 group-hover:opacity-100 sort-icon-assigned_user_group">↕</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 cursor-pointer hover:bg-gray-200 transition-colors group" onclick="toggleSort('visit_duration')">
                                    <div class="flex items-center">
                                        Visit duration
                                        <span class="ml-1 opacity-0 group-hover:opacity-100 sort-icon-visit_duration">↕</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 cursor-pointer hover:bg-gray-200 transition-colors group" onclick="toggleSort('description')">
                                    <div class="flex items-center">
                                        Description
                                        <span class="ml-1 opacity-0 group-hover:opacity-100 sort-icon-description">↕</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 cursor-pointer hover:bg-gray-200 transition-colors group" onclick="toggleSort('priority')">
                                    <div class="flex items-center">
                                        Priority
                                        <span class="ml-1 opacity-0 group-hover:opacity-100 sort-icon-priority">↕</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 cursor-pointer hover:bg-gray-200 transition-colors group" onclick="toggleSort('start_date')">
                                    <div class="flex items-center">
                                        Start Date
                                        <span class="ml-1 opacity-0 group-hover:opacity-100 sort-icon-start_date">↕</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 cursor-pointer hover:bg-gray-200 transition-colors group" onclick="toggleSort('expiry_date')">
                                    <div class="flex items-center">
                                        Due Date
                                        <span class="ml-1 opacity-0 group-hover:opacity-100 sort-icon-expiry_date">↕</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 cursor-pointer hover:bg-gray-200 transition-colors group" onclick="toggleSort('status')">
                                    <div class="flex items-center">
                                        Status
                                        <span class="ml-1 opacity-0 group-hover:opacity-100 sort-icon-status">↕</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="actionsTableBody">
                            <tr>
                                <td colspan="12" class="text-center py-4 text-gray-500">Loading...</td>
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
        const USER_ID = "<?= $_COOKIE['user_id'] ?? '' ?>";
        const USER_ROLE = "<?= $_COOKIE['user_type'] ?? '2' ?>"; // 1 = admin
        const IS_ADMIN = Number(USER_ROLE) === 1;

        let currentPage = 1;
        const rowsPerPage = 15;
        let totalRecords = 0;
        let sortBy = 'created_at';
        let sortOrder = 'DESC';

        /* ================= BASE API ================= */
        function getBaseApi() {
            // الادمن يرى كل الأكشنات
            if (IS_ADMIN || USER_ROLE === '6') {
                return '../api/actions.php?action=getAll';
            }

            if (USER_ROLE === '3') {
                // المدير يرى أكشنات فريقه
                return `../api/actions.php?action=getAll&manager_id=${USER_ID}`;
            }
            if (USER_ROLE === '5') {
                // مسؤول السلامة يرى أكشنات القسم
                return `../api/actions.php?action=getAll&super_manager_id=${USER_ID}`;
            }

            // المستخدم العادي يرى فقط المسند له
            return `../api/actions.php?action=assigned_to_me&user_id=${USER_ID}`;
        }

        /* ================= URL HELPERS ================= */
        function getStatusFromUrl() {
            const params = new URLSearchParams(window.location.search);
            return params.get('status') || '';
        }

        function setStatusToUrl(status) {
            const params = new URLSearchParams(window.location.search);
            status ? params.set('status', status) : params.delete('status');
            window.location.search = params.toString();
        }

        /* ================= FETCH ACTIONS ================= */
        async function fetchActions() {
            const params = new URLSearchParams(window.location.search);
            const baseApi = getBaseApi();

            // إضافة بيانات الـ Pagination و Sorting
            params.set('page', currentPage);
            params.set('limit', rowsPerPage);
            params.set('sort_by', sortBy);
            params.set('sort_order', sortOrder);

            const finalUrl = baseApi + (baseApi.includes('?') ? '&' : '?') + params.toString();

            try {
                const response = await fetch(finalUrl, {
                    headers: {
                        "Authorization": `Bearer ${TOKEN}`,
                        "Accept": "application/json"
                    }
                });

                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                totalRecords = result.data.total;
                renderActions(result.data.actions);
                renderPagination();

            } catch (error) {
                document.getElementById('actionsTableBody').innerHTML = `
                    <tr>
                        <td colspan="12" class="text-center py-4 text-red-500">
                            ${error.message}
                        </td>
                    </tr>`;
            }
        }

        /* ================= RENDER ================= */
        function renderActions(actions) {
            const tbody = document.getElementById('actionsTableBody');
            tbody.innerHTML = '';

            if (!actions || !actions.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="12" class="text-center py-4 text-gray-400">
                            No actions found
                        </td>
                    </tr>`;
                document.getElementById('paginationContainer').classList.add('hidden');
                return;
            }
            document.getElementById('paginationContainer').classList.remove('hidden');

            const today = new Date();

            actions.forEach(action => {
                let status = action.status ?? 'open';
                const expiry = new Date(action.expiry_date);

                // Overdue logic
                if (status === 'open' && expiry < today) {
                    status = 'overdue';
                }

                const statusColor = {
                    open: 'text-orange-500',
                    closed: 'text-green-600',
                    overdue: 'text-red-600'
                } [status];

                tbody.innerHTML += `
                    <tr class="border-b">
                        <td class="px-6 py-4">${action.action}</td>
                        <td class="px-6 py-4">${action.created_by_name}</td>
                        <td class="px-6 py-4">${action.group}</td>
                        <td class="px-6 py-4">${action.assigned_user_name}</td>
                        <td class="px-6 py-4">${action.assigned_user_group || '-'}</td>
                        <td class="px-6 py-4">${action.visit_duration || '-'}</td>
                        <td class="px-6 py-4">${action.description || '-'}</td>
                        <td class="px-6 py-4">${action.priority || '-'}</td>
                        <td class="px-6 py-4">${action.start_date || '-'}</td>
                        <td class="px-6 py-4">${action.expiry_date}</td>
                        <td class="px-6 py-4 font-semibold ${statusColor}">${status.toUpperCase()}</td>
                        <td class="px-6 py-4 text-right flex flex-row justify-end space-x-2">
                            <a href="action.php?id=${action.id}"
                            class="text-blue-600 hover:underline">
                            View
                            </a>
                            ${IS_ADMIN ? `
                            <a href="requester/update_action.php?id=${action.id}"
                            class="text-yellow-600 hover:underline">
                            Update
                            </a>
                            <button 
                                onclick="confirmDelete(${action.id})"
                                class="text-red-600 hover:underline">
                                Delete
                            </button>
                        ` : ``}
                        </td>
                    </tr>
                `;


            });

            updateSortIcons();
        }

        /* ================= SORTING ================= */
        function toggleSort(column) {
            // if (sortBy === column) {
            //     sortOrder = sortOrder === 'ASC' ? 'DESC' : 'ASC';
            // } else {
            //     sortBy = column;
            //     sortOrder = 'ASC';
            // }
            // currentPage = 1; // إعادة للصفحة الأولى عند التغيير
            // fetchActions();
        }

        function updateSortIcons() {
            // إخفاء كل الأيقونات أو تصفيرها
            document.querySelectorAll('[class^="sort-icon-"]').forEach(el => {
                el.innerHTML = '↕';
                el.classList.add('opacity-0');
            });

            const currentIcon = document.querySelector(`.sort-icon-${sortBy}`);
            if (currentIcon) {
                currentIcon.innerHTML = sortOrder === 'ASC' ? '↑' : '↓';
                currentIcon.classList.remove('opacity-0');
                currentIcon.classList.add('opacity-100');
            }
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

            // Previous button
            html += `
                <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} 
                    class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="sr-only">Previous</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01.02 1.06L8.832 10l3.978 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                    </svg>
                </button>
            `;

            // Page numbers
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

            // Next button
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

            // Mobile buttons
            document.getElementById('prevMobile').onclick = () => changePage(currentPage - 1);
            document.getElementById('prevMobile').disabled = currentPage === 1;
            document.getElementById('nextMobile').onclick = () => changePage(currentPage + 1);
            document.getElementById('nextMobile').disabled = currentPage === totalPages || totalPages === 0;
        }

        function changePage(page) {
            const totalPages = Math.ceil(totalRecords / rowsPerPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            fetchActions();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        async function confirmDelete(actionId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to undo this action!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('../api/requester/actions.php?id=' + actionId, {
                            method: 'DELETE',
                            headers: {
                                'Authorization': `Bearer ${TOKEN}`,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                        });

                        const result = await response.json();

                        if (!result.success) {
                            throw new Error(result.message || 'Delete failed');
                        }

                        Swal.fire(
                            'Deleted!',
                            'The action has been deleted.',
                            'success'
                        );

                        // إعادة تحميل الجدول
                        fetchActions();

                    } catch (error) {
                        Swal.fire(
                            'Error!',
                            error.message,
                            'error'
                        );
                    }
                }
            });
        }


        /* ================= INIT ================= */
        document.addEventListener("DOMContentLoaded", () => {
            const status = getStatusFromUrl();
            document.getElementById('statusFilter').value = status;
            fetchActions();
        });

        document.getElementById('exportExcel').addEventListener('click', () => {
            const params = new URLSearchParams(window.location.search);

            // احذف أي action قديم
            params.delete('action');

            // أضف exportExcel
            params.set('action', 'exportExcel');

            // تأكد من حذف بيانات الباجينيشن والترتيب لضمان تحميل الكل بنفس السلوك السابق
            params.delete('page');
            params.delete('limit');
            params.delete('sort_by');
            params.delete('sort_order');

            // حافظ على باقي الصلاحيات (manager_id, user_id, ...)
            const base = getBaseApi().split('?')[0];

            const url = base + '?' + params.toString();

            window.location.href = url;

        });

        /* ================= FILTER CHANGE ================= */
        document.getElementById('statusFilter').addEventListener('change', e => {
            setStatusToUrl(e.target.value);
        });
    </script>

</body>

</html>