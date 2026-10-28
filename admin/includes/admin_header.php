<?php
/**
 * Admin Panel Header Layout
 * Mount Excel Boarding School
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/auth.php';

requireAuth();

$currentUser = currentAdmin();
$activeNav = $activeNav ?? 'dashboard';
$adminTitle = $adminTitle ?? 'Administration Dashboard';

// Fetch quick badge counts
$pdo = getDB();
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'pending'")->fetchColumn();
$unreadMessagesCount = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($adminTitle) ?> | Mount Excel Admin</title>
  <link rel="icon" type="image/png" href="../images/favicon.png" />
  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="../css/admin.css?v=<?= e(file_exists(__DIR__ . '/../../css/admin.css') ? filemtime(__DIR__ . '/../../css/admin.css') : time()) ?>" />
</head>
<body class="admin-body">

  <div class="admin-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar" id="adminSidebar">
      <div class="sidebar-brand">
        <img src="../images/logo.jpg" alt="Mount Excel Logo" class="sidebar-logo" width="40" height="40" />
        <div class="sidebar-brand-text">
          <span class="sidebar-school">Mount Excel</span>
          <span class="sidebar-sub">Staff Portal</span>
        </div>
      </div>

      <nav class="sidebar-nav" aria-label="Admin navigation">
        <ul>
          <li class="sidebar-section-title">Workspace</li>
          <li>
            <a href="index.php" class="nav-item <?= ($activeNav === 'dashboard') ? 'active' : '' ?>" <?= ($activeNav === 'dashboard') ? 'aria-current="page"' : '' ?>>
              <i class="fas fa-th-large"></i> <span>Dashboard</span>
            </a>
          </li>
          <li>
            <a href="admissions.php" class="nav-item <?= ($activeNav === 'admissions') ? 'active' : '' ?>" <?= ($activeNav === 'admissions') ? 'aria-current="page"' : '' ?>>
              <i class="fas fa-user-graduate"></i> <span>Admissions</span>
              <?php if ($pendingCount > 0): ?>
                <span class="nav-badge badge-warning"><?= $pendingCount ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li>
            <a href="messages.php" class="nav-item <?= ($activeNav === 'messages') ? 'active' : '' ?>" <?= ($activeNav === 'messages') ? 'aria-current="page"' : '' ?>>
              <i class="fas fa-envelope"></i> <span>Contact Inquiries</span>
              <?php if ($unreadMessagesCount > 0): ?>
                <span class="nav-badge badge-primary"><?= $unreadMessagesCount ?></span>
              <?php endif; ?>
            </a>
          </li>
          <?php if (hasRole(['admin', 'super_admin'])): ?>
          <li class="sidebar-section-title">Manage</li>
          <li>
            <a href="export_admissions.php" class="nav-item <?= ($activeNav === 'export') ? 'active' : '' ?>" <?= ($activeNav === 'export') ? 'aria-current="page"' : '' ?>>
              <i class="fas fa-file-export"></i> <span>Export CSV</span>
            </a>
          </li>
          <li>
            <a href="settings.php" class="nav-item <?= ($activeNav === 'settings') ? 'active' : '' ?>" <?= ($activeNav === 'settings') ? 'aria-current="page"' : '' ?>>
              <i class="fas fa-sliders-h"></i> <span>Admission Settings</span>
            </a>
          </li>
          <li>
            <a href="gallery.php" class="nav-item <?= ($activeNav === 'gallery') ? 'active' : '' ?>" <?= ($activeNav === 'gallery') ? 'aria-current="page"' : '' ?>>
              <i class="fas fa-images"></i> <span>Gallery</span>
            </a>
          </li>
          <li>
            <a href="staff.php" class="nav-item <?= ($activeNav === 'staff') ? 'active' : '' ?>" <?= ($activeNav === 'staff') ? 'aria-current="page"' : '' ?>>
              <i class="fas fa-chalkboard-teacher"></i> <span>Staff</span>
            </a>
          </li>
          <?php endif; ?>
          <?php if (hasRole('super_admin')): ?>
          <li class="sidebar-section-title">Administration</li>
          <li>
            <a href="users.php" class="nav-item <?= ($activeNav === 'users') ? 'active' : '' ?>" <?= ($activeNav === 'users') ? 'aria-current="page"' : '' ?>>
              <i class="fas fa-users-cog"></i> <span>Manage Users</span>
            </a>
          </li>
          <?php endif; ?>
          <li class="sidebar-section-title">Account</li>
          <li>
            <a href="change_password.php" class="nav-item <?= ($activeNav === 'security') ? 'active' : '' ?>" <?= ($activeNav === 'security') ? 'aria-current="page"' : '' ?>>
              <i class="fas fa-key"></i> <span>Account Security</span>
            </a>
          </li>
          <li class="sidebar-divider"></li>
          <li>
            <a href="../index.php" target="_blank" rel="noopener noreferrer" class="nav-item">
              <i class="fas fa-external-link-alt"></i> <span>View Live Site</span>
            </a>
          </li>
          <li>
            <form method="POST" action="logout.php" class="sidebar-logout-form">
              <?= csrfField() ?>
              <button type="submit" class="nav-item text-danger">
                <i class="fas fa-sign-out-alt"></i> <span>Sign Out</span>
              </button>
            </form>
          </li>
        </ul>
      </nav>

      <div class="sidebar-footer">
        <p class="sidebar-user-name"><?= e($currentUser['full_name']) ?></p>
        <span class="sidebar-user-role"><?= strtoupper(e($currentUser['role'])) ?></span>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
      <!-- Top Bar -->
      <header class="admin-topbar">
        <button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="adminSidebar">
          <i class="fas fa-bars"></i>
        </button>

        <div class="topbar-title">
          <h2><?= e($adminTitle) ?></h2>
        </div>

        <div class="topbar-user">
          <span class="topbar-welcome">Logged in as <strong><?= e($currentUser['full_name']) ?></strong></span>
          <form method="POST" action="logout.php" class="topbar-logout-form">
            <?= csrfField() ?>
            <button type="submit" class="topbar-logout-btn" title="Sign Out" aria-label="Sign Out"><i class="fas fa-power-off"></i></button>
          </form>
        </div>
      </header>

      <button type="button" class="admin-sidebar-backdrop" id="sidebarBackdrop" aria-label="Close navigation menu" hidden></button>

      <!-- Page Content -->
      <main class="admin-content">
        <?= renderFlashMessages() ?>
