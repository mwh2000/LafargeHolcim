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
                </div>

                <!-- ✅ Users Table -->
                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3">Expiry Date</th>
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
        const TOKEN = "<?= $_SESSION['token'] ?? '' ?>";
        const user_id = "<?= $_SESSION['id'] ?? '' ?>";
        const BASE_API = `../../api/actions.php?action=assigned_to_me&user_id=${user_id}`;

        async function fetchActions() {
            // تجهيز URL parameters
            const params = new URLSearchParams();

            const finalUrl = `${BASE_API}&${params.toString()}`;

            try {
                const response = await fetch(finalUrl, {
                    method: "GET",
                    headers: {
                        "Authorization": `Bearer ${TOKEN}`,
                        "Accept": "application/json"
                    }
                });

                const data = await response.json();
                if (!data.success) throw new Error(data.message);

                // تأكدنا أن السيرفر يرجّع actions داخل data.data.actions
                const actions = data.data?.actions || [];
                renderActions(actions);
            } catch (error) {
                console.error("Error fetching actions:", error);
                document.getElementById('actionsTableBody').innerHTML =
                    `<tr><td colspan="5" class="text-center py-4 text-red-500">Error: ${error.message}</td></tr>`;
            }
        }

        function renderActions(actions) {
            const tableBody = document.getElementById('actionsTableBody');
            tableBody.innerHTML = "";

            if (!actions.length) {
                tableBody.innerHTML =
                    `<tr><td colspan="5" class="text-center py-4 text-gray-500">No actions found</td></tr>`;
                return;
            }



            actions.forEach(action => {
                const currentDate = new Date();
                const expiryDate = new Date(action.expiry_date);

                let statusText = action.status;

                // ✅ تحقق من الشروط
                if (action.status === 'open' && currentDate > expiryDate) {
                    statusText = 'Overdue';
                }

                tableBody.innerHTML += `
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4">${action.description}</td>
                    <td class="px-6 py-4">${action.expiry_date}</td>
                    <td class="px-6 py-4">${statusText}</td>
                    
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end space-x-2">
                            <a href="../action.php?id=${action.id}" class="text-blue-600 hover:text-blue-900">view</a>
                        </div>
                    </td>
                </tr>`;
            });
        }

        // ✅ أول تحميل للبيانات
        document.addEventListener("DOMContentLoaded", fetchActions);
    </script>



</body>

</html>