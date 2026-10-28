<?php
$pageTitle = 'Merit Scholarship Award';
$pageDesc = 'Merit Scholarship Award Distribution ceremony at Mount Excel Boarding School, honoring scholastic excellence and student diligence.';
$activePage = 'merit';
require_once __DIR__ . '/includes/header.php';

// Load photos from DB, fall back to hardcoded array if table is empty
$meritPhotos = [];
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT filename, title, caption
        FROM gallery_items
        WHERE album = 'merit' AND is_visible = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        $meritPhotos = $rows;
    }
} catch (Exception $e) {
    error_log('merit-scholarship.php DB error: ' . $e->getMessage());
}

// Static fallback
if (empty($meritPhotos)) {
    $meritPhotos = [
        ['filename' => 'images/event1.jpg', 'title' => 'Award Ceremony Stage',          'caption' => 'Honoring top rankers and dedicated students during the formal award assembly.'],
        ['filename' => 'images/merit2.jpg', 'title' => 'Certificate Distribution',      'caption' => 'Recognizing hard work, discipline, and outstanding marks across academic subjects.'],
        ['filename' => 'images/merit3.jpg', 'title' => 'Celebrating Student Achievement', 'caption' => 'Encouraging confidence, pride in learning, and a spirit of constructive competition.'],
        ['filename' => 'images/merit4.jpg', 'title' => 'Honored Recipients',            'caption' => 'Congratulations to all scholarship awardees for their hard work and excellence!'],
    ];
}
?>

<!-- Banner -->
<section class="page-banner">
  <div class="container banner-content">
    <h1 class="banner-title">Merit Scholarship Award Ceremony</h1>
    <p class="banner-breadcrumbs">
      <a href="index.php">Home</a> &rsaquo; <a href="gallery.php">Gallery</a> &rsaquo; <span>Merit Scholarship</span>
    </p>
  </div>
</section>

<!-- Event Description -->
<section class="section-pad">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Academic Recognition</span>
      <h2 class="section-title">Celebrating Dedication &amp; Scholastic Excellence</h2>
      <p class="section-intro">
        Mount Excel Boarding School recognizes outstanding academic performance through its annual Merit Scholarship Program. Students from various grades who excel in examinations and classroom projects are awarded scholarships and certificates of achievement.
      </p>
    </div>

    <!-- Photo Gallery Grid -->
    <div class="gallery-grid" style="margin-bottom: 40px;">
      <?php foreach ($meritPhotos as $item): 
        $imgSrc = str_starts_with($item['filename'], 'images/') || str_starts_with($item['filename'], 'uploads/')
          ? e($item['filename'])
          : 'uploads/gallery/' . e($item['filename']);
      ?>
      <div class="gallery-card">
        <div class="gallery-img-wrap" style="height: 260px;">
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
