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
    <title>LafargeHolcim | Equipment Sections</title>
</head>

<body class="bg-gray-50">

    <!-- ✅ Layout -->
    <?php renderNavbar('Equipment Sections'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%] flex">
        <?php renderSidebar('equipment_sections'); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700">Equipment Sections</h1>
                    <a href="add_equipment_section.php"
                        class="px-5 py-2 bg-[#0b6f76] text-white text-sm font-medium rounded-lg cursor-pointer hover:bg-[#0b6f76] hover:bg-opacity-80 transition inline-block text-center">
                        + Add Section
                    </a>
                </div>

                <!-- ✅ Filters -->
                <div class="bg-white p-4 rounded-lg shadow mb-6 flex flex-wrap gap-3">
                    <input type="text" id="searchInput" placeholder="Search by name"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full sm:w-1/3 outline-none focus:border-[#0b6f76]">
                </div>

                <!-- ✅ Sections Table -->
                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sectionsTableBody">
                            <tr>
                                <td colspan="3" class="text-center py-4 text-gray-500">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script>
        const BASE_API = "../../api/admin/equipment_sections.php?action=all";
        const DELETE_API = "../../api/admin/equipment_sections.php?action=delete";
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";

        // ================= FETCH SECTIONS =================
        async function fetchSections() {
            const search = document.getElementById('searchInput').value.trim();
            const params = new URLSearchParams();
            if (search) params.append("search", search);

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

                renderSections(data.data?.sections || []);
            } catch (error) {
                document.getElementById('sectionsTableBody').innerHTML =
                    `<tr><td colspan="3" class="text-center text-red-500 py-4">${error.message}</td></tr>`;
            }
        }

        // ================= DELETE SECTION =================
        function deleteSection(sectionId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This section will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete'
            }).then(async (result) => {
                if (!result.isConfirmed) return;

                try {
                    const response = await fetch(`${DELETE_API}&id=${sectionId}`, {
                        method: "DELETE",
                        headers: {
                            "Authorization": `Bearer ${TOKEN}`,
                            "Accept": "application/json"
                        }
                    });

                    const data = await response.json();
                    if (!data.success) throw new Error(data.message);

                    Swal.fire('Deleted!', 'Section has been deleted.', 'success');
                    fetchSections();
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            });
        }

        // ================= RENDER SECTIONS =================
        function renderSections(sections) {
            const tableBody = document.getElementById('sectionsTableBody');
            tableBody.innerHTML = "";

            if (!sections.length) {
                tableBody.innerHTML =
                    `<tr><td colspan="3" class="text-center py-4 text-gray-500">No sections found</td></tr>`;
                return;
            }

            sections.forEach(section => {
                tableBody.innerHTML += `
        <tr class="bg-white border-b hover:bg-gray-50">
            <td class="px-6 py-4 font-medium text-gray-900">${section.id}</td>
            <td class="px-6 py-4">${section.name}</td>
            <td class="px-6 py-4 text-right">
                <div class="flex justify-end space-x-2">
                    <a href="update_equipment_section.php?id=${section.id}" class="text-blue-600 hover:text-blue-900">Edit</a>
                    <button 
                        onclick="deleteSection(${section.id})"
                        class="text-red-600 hover:text-red-900">
                        Delete
                    </button>
                </div>
            </td>
        </tr>`;
            });
        }

        // ================= HELPERS =================
        function debounce(func, delay) {
            let timeout;
            return (...args) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        document.getElementById('searchInput').addEventListener('input', debounce(fetchSections, 400));
        document.addEventListener("DOMContentLoaded", fetchSections);
    </script>
</body>
</html>
