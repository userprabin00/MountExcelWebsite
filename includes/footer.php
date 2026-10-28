  </main>

  <!-- School Footer -->
  <footer class="site-footer">
    <div class="container footer-grid">
      <!-- Col 1: Identity -->
      <div class="footer-col footer-brand-col">
        <div class="footer-logo-wrap">
          <img src="images/logo.jpg" alt="Mount Excel Boarding School Logo" class="footer-logo" width="70" height="70" loading="lazy" />
          <div>
            <h3 class="footer-school-name">Mount Excel Boarding School</h3>
            <p class="footer-motto"><em>School for Excellence</em> &bull; Since 2015</p>
          </div>
        </div>
        <p class="footer-desc">
          Committed to empowering students from Nursery to Grade 8 with academic excellence, ethical values, and practical life skills in a caring and supportive environment.
        </p>
        <div class="footer-social-links">
          <a href="<?= SCHOOL_FACEBOOK ?>" target="_blank" rel="noopener noreferrer" aria-label="Mount Excel on Facebook" class="social-btn facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="<?= SCHOOL_INSTAGRAM ?>" target="_blank" rel="noopener noreferrer" aria-label="Mount Excel on Instagram" class="social-btn instagram"><i class="fab fa-instagram"></i></a>
          <a href="mailto:<?= e(SCHOOL_EMAIL) ?>" aria-label="Email Mount Excel" class="social-btn email"><i class="fas fa-envelope"></i></a>
        </div>
      </div>

      <!-- Col 2: Quick Links -->
      <div class="footer-col">
        <h4 class="footer-heading">Quick Links</h4>
        <ul class="footer-links">
          <li><a href="index.php"><i class="fas fa-angle-right"></i> Home</a></li>
          <li><a href="about.php"><i class="fas fa-angle-right"></i> About Us</a></li>
          <li><a href="academics.php"><i class="fas fa-angle-right"></i> Academics</a></li>
          <li><a href="admissions.php"><i class="fas fa-angle-right"></i> Admissions</a></li>
          <li><a href="facilities.php"><i class="fas fa-angle-right"></i> School Facilities</a></li>
          <li><a href="staff.php"><i class="fas fa-angle-right"></i> Executive Staff</a></li>
          <li><a href="contact.php"><i class="fas fa-angle-right"></i> Contact Us</a></li>
        </ul>
      </div>

      <!-- Col 3: Programs & Activities -->
      <div class="footer-col">
        <h4 class="footer-heading">Programs & Gallery</h4>
        <ul class="footer-links">
          <li><a href="academics.php#preprimary"><i class="fas fa-angle-right"></i> Pre-Primary (PG - UKG)</a></li>
          <li><a href="academics.php#primary"><i class="fas fa-angle-right"></i> Primary Level (Grades 1-5)</a></li>
          <li><a href="academics.php#lowersecondary"><i class="fas fa-angle-right"></i> Lower Secondary (Grades 6-8)</a></li>
          <li><a href="merit-scholarship.php"><i class="fas fa-angle-right"></i> Merit Scholarship</a></li>
          <li><a href="excursion.php"><i class="fas fa-angle-right"></i> Educational Excursions</a></li>
          <li><a href="art-classes.php"><i class="fas fa-angle-right"></i> Creative Art Classes</a></li>
          <li><a href="video.php"><i class="fas fa-angle-right"></i> Video Gallery</a></li>
        </ul>
      </div>

      <!-- Col 4: Contact & Hours -->
      <div class="footer-col footer-contact-col">
        <h4 class="footer-heading">Contact Information</h4>
        <ul class="footer-contact-list">
          <li>
            <i class="fas fa-map-marker-alt footer-icon"></i>
            <span>Indrawati Municipality 12, Bhimtar, Sindhupalchowk, Bagmati, Nepal</span>
          </li>
          <li>
            <i class="fas fa-phone-alt footer-icon"></i>
            <span><a href="tel:<?= e(preg_replace('/[^+0-9]/', '', SCHOOL_PHONE)) ?>"><?= e(SCHOOL_PHONE) ?></a></span>
          </li>
          <li>
            <i class="fas fa-envelope footer-icon"></i>
            <span><a href="mailto:<?= e(SCHOOL_EMAIL) ?>"><?= e(SCHOOL_EMAIL) ?></a></span>
          </li>
          <li>
            <i class="fas fa-clock footer-icon"></i>
            <span>Sunday &ndash; Friday: 9:00 AM &ndash; 4:00 PM</span>
          </li>
        </ul>
        <div class="footer-cta-box">
          <p class="cta-mini-text">Admissions are currently open for new academic sessions.</p>
          <a href="admissions.php" class="btn btn-sm btn-outline-gold">Apply Online &rarr;</a>
        </div>
      </div>
    </div>

    <!-- Copyright Sub-footer -->
    <div class="footer-bottom">
      <div class="container footer-bottom-container">
        <p class="copyright-text">
          &copy; <?= date('Y') ?> Mount Excel Boarding School. All rights reserved.
        </p>
        <p class="footer-credits">
          Indrawati 12, Bhimtar, Sindhupalchowk | School for Excellence
        </p>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="js/script.js?v=<?= e(file_exists(__DIR__ . '/../js/script.js') ? filemtime(__DIR__ . '/../js/script.js') : time()) ?>"></script>
</body>
</html>
