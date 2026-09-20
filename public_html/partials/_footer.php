<?php
/**
 * _footer.php — Shared site footer for Dabhi Chikki storefront
 * Exact clone styling of Yogurt Alley: Deep burgundy footer, slide-out Cart Drawer,
 * Location Modal, sticky mobile bar, and clean closing page shell.
 */
?>
  </div><!-- closes pb-24 md:pb-0 -->

  <!-- Site Footer (Exact Yogurt Alley Architecture) -->
  <footer class="bg-secondary text-secondary-foreground mt-16" style="--footer-hover:#f6dc94">
    <div class="mx-auto grid max-w-6xl px-4 py-12 gap-10 md:grid-cols-4 md:px-6 md:py-16">
      
      <!-- Brand Info & Address -->
      <div class="md:col-span-2">
        <a href="index.php" class="inline-block mb-3" aria-label="Dabhi Chikki Home">
          <img src="assets/images/logo-light.svg" alt="Dabhi Chikki" class="h-10 w-auto object-contain md:h-12">
        </a>
        <p class="mt-3 max-w-sm text-sm opacity-80 leading-relaxed">
          Handcrafted chikki made with 100% pure sugarcane jaggery and premium roasted nuts. Delivering the authentic taste of tradition across India since 2009.
        </p>

        <!-- Contact details -->
        <ul class="mt-5 space-y-2.5 text-sm leading-relaxed opacity-85 sm:space-y-3">
          <li class="grid grid-cols-[64px_1fr] items-baseline gap-x-3 sm:grid-cols-[72px_1fr]">
            <span class="text-[11px] font-semibold uppercase tracking-[0.14em] opacity-70 font-mono">Mail</span>
            <a href="mailto:care@dabhichikki.com" class="break-all hover:underline hover:text-[#f6dc94] transition">care@dabhichikki.com</a>
          </li>
          <li class="grid grid-cols-[64px_1fr] items-baseline gap-x-3 sm:grid-cols-[72px_1fr]">
            <span class="text-[11px] font-semibold uppercase tracking-[0.14em] opacity-70 font-mono">Phone</span>
            <a href="tel:+919876543210" class="hover:underline hover:text-[#f6dc94] transition">+91 98765 43210</a>
          </li>
          <li class="grid grid-cols-[64px_1fr] items-baseline gap-x-3 sm:grid-cols-[72px_1fr]">
            <span class="text-[11px] font-semibold uppercase tracking-[0.14em] opacity-70 font-mono">Office</span>
            <span class="leading-[1.65]">Station Road, Dabhi Sweets &amp; Confectionery, Rajkot, Gujarat — 360001</span>
          </li>
        </ul>

        <!-- Social Links -->
        <div class="mt-5 flex flex-wrap gap-3">
          <a href="https://instagram.com" target="_blank" rel="noreferrer" aria-label="Instagram" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 transition hover:bg-white/20 hover:scale-105 text-white">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line></svg>
          </a>
          <a href="https://facebook.com" target="_blank" rel="noreferrer" aria-label="Facebook" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 transition hover:bg-white/20 hover:scale-105 text-white">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
          </a>
          <a href="https://wa.me/919876543210" target="_blank" rel="noreferrer" aria-label="WhatsApp" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 transition hover:bg-white/20 hover:scale-105 text-white">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
          </a>
        </div>
      </div>

      <!-- Shop Links -->
      <div>
        <h4 class="font-display text-lg text-[#f6dc94] font-semibold">Menu</h4>
        <ul class="mt-3 space-y-2 text-sm opacity-90">
          <li><a href="product.php?slug=mandvi-chikki" class="footer-link transition-colors hover:text-[#f6dc94]">Mandvi Chikki (Groundnut)</a></li>
          <li><a href="product.php?slug=til-chikki" class="footer-link transition-colors hover:text-[#f6dc94]">TIL Chikki (Sesame)</a></li>
          <li><a href="product.php?slug=daliya-chikki" class="footer-link transition-colors hover:text-[#f6dc94]">Daliya Chikki (Split Gram)</a></li>
          <li><a href="product.php?slug=3-mix-chikki" class="footer-link transition-colors hover:text-[#f6dc94]">3 Mix Chikki (Signature)</a></li>
          <li><a href="index.php#products" class="footer-link transition-colors hover:text-[#f6dc94]">All 4 Signature Varieties</a></li>
        </ul>
      </div>

      <!-- Help & Legal Links -->
      <div>
        <h4 class="font-display text-lg text-[#f6dc94] font-semibold">Explore &amp; Help</h4>
        <ul class="mt-3 space-y-2 text-sm opacity-90">
          <li><a href="track.php" class="footer-link transition-colors hover:text-[#f6dc94]">Track Order</a></li>
          <li><a href="policy.php?page=about" class="footer-link transition-colors hover:text-[#f6dc94]">Our Story &amp; Craft</a></li>
          <li><a href="policy.php?page=shipping" class="footer-link transition-colors hover:text-[#f6dc94]">Shipping Policy</a></li>
          <li><a href="policy.php?page=refund" class="footer-link transition-colors hover:text-[#f6dc94]">Refund &amp; Returns</a></li>
          <li><a href="policy.php?page=privacy" class="footer-link transition-colors hover:text-[#f6dc94]">Privacy Policy</a></li>
          <li><a href="policy.php?page=terms" class="footer-link transition-colors hover:text-[#f6dc94]">Terms &amp; Conditions</a></li>
        </ul>
      </div>

    </div>

    <!-- Footer Bottom Copyright Bar -->
    <div class="border-t border-white/10">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-5 text-xs opacity-70 md:px-6 flex-wrap gap-2">
        <p>© <?= date('Y') ?> Dabhi Chikki. Handcrafted Since 2009. All rights reserved.</p>
        <p>Made with ❤️ in Gujarat, India</p>
      </div>
    </div>
  </footer>

  <!-- Cart Overlay Backdrop -->
  <div id="cart-backdrop" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm transition-opacity duration-300 pointer-events-none opacity-0"></div>

  <!-- Slide-out Cart Drawer (Exact Yogurt Alley Aside Architecture) -->
  <aside id="cart-drawer" class="fixed right-0 top-0 z-50 flex h-dvh w-full max-w-md flex-col bg-background shadow-pop transition-transform duration-300 ease-out translate-x-full">
    <div class="flex items-center justify-between border-b border-border px-5 py-4">
      <h3 class="font-display text-2xl">Your bag</h3>
      <button id="cart-close-btn" class="flex h-9 w-9 items-center justify-center rounded-full transition hover:bg-muted" aria-label="Close cart">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x h-5 w-5" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
      </button>
    </div>
    
    <!-- Cart Drawer Body -->
    <div class="flex-1 overflow-y-auto px-5 py-4" id="cart-drawer-body">
      <div class="flex h-full flex-col items-center justify-center text-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag h-14 w-14 text-muted-foreground" aria-hidden="true">
          <path d="M16 10a4 4 0 0 1-8 0"></path>
          <path d="M3.103 6.034h17.794"></path>
          <path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"></path>
        </svg>
        <p class="mt-4 font-display text-2xl">Your bag is empty</p>
        <p class="mt-1 text-sm text-muted-foreground">Add a chikki to get started</p>
        <button type="button" onclick="closeCartDrawer()" class="mt-6 rounded-full bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-soft transition hover:scale-105">Browse menu</button>
      </div>
    </div>

    <!-- Cart Drawer Footer (Subtotal, Delivery, Checkout) -->
    <div id="cart-drawer-footer" class="border-t border-border bg-card px-5 py-4 hidden"></div>
  </aside>

  <!-- Delivery Location Modal -->
  <div id="location-modal-backdrop" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm transition-opacity duration-300 pointer-events-none opacity-0 flex items-center justify-center p-4">
    <div class="relative w-full max-w-md rounded-3xl bg-background p-6 shadow-pop border border-border/60">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-display text-2xl text-foreground">Delivery Location</h3>
        <button id="location-close-btn" class="flex h-8 w-8 items-center justify-center rounded-full hover:bg-muted text-foreground">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
        </button>
      </div>
      <p class="text-sm text-muted-foreground mb-4">Enter your delivery pincode to check serviceability &amp; delivery time.</p>
      <div class="flex gap-2">
        <input type="text" id="pincode-input" placeholder="e.g. 360001 or Rajkot" class="flex-1 rounded-full border border-border bg-surface px-4 py-2.5 text-sm outline-none focus:border-primary text-foreground">
        <button id="save-location-btn" class="rounded-full bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-soft hover:bg-primary/90 transition">Apply</button>
      </div>
      <div id="pincode-status" class="mt-3 text-xs text-muted-foreground"></div>
    </div>
  </div>

  <!-- 10% OFF Welcome Promo Modal (Exact Yogurt Alley First-Time Promo) -->
  <div id="welcome-modal-backdrop" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm transition-opacity duration-300 pointer-events-none opacity-0 flex items-center justify-center p-4">
    <div class="relative w-full max-w-md overflow-hidden rounded-[28px] border border-border/60 bg-background p-6 sm:p-8 shadow-pop text-center">
      <!-- Close X Button -->
      <button id="welcome-modal-close" class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full bg-surface text-foreground/70 hover:bg-muted hover:text-foreground transition" aria-label="Close welcome offer">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
      </button>

      <!-- Badge Icon -->
      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary mb-4 shadow-soft">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/><path d="M20 2v4"/><path d="M22 4h-4"/><circle cx="4" cy="20" r="2"/></svg>
      </div>

      <span class="inline-block font-mono text-[11px] font-semibold uppercase tracking-[0.2em] text-primary">Special Welcome Gift</span>
      <h3 class="mt-2 font-display text-2xl sm:text-3xl text-foreground font-bold leading-tight">Get 10% Off Your First Box</h3>
      <p class="mt-2 text-sm text-foreground/75 leading-relaxed">
        Taste the authentic crunch of 100% pure jaggery chikki handcrafted since 2009. Use this coupon at checkout:
      </p>

      <!-- Coupon Code Box with Copy Button -->
      <div class="mt-5 flex items-center justify-between rounded-2xl border-2 border-dashed border-primary/40 bg-surface px-4 py-3">
        <span class="font-mono text-lg font-bold tracking-[0.18em] text-secondary" id="coupon-code-text">FIRST10</span>
        <button type="button" id="copy-coupon-btn" class="rounded-full bg-primary px-4 py-1.5 font-mono text-xs font-semibold text-primary-foreground shadow-soft transition hover:scale-105 active:scale-95">
          Copy Code
        </button>
      </div>

      <!-- Action Button -->
      <div class="mt-6">
        <a href="index.php#products" id="welcome-shop-btn" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-secondary py-3.5 text-sm font-semibold text-secondary-foreground shadow-pop transition hover:scale-[1.02] active:scale-98">
          Claim 10% Off &amp; Shop Now →
        </a>
      </div>

      <button type="button" id="welcome-dismiss-btn" class="mt-3 text-xs text-muted-foreground hover:text-foreground transition underline">
        No thanks, I'll pay full price
      </button>
    </div>
  </div>

  <!-- Sticky Floating Mobile Bottom Bar (Yogurt Alley Ae Component) -->
  <div id="mobile-bottom-bar" class="fixed inset-x-0 bottom-0 z-30 px-3 pb-3 md:hidden hidden animate-fade-up">
    <button type="button" onclick="openCartDrawer()" class="flex w-full items-center justify-between rounded-2xl bg-primary px-5 py-3.5 text-primary-foreground shadow-glow transition active:scale-[0.99]">
      <span class="flex items-center gap-2 text-sm font-semibold">
        <span id="mobile-bar-count" class="flex h-6 min-w-6 items-center justify-center rounded-full bg-white/20 px-1.5 text-xs">0</span>
        View bag
      </span>
      <span id="mobile-bar-total" class="text-base font-bold tracking-tight font-price">₹0 →</span>
    </button>
  </div>

  <!-- Toast Container -->
  <div id="toast-container"></div>

</div><!-- closes min-h-dvh bg-background -->

<style>
.footer-link:hover { color: var(--footer-hover); }
</style>

<!-- Global Scripts -->
<script src="assets/js/main.js"></script>
<script src="assets/js/cart.js"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $script): ?>
<script src="<?= htmlspecialchars($script) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
