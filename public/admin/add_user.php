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
    <title>
        KCML / SLV | Create User
    </title>
</head>

<body class="bg-gray-50">

    <!-- ✅ Layout -->
    <?php renderNavbar('Add User', '/public/manager.php'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('users'); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700">Add User</h1>
                </div>

                <!-- ✅ Filters -->
                <!-- Filters / Add User Form -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6 mx-auto">
                    <form id="filtersForm" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        onsubmit="return false;">

                        <!-- Name -->
                        <div class="flex flex-col">
                            <label for="Name" class="text-sm text-gray-600 mb-1">Name</label>
                            <input id="Name" type="text" required
                                class="w-full px-4 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0b6f76] transition" />
                        </div>

                        <!-- Email -->
                        <div class="flex flex-col">
                            <label for="Email" class="text-sm text-gray-600 mb-1">Email</label>
                            <input id="Email" type="email" required
                                class="w-full px-4 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0b6f76] transition" />
                        </div>

                        <!-- Password -->
                        <div class="flex flex-col">
                            <label for="Password" class="text-sm text-gray-600 mb-1">Password</label>
                            <input id="Password" type="password" required
                                class="w-full px-4 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0b6f76] transition" />
                        </div>

                        <!-- Phone -->
                        <div class="flex flex-col">
                            <label for="Phone" class="text-sm text-gray-600 mb-1">Phone</label>
                            <input id="Phone" type="tel"
                                class="w-full px-4 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0b6f76] transition" />
                        </div>

                        <!-- Department -->
                        <div class="flex flex-col">
                            <label for="Department" class="text-sm text-gray-600 mb-1">Department</label>
                            <input id="Department"
                                class="w-full px-4 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0b6f76] transition" />
                        </div>

                        <!-- Manager user (select populated by JS) -->
                        <div class="flex flex-col">
                            <label for="usersList" class="text-sm text-gray-600 mb-1">Manager (optional)</label>
                            <!-- The JS expects an element with id="usersList" to append options to.
                                 We also keep a hidden #manager input so existing submission logic reading #manager.value still works. -->
                            <select id="usersList"
                                class="w-full px-4 py-2 border border-gray-200 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-[#0b6f76] transition"
                                onchange="document.getElementById('manager').value = this.value">
                                <option value="">No manager</option>
                            </select>
                            <input type="hidden" id="manager" value="" />
                        </div>

                        <!-- Role -->
                        <div class="flex flex-col">
                            <label for="role" class="text-sm text-gray-600 mb-1">Role</label>
                            <select id="role"
                                class="w-full px-4 py-2 border border-gray-200 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-[#0b6f76] transition">
                                <option value="">Select role</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="flex flex-col">
                            <label for="status" class="text-sm text-gray-600 mb-1">Status</label>
                            <select id="status"
                                class="w-full px-4 py-2 border border-gray-200 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-[#0b6f76] transition">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <!-- Submit button spans full width on small, right-aligned on larger screens -->
                        <div class="lg:col-span-3 flex justify-end">
                            <input type="submit" value="Create User"
                                class="w-full sm:w-auto px-6 py-3 bg-[#0b6f76] text-white text-sm font-medium rounded-lg cursor-pointer hover:bg-[#095c63] transition" />
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Apply button triggers the existing fetchUsers function
        document.getElementById('applyFilters').addEventListener('click', () => {
            // update searchInput value is already in the form; existing fetchUsers will read it
            if (typeof fetchUsers === 'function') fetchUsers();
        });

        // Reset filters
        document.getElementById('resetFilters').addEventListener('click', () => {
            document.getElementById('filtersForm').reset();
            // After reset, re-run fetch to show all users
            if (typeof fetchUsers === 'function') fetchUsers();
        });

        // Optional: allow pressing Enter in any input to apply filters
        document.getElementById('filtersForm').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (typeof fetchUsers === 'function') fetchUsers();
            }
        });
    </script>

    <script>
        const API_USERS = "../../api/admin/users.php?action=all";
        const API_ROLES = "../../api/admin/roles.php";
        const API_CREATE_USER = "../../api/admin/users.php?action=create";
        const TOKEN = "<?= $_SESSION['token'] ?? '' ?>";

        /**
         * 🔹 تحميل المدراء
         */
        async function loadManagers() {
            try {
                const response = await fetch(API_USERS, {
                    headers: { "Authorization": `Bearer ${TOKEN}` }
                });
                const data = await response.json();

                if (!data.success) throw new Error(data.message);

                const managers = data.data?.users || [];
                const list = document.getElementById("usersList");
                list.innerHTML = "";

                managers.forEach(user => {
                    const option = document.createElement("option");
                    option.value = user.id;
                    option.textContent = `${user.name} (${user.email})`;
                    list.appendChild(option);
                });
            } catch (error) {
                console.error("Error loading managers:", error);
            }
        }

        /**
         * 🔹 تحميل الأدوار
         */
        async function loadRoles() {
            try {
                const response = await fetch(API_ROLES, {
                    headers: { "Authorization": `Bearer ${TOKEN}` }
                });
                const data = await response.json();

                if (!data.success) throw new Error(data.message);

                const roles = data.data?.roles || [];
                const roleSelect = document.getElementById("role");
                roleSelect.innerHTML = `<option value="">Select role</option>`;

                roles.forEach(role => {
                    const option = document.createElement("option");
                    option.value = role.id;
                    option.textContent = role.name;
                    roleSelect.appendChild(option);
                });
            } catch (error) {
                console.error("Error loading roles:", error);
            }
        }

        /**
         * 🔹 إرسال طلب إضافة المستخدم
         */
        document.getElementById("filtersForm").addEventListener("submit", async (e) => {
            e.preventDefault();

            // build payload but include only non-empty fields
            const raw = {
                name: document.getElementById("Name").value.trim(),
                email: document.getElementById("Email").value.trim(),
                password: document.getElementById("Password").value.trim(),
                phone: document.getElementById("Phone").value.trim(),
                department: document.getElementById("Department").value.trim(),
                manager_id: document.getElementById("manager").value.trim(),
                role_id: document.getElementById("role").value,
                is_active: document.getElementById("status").value,
            };

            const payload = {};
            Object.entries(raw).forEach(([key, value]) => {
                if (value !== '' && value !== null && typeof value !== 'undefined') {
                    // convert numeric-ish fields to numbers if appropriate
                    if (['manager_id', 'role_id', 'is_active'].includes(key)) {
                        // keep as number when possible
                        const n = Number(value);
                        payload[key] = isNaN(n) ? value : n;
                    } else {
                        payload[key] = value;
                    }
                }
            });

            try {
                const response = await fetch(API_CREATE_USER, {
                    method: "POST",
                    headers: {
                        "Authorization": `Bearer ${TOKEN}`,
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: "success",
                        title: "User added successfully",
                        text: data.message,
                    });
                } else

                    if (!data.success) {
                        Swal.fire({
                            icon: "error",
                            title: "Failed to add user",
                            text: data.message,
                        })
                    };
                document.getElementById("filtersForm").reset();
            } catch (error) {
                Swal.fire({
                    icon: "error",
                    title: "Failed to add user",
                    text: 'Unknown error occurred',
                    error: error.message
                });
                console.error("Add user error:", error);
            }
        });

        /**
         * 🔹 تحميل البيانات عند فتح الصفحة
         */
        document.addEventListener("DOMContentLoaded", () => {
            loadManagers();
            loadRoles();
        });
    </script>
</body>

</html>