<?php
/**
 * Global Header Component
 * Mount Excel Boarding School
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';

// Page variables with defaults
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME . ' | School for Excellence';
$pageDesc = isset($pageDesc) ? $pageDesc : 'Mount Excel Boarding School, Bhimtar, Sindhupalchowk. Providing quality education, holistic discipline, and excellence from Nursery to Grade 8 since 2015.';
$activePage = $activePage ?? '';
$siteUrl = rtrim((string)env('SITE_URL', 'https://mountexcel.edu.np'), '/');
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$siteBasePath = rtrim((string)(parse_url($siteUrl, PHP_URL_PATH) ?: ''), '/');
if ($siteBasePath !== '' && ($requestPath === $siteBasePath || strpos($requestPath, $siteBasePath . '/') === 0)) {
    $requestPath = substr($requestPath, strlen($siteBasePath)) ?: '/';
}
$canonicalUrl = $siteUrl . '/' . ltrim($requestPath, '/');
$styleVersion = file_exists(__DIR__ . '/../css/style.css') ? filemtime(__DIR__ . '/../css/style.css') : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDesc) ?>" />
  <meta name="keywords" content="Mount Excel Boarding School, Bhimtar, Indrawati, Sindhupalchowk, School in Sindhupalchowk, Nursery to Grade 8, Education Nepal" />
  <link rel="canonical" href="<?= e($canonicalUrl) ?>" />

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="images/favicon.png" />
  <link rel="apple-touch-icon" href="images/logo.jpg" />

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website" />
  <meta property="og:url" content="<?= e($canonicalUrl) ?>" />
  <meta property="og:title" content="<?= e($pageTitle) ?>" />
  <meta property="og:description" content="<?= e($pageDesc) ?>" />
  <meta property="og:image" content="<?= e($siteUrl) ?>/images/logo.jpg" />

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= e($pageTitle) ?>" />
  <meta name="twitter:description" content="<?= e($pageDesc) ?>" />
  <meta name="twitter:image" content="<?= e($siteUrl) ?>/images/logo.jpg" />

  <!-- Google Fonts & Font Awesome CDN -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Core Stylesheet -->
  <link rel="stylesheet" href="css/style.css?v=<?= e($styleVersion) ?>" />

  <!-- Schema.org Educational Organization Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "EducationalOrganization",
    "name": "Mount Excel Boarding School",
    "alternateName": "MEBS",
    "url": <?= json_encode($siteUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    "logo": <?= json_encode($siteUrl . '/images/logo.jpg', JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    "description": "Mount Excel Boarding School provides quality education from Nursery to Grade 8 with dedicated teachers, modern facilities, and a disciplined learning environment.",
    "foundingDate": "2015",
    "slogan": "School for Excellence",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Indrawati Municipality 12, Bhimtar",
      "addressLocality": "Sindhupalchowk",
      "addressRegion": "Bagmati Province",
      "addressCountry": "NP"
    },
    "contactPoint": {
      "@type": "ContactPoint",
      "telephone": <?= json_encode(SCHOOL_PHONE, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
      "contactType": "admissions",
      "email": <?= json_encode(SCHOOL_EMAIL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    },
    "sameAs": [
      "https://www.facebook.com/share/1CbR8iAh1s/",
      "https://www.instagram.com/mebs_134?igsh=cGt0NmN0M2hpMzV6",
      "https://www.tiktok.com/@mebs400?_r=1&_t=ZS-9Au8dWLSVYW"
    ]
  }
  </script>
</head>
<body>
  <!-- Accessibility Skip Link -->
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!-- Top Info Bar -->
  <div class="top-bar">
    <div class="container top-bar-container">
      <div class="top-info">
        <span><i class="fas fa-map-marker-alt"></i> Bhimtar-12, Sindhupalchowk</span>
        <span class="divider">|</span>
        <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', SCHOOL_PHONE)) ?>"><i class="fas fa-phone-alt"></i> <?= e(SCHOOL_PHONE) ?></a>
        <span class="divider">|</span>
        <a href="mailto:<?= e(SCHOOL_EMAIL) ?>"><i class="fas fa-envelope"></i> <?= e(SCHOOL_EMAIL) ?></a>
      </div>
      <div class="top-actions">
        <a href="<?= SCHOOL_FACEBOOK ?>" target="_blank" rel="noopener noreferrer" aria-label="School Facebook Page"><i class="fab fa-facebook-f"></i></a>
        <a href="<?= SCHOOL_INSTAGRAM ?>" target="_blank" rel="noopener noreferrer" aria-label="School Instagram Profile"><i class="fab fa-instagram"></i></a>
        <a href="<?= SCHOOL_TIKTOK ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
      </div>
    </div>
  </div>

  <!-- Main School Header & Navigation -->
  <header class="main-header">
    <div class="container header-container">
      <div class="brand">
        <a href="index.php" class="brand-link">
          <img src="images/logo.jpg" alt="Mount Excel Boarding School Logo" class="brand-logo" width="65" height="65" />
          <div class="brand-text">
            <span class="school-name">Mount Excel Boarding School</span>
            <span class="school-motto"><em>M.E.B.S. &bull; School for Excellence &bull; Since 2015</em></span>
          </div>
        </a>
      </div>

      <!-- Mobile Navigation Hamburger Button -->
      <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="primaryNav">
        <span class="hamburger-box">
          <span class="hamburger-inner"></span>
        </span>
      </button>

      <!-- Primary Navigation -->
      <nav class="primary-nav" id="primaryNav">
        <ul class="nav-list">
          <li class="nav-item"><a href="index.php" class="nav-link <?= ($activePage === 'home') ? 'active' : '' ?>">Home</a></li>
          <li class="nav-item"><a href="about.php" class="nav-link <?= ($activePage === 'about') ? 'active' : '' ?>">About Us</a></li>
          <li class="nav-item"><a href="academics.php" class="nav-link <?= ($activePage === 'academics') ? 'active' : '' ?>">Academics</a></li>
          <li class="nav-item"><a href="admissions.php" class="nav-link <?= ($activePage === 'admissions') ? 'active' : '' ?>">Admissions</a></li>
          <li class="nav-item"><a href="facilities.php" class="nav-link <?= ($activePage === 'facilities') ? 'active' : '' ?>">Facilities</a></li>
          <li class="nav-item"><a href="staff.php" class="nav-link <?= ($activePage === 'staff') ? 'active' : '' ?>">Staff</a></li>
          <li class="nav-item dropdown">
            <a href="gallery.php" class="nav-link <?= in_array($activePage, ['gallery', 'merit', 'excursion', 'art', 'video']) ? 'active' : '' ?>" aria-haspopup="true">Gallery <i class="fas fa-chevron-down nav-caret"></i></a>
            <ul class="dropdown-menu">
              <li><a href="gallery.php">All Galleries</a></li>
              <li><a href="merit-scholarship.php">Merit Scholarship</a></li>
              <li><a href="excursion.php">Excursion</a></li>
              <li><a href="art-classes.php">Art Classes</a></li>
              <li><a href="video.php">Video Gallery</a></li>
            </ul>
          </li>
          <li class="nav-item"><a href="contact.php" class="nav-link <?= ($activePage === 'contact') ? 'active' : '' ?>">Contact</a></li>
          <li class="nav-item nav-cta-item">
            <a href="admissions.php" class="btn btn-sm btn-primary-cta">Apply Now</a>
          </li>
        </ul>
      </nav>
    </div>
  </header>

  <!-- Flash Message Container -->
  <div class="container flash-container">
    <?= renderFlashMessages() ?>
  </div>

  <main id="main-content">
