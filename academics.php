<?php
$pageTitle = 'Academics & Curriculum';
$pageDesc = 'Academic curriculum at Mount Excel Boarding School, Bhimtar. Structured education from Nursery to Grade 8 focusing on foundational excellence and character development.';
$activePage = 'academics';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Banner -->
<section class="page-banner">
  <div class="container banner-content">
    <h1 class="banner-title">Academics & Learning Programs</h1>
    <p class="banner-breadcrumbs">
      <a href="index.php">Home</a> &rsaquo; <span>Academics</span>
    </p>
  </div>
</section>

<!-- Overview -->
<section class="section-pad">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Holistic Education</span>
      <h2 class="section-title">Academic Structure: Nursery to Grade 8</h2>
      <p class="section-intro">
        Our academic framework adheres to the national curriculum standards of Nepal while integrating interactive teaching methods, moral education, and practical skills.
      </p>
    </div>

    <!-- Levels Grid -->
    <div class="features-grid">
      <!-- Pre-Primary -->
      <div class="feature-card" id="preprimary">
        <div class="feature-icon-box"><i class="fas fa-shapes"></i></div>
        <div class="badge badge-gold" style="margin-bottom: 12px;">Pre-Primary Level</div>
        <h3 class="feature-title">Play Group to UKG</h3>
        <p class="feature-desc">
          Focuses on sensory learning, phonetics, foundational counting, socialization, motor skills, and creative play in a warm, welcoming environment.
        </p>
        <ul style="margin-top: 15px; font-size: 0.9rem; color: #475569; list-style: disc; padding-left: 20px;">
          <li>Play Group (PG)</li>
          <li>Nursery</li>
          <li>Lower Kindergarten (LKG)</li>
          <li>Upper Kindergarten (UKG)</li>
        </ul>
      </div>

      <!-- Primary -->
      <div class="feature-card" id="primary">
        <div class="feature-icon-box"><i class="fas fa-book-open"></i></div>
        <div class="badge badge-blue" style="margin-bottom: 12px;">Primary Level</div>
        <h3 class="feature-title">Grades 1 through 5</h3>
        <p class="feature-desc">
          Emphasizes core literacy in English and Nepali, mathematical proficiency, environmental science, moral values, and cooperative classroom activities.
        </p>
        <ul style="margin-top: 15px; font-size: 0.9rem; color: #475569; list-style: disc; padding-left: 20px;">
          <li>Interactive learning modules</li>
          <li>English and Nepali language skills</li>
          <li>Basic science & social studies</li>
          <li>Arts and physical exercise</li>
        </ul>
      </div>

      <!-- Lower Secondary -->
      <div class="feature-card" id="lowersecondary">
        <div class="feature-icon-box"><i class="fas fa-flask"></i></div>
        <div class="badge badge-gold" style="margin-bottom: 12px;">Lower Secondary</div>
        <h3 class="feature-title">Grades 6 through 8</h3>
        <p class="feature-desc">
          Deepens conceptual understanding, critical thinking, practical science demonstrations, computer literacy, and prepares students for secondary education.
        </p>
        <ul style="margin-top: 15px; font-size: 0.9rem; color: #475569; list-style: disc; padding-left: 20px;">
          <li>Analytical mathematics & science</li>
          <li>Computer education</li>
          <li>Social studies & civic duties</li>
          <li>Continuous formative assessment</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- Teaching Methodology & Evaluation -->
<section class="section-pad" style="background-color: #ffffff;">
  <div class="container">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;" class="about-grid">
      <div>
        <span class="section-pretitle">Teaching Methodology</span>
        <h2 class="section-title">How We Guide Young Learners</h2>
        <p style="font-size: 1.05rem; color: #334155; margin-bottom: 15px;">
          At Mount Excel, our teaching philosophy goes beyond rote memorization. Teachers utilize smart classroom techniques, visual aids, active classroom discussions, and practical experiments.
        </p>
        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
          <div style="font-size: 1.5rem; color: var(--accent-gold);"><i class="fas fa-check-circle"></i></div>
          <div>
            <h4 style="font-family: var(--font-heading); color: var(--primary-800);">Continuous Assessment</h4>
            <p style="font-size: 0.9rem; color: #64748b;">Regular unit tests, homework tracking, term examinations, and constructive feedback loops with parents.</p>
          </div>
        </div>
        <div style="display: flex; gap: 15px;">
          <div style="font-size: 1.5rem; color: var(--accent-gold);"><i class="fas fa-check-circle"></i></div>
          <div>
            <h4 style="font-family: var(--font-heading); color: var(--primary-800);">Merit Recognition</h4>
            <p style="font-size: 0.9rem; color: #64748b;">Annual scholarship awards and certificates recognizing scholastic achievement and motivating all learners.</p>
          </div>
        </div>
      </div>

      <div>
        <img src="images/classroom.jpg" alt="Classroom learning environment at Mount Excel Boarding School" style="border-radius: var(--radius-lg); box-shadow: var(--shadow-md); width: 100%; object-fit: cover; max-height: 380px;" loading="lazy" />
      </div>
    </div>
  </div>
</section>

<!-- Call to Action -->
<section class="section-pad" style="background-color: var(--primary-50); text-align: center;">
  <div class="container">
    <h2 style="font-family: var(--font-heading); font-size: 2rem; color: var(--primary-800); margin-bottom: 12px;">Interested in Enrolling Your Child?</h2>
    <p style="font-size: 1.05rem; color: var(--text-muted); max-width: 600px; margin: 0 auto 25px;">
      Explore our transparent admission process and submit an online application today.
    </p>
    <a href="admissions.php" class="btn btn-accent"><i class="fas fa-user-plus"></i> View Admissions & Apply Online</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
