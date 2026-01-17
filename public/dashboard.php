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
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

    <title>KCML / SLV | Dashboard</title>
</head>

<body class="bg-gray-50">

    <?php renderNavbar('Dashboard'); ?>

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

                    <select id="type_category" multiple class="multi-select w-full px-3 py-2 border rounded-md">
                        <!-- الخيارات سيتم تحميلها ديناميكياً -->
                    </select>

                    <select id="incident_classfication" multiple
                        class="multi-select w-full px-4 py-3 border rounded-md">
                        <option value="FA (First aid)">FA (First aid)</option>
                        <option value="MI (Medical Injury)">MI (Medical Injury)</option>
                        <option value="LTI (Lost Time Injury)">LTI (Lost Time Injury)</option>
                        <option value="PD (Property Damage)">PD (Property Damage)</option>
                        <option value="none">None</option>
                    </select>

                    <select id="environment" multiple class="multi-select w-full px-4 py-3 border rounded-md">
                        <option value="HK">HK</option>
                        <option value="Water Pollution">Water Pollution</option>
                        <option value="Dust emissions">Dust emissions</option>
                        <option value="NCR">NCR</option>
                    </select>

                    <select id="group" multiple class="multi-select w-full px-4 py-2 border rounded-md">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="F">F</option>
                        <option value="G">G</option>
                        <option value="H">H</option>
                        <option value="I">I</option>
                        <option value="J">J</option>
                        <option value="K">K</option>
                        <option value="L">L</option>
                        <option value="M">M</option>
                    </select>


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
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
        const USER_ID = "<?= $_COOKIE['user_id'] ?? '' ?>";
        const USER_ROLE = "<?= $_COOKIE['user_type'] ?? '2' ?>"; // 1=Admin, 2=User
        const IS_ADMIN = Number(USER_ROLE) === 1; // إذا 1 → Admin

        /* ================= INSTANCES TOMSELECT ================= */
        let typeCategorySelect, incidentClassSelect, environmentSelect, groupSelect;

        /* ================= HELPERS ================= */
        function getSelectedValues(selectEl) {
            // ترجع مصفوفة القيم المحددة
            return Array.from(selectEl.selectedOptions).map(opt => opt.value).filter(v => v);
        }

        function getActionsBaseUrl(status = '') {
            let base = '../public/actions.php';
            if (!IS_ADMIN) base += '?assigned_to_me=1';
            if (status) base += (base.includes('?') ? '&' : '?') + 'status=' + status;
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
                select.innerHTML = "";

                data.data.categories.forEach(cat => {
                    const opt = document.createElement("option");
                    opt.value = cat.id;
                    opt.textContent = cat.name;
                    select.appendChild(opt);
                });

                // 🌟 تهيئة TomSelect بعد إضافة كل الخيارات
                if (typeCategorySelect) typeCategorySelect.destroy();
                typeCategorySelect = new TomSelect(select, {
                    plugins: ['remove_button'],
                    placeholder: "Select Categories",
                    maxItems: null,
                });

            } catch (e) {
                console.error("Failed to load categories", e);
            }
        }

        /* ================= INITIALIZE STATIC SELECTS ================= */
        function initStaticSelects() {
            // Incident Classification
            if (incidentClassSelect) incidentClassSelect.destroy();
            incidentClassSelect = new TomSelect("#incident_classfication", {
                plugins: ['remove_button'],
                placeholder: "Select Incident Classification",
                maxItems: null
            });

            // Environment
            if (environmentSelect) environmentSelect.destroy();
            environmentSelect = new TomSelect("#environment", {
                plugins: ['remove_button'],
                placeholder: "Select Environment",
                maxItems: null
            });

            // Group
            if (groupSelect) groupSelect.destroy();
            groupSelect = new TomSelect("#group", {
                plugins: ['remove_button'],
                placeholder: "Select Group",
                maxItems: null
            });
        }

        /* ================= LOAD STATISTICS ================= */
        async function loadStatistics() {
            try {
                const fromDate = document.getElementById("from_date").value;
                const toDate = document.getElementById("to_date").value;
                const typeCategory = getSelectedValues(document.getElementById("type_category"));
                const incident_classfication = getSelectedValues(document.getElementById("incident_classfication"));
                const environment = getSelectedValues(document.getElementById("environment"));
                const group = getSelectedValues(document.getElementById("group"));

                const params = new URLSearchParams();
                if (fromDate) params.append("from_date", fromDate);
                if (toDate) params.append("to_date", toDate);

                typeCategory.forEach(val => params.append("type_category_id[]", val));
                incident_classfication.forEach(val => params.append("incident_classfication[]", val));
                environment.forEach(val => params.append("environment[]", val));
                group.forEach(val => params.append("group[]", val));

                // Admin → يشوف الكل (لا فلترة)
                if (USER_ROLE === '3') {
                    // Manager → يشوف أكشنات فريقه
                    params.append("manager_id", USER_ID);
                } else if (!IS_ADMIN) {
                    // User عادي → يشوف أكشناته فقط
                    params.append("assigned_user_id", USER_ID);
                }


                const response = await fetch(`../api/actions.php?action=getStatistics&${params.toString()}`, {
                    headers: { "Authorization": `Bearer ${TOKEN}` }
                });
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
            } catch (err) {
                console.error(err);
                Swal.fire("Error", "Unexpected error occurred", "error");
            }
        }

        /* ================= EVENTS ================= */
        document.addEventListener("DOMContentLoaded", () => {
            loadTypeCategories();   // خيارات ديناميكية
            initStaticSelects();    // تهيئة الحقول الثابتة
            loadStatistics();

            document.getElementById("applyFilters").addEventListener("click", loadStatistics);
        });
    </script>


</body>

</html>