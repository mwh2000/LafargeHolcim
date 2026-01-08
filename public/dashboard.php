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
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>KCML / SLV | Dashboard</title>
</head>

<body class="bg-gray-50">

    <?php renderNavbar('Dashboard', '/public/notifications.php'); ?>

    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('dashboard'); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">

                <h1 class="text-2xl font-semibold text-gray-700 mb-6">Dashboard</h1>

                <!-- ================= FILTERS ================= -->
                <div class="bg-white p-5 rounded-lg shadow mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">From Date</label>
                        <input type="date" id="from_date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">To Date</label>
                        <input type="date" id="to_date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Type Category</label>
                        <select id="type_category" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Incident Cause</label>
                        <select id="incident_cause" name="incident_cause"
                            class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                            <option value="">Incident</option>
                            <option value="FA (First aid)">FA (First aid)</option>
                            <option value="MI (Medical Injury)">MI (Medical Injury)</option>
                            <option value="LTI (Lost Time Injury)">LTI (Lost Time Injury)</option>
                            <option value="PD (Property Damage)">PD (Property Damage)</option>
                            <option value="none">None</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Environment</label>
                        <select id="environment" name="environment"
                            class="w-full px-4 py-3 border border-gray-200 rounded-md bg-white focus:ring-1 focus:ring-[#0b6f76]">
                            <option value="">Select Environment</option>
                            <option value="H.k">H.k</option>
                            <option value="Weater">Weater</option>
                            <option value="Dustiment">Dustiment</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button id="applyFilters"
                            class="w-full bg-[#0b6f76] text-white px-4 py-2 rounded-md text-sm hover:bg-opacity-90">
                            Apply Filters
                        </button>
                    </div>

                </div>

                <!-- ================= STATS ================= -->
                <div id="statsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6"></div>

            </main>
        </div>
    </div>

    <script>
        /* ================= AUTH ================= */
        const TOKEN = "<?= $_SESSION['token'] ?? '' ?>";
        const USER_ID = "<?= $_SESSION['id'] ?? '' ?>";
        const USER_ROLE = "<?= $_SESSION['user_type'] ?? '2' ?>"; // 1=Admin, 2=User
        const IS_ADMIN = Number(USER_ROLE) === 1; // إذا 1 → Admin

        /* ================= ACTIONS URL ================= */
        function getActionsBaseUrl(status = '') {
            let base = '../public/actions.php';

            if (!IS_ADMIN) {
                // المستخدم العادي فقط
                base += '?assigned_to_me=1';
            }

            if (status) {
                base += (base.includes('?') ? '&' : '?') + 'status=' + status;
            }

            return base;
        }

        /* ================= LOAD TYPE CATEGORIES ================= */
        async function loadTypeCategories() {
            try {
                const res = await fetch("../api/admin/type_categories.php", {
                    headers: { "Authorization": `Bearer ${TOKEN}` }
                });

                const data = await res.json();
                if (!data.success) return;

                const select = document.getElementById("type_category");
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

        /* ================= LOAD STATISTICS ================= */
        async function loadStatistics() {
            try {
                const fromDate = document.getElementById("from_date").value;
                const toDate = document.getElementById("to_date").value;
                const typeCategory = document.getElementById("type_category").value;
                const incident_cause = document.getElementById("incident_cause").value;
                const environment = document.getElementById("environment").value;

                const params = new URLSearchParams();
                if (fromDate) params.append("from_date", fromDate);
                if (toDate) params.append("to_date", toDate);
                if (typeCategory) params.append("type_category_id", typeCategory);
                if (incident_cause) params.append("incident_cause", incident_cause);
                if (environment) params.append("environment", environment);

                // ✅ فلترة حسب المستخدم فقط للمستخدمين الغير ادمن
                if (!IS_ADMIN) {
                    params.append("assigned_user_id", USER_ID);
                }

                const response = await fetch(
                    `../api/actions.php?action=getStatistics&${params.toString()}`,
                    { headers: { "Authorization": `Bearer ${TOKEN}` } }
                );

                const result = await response.json();
                if (!result.success) {
                    Swal.fire("Error", result.message || "Failed to fetch statistics", "error");
                    return;
                }

                const d = result.data;

                /* ================= CARDS ================= */
                document.getElementById("statsContainer").innerHTML = `
                <div onclick="location.href='${getActionsBaseUrl()}'"
                     class="cursor-pointer bg-white shadow-md rounded-lg p-5 hover:ring">
                    <p class="text-sm text-gray-500">Total Actions</p>
                    <p class="mt-2 text-2xl font-semibold">${d.total_actions}</p>
                </div>

                <div onclick="location.href='${getActionsBaseUrl('open')}'"
                     class="cursor-pointer bg-white shadow-md rounded-lg p-5 hover:ring">
                    <p class="text-sm text-gray-500">Open</p>
                    <p class="mt-2 text-2xl font-semibold text-orange-400">${d.open_actions}</p>
                </div>

                <div onclick="location.href='${getActionsBaseUrl('closed')}'"
                     class="cursor-pointer bg-white shadow-md rounded-lg p-5 hover:ring">
                    <p class="text-sm text-gray-500">Closed</p>
                    <p class="mt-2 text-2xl font-semibold text-green-500">${d.closed_actions}</p>
                </div>

                <div onclick="location.href='${getActionsBaseUrl('overdue')}'"
                     class="cursor-pointer bg-white shadow-md rounded-lg p-5 hover:ring">
                    <p class="text-sm text-gray-500">Overdue</p>
                    <p class="mt-2 text-2xl font-semibold text-red-500">${d.override_actions}</p>
                </div>
            `;

                /* ================= ACTIONS BY TYPE ================= */
                // document.getElementById("statsTable")?.remove();

                // let html = `
                // <div id="statsTable" class="bg-white shadow-md rounded-lg p-5 mt-6">
                //     <h2 class="text-lg font-semibold text-gray-700 mb-3">Actions by Type</h2>
                //     <table class="w-full text-sm text-left text-gray-500">
                //         <thead class="bg-gray-50 text-xs uppercase">
                //         <tr>
                //             <th class="px-6 py-3">Type</th>
                //             <th class="px-6 py-3">Count</th>
                //         </tr>
                //         </thead>
                //         <tbody>`;

                // if (!d.actions_by_type || d.actions_by_type.every(r => +r.action_count === 0)) {
                //     html += `
                //     <tr>
                //         <td colspan="2"
                //             class="px-6 py-6 text-center text-gray-400 italic">
                //             No actions available for selected filters
                //         </td>
                //     </tr>`;
                // } else {
                //     d.actions_by_type.forEach(r => {
                //         html += `
                //         <tr class="border-b">
                //             <td class="px-6 py-3">${r.type_name}</td>
                //             <td class="px-6 py-3 font-medium">${r.action_count}</td>
                //         </tr>`;
                //     });
                // }

                // html += `</tbody></table></div>`;
                // document.getElementById("statsContainer").after(
                //     Object.assign(document.createElement("div"), { innerHTML: html })
                // );

            } catch (err) {
                console.error(err);
                Swal.fire("Error", "Unexpected error occurred", "error");
            }
        }

        /* ================= EVENTS ================= */
        document.getElementById("applyFilters").addEventListener("click", loadStatistics);

        document.addEventListener("DOMContentLoaded", () => {
            loadTypeCategories();
            loadStatistics();
        });
    </script>

</body>

</html>