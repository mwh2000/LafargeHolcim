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

    <title>KCML / SLV | Good Practices List</title>
</head>

<body class="bg-gray-50">

    <?php renderNavbar('Good Practices'); ?>

    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('good_practices'); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">

                <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700">Good Practices List</h1>
                </div>

                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3">Start Date</th>
                                <th class="px-6 py-3">Assigned To</th>
                                <th class="px-6 py-3">Created By</th>
                                <th class="px-6 py-3">Created Date</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="dataTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-gray-500">Loading...</td>
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
        const API_URL = "../api/requester/good_practice.php";

        let currentPage = 1;
        const PAGE_LIMIT = 10;

        async function fetchData(page = 1) {
            currentPage = page;
            const params = new URLSearchParams();
            params.set("action", "getAll");
            params.set("page", currentPage);
            params.set("limit", PAGE_LIMIT);

            try {
                const response = await fetch(`${API_URL}?${params.toString()}`, {
                    headers: { "Authorization": `Bearer ${TOKEN}` }
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                renderData(result.data.records);
                updatePaginationControls(result.data.pagination);
            } catch (error) {
                document.getElementById('dataTableBody').innerHTML = `
                    <tr><td colspan="6" class="text-center py-4 text-red-500">${error.message}</td></tr>`;
            }
        }

        function updatePaginationControls(pagination) {
            const page = pagination.current_page;
            const total_pages = pagination.total_pages;
            
            document.getElementById('pageInfo').textContent = `Page ${page} of ${total_pages || 1}`;
            
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');

            prevBtn.disabled = page <= 1;
            nextBtn.disabled = page >= total_pages;

            prevBtn.onclick = () => fetchData(page - 1);
            nextBtn.onclick = () => fetchData(page + 1);
        }

        function renderData(records) {
            const tbody = document.getElementById('dataTableBody');
            tbody.innerHTML = '';

            if (!records || !records.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-gray-400">No good practices found</td></tr>';
                return;
            }

            records.forEach(r => {
                const description = r.description.length > 50 ? r.description.substring(0, 50) + '...' : r.description;
                tbody.innerHTML += `
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-medium text-gray-900" title="${r.description}">${description}</td>
                        <td class="px-6 py-4">${r.start_date || '-'}</td>
                        <td class="px-6 py-4">${r.assigned_user_name || '-'}</td>
                        <td class="px-6 py-4">${r.created_by_name || '-'}</td>
                        <td class="px-6 py-4">${r.created_at || '-'}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="good_practice.php?id=${r.id}"
                               class="text-[#0b6f76] hover:underline font-medium">
                                View
                            </a>
                        </td>
                    </tr>
                `;
            });
        }

        document.addEventListener("DOMContentLoaded", () => {
            fetchData();
        });
    </script>

</body>

</html>
