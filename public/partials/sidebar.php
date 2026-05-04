<?php

// sidebar.php
function renderSidebar($activePage = '')
{
  //get user role
  $userRole = $_COOKIE['user_type'] ?? null;

  // Define all possible navigation items
  $all_items = [
    'dashboard' => [
      'label' => 'Dashboard',
      'href' => BASE_URL . '/public/dashboard.php',
      'icon' => '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />'
    ],
    'users' => [
      'label' => 'Users',
      'href' => BASE_URL . '/public/admin/users.php',
      'icon' => '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />'
    ],
    'actions_assigned_to_me' => [
      'label' => 'Action Assigned to Me',
      'href' => BASE_URL . '/public/actions_assigned_to_me.php',
      'icon' => '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />'
    ],
    'actions_created_by_me' => [
      'label' => 'Action Created by Me',
      'href' => BASE_URL . '/public/actions_created_by_me.php',
      'icon' => '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />'
    ],
    'create_action' => [
      'label' => 'New Report',
      'href' => BASE_URL . '/public/requester/create_action.php',
      'icon' => '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />'
    ],
    'equipment_sections' => [
      'label' => 'Equipment Sections',
      'href' => BASE_URL . '/public/admin/equipment_sections.php',
      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />'
    ],
    'equipments' => [
      'label' => 'Equipments',
      'href' => BASE_URL . '/public/admin/equipments.php',
      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />'
    ],
    'energy_types' => [
      'label' => 'Energy Types',
      'href' => BASE_URL . '/public/admin/energy_types.php',
      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />'
    ],
    'permit' => [
      'label' => 'Permit',
      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h.01M9 16h.01M9 8h.01M13 12h3M13 16h3M13 8h3m-7 11h10a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v10a2 2 0 002 2z" />',
      'type' => 'group',
      'sub_links' => [
        'permit_dashboard' => [
          'label' => 'Dashboard',
          'href' => BASE_URL . '/public/permit_dashboard.php',
        ],
        'energy_isolation' => [
          'label' => 'Energy Isolation',
          'href' => BASE_URL . '/public/requester/add_energy_license.php',
        ],
        'hot_work' => [
          'label' => 'Hot Work',
          'href' => BASE_URL . '/public/requester/add_hot_work_license.php',
        ],
        'all_permits' => [
          'label' => 'All Permits',
          'href' => BASE_URL . '/public/permits.php',
        ],
      ]
    ],
    'energy_Isolation' => [
      'label' => 'Energy Isolation',
      'href' => BASE_URL . '/public/requester/add_energy_license.php',
      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04M12 2.944a11.955 11.955 0 01-8.618 3.04M12 2.944v17.056c-3.33 0-6.355-1.127-8.618-3.04M12 20c3.33 0 6.355-1.127 8.618-3.04" />'
    ]
  ];

  // Define role permissions (Link IDs available for each role)
  $role_permissions = [
    1 => ['dashboard', 'users', 'actions_assigned_to_me', 'actions_created_by_me', 'create_action', 'equipment_sections', 'equipments', 'energy_types', 'permit'], // Admin
    2 => ['dashboard', 'actions_assigned_to_me', 'actions_created_by_me', 'create_action'], // Requester
    3 => ['dashboard', 'create_action', 'actions_assigned_to_me', 'actions_created_by_me', 'permit'], // Area Manager
    4 => ['dashboard', 'actions_assigned_to_me', 'actions_created_by_me', 'create_action'], // Safety
    5 => ['dashboard', 'create_action', 'actions_assigned_to_me', 'actions_created_by_me', 'permit'], // Manager
    6 => ['dashboard', 'create_action', 'permit', 'energy_Isolation'], // Plant Manager
    7 => ['dashboard', 'actions_assigned_to_me', 'actions_created_by_me', 'create_action', 'permit'], // Shift Leader
    8 => ['dashboard', 'actions_assigned_to_me', 'actions_created_by_me', 'create_action'], // Isolation Officer
  ];

  // Map permissions to actual links
  $links = [];
  $allowed_keys = $role_permissions[$userRole] ?? [];

  foreach ($allowed_keys as $key) {
    if (isset($all_items[$key])) {
      $links[$key] = $all_items[$key];
    }
  }
?>

  <!-- Overlay للموبايل -->
  <div id="mobileSidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

  <!-- Sidebar -->
  <aside id="sidebar"
    class="bg-white shadow-sm sm:m-6 sm:rounded-3xl border-r border-gray-200 h-[calc(100vh-64px)] sm:min-h-100vh w-64 fixed top-18 left-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-50 overflow-y-auto">
    <div class="p-6">
      <div class="space-y-2">
        <?php foreach ($links as $key => $link):
          if (isset($link['type']) && $link['type'] === 'header'): ?>
            <div class="px-4 pt-4 pb-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-2 border-t border-gray-100">
              <?= $link['label'] ?>
            </div>
          <?php continue;
          endif;

          if (isset($link['type']) && $link['type'] === 'group'):
            $hasActiveSub = false;
            foreach ($link['sub_links'] as $subKey => $subLink) {
              if ($activePage === $subKey) {
                $hasActiveSub = true;
                break;
              }
            }
          ?>
            <div class="sidebar-group">
              <button type="button"
                class="group-toggle w-full flex items-center justify-between px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors"
                data-group="<?= $key ?>">
                <div class="flex items-center space-x-3">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?= $link['icon'] ?>
                  </svg>
                  <span><?= $link['label'] ?></span>
                </div>
                <svg class="w-4 h-4 transform transition-transform duration-200 <?= $hasActiveSub ? 'rotate-180' : '' ?>"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <div class="group-content space-y-1 mt-1 <?= $hasActiveSub ? '' : 'hidden' ?>" id="group-<?= $key ?>">
                <?php foreach ($link['sub_links'] as $subKey => $subLink):
                  $isSubActive = $activePage === $subKey;
                ?>
                  <a href="<?= $subLink['href'] ?>"
                    class="flex items-center space-x-3 pl-12 pr-4 py-2 rounded-lg text-sm transition-colors <?= $isSubActive ? 'text-[#0b6f76] bg-purple-50 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <span><?= $subLink['label'] ?></span>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php else:
            $isActive = $activePage === $key;
            $activeClasses = $isActive ? "text-[#0b6f76] bg-purple-50 font-medium" : "text-gray-700 hover:bg-gray-100";
          ?>
            <a href="<?= $link['href'] ?>"
              class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors <?= $activeClasses ?>">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <?= $link['icon'] ?>
              </svg>
              <span>
                <?= $link['label'] ?>
              </span>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>

  <script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileSidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (toggleBtn) {
      toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
      });
    }

    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
      });
    }

    // Toggle Groups
    document.querySelectorAll('.group-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const groupKey = btn.getAttribute('data-group');
        const content = document.getElementById(`group-${groupKey}`);
        const arrow = btn.querySelector('svg:last-child');

        content.classList.toggle('hidden');
        arrow.classList.toggle('rotate-180');
      });
    });
  </script>

<?php
}
