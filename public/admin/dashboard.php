<?php
require_once '../../core/Database.php';
require_once '../../config/config.php';
require_once __DIR__ . '../../partials/sidebar.php';
require_once __DIR__ . '../../partials/navbar.php';
require_once '../helpers/authCheck.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>KCML / SLV | Dashboard</title>
</head>

<body>
    <?php renderNavbar('Dashboard', '/public/manager.php'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('dashboard'); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700">Dashboard</h1>
                </div>

                <!-- ✅ Filters -->
                <div class="bg-white p-5 rounded-lg shadow mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                    <!-- From Date -->
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">From Date</label>
                        <input type="date" id="from_date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    </div>

                    <!-- To Date -->
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">To Date</label>
                        <input type="date" id="to_date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    </div>

                    <!-- Type Category -->
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Type Category</label>
                        <select id="type_category" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <option value="">All Categories</option>
                        </select>
                    </div>

                    <!-- Apply -->
                    <div class="flex items-end">
                        <button id="applyFilters"
                            class="w-full bg-[#0b6f76] text-white px-4 py-2 rounded-md text-sm hover:bg-opacity-90">
                            Apply Filters
                        </button>
                    </div>

                </div>


                <!-- ✅ Statistics Cards -->
                <div id="statsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                </div>

            </main>
        </div>
    </div>

    <script>
        async function loadTypeCategories() {
            try {
                const TOKEN = "<?= $_SESSION['token'] ?? '' ?>";

                const res = await fetch("../../api/admin/type_categories.php", {
                    headers: { "Authorization": `Bearer ${TOKEN}` }
                });

                const data = await res.json();
                if (!data.success) return;

                const select = document.getElementById("type_category");

                // تنظيف الخيارات القديمة (اختياري لكنه مهم)
                select.innerHTML = `<option value="">All Categories</option>`;

                data.data.categories.forEach(cat => {
                    const opt = document.createElement("option");
                    opt.value = cat.id;
                    opt.textContent = cat.name;
                    select.appendChild(opt);
                });


            } catch (e) {
                console.error("Failed to load categories", e);
            }
        }
    </script>


    <script>
        async function loadStatistics() {
            try {
                const TOKEN = "<?= $_SESSION['token'] ?? '' ?>";

                const fromDate = document.getElementById("from_date").value;
                const toDate = document.getElementById("to_date").value;
                const typeCategory = document.getElementById("type_category").value;

                const params = new URLSearchParams();

                if (fromDate) params.append("from_date", fromDate);
                if (toDate) params.append("to_date", toDate);
                if (typeCategory) params.append("type_category_id", typeCategory);

                const response = await fetch(
                    `../../api/actions.php?action=getStatistics&${params.toString()}`,
                    {
                        headers: {
                            "Authorization": `Bearer ${TOKEN}`
                        }
                    }
                );

                const result = await response.json();
                if (!result.success) {
                    Swal.fire("Error", result.message || "Failed to fetch statistics", "error");
                    return;
                }

                const data = result.data;

                /* ====== CARDS ====== */
                document.getElementById("statsContainer").innerHTML = `
            <div class="bg-white shadow-md rounded-lg p-5">
                <p class="text-sm text-gray-500">Total Actions</p>
                <p class="mt-2 text-2xl font-semibold">${data.total_actions}</p>
            </div>

            <div class="bg-white shadow-md rounded-lg p-5">
                <p class="text-sm text-gray-500">Open</p>
                <p class="mt-2 text-2xl font-semibold text-orange-400">${data.open_actions}</p>
            </div>

            <div class="bg-white shadow-md rounded-lg p-5">
                <p class="text-sm text-gray-500">Closed</p>
                <p class="mt-2 text-2xl font-semibold text-green-500">${data.closed_actions}</p>
            </div>

            <div class="bg-white shadow-md rounded-lg p-5">
                <p class="text-sm text-gray-500">Overdue</p>
                <p class="mt-2 text-2xl font-semibold text-red-500">${data.override_actions}</p>
            </div>
        `;

                /* ====== ACTIONS BY TYPE ====== */
                let typesHTML = `
<div class="bg-white shadow-md rounded-lg p-5 mt-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-3">Actions by Type</h2>
    <table class="w-full text-sm text-left text-gray-500">
        <thead class="bg-gray-50 text-xs uppercase">
            <tr>
                <th class="px-6 py-3">Type</th>
                <th class="px-6 py-3">Count</th>
            </tr>
        </thead>
        <tbody>
`;

                // ✅ تحقق إذا ماكو بيانات حقيقية
                if (!data.actions_by_type || data.actions_by_type.length === 0 ||
                    data.actions_by_type.every(row => Number(row.action_count) === 0)) {

                    typesHTML += `
        <tr>
            <td colspan="2" class="px-6 py-6 text-center text-gray-400 italic">
                No actions available for the selected filters
            </td>
        </tr>
    `;
                } else {
                    data.actions_by_type.forEach(row => {
                        typesHTML += `
            <tr class="border-b">
                <td class="px-6 py-3">${row.type_name}</td>
                <td class="px-6 py-3 font-medium text-gray-700">
                    ${row.action_count}
                </td>
            </tr>
        `;
                    });
                }

                typesHTML += `
        </tbody>
    </table>
</div>
`;


                // إزالة الجدول القديم إن وجد
                document.getElementById("statsTable")?.remove();

                const wrapper = document.createElement("div");
                wrapper.id = "statsTable";
                wrapper.innerHTML = typesHTML;

                document.getElementById("statsContainer").after(wrapper);

            } catch (err) {
                console.error(err);
                Swal.fire("Error", "Unexpected error occurred", "error");
            }
        }
    </script>


    <script>
        document.getElementById("applyFilters").addEventListener("click", () => {
            loadStatistics();
        });

        // تحميل أولي
        document.addEventListener("DOMContentLoaded", () => {
            loadTypeCategories();
            loadStatistics();
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            loadTypeCategories();
            loadStatistics(); // بدون فلترة = نفس النتائج القديمة
        });
    </script>


</body>

</html>