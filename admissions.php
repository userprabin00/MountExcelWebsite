<?php
$pageTitle = 'Admissions';
$pageDesc = 'Apply for admission at Mount Excel Boarding School. Online application form for Nursery to Grade 8 with clear guidelines and step-by-step procedures.';
$activePage = 'admissions';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Banner -->
<section class="page-banner">
  <div class="container banner-content">
    <h1 class="banner-title">Admissions & Enrollment</h1>
    <p class="banner-breadcrumbs">
      <a href="index.php">Home</a> &rsaquo; <span>Admissions</span>
    </p>
  </div>
</section>

<!-- Admissions Process Steps -->
<section class="section-pad">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">How to Join</span>
      <h2 class="section-title">Our Admission Process</h2>
      <p class="section-intro">
        We strive to make the admission process transparent, convenient, and welcoming for families.
      </p>
    </div>

    <div class="features-grid" style="grid-template-columns: repeat(4, 1fr);">
      <div class="feature-card" style="text-align: center;">
        <div class="feature-icon-box" style="margin-left: auto; margin-right: auto;"><i class="fas fa-file-alt"></i></div>
        <div class="badge badge-gold" style="margin-bottom: 10px;">Step 1</div>
        <h3 class="feature-title" style="font-size: 1.15rem;">Submit Application</h3>
        <p class="feature-desc">Complete the online form below or visit our school administration office in Bhimtar.</p>
      </div>

      <div class="feature-card" style="text-align: center;">
        <div class="feature-icon-box" style="margin-left: auto; margin-right: auto;"><i class="fas fa-search"></i></div>
        <div class="badge badge-blue" style="margin-bottom: 10px;">Step 2</div>
        <h3 class="feature-title" style="font-size: 1.15rem;">Document Review</h3>
        <p class="feature-desc">Our admissions team verifies submitted details and contacts parents via telephone.</p>
      </div>

      <div class="feature-card" style="text-align: center;">
        <div class="feature-icon-box" style="margin-left: auto; margin-right: auto;"><i class="fas fa-comments"></i></div>
        <div class="badge badge-gold" style="margin-bottom: 10px;">Step 3</div>
        <h3 class="feature-title" style="font-size: 1.15rem;">Interaction & Visit</h3>
        <p class="feature-desc">A brief, friendly interaction with the child and parents to understand learning readiness.</p>
      </div>

      <div class="feature-card" style="text-align: center;">
        <div class="feature-icon-box" style="margin-left: auto; margin-right: auto;"><i class="fas fa-check-circle"></i></div>
        <div class="badge badge-blue" style="margin-bottom: 10px;">Step 4</div>
        <h3 class="feature-title" style="font-size: 1.15rem;">Final Enrollment</h3>
        <p class="feature-desc">Confirmation of seat, fee payment schedule, uniform, and school bus route assignment.</p>
      </div>
    </div>
  </div>
</section>

