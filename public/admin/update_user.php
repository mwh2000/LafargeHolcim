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
    <title>KCML / SLV | Edit User</title>

</head>

<body class="bg-gray-50">

    <!-- ✅ Layout -->
    <?php renderNavbar('Update User'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%]">
        <?php renderSidebar('users'); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all">
            <main class="flex-1 overflow-y-auto p-8 md:pl-12">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <h1 class="text-2xl font-semibold text-gray-700">Update User</h1>
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
                            <input id="Password" type="password"
                                class="w-full px-4 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0b6f76] transition" />
                        </div>

                        <!-- Department -->
                        <div class="flex flex-col">
                            <label for="Department" class="text-sm text-gray-600 mb-1">Department</label>
                            <input id="Department"
                                class="w-full px-4 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-[#0b6f76] transition" />
                        </div>

                        <!-- Group -->
                        <div class="flex flex-col">
                            <label for="group" class="text-sm text-gray-600 mb-1">Group</label>
                            <select id="group" name="group"
                                class="w-full px-4 py-2 border border-gray-200 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-[#0b6f76] transition">
                                <option value=""></option>
                                <!-- options from A to M capital -->
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
                            <input type="submit" value="Update User"
                                class="w-full sm:w-auto px-6 py-3 bg-[#0b6f76] text-white text-sm font-medium rounded-lg cursor-pointer hover:bg-[#095c63] transition" />
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";

        const urlParams = new URLSearchParams(window.location.search);
        const USER_ID = urlParams.get("id");

        const API_USERS = "../../api/admin/users.php";
        const API_ROLES = "../../api/admin/roles.php";

        if (!USER_ID) {
            Swal.fire("Error", "User ID is missing", "error");
        }

        async function loadUserData() {
            try {
                const response = await fetch(`${API_USERS}?action=show&id=${USER_ID}`, {
                    headers: { "Authorization": `Bearer ${TOKEN}` }
                });

                const data = await response.json();
                if (!data.success) throw new Error(data.message);

                const user = data.data;

                document.getElementById("Name").value = user.name ?? '';
                document.getElementById("Email").value = user.email ?? '';
                document.getElementById("Department").value = user.department ?? '';
                document.getElementById("group").value = user.group ?? '';
                document.getElementById("status").value = user.is_active;
                document.getElementById("role").value = user.role_id ?? '';
                document.getElementById("manager").value = user.manager_id ?? '';
                document.getElementById("usersList").value = user.manager_id ?? '';

            } catch (error) {
                Swal.fire("Error", error.message, "error");
            }
        }

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

        async function loadManagers() {
            try {
                const response = await fetch(`${API_USERS}?action=all`, {
                    headers: { "Authorization": `Bearer ${TOKEN}` }
                });
                const data = await response.json();

                if (!data.success) throw new Error(data.message);

                const managers = data.data?.users || [];
                const list = document.getElementById("usersList");
                list.innerHTML = `<option value="">Select manager</option>`;

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

        document.addEventListener("DOMContentLoaded", async () => {
            await loadManagers();
            await loadRoles();
            await loadUserData();
        });

        document.getElementById("filtersForm").addEventListener("submit", async (e) => {
            e.preventDefault();

            const raw = {
                name: document.getElementById("Name").value.trim(),
                email: document.getElementById("Email").value.trim(),
                password: document.getElementById("Password").value.trim(),
                department: document.getElementById("Department").value.trim(),
                group: document.getElementById("group").value,
                manager_id: document.getElementById("manager").value || null,
                role_id: document.getElementById("role").value,
                is_active: document.getElementById("status").value,
            };

            // نحذف القيم الفاضية
            const payload = {};
            Object.entries(raw).forEach(([key, value]) => {
                // نسمح لـ manager_id يكون null
                if (
                    value !== '' &&
                    value !== undefined &&
                    (key === 'manager_id' || value !== null)
                ) {
                    payload[key] = value;
                }
            });

            try {
                const response = await fetch(`${API_USERS}?action=update&id=${USER_ID}`, {
                    method: "PUT",
                    headers: {
                        "Authorization": `Bearer ${TOKEN}`,
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (!data.success) throw new Error(data.message);

                Swal.fire("Success", "User updated successfully", "success");

            } catch (error) {
                Swal.fire("Error", error.message, "error");
            }
        });


    </script>
</body>

</html>