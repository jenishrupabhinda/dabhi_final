<?php
/**
 * policy.php — Policy Pages (refund, shipping, privacy, terms, about)
 * URL: /public_html/policy.php?page=shipping
 */
$pageName = $_GET['page'] ?? 'about';
$allowed  = ['about','refund','shipping','privacy','terms'];
if (!in_array($pageName, $allowed)) $pageName = 'about';

$titles = [
    'about'    => 'About Us',
    'refund'   => 'Refund Policy',
    'shipping' => 'Shipping Policy',
    'privacy'  => 'Privacy Policy',
    'terms'    => 'Terms & Conditions',
];

$pageTitle = $titles[$pageName] . ' — Dabhi Chikki';
require_once __DIR__ . '/partials/_header.php';
?>

<section class="policy-page">
  <div class="container">
    <div class="policy-content">
      <nav class="breadcrumb">
        <a href="index.php">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span><?= $titles[$pageName] ?></span>
      </nav>

      <!-- Nav tabs for policies -->
      <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:2rem;background:var(--bg-alt);padding:6px;border-radius:var(--radius)">
        <?php foreach ($titles as $key => $title): ?>
          <a href="?page=<?= $key ?>"
            style="padding:0.5rem 1rem;border-radius:calc(var(--radius) - 2px);font-size:0.875rem;font-weight:600;transition:all 0.2s;<?= $key === $pageName ? 'background:#fff;color:var(--text);box-shadow:var(--shadow-sm)' : 'color:var(--text-muted)' ?>"
          ><?= $title ?></a>
        <?php endforeach; ?>
      </div>

      <?php if ($pageName === 'about'): ?>
        <h1>About Dabhi Chikki</h1>
        <p class="policy-updated">Family business since 2009</p>
        <p>Dabhi Chikki was founded in 2009 with one simple belief — the best food comes from the purest ingredients. What started as a small family kitchen in Gujarat has grown into a trusted name in traditional Indian confectionery.</p>
        <h2>Our Story</h2>
        <p>For over 15 years, we have been crafting chikki the same way our grandparents taught us — using pure sugarcane jaggery, freshly roasted dry fruits, and absolutely nothing artificial. Every batch is made by hand, in small quantities, to ensure the quality and freshness that our customers deserve.</p>
        <h2>Our Products</h2>
        <p>We currently offer four signature chikki varieties:</p>
        <ul>
          <li><strong>Mandvi Chikki</strong> — Classic groundnut chikki, the original crowd favourite.</li>
          <li><strong>TIL Chikki</strong> — Sesame seed chikki, rich in calcium and iron.</li>
          <li><strong>Daliya Chikki</strong> — Broken wheat chikki, high in fibre and energy.</li>
          <li><strong>3 Mix Chikki</strong> — Our signature blend of groundnuts, sesame, and coconut crush.</li>
        </ul>
        <p>All products are available in 500g and 1kg packs. No preservatives. No artificial colours or flavours. Just pure taste.</p>
        <h2>Contact Us</h2>
        <p>📧 orders@dabhichikki.com &nbsp;·&nbsp; 📞 +91 98765 43210<br>
        📍 Surat, Gujarat, India</p>

      <?php elseif ($pageName === 'refund'): ?>
        <h1>Refund Policy</h1>
        <p class="policy-updated">Last updated: September 2026</p>
        <p>We want you to be completely satisfied with your Dabhi Chikki order. If you are not satisfied for any reason, we're here to help.</p>
        <h2>Returns</h2>
        <p>We accept returns within <strong>7 days</strong> of delivery for the following reasons:</p>
        <ul>
          <li>The product arrived damaged or broken</li>
          <li>Wrong product was delivered</li>
          <li>The product is expired or has quality issues</li>
        </ul>
        <h2>Non-Returnable Items</h2>
        <p>Due to the perishable nature of food products, we cannot accept returns if the product has been opened or partially consumed (except in quality-related cases).</p>
        <h2>Refund Process</h2>
        <p>Once we receive and inspect the returned product, we will process your refund within <strong>5–7 business days</strong>. Refunds will be credited to your original payment method.</p>
        <h2>How to Initiate a Return</h2>
        <p>Email us at <strong>orders@dabhichikki.com</strong> with your order number and photos of the issue. Our team will assist you promptly.</p>

      <?php elseif ($pageName === 'shipping'): ?>
        <h1>Shipping Policy</h1>
        <p class="policy-updated">Last updated: September 2026</p>
        <h2>Processing Time</h2>
        <p>Orders are processed within <strong>1–2 business days</strong> after confirmation. Orders placed on weekends or public holidays will be processed on the next working day.</p>
        <h2>Delivery Timeframe</h2>
        <p>Standard delivery across India typically takes <strong>4–7 business days</strong> after dispatch. Delivery times may vary based on your location and courier partner.</p>
        <h2>Shipping Charges</h2>
        <ul>
          <li><strong>Free shipping</strong> on all orders above ₹500</li>
          <li>A flat shipping fee of <strong>₹60</strong> applies to orders below ₹500</li>
        </ul>
        <h2>Tracking Your Order</h2>
        <p>Once your order is dispatched, you will receive a tracking number via email/SMS. You can also track your order using the <a href="track.php" style="color:var(--primary)">Track Order</a> page on our website.</p>
        <h2>Delivery Areas</h2>
        <p>We currently ship to all serviceable locations across India. If your pincode is not serviceable, you will be notified at checkout.</p>

      <?php elseif ($pageName === 'privacy'): ?>
        <h1>Privacy Policy</h1>
        <p class="policy-updated">Last updated: September 2026</p>
        <p>Your privacy is important to us. This policy explains how Dabhi Chikki collects, uses, and protects your personal information.</p>
        <h2>Information We Collect</h2>
        <ul>
          <li>Name, email address, and phone number (when you register or place an order)</li>
          <li>Delivery address and payment information</li>
          <li>Order history and preferences</li>
          <li>Device and browsing data (cookies, IP address) for analytics</li>
        </ul>
        <h2>How We Use Your Information</h2>
        <ul>
          <li>To process and fulfil your orders</li>
          <li>To send order confirmations and delivery updates</li>
          <li>To improve our products and services</li>
          <li>To send promotional offers (with your consent)</li>
        </ul>
        <h2>Data Security</h2>
        <p>We implement industry-standard security measures to protect your personal information. Payment data is processed securely via Cashfree and is never stored on our servers.</p>
        <h2>Data Sharing</h2>
        <p>We do not sell, trade, or rent your personal information to third parties. We may share data with our courier partners solely for the purpose of delivering your order.</p>
        <h2>Your Rights</h2>
        <p>You have the right to access, update, or delete your personal information. Contact us at orders@dabhichikki.com for any data-related requests.</p>

      <?php elseif ($pageName === 'terms'): ?>
        <h1>Terms &amp; Conditions</h1>
        <p class="policy-updated">Last updated: September 2026</p>
        <p>By using the Dabhi Chikki website and placing an order, you agree to the following terms and conditions.</p>
        <h2>Use of Website</h2>
        <p>This website is for personal, non-commercial use. You may not use any automated tools to scrape, copy, or misuse the content on this website.</p>
        <h2>Orders &amp; Payments</h2>
        <ul>
          <li>All prices are in Indian Rupees (₹) and are inclusive of applicable taxes (GST).</li>
          <li>We reserve the right to refuse or cancel orders at our discretion.</li>
          <li>For COD orders, payment is due at the time of delivery.</li>
        </ul>
        <h2>Product Information</h2>
        <p>While we strive for accuracy, product images, descriptions, and nutritional information are for illustrative purposes. Actual products may vary slightly from images shown.</p>
        <h2>Intellectual Property</h2>
        <p>All content on this website — including logos, images, and text — is the property of Dabhi Chikki and may not be reproduced without written permission.</p>
        <h2>Limitation of Liability</h2>
        <p>Dabhi Chikki is not liable for any indirect, incidental, or consequential damages arising from the use of our products or website.</p>
        <h2>Governing Law</h2>
        <p>These terms are governed by the laws of India. Any disputes will be subject to the jurisdiction of courts in Surat, Gujarat.</p>
        <h2>Contact</h2>
        <p>For any questions regarding these terms, contact us at orders@dabhichikki.com.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>
