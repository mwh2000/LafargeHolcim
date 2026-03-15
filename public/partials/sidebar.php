<?php

// sidebar.php
function renderSidebar($activePage = '')
{
  //get user role
  $userRole = $_COOKIE['user_type'];

  switch ($userRole) {
    // admin
    case 1:
      $links = [
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
      ];
      break;
    // requester
    case 2:
      $links = [
        'dashboard' => [
          'label' => 'Dashboard',
          'href' => BASE_URL . '/public/dashboard.php',
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
        'energy_insulation' => [
          'label' => 'Energy Insulation',
          'href' => BASE_URL . '/public/requester/add_energy_license.php',
          'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04M12 2.944a11.955 11.955 0 01-8.618 3.04M12 2.944v17.056c-3.33 0-6.355-1.127-8.618-3.04M12 20c3.33 0 6.355-1.127 8.618-3.04" />'
        ],
      ];
      break;
    // area manager
    case 3:
      $links = [
        'dashboard' => [
          'label' => 'Dashboard',
          'href' => BASE_URL . '/public/dashboard.php',
          'icon' => '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />'
        ],
        'create_action' => [
          'label' => 'New Report',
          'href' => BASE_URL . '/public/requester/create_action.php',
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
      ];
      break;
    // safety
    case 4:
      $links = [
        'dashboard' => [
          'label' => 'Dashboard',
          'href' => BASE_URL . '/public/dashboard.php',
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

      ];
      break;
    // manager
    case 5:
      $links = [
        'dashboard' => [
          'label' => 'Dashboard',
          'href' => BASE_URL . '/public/dashboard.php',
          'icon' => '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />'
        ],
        'create_action' => [
          'label' => 'New Report',
          'href' => BASE_URL . '/public/requester/create_action.php',
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
      ];
      break;

    // plant manager
    case 6:
      $links = [
        'dashboard' => [
          'label' => 'Dashboard',
          'href' => BASE_URL . '/public/dashboard.php',
          'icon' => '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />'
        ],
        'create_action' => [
          'label' => 'New Report',
          'href' => BASE_URL . '/public/requester/create_action.php',
          'icon' => '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />'
        ],
        'energy_insulation' => [
          'label' => 'Energy Insulation',
          'href' => BASE_URL . '/public/requester/add_energy_license.php',
          'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04M12 2.944a11.955 11.955 0 01-8.618 3.04M12 2.944v17.056c-3.33 0-6.355-1.127-8.618-3.04M12 20c3.33 0 6.355-1.127 8.618-3.04" />'
        ],
      ];
      break;
    default:
      $links = [];
      break;
  }
?>

  <!-- Overlay للموبايل -->
  <div id="mobileSidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

  <!-- Sidebar -->
  <aside id="sidebar"
    class="bg-white shadow-sm sm:m-6 sm:rounded-3xl border-r border-gray-200 h-[calc(100vh-64px)] sm:min-h-100vh w-64 fixed top-18 left-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-50">
    <div class="p-6">
      <div class="space-y-2">
        <?php foreach ($links as $key => $link):
          $isActive = $activePage === $key;
          $activeClasses = $isActive ? "text-[#0b6f76] bg-purple-50 font-medium" : "text-gray-700 hover:bg-gray-100";
        ?>
          <a href="<?= $link['href'] ?>"
            class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors <?= $activeClasses ?>">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <?= $link['icon'] ?>
            </svg>
            <span>
              <?= $link['label'] ?>
            </span>
          </a>
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
  </script>

<?php
}
