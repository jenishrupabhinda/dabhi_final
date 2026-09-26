/**
 * =====================================================================
 * DABHI CHIKKI — MODERN ADMIN SHELL CONTROLLER
 * Handles:
 * 1. Mobile off-canvas drawer (Open/Close with Hamburger, ✕, Backdrop, Esc)
 * 2. Sidebar scroll persistence (Fixes jump-to-top bug on page change)
 * 3. Active item detection & ensure-visible scroll
 * 4. Desktop stable full sidebar (Hamburger hidden on desktop >= 992px)
 * =====================================================================
 */

(function () {
  'use strict';

  // Prevent multiple executions if included more than once
  if (window.__adminShellInitialized) return;
  window.__adminShellInitialized = true;

  // Storage Keys
  const STORAGE_KEY_SCROLL   = 'adminSidebarScrollTop';
  const STORAGE_KEY_COLLAPSE = 'adminSidebarCollapsed';

  // Element handles
  let sidebar   = null;
  let layout    = null;
  let toggleBtn = null;
  let backdrop  = null;
  let closeBtn  = null;

  function initAdminShell() {
    sidebar   = document.getElementById('adminSidebar');
    layout    = document.querySelector('.admin-layout') || document.getElementById('adminApp');
    toggleBtn = document.getElementById('adminSidebarToggle') || document.getElementById('sidebarToggle');
    backdrop  = document.getElementById('sidebarBackdrop');
    closeBtn  = document.getElementById('sidebarCloseBtn');

    if (!sidebar) return;

    // Clean up any stale collapsed state to ensure desktop sidebar is fully expanded
    try {
      localStorage.removeItem(STORAGE_KEY_COLLAPSE);
      document.documentElement.classList.remove('sidebar-is-collapsed');
      if (layout) layout.classList.remove('sidebar-collapsed');
      document.body.classList.remove('admin-sidebar-open');
    } catch (e) {}

    setupScrollPersistence();
    setupActiveItemDetection();
    setupMobileDrawer();
    setupAccessibility();
    setupToastHelper();
  }

  // Toast Helper for Admin Notifications
  function setupToastHelper() {
    if (!window.showToast) {
      window.showToast = function (message, type = 'default', duration = 3000) {
        let container = document.getElementById('toast-container');
        if (!container) {
          container = document.createElement('div');
          container.id = 'toast-container';
          container.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none;';
          document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        const borderColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#c7613d';
        toast.style.cssText = 'pointer-events:auto;background:#210e0d;color:#ffffff;padding:11px 18px;border-radius:10px;font-size:0.88rem;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,0.3);border-left:4px solid ' + borderColor + ';transition:all 0.25s ease;transform:translateY(12px);opacity:0;display:flex;align-items:center;gap:8px;';
        toast.textContent = message;
        container.appendChild(toast);
        requestAnimationFrame(() => {
          toast.style.transform = 'translateY(0)';
          toast.style.opacity = '1';
        });
        setTimeout(() => {
          toast.style.transform = 'translateY(12px)';
          toast.style.opacity = '0';
          setTimeout(() => toast.remove(), 250);
        }, duration);
      };
    }
  }

  // ── 1. SIDEBAR SCROLL PERSISTENCE (Fixes jump-to-top bug) ───────────────────
  function setupScrollPersistence() {
    const scrollTarget = sidebar.querySelector('.sidebar-nav') || sidebar;

    // Restore saved scroll position immediately on page load
    try {
      const savedTop = sessionStorage.getItem(STORAGE_KEY_SCROLL);
      if (savedTop !== null) {
        const topVal = parseInt(savedTop, 10);
        if (!isNaN(topVal) && topVal > 0) {
          scrollTarget.scrollTop = topVal;
          sidebar.scrollTop = topVal;
        }
      }
    } catch (e) {}

    // Continuously record scroll position (debounced)
    let scrollTimer = null;
    const onScroll = function () {
      if (scrollTimer) clearTimeout(scrollTimer);
      scrollTimer = setTimeout(() => {
        try {
          const currentTop = scrollTarget.scrollTop || sidebar.scrollTop;
          sessionStorage.setItem(STORAGE_KEY_SCROLL, currentTop);
        } catch (e) {}
      }, 80);
    };

    scrollTarget.addEventListener('scroll', onScroll, { passive: true });
    if (scrollTarget !== sidebar) {
      sidebar.addEventListener('scroll', onScroll, { passive: true });
    }

    // Save scroll position immediately on any link click/touch
    sidebar.addEventListener('click', function (e) {
      const link = e.target.closest('.sidebar-link, a');
      if (link) {
        try {
          const currentTop = scrollTarget.scrollTop || sidebar.scrollTop;
          sessionStorage.setItem(STORAGE_KEY_SCROLL, currentTop);
        } catch (e) {}
      }
    });

    // Save before unload as fail-safe
    window.addEventListener('beforeunload', function () {
      try {
        const currentTop = scrollTarget.scrollTop || sidebar.scrollTop;
        sessionStorage.setItem(STORAGE_KEY_SCROLL, currentTop);
      } catch (e) {}
    });
  }

  // ── 2. ACTIVE NAVIGATION ITEM DETECTION & VISIBILITY CHECK ────────────────
  function setupActiveItemDetection() {
    const currentPath = window.location.pathname.replace(/\/$/, '');
    let activeItem = sidebar.querySelector('.sidebar-link.active');

    if (!activeItem) {
      const links = sidebar.querySelectorAll('.sidebar-link');
      for (let i = 0; i < links.length; i++) {
        const link = links[i];
        const href = (link.getAttribute('href') || '').split('?')[0].replace(/\/$/, '');
        if (href && (currentPath.endsWith(href) || currentPath.endsWith(href.replace('.php', '')))) {
          link.classList.add('active');
          activeItem = link;
          break;
        }
      }
    }

    if (activeItem) {
      activeItem.setAttribute('aria-current', 'page');

      // Verify active item is within visible scroll bounds of sidebar
      requestAnimationFrame(() => {
        setTimeout(() => {
          const container = sidebar.querySelector('.sidebar-nav') || sidebar;
          const containerRect = container.getBoundingClientRect();
          const itemRect = activeItem.getBoundingClientRect();

          const isFullyVisible = (
            itemRect.top >= containerRect.top + 10 &&
            itemRect.bottom <= containerRect.bottom - 10
          );

          if (!isFullyVisible) {
            activeItem.scrollIntoView({ block: 'nearest', behavior: 'instant' });
            try {
              sessionStorage.setItem(STORAGE_KEY_SCROLL, container.scrollTop || sidebar.scrollTop);
            } catch (e) {}
          }
        }, 60);
      });
    }

    // Tooltip data attribute initialization
    sidebar.querySelectorAll('.sidebar-link').forEach(link => {
      const label = link.querySelector('.link-label');
      if (label && !link.getAttribute('data-tooltip')) {
        link.setAttribute('data-tooltip', label.textContent.trim());
      }
    });
  }

  // ── 3. MOBILE OFF-CANVAS DRAWER & BACKDROP ─────────────────────────────────
  function setupMobileDrawer() {
    function openDrawer() {
      if (!sidebar) return;
      sidebar.classList.add('open');
      if (backdrop) {
        backdrop.classList.add('show');
        backdrop.setAttribute('aria-hidden', 'false');
      }
      document.body.classList.add('admin-sidebar-open');
      if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
    }

    function closeDrawer() {
      if (!sidebar) return;
      sidebar.classList.remove('open');
      if (backdrop) {
        backdrop.classList.remove('show');
        backdrop.setAttribute('aria-hidden', 'true');
      }
      document.body.classList.remove('admin-sidebar-open');
      if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
    }

    window.openAdminSidebar = openDrawer;
    window.closeAdminSidebar = closeDrawer;

    // Instant touch handlers for mobile buttons and backdrop
    if (toggleBtn) {
      toggleBtn.addEventListener('touchend', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (sidebar && sidebar.classList.contains('open')) {
          closeDrawer();
        } else {
          openDrawer();
        }
      }, { passive: false });
    }

    if (closeBtn) {
      closeBtn.addEventListener('touchend', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeDrawer();
      }, { passive: false });
    }

    if (backdrop) {
      backdrop.addEventListener('touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeDrawer();
      }, { passive: false });
    }

    // Delegated click handler on document to guarantee clicks on toggle/close/backdrop always work
    document.addEventListener('click', function (e) {
      const toggle = e.target.closest('#adminSidebarToggle, .admin-topbar-toggle-btn');
      if (toggle) {
        e.preventDefault();
        e.stopPropagation();
        if (sidebar && sidebar.classList.contains('open')) {
          closeDrawer();
        } else {
          openDrawer();
        }
        return;
      }

      const close = e.target.closest('#sidebarCloseBtn, .sidebar-close-btn');
      if (close) {
        e.preventDefault();
        e.stopPropagation();
        closeDrawer();
        return;
      }

      const back = e.target.closest('#sidebarBackdrop, .admin-sidebar-backdrop');
      if (back) {
        e.preventDefault();
        e.stopPropagation();
        closeDrawer();
        return;
      }
    }, true);

    // Keyboard ESC closes drawer
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
        closeDrawer();
      }
    });

    // Close drawer on sidebar link click on mobile
    if (sidebar) {
      sidebar.addEventListener('click', function (e) {
        const link = e.target.closest('.sidebar-link, .sidebar-brand a, .sidebar-logout-btn');
        if (link && window.innerWidth < 992) {
          closeDrawer();
        }
      });
    }

    // Window resize handler: auto close drawer if resized to desktop
    window.addEventListener('resize', function () {
      if (window.innerWidth >= 992 && sidebar && sidebar.classList.contains('open')) {
        closeDrawer();
      }
    }, { passive: true });
  }

  // ── 4. ACCESSIBILITY ───────────────────────────────────────────────────────
  function setupAccessibility() {
    if (toggleBtn) {
      toggleBtn.setAttribute('aria-controls', 'adminSidebar');
      toggleBtn.setAttribute('aria-label', 'Toggle admin navigation sidebar');
      toggleBtn.setAttribute('aria-expanded', 'false');
    }
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminShell);
  } else {
    initAdminShell();
  }
})();
