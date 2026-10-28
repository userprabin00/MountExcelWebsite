<?php
$pageTitle = 'Educational Excursion';
$pageDesc = 'Educational excursion by students of Mount Excel Boarding School. Outdoor learning, cultural heritage, and nature appreciation.';
$activePage = 'excursion';
require_once __DIR__ . '/includes/header.php';

// Load photos from DB, fall back to hardcoded array if table is empty
$excursionPhotos = [];
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT filename, title, caption
        FROM gallery_items
        WHERE album = 'excursion' AND is_visible = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        $excursionPhotos = $rows;
    }
} catch (Exception $e) {
    error_log('excursion.php DB error: ' . $e->getMessage());
}

// Static fallback (used when DB is empty or unavailable)
if (empty($excursionPhotos)) {
    $excursionPhotos = [
        ['filename' => 'images/event2.jpg',    'title' => 'Assembly at Excursion Ground',  'caption' => 'Students and teachers gathering at the scenic excursion grounds.'],
        ['filename' => 'images/excursion2.jpg', 'title' => 'Nature & Trail Walk', 'caption' => 'Observing local flora, landscape geology, and countryside beauty.'],
        ['filename' => 'images/excursion3.jpg', 'title' => 'Cultural Exploration', 'caption' => 'Learning about local history, heritage, and regional surroundings.'],
        ['filename' => 'images/excursion4.jpg', 'title' => 'Group Activities',     'caption' => 'Interactive games and outdoor learning exercises.'],
        ['filename' => 'images/excursion5.jpg', 'title' => 'Friends & Mentors',    'caption' => 'Bonding with teachers and peers outside the traditional classroom.'],
        ['filename' => 'images/excursion6.jpg', 'title' => 'Field Observations',   'caption' => 'Practical experiential learning encouraging keen observation.'],
        ['filename' => 'images/excursion7.jpg', 'title' => 'Scenic Landscapes',    'caption' => 'Taking in the pristine natural surroundings and clean fresh air.'],
        ['filename' => 'images/excursion8.jpg', 'title' => 'Excursion Memories',   'caption' => 'A memorable, safe, and joyful day of collective learning.'],
    ];
}
?>

<!-- Banner -->
<section class="page-banner">
  <div class="container banner-content">
    <h1 class="banner-title">Educational Excursion</h1>
    <p class="banner-breadcrumbs">
      <a href="index.php">Home</a> &rsaquo; <a href="gallery.php">Gallery</a> &rsaquo; <span>Excursion</span>
    </p>
  </div>
</section>

<!-- Excursion Story & Photos -->
<section class="section-pad">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Experiential Learning</span>
      <h2 class="section-title">Exploring Nature, History &amp; Local Culture</h2>
      <p class="section-intro">
        During this exciting educational excursion, our students explored local cultural traditions, enjoyed scenic natural vistas, and engaged in cooperative group activities that fostered friendship and curiosity.
      </p>
    </div>

    <!-- Photo Gallery Grid -->
    <div class="gallery-grid" style="margin-bottom: 40px;">
      <?php foreach ($excursionPhotos as $item): 
        $imgSrc = str_starts_with($item['filename'], 'images/') || str_starts_with($item['filename'], 'uploads/')
          ? e($item['filename'])
          : 'uploads/gallery/' . e($item['filename']);
      ?>
      <div class="gallery-card">
        <div class="gallery-img-wrap" style="height: 240px;">
          <img src="<?= $imgSrc ?>" alt="<?= e($item['title']) ?>" loading="lazy" />
        </div>
        <div class="gallery-card-body">
          <h4 class="gallery-card-title"><?= e($item['title']) ?></h4>
          <p class="gallery-card-desc"><?= e($item['caption']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align: center;">
      <a href="gallery.php" class="btn btn-outline-white" style="color: var(--primary-800); border-color: var(--primary-800);">
        <i class="fas fa-arrow-left"></i> Back to All Galleries
      </a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
