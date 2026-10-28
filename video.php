<?php
$pageTitle = 'Video Gallery';
$pageDesc = 'School video gallery of Mount Excel Boarding School, Bhimtar. Watch highlights of campus events, presentations, and student activities.';
$activePage = 'video';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Banner -->
<section class="page-banner">
  <div class="container banner-content">
    <h1 class="banner-title">School Video Gallery</h1>
    <p class="banner-breadcrumbs">
      <a href="index.php">Home</a> &rsaquo; <a href="gallery.php">Gallery</a> &rsaquo; <span>Videos</span>
    </p>
  </div>
</section>

<!-- Video Showcase -->
<section class="section-pad">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Motion & Moments</span>
      <h2 class="section-title">Video Highlights from Mount Excel</h2>
      <p class="section-intro">
        Watch our students engaged in learning, cultural celebrations, recitations, and campus life at Mount Excel Boarding School in Bhimtar.
      </p>
    </div>

    <div style="max-width: 900px; margin: 0 auto;">
      <!-- Video 1 -->
      <div class="video-player-card">
        <video controls preload="metadata" playsinline poster="images/event1.jpg" aria-label="Mount Excel School Event Video 1">
          <source src="images/video1.mp4" type="video/mp4" />
          Your browser does not support HTML5 video playback.
        </video>
        <div class="video-card-info">
          <div class="badge badge-gold" style="margin-bottom: 8px;">Video Feature 1</div>
          <h3 style="font-family: var(--font-heading); font-size: 1.35rem; color: var(--primary-800); margin-bottom: 6px;">
            School Assembly & Student Activities Showcase
          </h3>
          <p style="color: #64748b; font-size: 0.95rem;">
            Capturing the lively energy, morning routines, discipline, and community spirit shared by students and teachers on our Bhimtar campus.
          </p>
        </div>
      </div>

      <!-- Video 2 -->
      <div class="video-player-card">
        <video controls preload="metadata" playsinline poster="images/classroom.jpg" aria-label="Mount Excel School Event Video 2">
          <source src="images/video2.mp4" type="video/mp4" />
          Your browser does not support HTML5 video playback.
        </video>
        <div class="video-card-info">
          <div class="badge badge-blue" style="margin-bottom: 8px;">Video Feature 2</div>
          <h3 style="font-family: var(--font-heading); font-size: 1.35rem; color: var(--primary-800); margin-bottom: 6px;">
            Classroom Learning & Student Presentations
          </h3>
          <p style="color: #64748b; font-size: 0.95rem;">
            Insight into classroom interactions, student speeches, recitations, and co-curricular projects.
          </p>
        </div>
      </div>
    </div>

    <div style="text-align: center; margin-top: 40px;">
      <a href="gallery.php" class="btn btn-outline-white" style="color: var(--primary-800); border-color: var(--primary-800);">
        <i class="fas fa-arrow-left"></i> Back to All Galleries
      </a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
