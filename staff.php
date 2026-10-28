<?php
$pageTitle = 'Faculty & Staff';
$pageDesc = 'Meet the leadership and educators at Mount Excel Boarding School. Guided by Principal Mr. Nabaraj Rai and experienced, caring teachers.';
$activePage = 'staff';
require_once __DIR__ . '/includes/header.php';

// Load staff from DB
$staffMembers = [];
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT name, position, bio, photo_filename
        FROM staff_members
        WHERE is_visible = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute();
    $staffMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('staff.php DB error: ' . $e->getMessage());
}
?>

<!-- Banner -->
<section class="page-banner">
  <div class="container banner-content">
    <h1 class="banner-title">School Leadership &amp; Faculty</h1>
    <p class="banner-breadcrumbs">
      <a href="index.php">Home</a> &rsaquo; <span>Staff</span>
    </p>
  </div>
</section>

<!-- Executive Leadership Section (static — Principal is a special role) -->
<section class="section-pad">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Executive Department</span>
      <h2 class="section-title">School Leadership</h2>
      <p class="section-intro">
        Our administrative and academic direction is guided by experienced educational leaders dedicated to student welfare and school excellence.
      </p>
    </div>

    <div class="quote-card" style="margin-bottom: 50px;">
      <div class="quote-avatar-wrap">
        <img src="images/principal.jpg" alt="Mr. Nabaraj Rai, Principal of Mount Excel Boarding School" class="quote-avatar" width="180" height="210" loading="lazy" />
        <h3 class="quote-name">Mr. Nabaraj Rai</h3>
        <p class="quote-role">Principal &amp; Head of School</p>
      </div>
      <div>
        <span class="section-pretitle">Principal's Address</span>
        <h3 style="font-family: var(--font-heading); font-size: 1.6rem; color: var(--primary-800); margin-bottom: 15px;">Welcome from Mr. Nabaraj Rai</h3>
        <blockquote class="quote-text">
          "Dear students and parents,<br><br>
          At Mount Excel Boarding School, we care about every child. We don't just teach books &mdash; we help you grow with confidence, kindness, and knowledge. Our school is a happy place to learn, make friends, and discover your talents.<br><br>
          Join us, and let's build your bright future together!"
        </blockquote>
        <p style="margin-top: 15px; font-weight: 600; color: var(--primary-800);">&mdash; Mr. Nabaraj Rai, Principal</p>
      </div>
    </div>

    <!-- Teaching Faculty — Dynamic from DB -->
    <div class="section-header" style="margin-top: 60px;">
      <span class="section-pretitle">Academic Excellence</span>
      <h2 class="section-title">Our Teaching &amp; Support Team</h2>
      <p class="section-intro">
        Mount Excel takes pride in its dedicated team of teachers and support professionals who nurture each child from Nursery through Grade 8.
      </p>
    </div>

    <?php if (!empty($staffMembers)): ?>
    <!-- Staff cards from database -->
    <div class="features-grid">
      <?php foreach ($staffMembers as $member): ?>
      <div class="feature-card" style="text-align: center; padding: 28px 20px;">
        <?php if ($member['photo_filename']): ?>
        <img src="uploads/staff/<?= e($member['photo_filename']) ?>"
             alt="<?= e($member['name']) ?>"
             style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 0 auto 16px; display: block; border: 3px solid var(--primary-100);">
        <?php else: ?>
        <div class="feature-icon-box"><i class="fas fa-user-tie"></i></div>
        <?php endif; ?>
        <h3 class="feature-title"><?= e($member['name']) ?></h3>
        <p style="font-size: 0.85rem; font-weight: 600; color: var(--accent-gold); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.05em;">
          <?= e($member['position']) ?>
        </p>
        <?php if ($member['bio']): ?>
        <p class="feature-desc"><?= e($member['bio']) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <?php else: ?>
    <!-- Fallback: generic team categories when no staff members in DB -->
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-child"></i></div>
        <h3 class="feature-title">Pre-Primary Educators</h3>
        <p class="feature-desc">
          Specialized in early childhood development, Montessori-influenced activity teaching, and creating warm, safe classrooms for our youngest learners in PG, Nursery, LKG, and UKG.
        </p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-chalkboard"></i></div>
        <h3 class="feature-title">Primary &amp; Middle School Faculty</h3>
        <p class="feature-desc">
          Subject-specialist educators in English, Nepali, Mathematics, Science, Social Studies, and Computer Literacy committed to building strong conceptual clarity and discipline.
        </p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-hands-helping"></i></div>
        <h3 class="feature-title">Administrative &amp; Support Staff</h3>
        <p class="feature-desc">
          Our attentive administrative office team, certified bus drivers, and caring campus helpers ensure smooth operations and a secure learning environment daily.
        </p>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Staff Inquiries CTA -->
<section class="section-pad" style="background-color: #ffffff; text-align: center;">
  <div class="container">
    <h2 style="font-family: var(--font-heading); font-size: 2rem; color: var(--primary-800); margin-bottom: 12px;">Connect with Our School Staff</h2>
    <p style="font-size: 1.05rem; color: var(--text-muted); max-width: 600px; margin: 0 auto 25px;">
      For parent-teacher consultations, administrative questions, or student support, our office is ready to assist you.
    </p>
    <a href="contact.php" class="btn btn-primary"><i class="fas fa-envelope"></i> Contact School Office</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
