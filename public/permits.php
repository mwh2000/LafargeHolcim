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

    <title>KCML / SLV | Permits List</title>
</head>

<body class="bg-gray-50">

    <?php renderNavbar('Energy Isolation Permits'); ?>

    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('all_permits'); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">

                <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700">Permits List</h1>

                    <div class="flex flex-wrap gap-2 items-center">
                        <button id="resetFilters"
                            class="hidden text-gray-500 hover:text-gray-700 px-4 py-2 text-sm font-medium">
                            Clear Filters
                        </button>
                        <select id="statusFilter" class="border px-4 py-2 rounded-md text-sm">
                            <option value="">All Status</option>
                            <option value="open">Open</option>
                            <option value="not_active">Not Active</option>
                            <option value="close">Closed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <select id="sectionFilter" class="border px-4 py-2 rounded-md text-sm">
                            <option value="">All Sections</option>
                            <!-- Add more section options as needed -->
                        </select>
                    </div>
                </div>

                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">Equipment</th>
                                <th class="px-6 py-3">Section</th>
                                <th class="px-6 py-3">Requester</th>
                                <th class="px-6 py-3">Area Manager</th>
                                <th class="px-6 py-3">Created Date</th>
                                <th class="px-6 py-3 text-blue-600">Isolation confirmation</th>
                                <th class="px-6 py-3 text-green-600">Isolation close</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="permitsTableBody">
                            <tr>
                                <td colspan="9" class="text-center py-4 text-gray-500">Loading...</td>
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
        const API_URL = "../api/requester/energy_insulation.php";
        const API_URL_SECTIONS = "../api/admin/equipment_sections.php";

        // Fetch equipment sections for the section filter dropdown
        async function fetchSections() {
            try {
                const params = new URLSearchParams();
                params.set('action', 'all');
                const response = await fetch(`${API_URL_SECTIONS}?${params.toString()}`, {
                    headers: {
                        "Authorization": `Bearer ${TOKEN}`
                    }
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message);
                const sections = result.data.sections || [];
                const select = document.getElementById('sectionFilter');
                // Clear existing options except the first placeholder
                select.innerHTML = '<option value="">All Sections</option>';
                sections.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name;
                    select.appendChild(opt);
                });
            } catch (e) {
                console.error('Failed to load sections:', e);
            }
        }

        function getStatusText(status, effectiveStatus = null) {
            const resolved = effectiveStatus || status;
            const map = {
                'open': 'Open',
                'not_active': 'Not Active',
                'close': 'Closed',
                'active_isolation': 'Open',
                'rejected': 'Rejected',
                'completed': 'Closed'
            };
            return map[resolved] || status || 'Unknown';
        }

        function getStatusClass(status, effectiveStatus = null) {
            const resolved = effectiveStatus || status;
            const map = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'open': 'bg-blue-100 text-blue-800',
                'not_active': 'bg-red-100 text-red-800',
                'close': 'bg-green-100 text-green-800',
                'active_isolation': 'bg-blue-100 text-blue-800',
                'rejected': 'bg-red-100 text-red-800',
                'completed': 'bg-green-100 text-green-800'
            };
            return map[resolved] || 'bg-gray-100 text-gray-800';
        }

        let currentPage = 1;
        const PAGE_LIMIT = 10;

        async function fetchPermits(page = 1) {
            currentPage = page;
            const params = new URLSearchParams(window.location.search);
            params.set("action", "getAll");
            params.set("section", document.getElementById('sectionFilter').value);
            params.set("status", document.getElementById('statusFilter').value);
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

                renderPermits(result.data.licenses);
                updatePaginationControls(result.data);
            } catch (error) {
                document.getElementById('permitsTableBody').innerHTML = `
                    <tr><td colspan="9" class="text-center py-4 text-red-500">${error.message}</td></tr>`;
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

            permits.forEach(p => {
                tbody.innerHTML += `
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-medium text-gray-900">${p.equipment_name} <br> <span class="text-xs text-gray-400">${p.equipment_no}</span></td>
                        <td class="px-6 py-4">${p.section_name || '-'}</td>
                        <td class="px-6 py-4">${p.creator_name || p.requester_name}</td>
                        <td class="px-6 py-4">${p.area_manager_name || '-'}</td>
                        <td class="px-6 py-4">${p.date}</td>
                        <td class="px-6 py-4 text-xs font-semibold text-blue-800">${p.am_approved_at || '-'}</td>
                        <td class="px-6 py-4 text-xs font-semibold text-green-800">${p.isolation_removed_at || '-'}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold ${getStatusClass(p.status, p.effective_status || p.status)}">
                                ${getStatusText(p.status, p.effective_status || p.status)}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="requester/view_energy_license.php?id=${p.id}"
                               class="text-[#0b6f76] hover:underline font-medium">
                                View
                            </a>
                        </td>
                    </tr>
                `;
            });
        }

        document.getElementById('statusFilter').addEventListener('change', e => {
            const params = new URLSearchParams(window.location.search);
            if (e.target.value) params.set('status', e.target.value);
            else params.delete('status');
            // Update section filter param if present
            const sectionVal = document.getElementById('sectionFilter').value;
            if (sectionVal) params.set('section', sectionVal);
            else params.delete('section');
            window.location.search = params.toString();
        });
        document.getElementById('sectionFilter').addEventListener('change', e => {
            const params = new URLSearchParams(window.location.search);
            if (e.target.value) params.set('section', e.target.value);
            else params.delete('section');
            // Preserve status param
            const statusVal = document.getElementById('statusFilter').value;
            if (statusVal) params.set('status', statusVal);
            else params.delete('status');
            window.location.search = params.toString();
        });

        document.getElementById('resetFilters').addEventListener('click', () => {
            window.location.href = 'permits.php';
        });

        // Initialize filters and load data
        document.addEventListener("DOMContentLoaded", async () => {
            const params = new URLSearchParams(window.location.search);
            const status = params.get('status');
            const section = params.get('section');
            document.getElementById('statusFilter').value = status || '';
            document.getElementById('sectionFilter').value = section || '';
            if (status || section) {
                document.getElementById('resetFilters').classList.remove('hidden');
            }
            // Load sections first, then restore selected section and fetch permits
            await fetchSections();
            // Ensure the selected section persists after options are loaded
            document.getElementById('sectionFilter').value = section || '';
            fetchPermits();
        });
    </script>

</body>

</html>