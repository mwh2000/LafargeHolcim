<?php
require_once '../core/Database.php';
require_once '../config/config.php';

require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/partials/navbar.php';
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport"
    content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <script src="https://cdn.tailwindcss.com"></script>
  <title>
    LafargeHolcim Admin Panel
  </title>

  <style>
    .navbar {
      background: #0f766e;
      padding: 16px 24px;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 18px;
      font-weight: 600;
    }

    .container {
      max-width: 800px;
      margin: 40px auto;
      background: white;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    .section-title {
      font-size: 26px;
      margin-bottom: 20px;
      font-weight: bold;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .notification-badge {
      background: #ef4444;
      color: white;
      border-radius: 9999px;
      padding: 4px 10px;
      font-size: 14px;
    }

    .notif-card {
      border-bottom: 1px solid #e2e8f0;
      padding: 16px 0;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .notif-card:last-child {
      border-bottom: none;
    }

    .notif-item {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 10px;
      border-bottom: 1px solid #ddd;
      text-decoration: none;
      color: inherit;
      transition: background 0.2s ease;
    }

    .notif-item:hover {
      background: #f8fafc;
    }

    .notif-left {
      flex-shrink: 0;
    }

    .notif-img {
      width: 40px;
      height: 40px;
      object-fit: cover;
    }

    .notif-right {
      flex: 1;
    }

    .notif-body {
      margin-bottom: 6px;
    }

    .notif-time {
      font-size: 0.8em;
      color: #999;
    }

    .notif-dot {
      display: inline-block;
      width: 8px;
      height: 8px;
      background: #ef4444;
      border-radius: 50%;
      margin-left: 5px;
    }

    .filter-container {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
    }

    .filter-container label {
      font-weight: 500;
      color: #0f172a;
    }

    @media (max-width: 600px) {
      .container {
        margin: 20px;
        padding: 20px;
      }

      .section-title {
        font-size: 22px;
      }
    }
  </style>
</head>

<body>
  <?php renderNavbar('Notifications', '/public/notifications.php'); ?>
  <div class="dashboard-container min-h-screen bg-gray-50">
    <?php renderSidebar(); ?>

    <main class="p-6 ml-4 md:pl-64">
      <div class="section-title">
        <div>Notifications <span class="notification-badge" id="notif-count">0</span></div>

        <main class="p-6 ml-4 md:pl-64">
          <div class="section-title">
            Notifications
            <span class="notification-badge" id="notif-count">0</span>
          </div>

          <!-- ✅ فلتر التاريخ -->
          <div class="filter-container">
            <label for="filter-date">Filter by date:</label>
            <input type="date" id="filter-date" class="border px-3 py-2 rounded-md" />
          </div>

          <div id="notifications-container">
            <!-- Notifications will be dynamically loaded here -->
          </div>
        </main>
      </div>
    </main>
  </div>
</body>

</html>