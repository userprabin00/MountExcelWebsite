<?php
$pageTitle = 'Home';
$pageDesc = 'Mount Excel Boarding School - School for Excellence. Empowering students in Bhimtar, Sindhupalchowk from Nursery to Grade 8 since 2015.';
$activePage = 'home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section" aria-label="Welcome to Mount Excel Boarding School">
  <div class="container hero-content">
    <div class="hero-badge-pill">
      <i class="fas fa-graduation-cap"></i> <em>School for Excellence</em> &bull; Since 2015
    </div>
    <h1 class="hero-title">Welcome to Mount Excel Boarding School</h1>
    <p class="hero-subtitle">
      Empowering students from Nursery to Grade 8 to reach their highest potential through disciplined, holistic, and value-based quality education.
    </p>
    <div class="hero-cta-group">
      <a href="admissions.php" class="btn btn-lg btn-accent"><i class="fas fa-file-alt"></i> Apply for Admission</a>
      <a href="about.php" class="btn btn-lg btn-outline-white"><i class="fas fa-info-circle"></i> Discover Our School</a>
    </div>
  </div>
</section>

<!-- Key Stats Strip -->
<div class="container">
  <div class="stats-strip">
    <div class="stats-grid">
      <div class="stat-item">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-number">2015</div>
        <div class="stat-label">Year Established</div>
      </div>
      <div class="stat-item">
        <div class="stat-icon"><i class="fas fa-book-reader"></i></div>
        <div class="stat-number">Nursery &ndash; 8</div>
        <div class="stat-label">Academic Grades</div>
      </div>
      <div class="stat-item">
        <div class="stat-icon"><i class="fas fa-bus"></i></div>
        <div class="stat-number">Bus & Van</div>
        <div class="stat-label">Safe School Transit</div>
      </div>
      <div class="stat-item">
        <div class="stat-icon"><i class="fas fa-award"></i></div>
        <div class="stat-number">100%</div>
        <div class="stat-label">Commitment to Growth</div>
      </div>
    </div>
  </div>
</div>

<!-- Mission & Philosophy -->
<section class="section-pad">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Who We Are</span>
      <h2 class="section-title">Our Mission & Educational Vision</h2>
      <p class="section-intro">
        We aim to provide a holistic and disciplined learning environment that inspires excellence in academics, character, and practical life skills.
      </p>
    </div>

    <!-- Feature Pillars -->
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-chalkboard-teacher"></i></div>
        <h3 class="feature-title">Modern Classrooms</h3>
        <p class="feature-desc">
          Smart digital content, comfortable and supportive learning spaces designed for active student engagement and collaborative inquiry.
        </p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-user-graduate"></i></div>
        <h3 class="feature-title">Experienced Faculty</h3>
        <p class="feature-desc">
          Caring, qualified, and motivated teachers who nurture every child with individual attention, moral guidance, and academic rigor.
        </p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-box"><i class="fas fa-palette"></i></div>
        <h3 class="feature-title">Extracurricular Activities</h3>
        <p class="feature-desc">
          A wide range of creative clubs, fine arts, educational excursions, and sports that help students explore their diverse talents and build confidence.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Principal's Message Preview -->
<section class="section-pad" style="background-color: #f1f5f9;">
  <div class="container">
    <div class="quote-card">
      <div class="quote-avatar-wrap">
        <img src="images/principal.jpg" alt="Mr. Nabaraj Rai, Principal of Mount Excel Boarding School" class="quote-avatar" width="170" height="200" loading="lazy" />
        <h3 class="quote-name">Mr. Nabaraj Rai</h3>
        <p class="quote-role">Principal</p>
      </div>
      <div>
        <span class="section-pretitle">Leadership Message</span>
        <h3 style="font-family: var(--font-heading); font-size: 1.6rem; color: var(--primary-800); margin-bottom: 15px;">A Message from the Principal</h3>
        <blockquote class="quote-text">
          "Dear students and parents, at Mount Excel Boarding School, we care about every child. We don’t just teach books &mdash; we help you grow with confidence, kindness, and knowledge. Our school is a happy place to learn, make friends, and discover your talents. Join us, and let’s build your bright future together!"
        </blockquote>
        <div style="margin-top: 20px;">
          <a href="about.php" class="btn btn-sm btn-primary">Read More About Our School &rarr;</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- School Transportation Service -->
