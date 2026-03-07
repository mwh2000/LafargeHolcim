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
                                <th class="px-6 py-3">Action</th>
                                <th class="px-6 py-3">Created by</th>
                                <th class="px-6 py-3">By Group</th>
                                <th class="px-6 py-3">Assigned to</th>
                                <th class="px-6 py-3">To Group</th>
                                <th class="px-6 py-3">Visit duration</th>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3">Priority</th>
                                <th class="px-6 py-3">Start Date</th>
                                <th class="px-6 py-3">Due Date</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="actionsTableBody">
                            <tr>
                                <td colspan="4" class="text-center py-4 text-gray-500">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
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

            const finalUrl = baseApi + (params.toString() ? '&' + params.toString() : '');

            try {
                const response = await fetch(finalUrl, {
                    headers: {
                        "Authorization": `Bearer ${TOKEN}`,
                        "Accept": "application/json"
                    }
                });

                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                renderActions(result.data.actions);

            } catch (error) {
                document.getElementById('actionsTableBody').innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-4 text-red-500">
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
                        <td colspan="4" class="text-center py-4 text-gray-400">
                            No actions found
                        </td>
                    </tr>`;
                return;
            }

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