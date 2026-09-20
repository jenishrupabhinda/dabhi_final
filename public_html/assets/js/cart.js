/**
 * cart.js — Cart Drawer, Location Modal & Storefront Interactions for Dabhi Chikki
 * Replicates Yogurt Alley Cart Drawer interactions & layout
 */

(function () {
  'use strict';

  const API = 'api/cart.php';
  const ITEMS_API = 'api/cart-items.php';

  /* ── DOM Elements ─────────────────────────────────── */
  const drawer   = document.getElementById('cart-drawer');
  const backdrop = document.getElementById('cart-backdrop');
  const closeBtn = document.getElementById('cart-close-btn');
  const cartBtn  = document.getElementById('cart-toggle-btn');
  const bodyEl   = document.getElementById('cart-drawer-body');
  const footerEl = document.getElementById('cart-drawer-footer');
  const badgeEl  = document.getElementById('cart-badge');

  // Mobile Bottom Bar Elements
  const mobileBar      = document.getElementById('mobile-bottom-bar');
  const mobileBarCount = document.getElementById('mobile-bar-count');
  const mobileBarTotal = document.getElementById('mobile-bar-total');

  let isOpen = false;

  /* ── Open / Close Drawer ─────────────────────────── */
  function openDrawer() {
    if (!drawer || !backdrop) return;
    isOpen = true;
    drawer.classList.remove('translate-x-full');
    drawer.classList.add('translate-x-0');
    backdrop.classList.remove('pointer-events-none', 'opacity-0');
    backdrop.classList.add('pointer-events-auto', 'opacity-100');
    document.body.style.overflow = 'hidden';
    loadCartItems();
  }

  function closeDrawer() {
    if (!drawer || !backdrop) return;
    isOpen = false;
    drawer.classList.remove('translate-x-0');
    drawer.classList.add('translate-x-full');
    backdrop.classList.remove('pointer-events-auto', 'opacity-100');
    backdrop.classList.add('pointer-events-none', 'opacity-0');
    document.body.style.overflow = '';
  }

  window.openCartDrawer = openDrawer;
  window.closeCartDrawer = closeDrawer;

  if (cartBtn)  cartBtn.addEventListener('click', openDrawer);
  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
  if (backdrop) backdrop.addEventListener('click', closeDrawer);

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && isOpen) closeDrawer();
  });

  /* ── Badge & Mobile Bar Update ──────────────────── */
  function updateBadgeAndBar(count, total = 0) {
    if (badgeEl) {
      if (count > 0) {
        badgeEl.textContent = count > 99 ? '99+' : count;
        badgeEl.classList.remove('hidden');
      } else {
        badgeEl.textContent = '0';
        badgeEl.classList.add('hidden');
      }
    }

    if (mobileBar) {
      if (count > 0) {
        mobileBar.classList.remove('hidden');
        if (mobileBarCount) mobileBarCount.textContent = count;
        if (mobileBarTotal) mobileBarTotal.textContent = '₹' + Math.round(total) + ' →';
      } else {
        mobileBar.classList.add('hidden');
      }
    }
  }

  /* ── Fetch Count On Load ─────────────────────────── */
  async function fetchCount() {
    try {
      const res = await fetch(API + '?action=count', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      if (data.ok) {
        updateBadgeAndBar(data.cart_count, data.subtotal || 0);
      }
    } catch (_) {}
  }
  fetchCount();

  /* ── Load Items ──────────────────────────────────── */
  async function loadCartItems() {
    if (!bodyEl) return;
    bodyEl.innerHTML = `
      <div class="flex h-full items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-primary border-t-transparent"></div>
      </div>`;

    try {
      const res = await fetch(ITEMS_API, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      renderCart(data);
    } catch (_) {
      bodyEl.innerHTML = `<p class="text-center py-10 text-sm text-destructive">Failed to load bag contents.</p>`;
    }
  }

  /* ── Render Items ────────────────────────────────── */
  function renderCart(data) {
    if (!bodyEl) return;
    const items = data.items || [];
    const count = data.item_count || 0;
    const subtotal = parseFloat(data.subtotal) || 0;
    const freeShippingThreshold = parseFloat(data.free_shipping_threshold) || 999;
    const defaultShippingCharge = parseFloat(data.default_shipping_charge) || 60;
    const isFree = subtotal >= freeShippingThreshold;
    const shippingFee = isFree ? 0 : defaultShippingCharge;
    const total = subtotal + shippingFee;
    const remainingForFree = Math.max(0, freeShippingThreshold - subtotal);

    updateBadgeAndBar(count, total);

    if (items.length === 0) {
      bodyEl.innerHTML = `
        <div class="flex h-full flex-col items-center justify-center text-center py-12">
          <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground"><path d="M16 10a4 4 0 0 1-8 0"></path><path d="M3.103 6.034h17.794"></path><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"></path></svg>
          <p class="mt-4 font-display text-2xl text-foreground">Your bag is empty</p>
          <p class="mt-1 text-sm text-muted-foreground">Add a chikki to get started</p>
          <button type="button" onclick="closeCartDrawer()" class="mt-6 inline-flex rounded-full bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-soft transition hover:scale-105">Browse menu</button>
        </div>`;
      if (footerEl) footerEl.classList.add('hidden');
      return;
    }

    let itemsHtml = '<ul class="space-y-3">';
    items.forEach(item => {
      let imgSrc = (item.product_image || 'assets/images/products/mandvi-chikki-1.jpg');
      if (/Mandvi Chikki \(1\)/i.test(imgSrc)) {
        imgSrc = 'assets/images/products/mandvi-chikki-1.jpg';
      } else if (/Mandvi Chikki \(2\)/i.test(imgSrc)) {
        imgSrc = 'assets/images/products/mandvi-chikki-2.jpg';
      } else if (/Tal Chikki \(1\)/i.test(imgSrc)) {
        imgSrc = 'assets/images/products/til-chikki-1.jpg';
      } else if (/Tal Chikki \(2\)/i.test(imgSrc)) {
        imgSrc = 'assets/images/products/til-chikki-2.jpg';
      } else if (/Daliya Chikki \(1\)/i.test(imgSrc)) {
        imgSrc = 'assets/images/products/daliya-chikki-1.jpg';
      } else if (/Daliya Chikki \(2\)/i.test(imgSrc)) {
        imgSrc = 'assets/images/products/daliya-chikki-2.jpg';
      } else if (/3\s*-\s*Mix Chikki.*\(1\)/i.test(imgSrc)) {
        imgSrc = 'assets/images/products/3-mix-chikki-1.jpg';
      } else if (/3\s*-\s*Mix Chikki.*\(2\)/i.test(imgSrc)) {
        imgSrc = 'assets/images/products/3-mix-chikki-2.jpg';
      } else if (/Dabhi_Logo/i.test(imgSrc)) {
        imgSrc = 'assets/images/logo.png';
      } else {
        // Strip any leading domain, absolute slash or arbitrary parent folder prefix to keep it cleanly relative
        imgSrc = imgSrc.replace(/^.*?assets\//i, 'assets/').replace(/^\/+/, '');
      }
      const weight = item.weight_grams >= 1000
        ? (item.weight_grams / 1000) + 'kg'
        : item.weight_grams + 'g';
      const itemPrice = parseFloat(item.selling_price) || 0;
      const lineTotal = itemPrice * item.quantity;

      itemsHtml += `
        <li class="flex gap-3 rounded-2xl bg-card p-3 shadow-soft border border-border/60" data-item-id="${item.id}">
          <img src="${imgSrc}" alt="${item.product_name}" class="h-16 w-16 rounded-xl object-cover bg-muted shrink-0" loading="lazy">
          <div class="flex flex-1 flex-col">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <h4 class="font-display text-base leading-tight text-foreground truncate">${item.product_name}</h4>
                <p class="mt-0.5 text-xs text-muted-foreground font-mono">${weight}</p>
              </div>
              <button class="cart-remove-btn text-muted-foreground transition hover:text-destructive p-1" data-id="${item.id}" aria-label="Remove item">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
              </button>
            </div>
            <div class="mt-auto flex items-center justify-between pt-2">
              <div class="flex items-center gap-1 rounded-full bg-muted p-1">
                <button class="cart-qty-btn flex h-6 w-6 items-center justify-center rounded-full bg-background shadow-soft transition active:scale-95 text-xs font-bold" data-action="dec" data-id="${item.id}">−</button>
                <span class="w-6 text-center text-xs font-semibold">${item.quantity}</span>
                <button class="cart-qty-btn flex h-6 w-6 items-center justify-center rounded-full bg-background shadow-soft transition active:scale-95 text-xs font-bold" data-action="inc" data-id="${item.id}">+</button>
              </div>
              <span class="text-sm font-bold text-foreground font-price">₹${Math.round(lineTotal)}</span>
            </div>
          </div>
        </li>`;
    });

    itemsHtml += `
      </ul>
      <div class="mt-4 text-center">
        <button id="clear-cart-btn" class="text-xs font-medium text-muted-foreground hover:text-foreground transition">Clear bag</button>
      </div>`;

    bodyEl.innerHTML = itemsHtml;

    // Cart Footer
    if (footerEl) {
      footerEl.classList.remove('hidden');
      footerEl.innerHTML = `
        <div class="space-y-2 mb-4">
          <div class="flex items-center justify-between text-sm">
            <span class="text-muted-foreground">Subtotal</span>
            <span class="font-price font-semibold text-foreground">₹${Math.round(subtotal)}</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-muted-foreground">Delivery</span>
            <span class="font-price font-semibold ${isFree ? 'text-emerald-700' : 'text-foreground'}">
              ${isFree ? 'FREE' : '₹' + shippingFee}
            </span>
          </div>
          ${remainingForFree > 0 ? `
            <div class="rounded-xl bg-accent/40 px-3 py-1.5 text-center text-xs text-secondary font-medium">
              Add ₹${Math.round(remainingForFree)} more for <strong>FREE shipping</strong>!
            </div>
          ` : `
            <div class="rounded-xl bg-emerald-50 px-3 py-1.5 text-center text-xs text-emerald-700 font-medium">
              🎉 You've unlocked FREE Pan-India Shipping!
            </div>
          `}
          <div class="border-t border-border/60 pt-2 flex items-baseline justify-between">
            <span class="text-base font-semibold text-foreground">Total</span>
            <span class="font-display text-2xl text-foreground font-price">₹${Math.round(total)}</span>
          </div>
        </div>
        <a href="checkout.php" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-primary py-4 font-semibold text-primary-foreground shadow-glow transition hover:scale-[1.01] active:scale-[0.99]">
          Checkout →
        </a>
      `;
    }

    // Attach listeners
    bodyEl.querySelectorAll('.cart-qty-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = parseInt(btn.dataset.id);
        const action = btn.dataset.action;
        const qtySpan = btn.parentElement.querySelector('span');
        let currentQty = parseInt(qtySpan.textContent);
        let newQty = action === 'inc' ? currentQty + 1 : currentQty - 1;
        if (newQty < 1) {
          removeItem(id);
        } else {
          updateQty(id, newQty);
        }
      });
    });

    bodyEl.querySelectorAll('.cart-remove-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = parseInt(btn.dataset.id);
        removeItem(id);
      });
    });

    const clearBtn = document.getElementById('clear-cart-btn');
    if (clearBtn) {
      clearBtn.addEventListener('click', async () => {
        if (!confirm('Clear all items from your bag?')) return;
        for (const itm of items) {
          await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'remove', item_id: itm.id })
          });
        }
        loadCartItems();
      });
    }
  }

  /* ── Update Qty ──────────────────────────────────── */
  async function updateQty(itemId, quantity) {
    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'update', item_id: itemId, quantity })
      });
      const data = await res.json();
      if (data.ok) loadCartItems();
    } catch (_) {}
  }

  /* ── Remove Item ─────────────────────────────────── */
  async function removeItem(itemId) {
    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'remove', item_id: itemId })
      });
      const data = await res.json();
      if (data.ok) {
        if (window.showToast) window.showToast('Item removed from bag', 'default');
        loadCartItems();
      }
    } catch (_) {}
  }

  /* ── Add to Cart Global ──────────────────────────── */
  window.addToCart = async function (variantId, quantity = 1, btn = null, productName = '') {
    if (!variantId) {
      if (window.showToast) window.showToast('Please select a weight', 'error');
      return;
    }

    if (btn) {
      btn.classList.add('scale-90', 'opacity-70');
      setTimeout(() => btn.classList.remove('scale-90', 'opacity-70'), 300);
    }

    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'add', variant_id: variantId, quantity })
      });
      const data = await res.json();
      if (data.ok) {
        const msg = data.message || (productName ? 'Added ' + productName : 'Added to bag');
        if (window.showToast) window.showToast(msg, 'success');
        updateBadgeAndBar(data.cart_count || 1, data.subtotal || 0);
        // Note: Yogurt Alley does not open cart drawer on add; it displays top-center notification
      } else {
        if (window.showToast) window.showToast(data.error || 'Could not add to bag', 'error');
      }
    } catch (_) {
      if (window.showToast) window.showToast('Connection error', 'error');
    }
  };

  /* ── Location Modal Interactions ─────────────────── */
  const locBtnDesktop = document.getElementById('location-btn-desktop');
  const locBtnMobile  = document.getElementById('location-btn-mobile');
  const locBackdrop   = document.getElementById('location-modal-backdrop');
  const locCloseBtn   = document.getElementById('location-close-btn');
  const pincodeIn     = document.getElementById('pincode-input');
  const saveLocBtn    = document.getElementById('save-location-btn');
  const deskLocText   = document.getElementById('desktop-loc-text');
  const mobLocText    = document.getElementById('mobile-loc-text');
  const pinStatus     = document.getElementById('pincode-status');

  // Load saved location
  const savedLoc = localStorage.getItem('dabhi_delivery_location');
  if (savedLoc) {
    if (deskLocText) deskLocText.textContent = savedLoc;
    if (mobLocText)  mobLocText.textContent  = savedLoc;
  }

  function openLocationModal() {
    if (!locBackdrop) return;
    locBackdrop.classList.remove('pointer-events-none', 'opacity-0');
    locBackdrop.classList.add('pointer-events-auto', 'opacity-100');
    if (pincodeIn) pincodeIn.focus();
  }

  function closeLocationModal() {
    if (!locBackdrop) return;
    locBackdrop.classList.remove('pointer-events-auto', 'opacity-100');
    locBackdrop.classList.add('pointer-events-none', 'opacity-0');
  }

  if (locBtnDesktop) locBtnDesktop.addEventListener('click', openLocationModal);
  if (locBtnMobile)  locBtnMobile.addEventListener('click', openLocationModal);
  if (locCloseBtn)   locCloseBtn.addEventListener('click', closeLocationModal);
  if (locBackdrop)   locBackdrop.addEventListener('click', (e) => {
    if (e.target === locBackdrop) closeLocationModal();
  });

  if (saveLocBtn && pincodeIn) {
    saveLocBtn.addEventListener('click', () => {
      const val = pincodeIn.value.trim();
      if (!val) {
        if (pinStatus) {
          pinStatus.textContent = 'Please enter a valid pincode or city name.';
          pinStatus.className = 'mt-3 text-xs text-destructive';
        }
        return;
      }
      localStorage.setItem('dabhi_delivery_location', val);
      if (deskLocText) deskLocText.textContent = val;
      if (mobLocText)  mobLocText.textContent  = val;
      if (pinStatus) {
        pinStatus.textContent = `✓ Delivery available to ${val}! Pan-India dispatch in 24 hrs.`;
        pinStatus.className = 'mt-3 text-xs text-emerald-700 font-semibold';
      }
      setTimeout(closeLocationModal, 900);
    });
  }

  /* ── Reels Video Interactive Autoplay & Sound Toggle ── */
  document.addEventListener('DOMContentLoaded', () => {
    const reelVideos = document.querySelectorAll('.reel-card video');
    if (reelVideos.length > 0 && 'IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          const vid = entry.target;
          const card = vid.closest('.reel-card');
          const playBtn = card ? card.querySelector('.reel-play-btn') : null;
          if (entry.isIntersecting) {
            vid.play().then(() => {
              if (playBtn) playBtn.dataset.playing = 'true';
            }).catch(() => {});
          } else {
            vid.pause();
            if (playBtn) playBtn.dataset.playing = 'false';
          }
        });
      }, { threshold: 0.5 });

      reelVideos.forEach(v => observer.observe(v));
    }
  });

})();
