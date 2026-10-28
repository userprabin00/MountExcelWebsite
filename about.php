<?php
$pageTitle = 'About Us';
$pageDesc = 'Learn about Mount Excel Boarding School, founded in 2015 in Bhimtar, Sindhupalchowk. Dedicated to academic excellence, discipline, and character building.';
$activePage = 'about';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Banner -->
<section class="page-banner">
  <div class="container banner-content">
    <h1 class="banner-title">About Mount Excel Boarding School</h1>
    <p class="banner-breadcrumbs">
      <a href="index.php">Home</a> &rsaquo; <span>About Us</span>
    </p>
  </div>
</section>

<!-- Main Introduction -->
<section class="section-pad">
  <div class="container">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;" class="about-grid">
      <div>
        <span class="section-pretitle">Our Heritage & Mission</span>
        <h2 class="section-title">Nurturing Bright Minds with Care Since 2015</h2>
        <p style="font-size: 1.05rem; margin-bottom: 16px; color: #334155;">
          Mount Excel Boarding School was established in 2015 with a clear dedication: to provide high-quality, holistic, and value-grounded education for children in Indrawati Municipality and the wider Sindhupalchowk district.
        </p>
        <p style="font-size: 1.05rem; margin-bottom: 16px; color: #334155;">
          Guided by our foundational motto, <strong><em>"School for Excellence"</em></strong>, we provide a warm, secure, and disciplined learning environment serving students from Nursery through Grade 8.
        </p>
        <p style="font-size: 1.05rem; color: #334155;">
          Our curriculum balances strong academic rigor with character building, creative expression, sports, and practical life skills &mdash; preparing young learners to become confident, respectful, and responsible citizens.
        </p>
      </div>

      <div>
        <img src="images/school1.jpg" alt="Mount Excel Boarding School campus and building" style="border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); width: 100%; object-fit: cover; max-height: 400px;" loading="lazy" />
      </div>
    </div>
  </div>
</section>

<!-- Mission & Vision Pillars -->
<section class="section-pad" style="background-color: #ffffff;">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Guiding Principles</span>
      <h2 class="section-title">Our Mission, Vision & Core Values</h2>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-bullseye"></i></div>
        <h3 class="feature-title">Our Mission</h3>
        <p class="feature-desc">
          To provide an inclusive, safe, and disciplined educational environment that fosters high academic standards, ethical values, and mutual respect in every learner.
        </p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-eye"></i></div>
        <h3 class="feature-title">Our Vision</h3>
        <p class="feature-desc">
          To be a leading center of early and primary education in Sindhupalchowk, recognized for scholastic achievement, moral character, and joyful holistic learning.
        </p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-heart"></i></div>
        <h3 class="feature-title">Core Values</h3>
        <p class="feature-desc">
          Excellence, Integrity, Discipline, Compassion, and Curiosity. We believe in nurturing children who care for one another and strive to excel in every sphere.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Principal Leadership Section -->
<section class="section-pad">
  <div class="container">
    <div class="quote-card">
      <div class="quote-avatar-wrap">
        <img src="images/principal.jpg" alt="Mr. Nabaraj Rai, Principal of Mount Excel Boarding School" class="quote-avatar" width="170" height="200" loading="lazy" />
        <h3 class="quote-name">Mr. Nabaraj Rai</h3>
        <p class="quote-role">Principal & Head of School</p>
      </div>
      <div>
        <span class="section-pretitle">From the Principal's Desk</span>
        <h3 style="font-family: var(--font-heading); font-size: 1.6rem; color: var(--primary-800); margin-bottom: 15px;">Dedicated to Every Child's Journey</h3>
        <blockquote class="quote-text">
          "Dear students and parents,<br><br>
          At Mount Excel Boarding School, we care about every child. We don’t just teach books &mdash; we help you grow with confidence, kindness, and knowledge. Our school is a happy place to learn, make friends, and discover your talents.<br><br>
          Join us, and let’s build your bright future together!"
        </blockquote>
      </div>
    </div>
  </div>
</section>

<!-- Call to Action -->
<section class="section-pad" style="background-color: var(--primary-50); text-align: center;">
  <div class="container">
    <h2 style="font-family: var(--font-heading); font-size: 2rem; color: var(--primary-800); margin-bottom: 12px;">Experience Mount Excel in Person</h2>
    <p style="font-size: 1.05rem; color: var(--text-muted); max-width: 650px; margin: 0 auto 25px;">
      We invite parents and guardians to visit our campus in Bhimtar, meet our teachers, and learn more about our admissions process.
    </p>
    <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
      <a href="admissions.php" class="btn btn-accent"><i class="fas fa-file-alt"></i> Apply for Admission</a>
      <a href="contact.php" class="btn btn-primary"><i class="fas fa-map-marker-alt"></i> Visit Our Campus</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
