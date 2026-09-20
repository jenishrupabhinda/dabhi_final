</main>
<!-- ===== Site Footer ===== -->
<footer class="site-footer" role="contentinfo">
  <div class="footer-main">
    <div class="container">
      <div class="footer-grid">

        <!-- Brand -->
        <div class="footer-brand">
          <img src="<?= asset('images/logo.png') ?>" alt="<?= e(APP_NAME) ?>">
          <p>Authentic handcrafted chikki &amp; sweets made from the finest ingredients. A taste of Gujarat, delivered to your doorstep.</p>
          <div style="display:flex;gap:10px;margin-top:16px;">
            <a href="#" aria-label="Facebook" style="color:rgba(255,255,255,0.5);font-size:1.2rem;">&#x1F426;</a>
            <a href="#" aria-label="Instagram" style="color:rgba(255,255,255,0.5);font-size:1.2rem;">&#x1F4F7;</a>
            <a href="https://wa.me/" aria-label="WhatsApp" style="color:rgba(255,255,255,0.5);font-size:1.2rem;">&#x1F4AC;</a>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-col">
          <h5>Shop</h5>
          <ul>
            <?php
            $footerCats = Database::fetchAll('SELECT name, slug FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order LIMIT 6');
            foreach ($footerCats as $fc):
            ?>
            <li><a href="<?= url('shop.php?category=' . urlencode($fc['slug'])) ?>"><?= e($fc['name']) ?></a></li>
            <?php endforeach; ?>
            <li><a href="<?= url('build-your-box.php') ?>">Build Your Box</a></li>
          </ul>
        </div>

        <!-- Info -->
        <div class="footer-col">
          <h5>Info</h5>
          <ul>
            <li><a href="<?= url('track-order.php') ?>">Track Order</a></li>
            <li><a href="<?= url('shop.php') ?>">About Us</a></li>
            <li><a href="<?= url('shop.php') ?>">Contact</a></li>
            <li><a href="<?= url('shop.php') ?>">Shipping Policy</a></li>
            <li><a href="<?= url('shop.php') ?>">Return Policy</a></li>
            <li><a href="<?= url('shop.php') ?>">Privacy Policy</a></li>
          </ul>
        </div>

        <!-- Contact -->
        <div class="footer-col">
          <h5>Contact</h5>
          <ul>
            <li style="display:flex;gap:6px;align-items:flex-start;">
              <span>📍</span>
              <span>Gujarat, India</span>
            </li>
            <li style="display:flex;gap:6px;align-items:center;">
              <span>📞</span>
              <a href="tel:+91XXXXXXXXXX">+91 XXXXX XXXXX</a>
            </li>
            <li style="display:flex;gap:6px;align-items:center;">
              <span>✉️</span>
              <a href="mailto:orders@dabhichikki.com">orders@dabhichikki.com</a>
            </li>
          </ul>
        </div>

      </div><!-- /footer-grid -->
    </div>
  </div>

  <div class="container">
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</span>
      <span>Made with ❤️ in Gujarat, India &nbsp;|&nbsp; GST compliant &nbsp;|&nbsp; Secure Checkout</span>
    </div>
  </div>
</footer>

<!-- ===== Main JS ===== -->
<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
