<?php

// sidebar.php
function renderSidebar($activePage = '')
{
  //get user role
  $userRole = $_SESSION['user_type'];

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
