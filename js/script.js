/**
 * Mount Excel Boarding School - Client JavaScript
 * Mobile Navigation, Accessible Form Validation & Interactive UI
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // ============================================================================
  // 1. Mobile Navigation Drawer Toggle
  // ============================================================================
  const mobileToggle = document.getElementById('mobileNavToggle');
  const primaryNav = document.getElementById('primaryNav');

  if (mobileToggle && primaryNav) {
    mobileToggle.addEventListener('click', () => {
      const isExpanded = mobileToggle.getAttribute('aria-expanded') === 'true';
      mobileToggle.setAttribute('aria-expanded', !isExpanded);
      mobileToggle.classList.toggle('open');
      primaryNav.classList.toggle('open');
    });

    // Close mobile nav on escape key press
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && primaryNav.classList.contains('open')) {
        mobileToggle.setAttribute('aria-expanded', 'false');
        mobileToggle.classList.remove('open');
        primaryNav.classList.remove('open');
        mobileToggle.focus();
      }
    });
  }

  // ============================================================================
  // 2. Client-side Validators (Unicode & Nepali Support)
  // ============================================================================

  /**
   * Supports English letters and Devanagari script (Nepali/Hindi), dots, spaces, hyphens
   * Handles names like "राम श्रेष्ठ", "Om Rai", "Aarav K.C."
   */
  const isValidPersonName = (name) => {
    const trimmed = name.trim();
    if (trimmed.length < 2 || trimmed.length > 100) return false;
    // Unicode regex: letters (\p{L}), Devanagari (\u0900-\u097F), dots, spaces, hyphens
    const nameRegex = /^[\u0900-\u097Fa-zA-Z\s\.\'\-]+$/;
    return nameRegex.test(trimmed);
  };

  /**
   * Supports standard Nepali mobile numbers:
   * 98XXXXXXXX, 97XXXXXXXX, 96XXXXXXXX, +977-98XXXXXXXX, and landlines like 01-XXXXXXX
   */
  const isValidNepaliPhone = (phone) => {
    const clean = phone.replace(/[\s\-\(\)]/g, '');
    const phoneRegex = /^(\+?977)?(9[678]\d{8}|0\d{1,2}\d{6,7})$/;
    return phoneRegex.test(clean);
  };

  const isValidEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email.trim());
  };

  // Helper to show inline error or alert box
  const showFormError = (form, message) => {
    let errorBox = form.querySelector('.form-inline-error');
    if (!errorBox) {
      errorBox = document.createElement('div');
      errorBox.className = 'alert alert-error form-inline-error';
      errorBox.setAttribute('role', 'alert');
      form.insertBefore(errorBox, form.firstChild);
    }
    errorBox.textContent = message;
    errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  };

  const clearFormError = (form) => {
    const errorBox = form.querySelector('.form-inline-error');
    if (errorBox) {
      errorBox.remove();
    }
  };

  // ============================================================================
  // 3. Admission Form Validation & Double-Submission Guard
  // ============================================================================
  const admissionForm = document.getElementById('admissionForm');
  if (admissionForm) {
    admissionForm.addEventListener('submit', function (e) {
      clearFormError(this);

      const fullName = (document.getElementById('fullName') || {}).value || '';
      const dob = (document.getElementById('dob') || {}).value || '';
      const gender = (document.getElementById('gender') || {}).value || '';
      const grade = (document.getElementById('grade') || {}).value || '';
      const parentContact = (document.getElementById('parentContact') || {}).value || '';
      const address = (document.getElementById('address') || {}).value || '';

      if (!isValidPersonName(fullName)) {
        e.preventDefault();
        showFormError(this, 'Please enter a valid student full name (English or Nepali Unicode letters only).');
        return;
      }

      if (!dob) {
        e.preventDefault();
        showFormError(this, 'Please select the student\'s date of birth.');
        return;
      }

      if (!gender) {
        e.preventDefault();
        showFormError(this, 'Please select the student\'s gender.');
        return;
      }

      if (!grade) {
        e.preventDefault();
        showFormError(this, 'Please select the grade applying for.');
        return;
      }

      if (!isValidNepaliPhone(parentContact)) {
        e.preventDefault();
        showFormError(this, 'Please enter a valid Nepali contact number (10 digits starting with 98, 97, or 96).');
        return;
      }

      if (address.trim().length < 5) {
        e.preventDefault();
        showFormError(this, 'Please provide a complete home address (at least 5 characters).');
        return;
      }

      // Prevent duplicate submission
      const submitBtn = this.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Application...';
      }
    });
  }

  // ============================================================================
  // 4. Contact Form Validation & Double-Submission Guard
  // ============================================================================
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
      clearFormError(this);

      const name = (document.getElementById('name') || {}).value || '';
      const email = (document.getElementById('email') || {}).value || '';
      const phone = (document.getElementById('phone') || {}).value || '';
      const message = (document.getElementById('message') || {}).value || '';

      if (!isValidPersonName(name)) {
        e.preventDefault();
        showFormError(this, 'Please enter your full name (English or Nepali Unicode letters).');
        return;
      }

      if (!isValidEmail(email)) {
        e.preventDefault();
        showFormError(this, 'Please enter a valid email address.');
        return;
      }

      if (phone.trim() !== '' && !isValidNepaliPhone(phone)) {
        e.preventDefault();
        showFormError(this, 'If providing a phone number, please enter a valid Nepali phone number.');
        return;
      }

      if (message.trim().length < 10) {
        e.preventDefault();
        showFormError(this, 'Please enter a descriptive message (at least 10 characters).');
        return;
      }

      // Prevent duplicate submission
      const submitBtn = this.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending Message...';
      }
    });
  }
});
