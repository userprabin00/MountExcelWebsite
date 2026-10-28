<?php
$pageTitle = 'School Gallery';
$pageDesc = 'Photo and video gallery of Mount Excel Boarding School. Moments from our annual merit scholarships, excursions, art showcases, and student activities.';
$activePage = 'gallery';
require_once __DIR__ . '/includes/header.php';

// Pull cover image (first visible photo) per named album from DB
$coverImages = [
    'merit'     => 'images/event1.jpg',  // static fallbacks
    'excursion' => 'images/event2.jpg',
    'art'       => 'images/event3.jpg',
];
$generalPhotos = [];

try {
    $pdo = getDB();

    // Cover images per album (first visible photo, ordered by sort_order)
    foreach (['merit', 'excursion', 'art'] as $album) {
        $stmt = $pdo->prepare("
            SELECT filename FROM gallery_items
            WHERE album = :album AND is_visible = 1
            ORDER BY sort_order ASC, id ASC
            LIMIT 1
        ");
        $stmt->execute([':album' => $album]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $coverImages[$album] = str_starts_with($row['filename'], 'images/') || str_starts_with($row['filename'], 'uploads/')
              ? $row['filename']
              : 'uploads/gallery/' . $row['filename'];
        }
    }

    // General album photos (if any exist)
    $stmt = $pdo->prepare("
        SELECT filename, title, caption FROM gallery_items
        WHERE album = 'general' AND is_visible = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute();
    $generalPhotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('gallery.php DB error: ' . $e->getMessage());
}
?>

<!-- Banner -->
<section class="page-banner">
  <div class="container banner-content">
    <h1 class="banner-title">School Photo &amp; Video Gallery</h1>
    <p class="banner-breadcrumbs">
      <a href="index.php">Home</a> &rsaquo; <span>Gallery</span>
    </p>
  </div>
</section>

<!-- Gallery Collections Overview -->
<section class="section-pad">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Life at Mount Excel</span>
      <h2 class="section-title">Explore Our School Activities</h2>
      <p class="section-intro">
        Browse authentic photographic highlights capturing academic achievements, creative explorations, cultural excursions, and campus celebrations.
      </p>
    </div>

    <!-- Featured Category Cards -->
    <div class="features-grid" style="margin-bottom: 60px;">
      <!-- Merit Scholarship -->
      <div class="gallery-card">
        <div class="gallery-img-wrap">
          <img src="<?= e($coverImages['merit']) ?>" alt="Merit Scholarship Award ceremony at Mount Excel" loading="lazy" />
        </div>
        <div class="gallery-card-body">
          <div class="badge badge-gold" style="margin-bottom: 8px;">Academic Honors</div>
          <h3 class="gallery-card-title">Merit Scholarship Awards</h3>
          <p class="gallery-card-desc">Annual award ceremony celebrating top academic performers with certificates and scholarships.</p>
          <a href="merit-scholarship.php" class="btn btn-sm btn-primary">View Photos &rarr;</a>
        </div>
      </div>

      <!-- Excursion -->
      <div class="gallery-card">
        <div class="gallery-img-wrap">
          <img src="<?= e($coverImages['excursion']) ?>" alt="Excursion with Mount Excel students" loading="lazy" />
        </div>
        <div class="gallery-card-body">
          <div class="badge badge-blue" style="margin-bottom: 8px;">Outdoor Learning</div>
          <h3 class="gallery-card-title">Excursion</h3>
          <p class="gallery-card-desc">Students and teachers exploring regional cultural heritage, scenic views, and team camaraderie.</p>
          <a href="excursion.php" class="btn btn-sm btn-primary">View Photos &rarr;</a>
        </div>
      </div>

      <!-- Art Classes -->
      <div class="gallery-card">
        <div class="gallery-img-wrap">
          <img src="<?= e($coverImages['art']) ?>" alt="Art classes showcase at Mount Excel" loading="lazy" />
        </div>
        <div class="gallery-card-body">
          <div class="badge badge-gold" style="margin-bottom: 8px;">Creativity &amp; Arts</div>
          <h3 class="gallery-card-title">Creative Art Classes</h3>
          <p class="gallery-card-desc">Showcasing student drawings, paintings, crafts, and fine-motor creative projects.</p>
          <a href="art-classes.php" class="btn btn-sm btn-primary">View Photos &rarr;</a>
        </div>
      </div>
    </div>

    <?php if (!empty($generalPhotos)): ?>
    <!-- General Album Section (admin-managed) -->
    <div class="section-header" style="margin-top: 40px;">
      <span class="section-pretitle">School Life</span>
      <h2 class="section-title">General Photo Gallery</h2>
    </div>
    <div class="gallery-grid" style="margin-bottom: 60px;">
      <?php foreach ($generalPhotos as $photo): 
        $gImgSrc = str_starts_with($photo['filename'], 'images/') || str_starts_with($photo['filename'], 'uploads/')
          ? e($photo['filename'])
          : 'uploads/gallery/' . e($photo['filename']);
      ?>
      <div class="gallery-card">
        <div class="gallery-img-wrap" style="height: 240px;">
          <img src="<?= $gImgSrc ?>" alt="<?= e($photo['title']) ?>" loading="lazy" />
        </div>
        <?php if ($photo['title'] || $photo['caption']): ?>
        <div class="gallery-card-body">
          <?php if ($photo['title']): ?>
          <h4 class="gallery-card-title"><?= e($photo['title']) ?></h4>
          <?php endif; ?>
          <?php if ($photo['caption']): ?>
          <p class="gallery-card-desc"><?= e($photo['caption']) ?></p>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Video Gallery Spotlight Card -->
    <div style="background: linear-gradient(135deg, var(--primary-900) 0%, var(--primary-800) 100%); color: #ffffff; border-radius: var(--radius-lg); padding: 40px; display: flex; align-items: center; justify-content: space-between; gap: 30px; flex-wrap: wrap; box-shadow: var(--shadow-lg);">
      <div style="max-width: 650px;">
        <div class="badge badge-gold" style="margin-bottom: 12px;"><i class="fas fa-video"></i> Video Highlights</div>
        <h3 style="font-family: var(--font-heading); font-size: 1.8rem; margin-bottom: 10px; color: #ffffff;">Watch Our Students in Action</h3>
        <p style="color: #cbd5e1; font-size: 1rem;">
          Explore video documentation of school events, recitations, cultural presentations, and celebrations at Mount Excel Boarding School.
        </p>
      </div>
      <div>
        <a href="video.php" class="btn btn-lg btn-accent"><i class="fas fa-play"></i> Watch School Videos</a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
