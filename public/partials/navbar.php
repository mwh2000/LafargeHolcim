<?php

$userData = json_decode($_COOKIE['user_data'], true);
// navbar.php
function renderNavbar($pageRoute = 'Dashboard', $notificationsPageURL = '/public/notifications.php')
{
    global $config, $userData;
    ?>
    <nav class="bg-white shadow-sm border-b border-gray-200 px-4 md:px-8 py-3 md:py-4 sticky top-0 z-30">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-0">

            <!-- Left Section -->
            <div class="flex items-center space-x-3 md:space-x-5">
                <!-- Sidebar toggle for mobile -->
                <button id="sidebarToggle"
                    class="md:hidden p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="flex items-center space-x-2 md:space-x-3">
                    <div class="w-9 h-9 md:w-10 md:h-10 rounded-lg flex items-center justify-center bg-gray-100">
                        <img src="<?= BASE_URL ?>/public/images/logo.png" alt="Logo"
                            class="w-7 h-7 md:w-8 md:h-8 object-contain">
                    </div>
                    <div class="col">
                        <span class="text-xs md:text-sm font-semibold text-gray-700 tracking-wide">KCML / SLV</span>
                        <span class="text-xs text-gray-400 block tracking-wide">
                            <?php echo $userData['name'] ?>
                        </span>
                    </div>
                </div>
                <div class="hidden md:block text-sm text-gray-500">
                    <span><?php echo $pageRoute ?></span>
                </div>
            </div>

            <!-- Right Section -->
            <div class="flex items-center space-x-2 md:space-x-4 mt-2 md:mt-0">
                <div class="block md:hidden text-xs text-gray-500 mr-2">
                    <span><?php echo $pageRoute ?></span>
                </div>
                <button type="button" id="notifications"
                    class="relative px-3 py-2 bg-gray-50 hover:bg-gray-100 text-red-500 text-sm font-medium rounded-lg shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-200">
                    <svg class="w-6 h-6 text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 16 21">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 3.464V1.1m0 2.365a5.338 5.338 0 0 1 5.133 5.368v1.8c0 2.386 1.867 2.982 1.867 4.175C15 15.4 15 16 14.462 16H1.538C1 16 1 15.4 1 14.807c0-1.193 1.867-1.789 1.867-4.175v-1.8A5.338 5.338 0 0 1 8 3.464ZM4.54 16a3.48 3.48 0 0 0 6.92 0H4.54Z" />
                    </svg>
                    <span id="notifications_count"
                        class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                        0
                    </span>
                </button>
                <a href="<?= BASE_URL ?>/public/logout.php" id="logoutButton"
                    class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <script>
        document.getElementById('notifications').addEventListener('click', () => {
            window.location.href = '<?= BASE_URL . $notificationsPageURL ?>';
        });

        function loadNotificationsCount() {
            fetch(BASE_URL + '/api/notifications.php?action=get_notifications_count&is_opened=0', {
                method: 'GET',
                credentials: 'include'
            })
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('notifications_count');

                    if (data.success && data.count > 0) {
                        badge.innerText = data.count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(err => console.error(err));
        }

        // تحميل العداد عند فتح الصفحة
        document.addEventListener('DOMContentLoaded', loadNotificationsCount);
    </script>
    <?php
}
?>