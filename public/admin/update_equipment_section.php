<?php
require_once '../../core/Database.php';
require_once '../../config/config.php';

require_once __DIR__ . '/../partials/sidebar.php';
require_once __DIR__ . '/../partials/navbar.php';

require_once '../helpers/authCheck.php';

$sectionId = $_GET['id'] ?? null;
if (!$sectionId) {
    header('Location: equipment_sections.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Edit Equipment Section</title>
</head>
<body class="bg-gray-50">
    <!-- ✅ Layout -->
    <?php renderNavbar('Edit Equipment Section'); ?>
    <div class="dashboard-container min-h-screen bg-[#0b6f76] bg-opacity-[5%] flex">
        <?php renderSidebar('equipment_sections'); ?>

        <!-- ✅ Main Content -->
        <div class="flex-1 flex flex-col sm:ml-64 transition-all p-8">
            <div class="bg-white p-6 rounded-lg shadow max-w-lg w-full mb-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Edit Equipment Section</h2>

                <form id="updateSectionForm" class="space-y-4">
                    <input type="hidden" id="section_id" value="<?= htmlspecialchars($sectionId) ?>">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Section Name</label>
                        <input type="text" id="name" name="name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-[#0b6f76] focus:border-[#0b6f76]">
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="equipment_sections.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition">Cancel</a>
                        <button type="submit" class="px-4 py-2 bg-[#0b6f76] text-white rounded-md hover:bg-opacity-90 transition">Update Section</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const API_URL = "../../api/admin/equipment_sections.php?action=update";
        const FETCH_URL = "../../api/admin/equipment_sections.php?action=show";
        const TOKEN = "<?= $_COOKIE['token'] ?? '' ?>";
        const SECTION_ID = document.getElementById('section_id').value;

        // Fetch existing section data
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const response = await fetch(`${FETCH_URL}&id=${SECTION_ID}`, {
                    headers: {
                        'Authorization': `Bearer ${TOKEN}`
                    }
                });
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('name').value = data.data.name;
                } else {
                    Swal.fire('Error', 'Failed to load section details', 'error').then(() => {
                        window.location.href = 'equipment_sections.php';
                    });
                }
            } catch (error) {
                Swal.fire('Error', 'Network error', 'error');
            }
        });

        // Handle update submission
        document.getElementById('updateSectionForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const name = document.getElementById('name').value.trim();

            if (!name) {
                Swal.fire('Error', 'Section Name is required', 'error');
                return;
            }

            try {
                const response = await fetch(`${API_URL}&id=${SECTION_ID}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${TOKEN}`
                    },
                    body: JSON.stringify({ name })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Equipment section updated successfully',
                        confirmButtonColor: '#0b6f76'
                    }).then(() => {
                        window.location.href = 'equipment_sections.php';
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
