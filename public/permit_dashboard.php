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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <title>KCML / SLV | Permit Dashboard</title>
</head>

<body class="bg-gray-50">

    <?php renderNavbar('Permit Dashboard / لوحة بيانات التصريحات'); ?>

    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('permit_dashboard'); ?>

        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">

                <h1 class="text-2xl font-semibold text-gray-700 mb-6">Permit Dashboard</h1>

                <!-- ================= FILTERS ================= -->
                <div class="bg-white p-5 rounded-lg shadow mb-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                    </div>

                    <div class="flex justify-end gap-2">
                        <button id="clearFilters"
                            class="hidden text-gray-500 hover:text-gray-700 px-4 py-2 text-sm font-medium">
                            Clear Filters
                        </button>
                        <button id="applyFilters"
                            class="bg-[#0b6f76] text-white px-6 py-2 rounded-md text-sm hover:bg-opacity-90">
                            Apply Filters
                        </button>
                    </div>
                </div>

                <!-- ================= CHART ================= -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4 text-center">
                        Permits Status Overview
                    </h2>

                    <div class="flex justify-center">
                        <div class="w-72 h-72">
                            <canvas id="permitStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- ================= STATS ================= -->
                <div id="statsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Cards will be injected here -->
                </div>

            </main>
        </div>
    </div>

    <script>
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
        const API_URL = "../api/requester/energy_insulation.php";
        let permitStatusChart = null;

        function getPermitsUrl(status = '') {
            let base = 'permits.php';
            const params = new URLSearchParams();
            const fromDate = document.getElementById("from_date").value;
            const toDate = document.getElementById("to_date").value;

            if (fromDate) params.append("from_date", fromDate);
            if (toDate) params.append("to_date", toDate);
            if (status) params.append("status", status);

            return base + '?' + params.toString();
        }

        function renderStatusChart(stats) {
            const ctx = document.getElementById('permitStatusChart');
            const total = stats.pending + stats.active_isolation + stats.completed;

            const chartData = {
                labels: [
                    `Open: ${stats.pending}`,
                    `Active Isolation: ${stats.active_isolation}`,
                    `Completed: ${stats.completed}`
                ],
                datasets: [{
                    data: [stats.pending, stats.active_isolation, stats.completed],
                    backgroundColor: [
                        '#fbbf24', // yellow
                        '#3b82f6', // blue
                        '#10b981'  // green
                    ],
                    hoverOffset: 6
                }]
            };

            if (permitStatusChart) {
                permitStatusChart.data = chartData;
                permitStatusChart.update();
                return;
            }

            permitStatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        async function loadStatistics() {
            try {
                const fromDate = document.getElementById("from_date").value;
                const toDate = document.getElementById("to_date").value;

                const params = new URLSearchParams();
                params.append("action", "getStatistics");
                if (fromDate) params.append("from_date", fromDate);
                if (toDate) params.append("to_date", toDate);

                const res = await fetch(`${API_URL}?${params.toString()}`, {
                    headers: { "Authorization": `Bearer ${TOKEN}` }
                });
                const result = await res.json();
                if (!result.success) return;

                const d = result.data;
                renderStatusChart(d);

                document.getElementById("statsContainer").innerHTML = `
                    <div onclick="location.href='${getPermitsUrl()}'"
                         class="cursor-pointer bg-white shadow-md rounded-lg p-5 border-l-4 border-gray-400 hover:shadow-lg transition">
                        <p class="text-sm text-gray-500">Total Permits</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-700">${d.total}</p>
                    </div>

                    <div onclick="location.href='${getPermitsUrl('pending')}'"
                         class="cursor-pointer bg-white shadow-md rounded-lg p-5 border-l-4 border-yellow-400 hover:shadow-lg transition">
                        <p class="text-sm text-gray-500">Open</p>
                        <p class="mt-2 text-2xl font-semibold text-yellow-600">${d.pending}</p>
                    </div>

                    <div onclick="location.href='${getPermitsUrl('active_isolation')}'"
                         class="cursor-pointer bg-white shadow-md rounded-lg p-5 border-l-4 border-blue-400 hover:shadow-lg transition">
                        <p class="text-sm text-gray-500">Active Isolation</p>
                        <p class="mt-2 text-2xl font-semibold text-blue-600">${d.active_isolation}</p>
                    </div>

                    <div onclick="location.href='${getPermitsUrl('completed')}'"
                         class="cursor-pointer bg-white shadow-md rounded-lg p-5 border-l-4 border-green-400 hover:shadow-lg transition">
                        <p class="text-sm text-gray-500">Completed</p>
                        <p class="mt-2 text-2xl font-semibold text-green-600">${d.completed}</p>
                    </div>
                `;

                // Toggle Clear Button
                const hasFilters = fromDate || toDate;
                document.getElementById("clearFilters").classList.toggle("hidden", !hasFilters);

            } catch (err) {
                console.error(err);
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            loadStatistics();
            document.getElementById("applyFilters").addEventListener("click", loadStatistics);
            document.getElementById("clearFilters").addEventListener("click", () => {
                document.getElementById("from_date").value = "";
                document.getElementById("to_date").value = "";
                loadStatistics();
            });
        });
    </script>

</body>

</html>
