<?php
require_once '../../core/Database.php';
require_once '../../config/config.php';

require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/navbar.php';

require_once '../helpers/authCheck.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <title>LafargeHolcim | Equipments</title>
</head>

<body class="bg-gray-50">

    <!-- ✅ Layout -->
    <?php renderNavbar('Equipments'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%] flex">
        <?php renderSidebar('equipments'); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700">Equipments</h1>
                    <a href="add_equipment.php"
                        class="px-5 py-2 bg-[#0b6f76] text-white text-sm font-medium rounded-lg cursor-pointer hover:bg-[#0b6f76] hover:bg-opacity-80 transition inline-block text-center">
                        + Add Equipment
                    </a>
                </div>

                <!-- ✅ Filters -->
                <div class="bg-white p-4 rounded-lg shadow mb-6 flex flex-wrap gap-3">
                    <input type="text" id="searchInput" placeholder="Search by name"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full sm:w-1/3 outline-none focus:border-[#0b6f76]">
                    
                    <select id="sectionFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#0b6f76]">
                        <option value="">All Sections</option>
                    </select>
                </div>

                <!-- ✅ Equipments Table -->
                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Section</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="equipmentsTableBody">
                            <tr>
                                <td colspan="4" class="text-center py-4 text-gray-500">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ✅ Pagination -->
                <div id="paginationContainer" class="mt-6 flex justify-between items-center bg-white p-4 rounded-lg shadow">
                    <span id="pageInfo" class="text-sm text-gray-600">Showing 1-10 of 0</span>
                    <div class="flex gap-2">
                        <button id="prevBtn" onclick="changePage(currentPage - 1)" 
                            class="px-4 py-2 border rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition">
                            Previous
                        </button>
                        <button id="nextBtn" onclick="changePage(currentPage + 1)" 
                            class="px-4 py-2 border rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition">
                            Next
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const BASE_API = "../../api/admin/equipments.php?action=all";
        const DELETE_API = "../../api/admin/equipments.php?action=delete";
        const SECTIONS_API = "../../api/admin/equipment_sections.php?action=all";
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";

        let currentPage = 1;
        const itemsPerPage = 10;
        let totalItems = 0;

        // ================= FETCH SECTIONS =================
        async function fetchSections() {
            try {
                const response = await fetch(SECTIONS_API, {
                    headers: {
                        "Authorization": `Bearer ${TOKEN}`,
                        "Accept": "application/json"
                    }
                });
                const data = await response.json();
                
                if (data.success && data.data?.sections) {
                    const select = document.getElementById('sectionFilter');
                    data.data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.id;
                        option.textContent = section.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error("Error fetching sections", error);
            }
        }

        // ================= FETCH EQUIPMENTS =================
        async function fetchEquipments() {
            const search = document.getElementById('searchInput').value.trim();
            const sectionId = document.getElementById('sectionFilter').value;
            
            const params = new URLSearchParams();
            if (search) params.append("search", search);
            if (sectionId) params.append("section_id", sectionId);
            params.append("page", currentPage);
            params.append("limit", itemsPerPage);

            const finalUrl = `${BASE_API}&${params.toString()}`;

            try {
                const response = await fetch(finalUrl, {
                    headers: {
                        "Authorization": `Bearer ${TOKEN}`,
                        "Accept": "application/json"
                    }
                });

                const data = await response.json();
                if (!data.success) throw new Error(data.message);

                totalItems = data.data?.total || 0;
                renderEquipments(data.data?.equipments || []);
                renderPagination();
            } catch (error) {
                document.getElementById('equipmentsTableBody').innerHTML =
                    `<tr><td colspan="4" class="text-center text-red-500 py-4">${error.message}</td></tr>`;
            }
        }

        // ================= DELETE EQUIPMENT =================
        function deleteEquipment(equipmentId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This equipment will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete'
            }).then(async (result) => {
                if (!result.isConfirmed) return;

                try {
                    const response = await fetch(`${DELETE_API}&id=${equipmentId}`, {
                        method: "DELETE",
                        headers: {
                            "Authorization": `Bearer ${TOKEN}`,
                            "Accept": "application/json"
                        }
                    });

                    const data = await response.json();
                    if (!data.success) throw new Error(data.message);

                    Swal.fire('Deleted!', 'Equipment has been deleted.', 'success');
                    fetchEquipments();
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            });
        }

        // ================= RENDER EQUIPMENTS =================
        function renderEquipments(equipments) {
            const tableBody = document.getElementById('equipmentsTableBody');
            tableBody.innerHTML = "";

            if (!equipments.length) {
                tableBody.innerHTML =
                    `<tr><td colspan="4" class="text-center py-4 text-gray-500">No equipments found</td></tr>`;
                return;
            }

            equipments.forEach(item => {
                tableBody.innerHTML += `
        <tr class="bg-white border-b hover:bg-gray-50">
            <td class="px-6 py-4 font-medium text-gray-900">${item.id}</td>
            <td class="px-6 py-4">${item.name}</td>
            <td class="px-6 py-4">${item.section_name ?? '-'}</td>
            <td class="px-6 py-4 text-right">
                <div class="flex justify-end space-x-2">
                    <a href="update_equipment.php?id=${item.id}" class="text-blue-600 hover:text-blue-900">Edit</a>
                    <button 
                        onclick="deleteEquipment(${item.id})"
                        class="text-red-600 hover:text-red-900">
                        Delete
                    </button>
                </div>
            </td>
        </tr>`;
            });
        }

        // ================= RENDER PAGINATION =================
        function renderPagination() {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            const start = (currentPage - 1) * itemsPerPage + 1;
            const end = Math.min(currentPage * itemsPerPage, totalItems);

            document.getElementById('pageInfo').textContent = 
                totalItems > 0 ? `Showing ${start}-${end} of ${totalItems}` : "No equipments to show";

            document.getElementById('prevBtn').disabled = currentPage <= 1;
            document.getElementById('nextBtn').disabled = currentPage >= totalPages;
        }

        function changePage(page) {
            currentPage = page;
            fetchEquipments();
        }

        // ================= INIT =================
        function debounce(func, delay) {
            let timeout;
            return (...args) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        const debouncedFetch = debounce(() => {
            currentPage = 1;
            fetchEquipments();
        }, 400);

        document.getElementById('searchInput').addEventListener('input', debouncedFetch);

        document.getElementById('sectionFilter').addEventListener('change', () => {
            currentPage = 1;
            fetchEquipments();
        });
        
        document.addEventListener("DOMContentLoaded", () => {
            fetchSections();
            fetchEquipments();
        });
    </script>
</body>
</html>
