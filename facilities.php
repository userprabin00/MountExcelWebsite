<?php
$pageTitle = 'School Facilities';
$pageDesc = 'Explore the facilities at Mount Excel Boarding School in Bhimtar: modern classrooms, science and computer lab, safe transportation, and creative spaces.';
$activePage = 'facilities';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Banner -->
<section class="page-banner">
  <div class="container banner-content">
    <h1 class="banner-title">School Campus & Facilities</h1>
    <p class="banner-breadcrumbs">
      <a href="index.php">Home</a> &rsaquo; <span>Facilities</span>
    </p>
  </div>
</section>

<!-- Facilities Intro -->
<section class="section-pad">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Learning Environment</span>
      <h2 class="section-title">Designed for Growth, Safety & Excellence</h2>
      <p class="section-intro">
        Our school infrastructure provides children with comfortable, well-ventilated, and resource-rich spaces where they can learn, discover, and thrive.
      </p>
    </div>

    <div class="features-grid">
      <!-- Classrooms -->
      <div class="feature-card">
        <div style="height: 200px; margin: -35px -30px 25px -30px; overflow: hidden; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
          <img src="images/classroom.jpg" alt="Modern Classrooms at Mount Excel Boarding School" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy" />
        </div>
        <h3 class="feature-title">Modern & Engaging Classrooms</h3>
        <p class="feature-desc">
          Well-lit, ventilated classrooms equipped with age-appropriate furniture, teaching aids, and learning resources to maintain optimal student engagement and comfort.
        </p>
      </div>

      <!-- Science & Computer Lab -->
      <div class="feature-card">
        <div style="height: 200px; margin: -35px -30px 25px -30px; overflow: hidden; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
          <img src="images/lab.jpg" alt="Science and Computer Lab at Mount Excel Boarding School" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy" />
        </div>
        <h3 class="feature-title">Science & Computer Laboratory</h3>
        <p class="feature-desc">
          Practical learning spaces where students explore foundational science experiments and develop essential digital literacy under teacher supervision.
        </p>
      </div>

      <!-- Campus Building -->
      <div class="feature-card">
        <div style="height: 200px; margin: -35px -30px 25px -30px; overflow: hidden; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
          <img src="images/school1.jpg" alt="Mount Excel Boarding School Campus in Bhimtar" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy" />
        </div>
        <h3 class="feature-title">Safe Campus & Play Areas</h3>
        <p class="feature-desc">
          A secure, enclosed school perimeter in scenic Bhimtar that provides recreational play areas for sports, morning assembly, and healthy physical activity.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Transportation Focus Section -->
<section class="section-pad" style="background-color: #ffffff;">
  <div class="container">
    <div class="bus-card">
      <div class="bus-icon"><i class="fas fa-bus-alt"></i></div>
      <div class="bus-info">
        <h3>Dedicated School Transportation (Bus & Van)</h3>
        <p style="margin-bottom: 12px;">
          To ensure convenient and safe daily commutes for students across Bhimtar and neighboring settlements in Indrawati Municipality, Mount Excel operates <strong>one dedicated school bus and one school van</strong>.
        </p>
        <p>
          Both vehicles undergo regular technical safety inspections and are staffed by trained, responsible drivers. Pick-up and drop-off points are supervised to ensure student safety at all times.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Co-Curricular & Creative Spaces -->
<section class="section-pad">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Beyond Textbooks</span>
      <h2 class="section-title">Creative & Co-Curricular Facilities</h2>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-palette"></i></div>
        <h3 class="feature-title">Art & Craft Studio</h3>
        <p class="feature-desc">
          Dedicated creative sessions where students develop fine motor skills and artistic expression through painting, sketching, and traditional Nepali crafts.
        </p>
        <a href="art-classes.php" style="display: inline-block; margin-top: 15px; font-weight: 600; color: var(--primary-800);">View Art Gallery &rarr;</a>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-hiking"></i></div>
        <h3 class="feature-title">Educational Field Excursions</h3>
        <p class="feature-desc">
          Regular outdoor learning tours, heritage explorations, and nature outings that build teamwork and cultural knowledge.
        </p>
        <a href="excursion.php" style="display: inline-block; margin-top: 15px; font-weight: 600; color: var(--primary-800);">View Excursion Gallery &rarr;</a>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-trophy"></i></div>
        <h3 class="feature-title">Merit & Scholastic Recognition</h3>
        <p class="feature-desc">
          Structured awards program recognizing academic diligence, sportsmanship, and student leadership across all grade levels.
        </p>
        <a href="merit-scholarship.php" style="display: inline-block; margin-top: 15px; font-weight: 600; color: var(--primary-800);">View Merit Awards &rarr;</a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