<section class="section-pad">
  <div class="container">
    <div class="bus-card">
      <div class="bus-icon"><i class="fas fa-bus-alt"></i></div>
      <div class="bus-info">
        <h3>Safe & Reliable School Transportation</h3>
        <p>
          Mount Excel Boarding School provides safe and reliable school transportation for students across local routes. We operate <strong>one school bus and one van</strong>, both regularly maintained and driven by trained, responsible drivers. This ensures a comfortable, supervised, and timely journey to and from school every day.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Gallery & Activities Preview -->
<section class="section-pad" style="background-color: #ffffff;">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Campus Life</span>
      <h2 class="section-title">Recent Activities & Events</h2>
      <p class="section-intro">
        Our students participate in engaging academic competitions, cultural events, and outdoor learning experiences.
      </p>
    </div>

    <div class="gallery-grid">
      <!-- Event 1: Merit -->
      <div class="gallery-card">
        <div class="gallery-img-wrap">
          <img src="images/event1.jpg" alt="Merit Scholarship Award Distribution ceremony" loading="lazy" />
        </div>
        <div class="gallery-card-body">
          <h3 class="gallery-card-title">Merit Scholarship Distribution</h3>
          <p class="gallery-card-desc">Recognizing outstanding academic performance and celebrating excellence with certificates and awards.</p>
          <a href="merit-scholarship.php" class="btn btn-sm btn-outline-white" style="color: var(--primary-800); border-color: var(--primary-800);">View Event Details &rarr;</a>
        </div>
      </div>

      <!-- Event 2: Excursion -->
      <div class="gallery-card">
        <div class="gallery-img-wrap">
          <img src="images/event2.jpg" alt="Students excursion" loading="lazy" />
        </div>
        <div class="gallery-card-body">
          <h3 class="gallery-card-title">Excursion</h3>
          <p class="gallery-card-desc">Experiential outdoor learning exploring local cultural heritage, teamwork, and natural landscapes.</p>
          <a href="excursion.php" class="btn btn-sm btn-outline-white" style="color: var(--primary-800); border-color: var(--primary-800);">View Event Details &rarr;</a>
        </div>
      </div>

      <!-- Event 3: Art -->
      <div class="gallery-card">
        <div class="gallery-img-wrap">
          <img src="images/event3.jpg" alt="Creative Art Classes at Mount Excel" loading="lazy" />
        </div>
        <div class="gallery-card-body">
          <h3 class="gallery-card-title">Art & Creative Classes</h3>
          <p class="gallery-card-desc">Fostering imagination, fine motor skills, and artistic expression through painting, sketch, and crafts.</p>
          <a href="art-classes.php" class="btn btn-sm btn-outline-white" style="color: var(--primary-800); border-color: var(--primary-800);">View Event Details &rarr;</a>
        </div>
      </div>
    </div>

    <div style="text-align: center; margin-top: 40px;">
      <a href="gallery.php" class="btn btn-primary"><i class="fas fa-images"></i> View All Galleries & Videos</a>
    </div>
  </div>
</section>

<!-- Call to Action Banner -->
<section class="section-pad" style="background: linear-gradient(135deg, var(--primary-900) 0%, var(--primary-800) 100%); color: #ffffff; text-align: center;">
  <div class="container">
    <h2 style="font-family: var(--font-heading); font-size: 2.4rem; font-weight: 800; margin-bottom: 16px;">Enroll Your Child at Mount Excel Today</h2>
    <p style="font-size: 1.15rem; color: #cbd5e1; max-width: 650px; margin: 0 auto 30px;">
      Give your child the foundation for a lifetime of excellence. Applications are open for Nursery through Grade 8.
    </p>
    <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
      <a href="admissions.php" class="btn btn-lg btn-accent"><i class="fas fa-paper-plane"></i> Apply Online Now</a>
      <a href="contact.php" class="btn btn-lg btn-outline-white"><i class="fas fa-phone-alt"></i> Contact Admissions Office</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
