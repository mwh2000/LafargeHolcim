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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Add Equipment</title>
</head>
<body class="bg-gray-50">
    <!-- ✅ Layout -->
    <?php renderNavbar('Add Equipment'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%] flex">
        <?php renderSidebar('equipments'); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all p-8">
            <div class="bg-white p-6 rounded-lg shadow max-w-lg w-full mb-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">New Equipment</h2>

                <form id="addEquipmentForm" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Equipment Name</label>
                        <input type="text" id="name" name="name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-[#0b6f76] focus:border-[#0b6f76]">
                    </div>

                    <div>
                        <label for="section_id" class="block text-sm font-medium text-gray-700">Equipment Section</label>
                        <select id="section_id" name="section_id" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-[#0b6f76] focus:border-[#0b6f76]">
                            <option value="">Select a section...</option>
                        </select>
                    </div>

                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700">Equipment Image</label>
                        <input type="file" id="image" name="image" accept="image/*" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-[#0b6f76] focus:border-[#0b6f76]">
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <a href="equipments.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition">Cancel</a>
                        <button type="submit" class="px-4 py-2 bg-[#0b6f76] text-white rounded-md hover:bg-opacity-90 transition">Save Equipment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const API_URL = "../../api/admin/equipments.php?action=create";
        const SECTIONS_API = "../../api/admin/equipment_sections.php?action=all";
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";

        document.addEventListener('DOMContentLoaded', async () => {
            // Fetch sections
            try {
                const response = await fetch(SECTIONS_API, {
                    headers: { 'Authorization': `Bearer ${TOKEN}` }
                });
                const data = await response.json();
                
                if (data.success && data.data?.sections) {
                    const select = document.getElementById('section_id');
                    data.data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.id;
                        option.textContent = section.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Failed to load sections', error);
            }
        });

        document.getElementById('addEquipmentForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const name = document.getElementById('name').value.trim();
            const section_id = document.getElementById('section_id').value;
            const imageFile = document.getElementById('image').files[0];

            if (!name || !section_id) {
                Swal.fire('Error', 'Please fill all required fields', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('name', name);
            formData.append('section_id', section_id);
            if (imageFile) {
                formData.append('image', imageFile);
            }

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${TOKEN}`
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Equipment created successfully',
                        confirmButtonColor: '#0b6f76'
                    }).then(() => {
                        window.location.href = 'equipments.php';
                    });
                } else {
                    Swal.fire('Error', data.message || 'Something went wrong', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Network error or server unreachable', 'error');
            }
        });
    </script>
</body>
</html>
