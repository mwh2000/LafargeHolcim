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
    <?php renderNavbar('Actions', '/public/manager.php'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('actions_assigned_to_me'); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700">Actions</h1>

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
                                <th class="px-6 py-3">Description</th>
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
            </main>
        </div>
    </div>

    <script>
        /* ================= AUTH ================= */
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
        const user_id = "<?= $_COOKIE['user_id'] ?? '' ?>";
        const BASE_API = `../api/actions.php?action=assigned_to_me&user_id=${user_id}`;

        /* ================= ELEMENTS ================= */
        const statusFilter = document.getElementById('statusFilter');
        const fromDate = document.getElementById('fromDate');
        const toDate = document.getElementById('toDate');
        const tableBody = document.getElementById('actionsTableBody');

        /* ================= FETCH ================= */
        async function fetchActions(filters = {}) {

            const params = new URLSearchParams();

            if (filters.status) params.append('status', filters.status);
            if (filters.from_date) params.append('from_date', filters.from_date);
            if (filters.to_date) params.append('to_date', filters.to_date);

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

                renderActions(data.data?.actions || []);

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
                return;
            }

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
                    <td class="px-6 py-4">${action.description}</td>
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

        /* ================= FILTERS ================= */
        function getFilters() {
            return {
                status: statusFilter.value,
                from_date: fromDate.value,
                to_date: toDate.value
            };
        }

        statusFilter.addEventListener('change', () => {
            fetchActions(getFilters());
        });

        fromDate.addEventListener('change', () => {
            fetchActions(getFilters());
        });

        toDate.addEventListener('change', () => {
            fetchActions(getFilters());
        });

        /* ================= INIT ================= */
        document.addEventListener("DOMContentLoaded", () => {
            fetchActions();
        });
    </script>




</body>

</html>