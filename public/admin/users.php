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
    <title>
        LafargeHolcim | Users
    </title>
</head>

<body class="bg-gray-50">

    <!-- ✅ Layout -->
    <?php renderNavbar('Users'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('users'); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700">Users</h1>
                    <a href="add_user.php"
                        class="px-5 py-2 bg-[#0b6f76] text-white text-sm font-medium rounded-lg cursor-pointer hover:bg-[#0b6f76] hover:bg-opacity-80 transition">
                        + Add User
                    </a>
                </div>

                <!-- ✅ Filters -->
                <div class="bg-white p-4 rounded-lg shadow mb-6 flex flex-wrap gap-3">
                    <input type="text" id="searchInput" placeholder="Search by name or email"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full sm:w-1/3">

                    <select id="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All Statuses</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <!-- ✅ Users Table -->
                <div class="bg-white shadow-md rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">Department</th>
                                <th class="px-6 py-3">Manager email</th>
                                <th class="px-6 py-3">Role</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
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
        const BASE_API = "../../api/admin/users.php?action=all";
        const DELETE_API = "../../api/admin/users.php?action=delete";
        const TOKEN = "<?= $_SESSION['token'] ?? '' ?>";

        // ================= FETCH USERS =================
        async function fetchUsers() {
            const search = document.getElementById('searchInput').value.trim();
            const status = document.getElementById('statusFilter').value;

            const params = new URLSearchParams();
            if (search) params.append("search", search);
            if (status === "1") params.append("is_active", 1);
            if (status === "0") params.append("is_active", 0);

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

                renderUsers(data.data?.users || []);
            } catch (error) {
                document.getElementById('usersTableBody').innerHTML =
                    `<tr><td colspan="6" class="text-center text-red-500 py-4">${error.message}</td></tr>`;
            }
        }

        // ================= DELETE USER =================
        function deleteUser(userId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This user will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete'
            }).then(async (result) => {
                if (!result.isConfirmed) return;

                try {
                    const response = await fetch(`${DELETE_API}&id=${userId}`, {
                        method: "DELETE",
                        headers: {
                            "Authorization": `Bearer ${TOKEN}`,
                            "Accept": "application/json"
                        }
                    });

                    const data = await response.json();
                    if (!data.success) throw new Error(data.message);

                    Swal.fire('Deleted!', 'User has been deleted.', 'success');
                    fetchUsers(); // تحديث بدون ريفرش
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            });
        }

        // ================= RENDER USERS =================
        function renderUsers(users) {
            const tableBody = document.getElementById('usersTableBody');
            tableBody.innerHTML = "";

            if (!users.length) {
                tableBody.innerHTML =
                    `<tr><td colspan="6" class="text-center py-4 text-gray-500">No users found</td></tr>`;
                return;
            }

            users.forEach(user => {
                tableBody.innerHTML += `
        <tr class="bg-white border-b hover:bg-gray-50">
            <td class="px-6 py-4">${user.name}</td>
            <td class="px-6 py-4">${user.email}</td>
            <td class="px-6 py-4">${user.department ?? '-'}</td>
            <td class="px-6 py-4">${user.manager_email ?? '-'}</td>
            <td class="px-6 py-4">${user.role_name}</td>
            <td class="px-6 py-4 text-right">
                <div class="flex justify-end space-x-2">
                    <button onclick="window.location.href='update_user.php?id=${user.id}'" class="text-blue-600 hover:text-blue-900">Edit</button>
                    <button 
                        onclick="deleteUser(${user.id})"
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

        document.getElementById('searchInput').addEventListener('input', debounce(fetchUsers, 400));
        document.getElementById('statusFilter').addEventListener('change', fetchUsers);
        document.addEventListener("DOMContentLoaded", fetchUsers);
    </script>

</body>

</html>