<!-- Admission Application Form Section -->
<section class="section-pad" style="background-color: #ffffff;" id="application-form">
  <div class="container">
    <div class="section-header">
      <span class="section-pretitle">Online Application</span>
      <h2 class="section-title">Admission Application Form</h2>
      <p class="section-intro">
        Please fill in all required fields accurately. Once submitted, an official application reference number will be generated for your records.
      </p>
    </div>

    <?php 
    $admissionOpen = isAdmissionOpen();
    if (!$admissionOpen): 
    ?>
      <!-- Admission Closed Notice Card -->
      <div class="form-container-card" style="text-align: center; padding: 45px 30px; border-top: 5px solid var(--accent-gold); max-width: 750px; margin: 0 auto;">
        <div style="width: 75px; height: 75px; border-radius: 50%; background: #eff6ff; color: var(--primary-800); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 20px;">
          <i class="fas fa-calendar-alt"></i>
        </div>
        <h3 style="font-family: var(--font-heading); color: var(--primary-900); font-size: 1.5rem; margin-bottom: 12px;">
          Admission Notice
        </h3>
        <p style="font-size: 1.08rem; color: #334155; line-height: 1.7; max-width: 620px; margin: 0 auto 30px; font-weight: 500;">
          <?= nl2br(e(getAdmissionClosedMessage())) ?>
        </p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
          <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', SCHOOL_PHONE)) ?>" class="btn btn-primary btn-lg">
            <i class="fas fa-phone-alt"></i> Call School (<?= e(SCHOOL_PHONE) ?>)
          </a>
          <a href="contact.php" class="btn btn-outline btn-lg" style="border: 2px solid var(--primary-800); color: var(--primary-800);">
            <i class="fas fa-envelope"></i> Contact Us
          </a>
        </div>
      </div>
    <?php else: ?>
      <!-- Online Application Form -->
      <div class="form-container-card">
        <form action="submit_admission.php" method="POST" id="admissionForm" novalidate>
          <!-- CSRF Protection Token -->
          <?= csrfField() ?>

          <!-- Anti-Spam Honeypot Field (hidden from genuine users) -->
          <div class="honey-field" aria-hidden="true">
            <label for="website_hp">Leave this empty:</label>
            <input type="text" id="website_hp" name="website_hp" tabindex="-1" autocomplete="off" />
          </div>

          <div class="form-grid">
            <!-- Student Full Name -->
            <div class="form-group col-full">
              <label for="fullName" class="form-label">Student Full Name <span class="required-star">*</span></label>
              <input type="text" id="fullName" name="fullName" value="<?= e(old('fullName')) ?>" class="form-control" placeholder="e.g. Aarav Sharma or राम श्रेष्ठ" required maxlength="100" />
              <span class="form-help-text">Supports English letters and Nepali Unicode (Devanagari).</span>
            </div>

            <!-- Date of Birth -->
            <div class="form-group">
              <label for="dob" class="form-label">Date of Birth (AD) <span class="required-star">*</span></label>
              <input type="date" id="dob" name="dob" value="<?= e(old('dob')) ?>" class="form-control" required max="<?= date('Y-m-d') ?>" />
            </div>

            <!-- Gender -->
            <div class="form-group">
              <label for="gender" class="form-label">Gender <span class="required-star">*</span></label>
              <select id="gender" name="gender" class="form-control" required>
                <option value="">-- Select Gender --</option>
                <option value="Male" <?= (old('gender') === 'Male') ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= (old('gender') === 'Female') ? 'selected' : '' ?>>Female</option>
                <option value="Other" <?= (old('gender') === 'Other') ? 'selected' : '' ?>>Other</option>
              </select>
            </div>

            <!-- Grade Applying For -->
            <div class="form-group">
              <label for="grade" class="form-label">Applying for Grade <span class="required-star">*</span></label>
              <select id="grade" name="grade" class="form-control" required>
                <option value="">-- Select Academic Grade --</option>
                <?php 
                $gradesMap = [
                  'PG'      => 'Play Group (PG)',
                  'Nursery' => 'Nursery',
                  'LKG'     => 'LKG',
                  'UKG'     => 'UKG',
                  'Grade 1' => 'Grade 1',
                  'Grade 2' => 'Grade 2',
                  'Grade 3' => 'Grade 3',
                  'Grade 4' => 'Grade 4',
                  'Grade 5' => 'Grade 5',
                  'Grade 6' => 'Grade 6',
                  'Grade 7' => 'Grade 7',
                  'Grade 8' => 'Grade 8'
                ];
                foreach ($gradesMap as $code => $label): 
                  $gradeOpen = isGradeAdmissionOpen($code);
                ?>
                  <option value="<?= $code ?>" <?= !$gradeOpen ? 'disabled' : '' ?> <?= (old('grade') === $code) ? 'selected' : '' ?>>
                    <?= $label ?><?= !$gradeOpen ? ' (Closed)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Parent / Guardian Contact -->
            <div class="form-group">
              <label for="parentContact" class="form-label">Parent Mobile Number <span class="required-star">*</span></label>
              <input type="tel" id="parentContact" name="parentContact" value="<?= e(old('parentContact')) ?>" class="form-control" placeholder="e.g. 9851148834 or 97XXXXXXXX" required maxlength="15" />
              <span class="form-help-text">10-digit Nepali mobile number (NTC/Ncell/Smart).</span>
            </div>

            <!-- Parent / Guardian Name -->
            <div class="form-group">
              <label for="parentName" class="form-label">Parent / Guardian Full Name</label>
              <input type="text" id="parentName" name="parentName" value="<?= e(old('parentName')) ?>" class="form-control" placeholder="Parent or guardian full name" maxlength="100" />
            </div>

            <!-- Parent Email -->
            <div class="form-group">
              <label for="parentEmail" class="form-label">Parent Email Address</label>
              <input type="email" id="parentEmail" name="parentEmail" value="<?= e(old('parentEmail')) ?>" class="form-control" placeholder="e.g. parent@example.com" maxlength="120" />
            </div>

            <!-- Previous School -->
            <div class="form-group col-full">
              <label for="previousSchool" class="form-label">Previous School Attended (If applicable)</label>
              <input type="text" id="previousSchool" name="previousSchool" value="<?= e(old('previousSchool')) ?>" class="form-control" placeholder="Name of previous school or 'None' for new admissions" maxlength="150" />
            </div>

            <!-- Permanent / Home Address -->
            <div class="form-group col-full">
              <label for="address" class="form-label">Residential Address <span class="required-star">*</span></label>
              <textarea id="address" name="address" class="form-control" rows="3" placeholder="Municipality, Ward No, Village / Tole (e.g., Indrawati-12, Bhimtar)" required minlength="5" maxlength="500"><?= e(old('address')) ?></textarea>
            </div>
          </div>

          <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 15px; border-radius: var(--radius-md); margin-bottom: 25px; font-size: 0.85rem; color: #475569;">
            <p><i class="fas fa-shield-alt" style="color: var(--primary-800);"></i> <strong>Privacy Notice:</strong> We collect the student's name and date of birth, address, and parent/guardian contact details to process this application and communicate about admission. Only authorized school staff handling admissions can access these details. To request deletion, contact the school at <a href="mailto:<?= e(SCHOOL_EMAIL) ?>"><?= e(SCHOOL_EMAIL) ?></a>.</p>
            <p lang="ne"><strong>गोपनीयता सूचना:</strong> भर्ना प्रक्रिया र आवश्यक सम्पर्कका लागि विद्यार्थीको नाम, जन्ममिति, ठेगाना तथा अभिभावकको सम्पर्क विवरण सङ्कलन गरिन्छ। भर्ना सम्बन्धी काम गर्ने अधिकृत विद्यालय कर्मचारीले मात्र यी विवरण हेर्न पाउँछन्। विवरण मेटाउन अनुरोध गर्न विद्यालयमा <a href="mailto:<?= e(SCHOOL_EMAIL) ?>"><?= e(SCHOOL_EMAIL) ?></a> मार्फत सम्पर्क गर्नुहोस्।</p>
          </div>

          <?php if (env('TURNSTILE_SECRET', '') !== '' && env('TURNSTILE_SITE_KEY', '') !== ''): ?>
            <div class="form-group"><div class="cf-turnstile" data-sitekey="<?= e(env('TURNSTILE_SITE_KEY')) ?>"></div></div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
          <?php endif; ?>

          <button type="submit" class="btn btn-accent btn-lg" style="width: 100%;">
            <i class="fas fa-check-circle"></i> Submit Admission Application
          </button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
