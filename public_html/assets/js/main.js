/**
 * main.js — Global JS for Dabhi Chikki storefront
 * Handles: sticky header, mobile nav, toast notifications, page loader
 */

(function () {
  'use strict';

  /* ── Page Loader ─────────────────────────────────── */
  window.addEventListener('load', () => {
    const loader = document.getElementById('page-loader');
    if (loader) {
      setTimeout(() => loader.classList.add('hidden'), 200);
    }
  });

  /* ── Sticky Header on Scroll ─────────────────────── */
  const header = document.querySelector('.site-header');
  if (header) {
    const onScroll = () => {
      header.classList.toggle('scrolled', window.scrollY > 10);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── Mobile Navigation ───────────────────────────── */
  const hamburger = document.querySelector('.hamburger');
  const mobileNav = document.querySelector('.mobile-nav');
  if (hamburger && mobileNav) {
    hamburger.addEventListener('click', () => {
      const isOpen = hamburger.classList.toggle('open');
      mobileNav.classList.toggle('open', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });
    // Close on link click
    mobileNav.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', () => {
        hamburger.classList.remove('open');
        mobileNav.classList.remove('open');
        document.body.style.overflow = '';
      });
    });
  }

  /* ── Toast Notifications (Yogurt Alley Sonner Style) ── */
  window.showToast = function (message, type = 'default', duration = 3000) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }

    const icons = {
      success: `<svg viewBox="0 0 24 24" fill="none" stroke="#00bb7f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>`,
      error:   `<svg viewBox="0 0 24 24" fill="none" stroke="#d40924" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
      default: `<svg viewBox="0 0 24 24" fill="none" stroke="#00bb7f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>`
    };

    const toast = document.createElement('div');
    toast.className = `toast-pill ${type}`;
    toast.innerHTML = `${icons[type] || icons.default}<span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.animation = 'toastSlideUp 0.25s ease-out forwards';
      setTimeout(() => toast.remove(), 250);
    }, duration);
  };

  /* ── Active Nav Link ─────────────────────────────── */
  const currentPage = window.location.pathname.split('/').pop() || 'index.php';
  document.querySelectorAll('.nav-link').forEach(link => {
    const href = (link.getAttribute('href') || '').split('/').pop();
    if (href === currentPage) {
      link.classList.add('active');
    }
  });

  /* ── Smooth Anchor Scroll ────────────────────────── */
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', e => {
      const target = document.querySelector(anchor.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* ── Accordion ───────────────────────────────────── */
  document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', () => {
      const item = header.closest('.accordion-item');
      const isOpen = item.classList.contains('open');
      document.querySelectorAll('.accordion-item').forEach(i => i.classList.remove('open'));
      if (!isOpen) item.classList.add('open');
    });
  });

  /* ── Format currency ─────────────────────────────── */
  window.formatPrice = function (amount) {
    return '₹' + parseFloat(amount).toFixed(2).replace(/\.00$/, '');
  };

  /* ── Top Announcement Bar Rotator ────────────────── */
  const announcements = [
    '✦ 100% PURE JAGGERY · ZERO REFINED SUGAR · HANDCRAFTED SINCE 2009 ✦',
    '✦ FREE PAN-INDIA DELIVERY ON ORDERS ABOVE ₹500 ✦',
    '✦ FRESHLY ROASTED & HANDMADE DAILY IN RAJKOT, GUJARAT ✦',
    '✦ USE CODE FIRST10 TO GET 10% OFF YOUR FIRST ORDER ✦'
  ];
  let annIndex = 0;
  const annEl = document.getElementById('ann-text');
  const annPrev = document.getElementById('ann-prev');
  const annNext = document.getElementById('ann-next');

  function showAnnouncement(idx) {
    if (!annEl) return;
    annEl.style.opacity = '0';
    setTimeout(() => {
      annIndex = (idx + announcements.length) % announcements.length;
      annEl.textContent = announcements[annIndex];
      annEl.style.opacity = '1';
    }, 200);
  }

  if (annEl) {
    let annTimer = setInterval(() => showAnnouncement(annIndex + 1), 4000);

    if (annPrev) {
      annPrev.addEventListener('click', () => {
        clearInterval(annTimer);
        showAnnouncement(annIndex - 1);
        annTimer = setInterval(() => showAnnouncement(annIndex + 1), 4000);
      });
    }

    if (annNext) {
      annNext.addEventListener('click', () => {
        clearInterval(annTimer);
        showAnnouncement(annIndex + 1);
        annTimer = setInterval(() => showAnnouncement(annIndex + 1), 4000);
      });
    }
  }

  /* ── Welcome 10% OFF Promo Modal ─────────────────── */
  const welcomeModal = document.getElementById('welcome-modal-backdrop');
  const welcomeClose = document.getElementById('welcome-modal-close');
  const welcomeDismiss = document.getElementById('welcome-dismiss-btn');
  const welcomeShop = document.getElementById('welcome-shop-btn');
  const copyCouponBtn = document.getElementById('copy-coupon-btn');

  function openWelcomeModal() {
    if (!welcomeModal) return;
    welcomeModal.classList.remove('pointer-events-none', 'opacity-0');
    welcomeModal.classList.add('pointer-events-auto', 'opacity-100');
  }

  function closeWelcomeModal() {
    if (!welcomeModal) return;
    welcomeModal.classList.remove('pointer-events-auto', 'opacity-100');
    welcomeModal.classList.add('pointer-events-none', 'opacity-0');
    sessionStorage.setItem('dabhi_welcome_seen', 'true');
  }

  if (welcomeModal) {
    // Show after 3.5 seconds on first visit in session
    if (!sessionStorage.getItem('dabhi_welcome_seen')) {
      setTimeout(openWelcomeModal, 3500);
    }

    if (welcomeClose) welcomeClose.addEventListener('click', closeWelcomeModal);
    if (welcomeDismiss) welcomeDismiss.addEventListener('click', closeWelcomeModal);
    if (welcomeShop) welcomeShop.addEventListener('click', closeWelcomeModal);

    welcomeModal.addEventListener('click', (e) => {
      if (e.target === welcomeModal) closeWelcomeModal();
    });

    if (copyCouponBtn) {
      copyCouponBtn.addEventListener('click', () => {
        navigator.clipboard.writeText('FIRST10').then(() => {
          copyCouponBtn.textContent = 'Copied! ✓';
          copyCouponBtn.classList.remove('bg-primary');
          copyCouponBtn.classList.add('bg-emerald-600');
          if (window.showToast) window.showToast('Coupon FIRST10 copied to clipboard! 🎉', 'success');
          setTimeout(() => {
            copyCouponBtn.textContent = 'Copy Code';
            copyCouponBtn.classList.remove('bg-emerald-600');
            copyCouponBtn.classList.add('bg-primary');
          }, 2500);
        }).catch(() => {
          if (window.showToast) window.showToast('Coupon code is FIRST10', 'default');
        });
      });
    }
  }

})();
