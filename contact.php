<?php
$pageTitle = 'Contact Us';
$pageDesc = 'Get in touch with Mount Excel Boarding School in Bhimtar, Sindhupalchowk. Admissions office, phone numbers, email, location map, and inquiry form.';
$activePage = 'contact';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Banner -->
<section class="page-banner">
  <div class="container banner-content">
    <h1 class="banner-title">Contact & Campus Location</h1>
    <p class="banner-breadcrumbs">
      <a href="index.php">Home</a> &rsaquo; <span>Contact Us</span>
    </p>
  </div>
</section>

<!-- Contact Info & Form Grid -->
<section class="section-pad">
  <div class="container">
    <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px; align-items: start;" class="contact-grid">
      
      <!-- Contact Details & School Info -->
      <div>
        <span class="section-pretitle">Get in Touch</span>
        <h2 class="section-title">We are Here to Help</h2>
        <p style="font-size: 1.05rem; color: #475569; margin-bottom: 30px;">
          Have questions regarding admissions, academics, transportation, or fee structures? Reach out to our administration office.
        </p>

        <div style="display: flex; flex-direction: column; gap: 20px;">
          <!-- Address -->
          <div style="display: flex; gap: 16px; align-items: flex-start;">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: var(--primary-50); color: var(--primary-800); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
              <i class="fas fa-map-marker-alt"></i>
            </div>
            <div>
              <h4 style="font-family: var(--font-heading); color: var(--primary-800); font-size: 1.05rem;">Campus Location</h4>
              <p style="color: #64748b; font-size: 0.95rem;">Indrawati Municipality 12, Bhimtar, Sindhupalchowk, Bagmati Province, Nepal</p>
            </div>
          </div>

          <!-- Phone -->
          <div style="display: flex; gap: 16px; align-items: flex-start;">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: var(--primary-50); color: var(--primary-800); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
              <i class="fas fa-phone-alt"></i>
            </div>
            <div>
              <h4 style="font-family: var(--font-heading); color: var(--primary-800); font-size: 1.05rem;">Direct Phone</h4>
              <p style="color: #64748b; font-size: 0.95rem;">
                <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', SCHOOL_PHONE)) ?>" style="color: var(--primary-800); font-weight: 600;"><?= e(SCHOOL_PHONE) ?></a>
              </p>
            </div>
          </div>

          <!-- Email -->
          <div style="display: flex; gap: 16px; align-items: flex-start;">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: var(--primary-50); color: var(--primary-800); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
              <i class="fas fa-envelope"></i>
            </div>
            <div>
              <h4 style="font-family: var(--font-heading); color: var(--primary-800); font-size: 1.05rem;">Official Email</h4>
              <p style="color: #64748b; font-size: 0.95rem;">
                <a href="mailto:<?= e(SCHOOL_EMAIL) ?>" style="color: var(--primary-800); font-weight: 600;"><?= e(SCHOOL_EMAIL) ?></a>
              </p>
            </div>
          </div>

          <!-- Working Hours -->
          <div style="display: flex; gap: 16px; align-items: flex-start;">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: var(--primary-50); color: var(--primary-800); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
              <i class="fas fa-clock"></i>
            </div>
            <div>
              <h4 style="font-family: var(--font-heading); color: var(--primary-800); font-size: 1.05rem;">Office Hours</h4>
              <p style="color: #64748b; font-size: 0.95rem;">Sunday &ndash; Friday: 9:00 AM &ndash; 4:00 PM<br>(Closed on Saturdays & Public Holidays)</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact Inquiry Form Card -->
      <div class="form-container-card" style="margin: 0; max-width: 100%;">
        <h3 style="font-family: var(--font-heading); color: var(--primary-800); font-size: 1.4rem; margin-bottom: 8px;">Send Us an Inquiry</h3>
        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 25px;">Fill out the form below and our team will get back to you shortly.</p>

        <form action="submit_contact.php" method="POST" id="contactForm" novalidate>
          <?= csrfField() ?>

          <!-- Honeypot -->
          <div class="honey-field" aria-hidden="true">
            <label for="contact_hp">Leave empty:</label>
            <input type="text" id="contact_hp" name="contact_hp" tabindex="-1" autocomplete="off" />
          </div>

          <div class="form-group">
            <label for="name" class="form-label">Your Full Name <span class="required-star">*</span></label>
            <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" class="form-control" placeholder="Your full name" required maxlength="100" />
          </div>

          <div class="form-grid">
            <div class="form-group">
              <label for="email" class="form-label">Email Address <span class="required-star">*</span></label>
              <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" class="form-control" placeholder="name@example.com" required maxlength="100" />
            </div>

            <div class="form-group">
              <label for="phone" class="form-label">Contact Number (Optional)</label>
              <input type="tel" id="phone" name="phone" value="<?= e(old('phone')) ?>" class="form-control" placeholder="e.g. 98XXXXXXXX" maxlength="15" />
            </div>
          </div>

          <div class="form-group">
            <label for="subject" class="form-label">Inquiry Subject</label>
            <input type="text" id="subject" name="subject" value="<?= e(old('subject')) ?>" class="form-control" placeholder="e.g. Admission Inquiry, Bus Route, Fee Details" maxlength="150" />
          </div>

          <div class="form-group">
            <label for="message" class="form-label">Message <span class="required-star">*</span></label>
            <textarea id="message" name="message" class="form-control" rows="5" placeholder="How can we help you?" required minlength="10" maxlength="5000"><?= e(old('message')) ?></textarea>
          </div>

          <?php if (env('TURNSTILE_SECRET', '') !== '' && env('TURNSTILE_SITE_KEY', '') !== ''): ?>
            <div class="form-group"><div class="cf-turnstile" data-sitekey="<?= e(env('TURNSTILE_SITE_KEY')) ?>"></div></div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
          <?php endif; ?>

          <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
            <i class="fas fa-paper-plane"></i> Send Message
          </button>
        </form>
      </div>
    </div>

    <!-- Embedded Google Map -->
    <div style="margin-top: 60px; border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-md); border: 1px solid var(--border-color);">
      <iframe
        title="Mount Excel Boarding School Location in Bhimtar, Sindhupalchowk"
        src="https://www.google.com/maps?q=Mount+Excel+Boarding+School,+Bhimtar,+Sindhupalchowk&output=embed"
        width="100%"
        height="400"
        style="border:0; display:block;"
        allowfullscreen=""
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade">
      </iframe>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
