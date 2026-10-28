/**
 * Mount Excel Boarding School - Admin Dashboard Scripts
 */

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // 1. Sidebar Toggle on Mobile
  const sidebar = document.getElementById('adminSidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const backdrop = document.getElementById('sidebarBackdrop');

  if (sidebar && toggleBtn) {
    const closeSidebar = () => {
      sidebar.classList.remove('open');
      toggleBtn.setAttribute('aria-expanded', 'false');
      toggleBtn.setAttribute('aria-label', 'Open navigation menu');
      if (backdrop) backdrop.hidden = true;
      if (window.innerWidth <= 900) toggleBtn.focus();
    };
    const openSidebar = () => {
      sidebar.classList.add('open');
      toggleBtn.setAttribute('aria-expanded', 'true');
      toggleBtn.setAttribute('aria-label', 'Close navigation menu');
      if (backdrop) backdrop.hidden = false;
      const firstLink = sidebar.querySelector('.sidebar-nav a');
      if (firstLink) firstLink.focus();
    };

    toggleBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (sidebar.classList.contains('open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    if (backdrop) backdrop.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', (e) => {
      if (window.innerWidth <= 900 && sidebar.classList.contains('open')) {
        if (e.key === 'Escape') {
          closeSidebar();
          return;
        }
        if (e.key === 'Tab') {
          const focusable = Array.from(sidebar.querySelectorAll('a[href], button:not([disabled])'));
          if (!focusable.length) return;
          const first = focusable[0];
          const last = focusable[focusable.length - 1];
          if (!sidebar.contains(document.activeElement)) {
            e.preventDefault();
            first.focus();
          } else if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
          } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
          }
        }
      }
    });

    sidebar.querySelectorAll('.sidebar-nav a').forEach((link) => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 900) closeSidebar();
      });
    });

    // Close when clicking outside on mobile
    document.addEventListener('click', (e) => {
      if (window.innerWidth <= 900 && sidebar.classList.contains('open')) {
        if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
          closeSidebar();
        }
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 900) closeSidebar();
    });
  }

  // 2. Destructive Actions Confirmation
  const confirmElements = document.querySelectorAll('[data-confirm]');
  confirmElements.forEach(el => {
    el.addEventListener('click', (e) => {
      const message = el.getAttribute('data-confirm') || 'Are you sure you want to proceed with this action?';
      if (!confirm(message)) {
        e.preventDefault();
      }
    });
  });
});
