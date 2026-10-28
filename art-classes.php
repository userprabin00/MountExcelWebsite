<?php
$pageTitle = 'Creative Art Classes';
$pageDesc = 'Student art showcase at Mount Excel Boarding School. Drawings, paintings, and creative artwork crafted by young artists.';
$activePage = 'art';
require_once __DIR__ . '/includes/header.php';

// Load photos from DB, fall back to hardcoded array if table is empty
$artPhotos = [];
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT filename, title, caption
        FROM gallery_items
        WHERE album = 'art' AND is_visible = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        $artPhotos = $rows;
    }
} catch (Exception $e) {
    error_log('art-classes.php DB error: ' . $e->getMessage());
}

// Static fallback
if (empty($artPhotos)) {
    $artPhotos = [
        ['filename' => 'images/event3.jpg', 'title' => 'Classroom Art Session',    'caption' => 'Students collaborating on drawing and fine motor projects under guidance.'],
        ['filename' => 'images/art1.jpg',   'title' => 'Coloring & Sketching',     'caption' => 'Creative color shading and pencil sketch work from primary students.'],
        ['filename' => 'images/art2.jpg',   'title' => 'Visual Arts & Imagination', 'caption' => 'Expressing thoughts, nature themes, and festive ideas through artwork.'],
        ['filename' => 'images/art3.jpg',   'title' => 'Fine Crafts & Patterns',   'caption' => 'Handmade crafts developing patience, focus, and eye-hand coordination.'],
        ['filename' => 'images/art4.jpg',   'title' => 'Student Exhibition Display', 'caption' => "Proudly showcasing artistic achievements during parents' visiting day."],
    ];
}
?>

<!-- Banner -->
<section class="page-banner">
  <div class="container banner-content">
    <h1 class="banner-title">Creative Art Classes Showcase</h1>
    <p class="banner-breadcrumbs">
      <a href="index.php">Home</a> &rsaquo; <a href="gallery.php">Gallery</a> &rsaquo; <span>Art Classes</span>
    </p>
  </div>
</section>

<!-- Art Description & Photos -->
<section class="section-pad">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Creative Expression</span>
      <h2 class="section-title">Nurturing Imagination &amp; Fine Arts</h2>
      <p class="section-intro">
        Our art classes allow students to explore their natural creativity through drawing, coloring, painting, and craft activities. Students regularly display their creations during school exhibitions and cultural celebrations.
      </p>
    </div>

    <!-- Photo Gallery Grid -->
    <div class="gallery-grid" style="margin-bottom: 40px;">
      <?php foreach ($artPhotos as $item): 
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
