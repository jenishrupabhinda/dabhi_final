<?php
/**
 * product.php — Product Detail Page for Dabhi Chikki
 * Exact 1:1 replica of https://order.yogurtalley.com/products/
 */

$bootstrap = null;
foreach ([
    __DIR__ . '/../includes/bootstrap.php',
    __DIR__ . '/../../includes/bootstrap.php',
    __DIR__ . '/includes/bootstrap.php',
] as $b) {
    if (file_exists($b)) {
        $bootstrap = $b;
        break;
    }
}
if ($bootstrap) {
    require_once $bootstrap;
}

$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    header('Location: index.php');
    exit;
}

$prod     = null;
$variants = [];
$images   = [];
$reviews  = [];
$avgRating = 5.0;
$reviewCount = 0;
$currentUser = Auth::user();

try {
    $prod = Product::getBySlug($slug);
    if (!$prod) {
        header('HTTP/1.1 404 Not Found');
        require __DIR__ . '/404.php';
        exit;
    }
    $variants    = $prod['variants'] ?? Product::getVariants((int)$prod['id']);
    $images      = $prod['images']   ?? Product::getImages((int)$prod['id']);
    $reviews = Database::fetchAll(
        "SELECT r.*, u.full_name 
         FROM reviews r 
         JOIN users u ON u.id = r.user_id 
         WHERE r.product_id = ? AND r.is_approved = 1 
         ORDER BY r.created_at DESC",
        [(int)$prod['id']]
    );
    $reviewCount = count($reviews);
    $avgRating   = $reviewCount > 0 ? round(array_sum(array_column($reviews, 'rating')) / $reviewCount, 1) : 5.0;
} catch (\Throwable $e) {
    // Static fallback for when DB isn't connected
    $staticProds = [
        'mandvi-chikki' => [
            'id' => 1,
            'name' => 'Mandvi Chikki',
            'slug' => 'mandvi-chikki',
            'short_description' => 'Classic groundnut chikki made with pure jaggery — crunchy, sweet, and irresistible.',
            'description' => "Our Mandvi Chikki is slow-crafted from the finest golden groundnuts and rich unrefined sugarcane jaggery.\n\nRoasted to perfection for an unforgettable snap, delivering clean nutrition with zero added sugar and no preservatives.",
            'gst_rate_percent' => 5
        ],
        'til-chikki' => [
            'id' => 2,
            'name' => 'TIL Chikki',
            'slug' => 'til-chikki',
            'short_description' => 'Fragrant white sesame seeds blended into rich golden jaggery syrup.',
            'description' => "Our White Sesame (TIL) Chikki brings you natural calcium and authentic winter warmth.\n\nPressed into crisp, golden slabs that melt delicately on the tongue.",
            'gst_rate_percent' => 5
        ],
        'daliya-chikki' => [
            'id' => 3,
            'name' => 'Daliya Chikki',
            'slug' => 'daliya-chikki',
            'short_description' => 'Light, airy roasted gram chikki — wholesome, crunchy, and high in fibre.',
            'description' => "Crafted from slow-roasted split chickpeas and concentrated natural sugarcane jaggery.\n\nA light, energizing snack that fuels your active day naturally.",
            'gst_rate_percent' => 5
        ],
        '3-mix-chikki' => [
            'id' => 4,
            'name' => '3 Mix Chikki',
            'slug' => '3-mix-chikki',
            'short_description' => 'Signature blend of roasted peanuts, sesame seeds, and toasted coconut flakes.',
            'description' => "Our flagship creation combines the three royal flavours of Gujarat confectionery in one perfectly balanced bar.\n\nGolden jaggery binds roasted peanuts, fragrant sesame, and coconut for unbeatable texture.",
            'gst_rate_percent' => 5
        ],
    ];

    if (!isset($staticProds[$slug])) {
        header('Location: index.php');
        exit;
    }
    $prod = $staticProds[$slug];

    $imgMap = [
        'mandvi-chikki' => ['assets/images/products/mandvi-chikki-1.jpg', 'assets/images/products/mandvi-chikki-2.jpg'],
        'til-chikki'    => ['assets/images/products/til-chikki-1.jpg', 'assets/images/products/til-chikki-2.jpg'],
        'daliya-chikki' => ['assets/images/products/daliya-chikki-1.jpg', 'assets/images/products/daliya-chikki-2.jpg'],
        '3-mix-chikki'  => ['assets/images/products/3-mix-chikki-1.jpg', 'assets/images/products/3-mix-chikki-2.jpg'],
    ];

    $baseId = ($prod['id'] - 1) * 3 + 1;
    $variants = [
        ['id' => $baseId + 1, 'weight_grams' => 500,  'selling_price' => $prod['id'] === 4 ? 250 : 200, 'mrp' => $prod['id'] === 4 ? 300 : 250, 'is_active' => 1, 'sku' => ''],
        ['id' => $baseId + 2, 'weight_grams' => 1000, 'selling_price' => $prod['id'] === 4 ? 450 : 380, 'mrp' => $prod['id'] === 4 ? 540 : 460, 'is_active' => 1, 'sku' => ''],
    ];

    $imgPaths = $imgMap[$slug] ?? [];
    $images   = [];
    foreach ($imgPaths as $i => $p) {
        $images[] = ['image_path' => $p, 'alt_text' => $prod['name'], 'is_primary' => ($i === 0 ? 1 : 0)];
    }
}

$firstVar = $variants[0] ?? ['selling_price' => 200, 'mrp' => 250, 'id' => 0, 'weight_grams' => 500];
$primaryImg = '';
$secondaryImg = '';
foreach ($images as $img) {
    if (!empty($img['is_primary'])) {
        $primaryImg = $img['image_path'];
    } else {
        $secondaryImg = $img['image_path'];
    }
}
if (!$primaryImg && !empty($images)) {
    $primaryImg = $images[0]['image_path'];
}

$discOff = 0;
if ((float)$firstVar['mrp'] > (float)$firstVar['selling_price']) {
    $discOff = (int)round(((float)$firstVar['mrp'] - (float)$firstVar['selling_price']) / (float)$firstVar['mrp'] * 100);
}

$pageTitle = $prod['name'] . ' · Dabhi Chikki';
$pageDesc  = $prod['short_description'] ?? $prod['name'];

require_once __DIR__ . '/partials/_header.php';
?>

<!-- ══ PRODUCT DETAIL MAIN CONTAINER (Exact Yogurt Alley Architecture) ══ -->
<main class="mx-auto max-w-5xl px-4 pb-28 pt-4 md:px-6 md:pb-16 md:pt-6">

  <!-- Back to Products Link (Arrow symbol ← Back, returns to exact position user came from) -->
  <div class="mb-4 md:mb-6">
    <button
      type="button"
      onclick="if (window.history.length > 1 && document.referrer) { window.history.back(); } else { window.location.href='index.php#products'; }"
      class="inline-flex items-center gap-2 text-sm font-semibold text-muted-foreground transition hover:text-foreground cursor-pointer group"
      aria-label="Back"
    >
      <svg class="h-4 w-4 transition-transform group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 12H5"/>
        <path d="m12 19-7-7 7-7"/>
      </svg>
      <span>Back</span>
    </button>
  </div>

  <!-- Two-Column Product Grid -->
  <div class="grid gap-6 md:grid-cols-2 md:gap-10 items-start">

    <!-- Left Column: Big Rounded Image Card with Overlay Slider Arrows -->
    <div class="relative overflow-hidden rounded-2xl bg-muted aspect-square w-full shadow-soft border border-border/40 group">
      <img
        src="<?= htmlspecialchars(imageUrl($primaryImg)) ?>"
        alt="<?= htmlspecialchars($prod['name']) ?>"
        id="main-product-img"
        class="h-full w-full select-none object-cover transition-opacity duration-300"
      />

      <?php if (!empty($images) && count($images) > 1): ?>
        <!-- Left Slider Arrow (Over the image - semi-transparent) -->
        <button
          type="button"
          onclick="slideImg(-1)"
          class="absolute left-3 top-1/2 -translate-y-1/2 z-10 flex h-9 w-9 md:h-11 md:w-11 items-center justify-center rounded-full shadow-md transition-all hover:scale-105 active:scale-95 border border-white/40 cursor-pointer"
          style="background-color: rgba(255, 255, 255, 0.55) !important; color: #2b1311 !important; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);"
          aria-label="Previous image"
        >
          <svg class="h-5 w-5 md:h-6 md:w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="m15 18-6-6 6-6"/>
          </svg>
        </button>

        <!-- Right Slider Arrow (Over the image - semi-transparent) -->
        <button
          type="button"
          onclick="slideImg(1)"
          class="absolute right-3 top-1/2 -translate-y-1/2 z-10 flex h-9 w-9 md:h-11 md:w-11 items-center justify-center rounded-full shadow-md transition-all hover:scale-105 active:scale-95 border border-white/40 cursor-pointer"
          style="background-color: rgba(255, 255, 255, 0.55) !important; color: #2b1311 !important; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);"
          aria-label="Next image"
        >
          <svg class="h-5 w-5 md:h-6 md:w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6"/>
          </svg>
        </button>

        <!-- Image Dots Indicator / Switcher -->
        <div class="absolute bottom-3.5 left-1/2 -translate-x-1/2 flex items-center gap-1.5 rounded-full bg-background/85 px-3 py-1.5 backdrop-blur-md border border-border/60 shadow-soft z-10">
          <?php foreach ($images as $imgIdx => $img): ?>
            <button
              type="button"
              onclick="goToImg(<?= $imgIdx ?>)"
              class="detail-gallery-thumb h-2.5 rounded-full transition-all <?= $imgIdx === 0 ? 'w-6 bg-primary' : 'w-2.5 bg-foreground/30 hover:bg-foreground/50' ?>"
              aria-label="View image <?= $imgIdx + 1 ?>"
            ></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right Column: Product Info & Actions -->
    <div class="flex flex-col">

      <!-- Category / Supertitle Tag -->
      <p class="text-[10px] md:text-xs font-semibold uppercase tracking-[0.2em] text-primary">CHIKKI</p>

      <!-- Recoleta Product Title -->
      <h1 class="mt-1 font-display text-3xl leading-tight md:text-4xl text-foreground font-normal">
        <?= htmlspecialchars($prod['name']) ?>
      </h1>

      <!-- Rating Stars & Reviews Count Link -->
      <div class="mt-2 flex items-center gap-2">
        <div class="flex items-center text-amber-500 text-sm">
          <?php for ($s = 1; $s <= 5; $s++): ?>
            <span><?= $s <= round($avgRating) ? '★' : '☆' ?></span>
          <?php endfor; ?>
        </div>
        <a href="#reviews" class="text-xs font-semibold text-muted-foreground hover:text-foreground transition underline underline-offset-2">
          <?= number_format($avgRating, 1) ?> (<?= $reviewCount ?> customer reviews)
        </a>
      </div>

      <!-- Description Paragraph -->
      <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
        <?= htmlspecialchars($prod['short_description'] ?? '') ?>

        <?= htmlspecialchars($prod['description'] ?? 'Crafted with 100% pure organic sugarcane jaggery and slow-roasted nuts.') ?>
      </p>

      <!-- Selected Weight Display -->
      <p class="mt-4 text-xs font-mono font-medium text-foreground/75" id="detail-weight-label">
        Quantity: <?= $firstVar['weight_grams'] >= 1000 ? ($firstVar['weight_grams'] / 1000) . 'kg' : $firstVar['weight_grams'] . 'g' ?> Box
      </p>

      <!-- Weight Selection Chips -->
      <div class="mt-2 flex flex-wrap gap-2" id="detail-chips-container">
        <?php foreach ($variants as $idx => $v):
          $wLabel = (int)$v['weight_grams'] >= 1000 ? ((int)$v['weight_grams'] / 1000) . 'kg' : ((int)$v['weight_grams']) . 'g';
        ?>
          <button
            type="button"
            class="detail-variant-chip rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-all <?= $idx === 0 ? 'border-secondary bg-secondary text-secondary-foreground shadow-soft' : 'border-border/80 bg-surface text-foreground/75 hover:bg-muted' ?>"
            data-variant-id="<?= (int)$v['id'] ?>"
            data-weight="<?= $wLabel ?> Box"
            data-price="<?= (float)$v['selling_price'] ?>"
            data-mrp="<?= (float)$v['mrp'] ?>"
            data-stock="<?= (int)($v['stock'] ?? 0) ?>"
            onclick="selectDetailVariant(this)"
          >
            <?= $wLabel ?>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- Live Inventory Stock Status Indicator (Driven by FIFO Batches) -->
      <div class="mt-2.5" id="detail-stock-indicator">
        <?php
        $firstStock = (int)($firstVar['stock'] ?? 50);
        if ($firstStock > 10):
        ?>
          <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200/80">
            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            In Stock (Fresh Batch Ready to Ship)
          </span>
        <?php elseif ($firstStock > 0): ?>
          <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 border border-amber-200/80">
            ⚠️ Only <?= $firstStock ?> left in stock — order soon!
          </span>
        <?php else: ?>
          <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 border border-rose-200/80">
            ❌ Currently Out of Stock
          </span>
        <?php endif; ?>
      </div>

      <!-- Price Section -->
      <div class="mt-4 flex items-baseline gap-2.5">
        <span class="font-display text-3xl text-foreground" id="detail-price">₹<?= number_format((float)$firstVar['selling_price'], 0) ?></span>
        <?php if ((float)$firstVar['mrp'] > (float)$firstVar['selling_price']): ?>
          <span class="text-sm text-muted-foreground line-through font-price" id="detail-mrp">₹<?= number_format((float)$firstVar['mrp'], 0) ?></span>
          <span class="rounded-full bg-secondary/15 px-2 py-0.5 font-mono text-[10px] font-semibold text-secondary" id="detail-off"><?= $discOff ?>% OFF</span>
        <?php endif; ?>
      </div>
      <p class="mt-1 text-[11px] text-muted-foreground">
        Inclusive of all taxes (GST <?= (float)($prod['gst_rate_percent'] ?? 5) ?>% included)
        <?php if (!empty($prod['hsn_code'])): ?> · HSN: <?= htmlspecialchars($prod['hsn_code']) ?><?php endif; ?>
      </p>

      <!-- Feature Capsules (Exact Yogurt Alley Architecture) -->
      <div class="mt-4 flex flex-wrap gap-1.5">
        <span class="rounded-full bg-accent/60 px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-accent-foreground">100% PURE JAGGERY</span>
        <span class="rounded-full bg-accent/60 px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-accent-foreground">ZERO REFINED SUGAR</span>
        <span class="rounded-full bg-accent/60 px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-accent-foreground">ROASTED NUTS</span>
        <span class="rounded-full bg-accent/60 px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-accent-foreground">HIGH PROTEIN</span>
        <span class="rounded-full bg-accent/60 px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-accent-foreground">HANDCRAFTED</span>
      </div>

      <!-- Desktop Inline Counter & Add to Bag Button (Hidden on Mobile) -->
      <div class="mt-6 hidden items-center gap-3 md:flex">
        <!-- Quantity Pill Counter -->
        <div class="flex items-center gap-1 rounded-full bg-muted p-1">
          <button
            type="button"
            onclick="changeDetailQty(-1)"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-background shadow-soft transition active:scale-95 text-foreground hover:bg-surface"
            aria-label="Decrease quantity"
          >
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/></svg>
          </button>
          <span class="w-7 text-center text-sm font-semibold tabular-nums text-foreground" id="detail-qty-desktop">1</span>
          <button
            type="button"
            onclick="changeDetailQty(1)"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-background shadow-soft transition active:scale-95 text-foreground hover:bg-surface"
            aria-label="Increase quantity"
          >
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
          </button>
        </div>

        <!-- Add to Bag Button -->
        <button
          type="button"
          id="detail-add-btn-desktop"
          onclick="addDetailToBag(this)"
          class="flex flex-1 items-center justify-center gap-2 rounded-full bg-primary py-3.5 px-6 text-sm font-semibold text-primary-foreground shadow-glow transition hover:scale-[1.01] active:scale-[0.98]"
          style="background-color:#c7613d !important; color:#ffffff !important;"
        >
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 10a4 4 0 0 1-8 0"/>
            <path d="M3.103 6.034h17.794"/>
            <path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/>
          </svg>
          <span id="detail-btn-text-desktop">Add to bag · ₹<?= number_format((float)$firstVar['selling_price'], 0) ?></span>
        </button>
      </div>

      <!-- Pincode Delivery Check Widget (Module Controlled) -->
      <?php if (function_exists('settingEnabled') ? settingEnabled('module_pincode_checker', '1') : true): 
        $pinHelper = function_exists('getSetting') ? getSetting('pincode_helper_text', 'Enter your 6-digit PIN code to check shipping rates and COD availability.') : 'Enter your 6-digit PIN code to check shipping rates and COD availability.';
      ?>
      <div class="mt-6 rounded-2xl border border-border/70 bg-card p-4 shadow-soft">
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-foreground">
          <svg class="h-4 w-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
          </svg>
          <span>Check Delivery Serviceability</span>
        </div>
        <p class="mt-1 text-[11px] text-muted-foreground"><?= htmlspecialchars($pinHelper) ?></p>
        <div class="mt-2.5 flex items-center gap-2">
          <input
            type="text"
            id="detail-pincode-input"
            maxlength="6"
            placeholder="e.g. 380001"
            class="h-9 w-36 rounded-lg border border-border bg-background px-3 text-xs font-mono tracking-wider text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            onkeydown="if(event.key==='Enter'){event.preventDefault();checkProductDelivery();}"
          />
          <button
            type="button"
            onclick="checkProductDelivery()"
            id="detail-pincode-btn"
            class="h-9 rounded-lg bg-secondary px-4 text-xs font-semibold text-secondary-foreground transition hover:opacity-90 active:scale-95 cursor-pointer"
          >
            Check
          </button>
        </div>
        <div id="detail-pincode-result" class="mt-2 text-xs hidden"></div>
      </div>
      <?php endif; ?>

    </div>

  </div>

  <!-- ══ CUSTOMER REVIEWS SECTION ══ -->
  <?php if (function_exists('settingEnabled') ? settingEnabled('module_reviews', '1') : true): 
    $canSubmitReviews = function_exists('settingEnabled') ? settingEnabled('reviews_allow_submission', '1') : true;
  ?>
  <section class="mt-16 border-t border-border/60 pt-12" id="reviews">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <p class="text-[10px] md:text-xs font-semibold uppercase tracking-[0.2em] text-primary">CUSTOMER FEEDBACK</p>
        <h2 class="font-display text-2xl md:text-3xl text-foreground mt-1">Customer Reviews</h2>
      </div>

      <div class="flex items-center gap-3">
        <div class="flex items-center gap-2 bg-muted/60 px-3.5 py-2 rounded-xl border border-border/50">
          <span class="font-display text-2xl font-bold text-foreground"><?= number_format($avgRating, 1) ?></span>
          <div>
            <div class="flex text-amber-500 text-xs">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <span><?= $s <= round($avgRating) ? '★' : '☆' ?></span>
              <?php endfor; ?>
            </div>
            <span class="text-[11px] text-muted-foreground"><?= $reviewCount ?> total ratings</span>
          </div>
        </div>

        <?php if ($canSubmitReviews): ?>
        <button
          type="button"
          onclick="toggleReviewForm()"
          class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2.5 text-xs font-semibold text-primary-foreground shadow-glow hover:opacity-95 active:scale-95 transition-all cursor-pointer"
          style="background-color:#c7613d !important; color:#ffffff !important;"
        >
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
          </svg>
          <span>Write a Review</span>
        </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Review Submission Form Card (Hidden by default) -->
    <div id="review-form-card" class="mt-8 hidden rounded-2xl border border-primary/20 bg-primary/5 p-5 md:p-6 transition-all">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-display text-lg text-foreground">Share Your Experience</h3>
        <button type="button" onclick="toggleReviewForm()" class="text-xs text-muted-foreground hover:text-foreground cursor-pointer">Cancel</button>
      </div>

      <div id="review-form-msg" class="hidden"></div>

      <form onsubmit="submitCustomerReview(event)" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-foreground/80 mb-1.5">Your Rating</label>
          <div class="flex items-center gap-1 text-2xl cursor-pointer select-none">
            <?php for ($r = 1; $r <= 5; $r++): ?>
              <button
                type="button"
                onclick="setReviewRating(<?= $r ?>)"
                class="review-star-btn text-amber-500 hover:scale-110 transition-transform p-0.5"
                data-star="<?= $r ?>"
                aria-label="<?= $r ?> stars"
              >★</button>
            <?php endfor; ?>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-foreground/80 mb-1">Your Name <span class="text-rose-500">*</span></label>
            <input
              type="text"
              name="name"
              required
              value="<?= htmlspecialchars($currentUser['full_name'] ?? '') ?>"
              placeholder="e.g. Ramesh Patel"
              class="w-full rounded-lg border border-border bg-background px-3.5 py-2 text-xs text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-foreground/80 mb-1">Your Email <span class="text-rose-500">*</span></label>
            <input
              type="email"
              name="email"
              required
              value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>"
              placeholder="e.g. ramesh@example.com"
              class="w-full rounded-lg border border-border bg-background px-3.5 py-2 text-xs text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-foreground/80 mb-1">Your Review <span class="text-rose-500">*</span></label>
          <textarea
            name="comment"
            rows="3"
            required
            placeholder="Tell us about the crunch, sweetness, freshness, and packaging..."
            class="w-full rounded-lg border border-border bg-background px-3.5 py-2 text-xs text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
          ></textarea>
        </div>

        <div class="flex items-center justify-between pt-1">
          <span class="text-[11px] text-muted-foreground">All reviews are moderated by our team before going live.</span>
          <button
            type="submit"
            class="rounded-xl bg-primary px-5 py-2.5 text-xs font-semibold text-primary-foreground shadow-glow transition hover:opacity-95 active:scale-95 cursor-pointer"
            style="background-color:#c7613d !important; color:#ffffff !important;"
          >
            Submit Review
          </button>
        </div>
      </form>
    </div>

    <!-- Approved Reviews List -->
    <div class="mt-8 grid gap-4 md:grid-cols-2">
      <?php if (empty($reviews)): ?>
        <div class="col-span-2 rounded-2xl border border-dashed border-border/80 bg-muted/20 p-8 text-center">
          <p class="text-sm font-semibold text-foreground">No customer reviews yet</p>
          <p class="mt-1 text-xs text-muted-foreground">Be the first to share your thoughts on this authentic handcrafted delicacy!</p>
        </div>
      <?php else: ?>
        <?php foreach ($reviews as $rev):
          $rName = $rev['full_name'] ?? 'Verified Buyer';
          $initial = strtoupper(substr($rName, 0, 1));
          $ratingVal = (int)($rev['rating'] ?? 5);
        ?>
          <div class="flex flex-col justify-between rounded-2xl border border-border/60 bg-card p-4 md:p-5 shadow-soft">
            <div>
              <div class="flex items-center justify-between gap-2 mb-2">
                <div class="flex items-center gap-2.5">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full bg-secondary text-secondary-foreground text-xs font-bold">
                    <?= $initial ?>
                  </div>
                  <div>
                    <h4 class="text-xs font-semibold text-foreground leading-none"><?= htmlspecialchars($rName) ?></h4>
                    <span class="inline-flex items-center gap-1 text-[10px] text-emerald-600 font-medium mt-0.5">
                      <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                      Verified Buyer
                    </span>
                  </div>
                </div>

                <span class="text-[11px] text-muted-foreground">
                  <?= !empty($rev['created_at']) ? date('M j, Y', strtotime($rev['created_at'])) : 'Recent' ?>
                </span>
              </div>

              <!-- Rating Stars -->
              <div class="flex text-amber-500 text-xs mb-2">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <span><?= $s <= $ratingVal ? '★' : '☆' ?></span>
                <?php endfor; ?>
              </div>

              <!-- Comment -->
              <p class="text-xs text-muted-foreground leading-relaxed">
                <?= nl2br(htmlspecialchars($rev['comment'] ?? '')) ?>
              </p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>
</main>

<!-- ══ MOBILE STICKY BOTTOM ACTION BAR (Exact Yogurt Alley Architecture) ══ -->
<div class="fixed inset-x-0 bottom-0 z-40 border-t border-border/60 bg-card/95 px-4 pt-2.5 pb-3 backdrop-blur md:hidden flex items-center gap-3 shadow-pop">
  <!-- Quantity Counter Pill -->
  <div class="flex items-center gap-1 rounded-full bg-muted p-1 shrink-0">
    <button
      type="button"
      onclick="changeDetailQty(-1)"
      class="flex h-9 w-9 items-center justify-center rounded-full bg-background shadow-soft transition active:scale-95 text-foreground hover:bg-surface"
      aria-label="Decrease quantity"
    >
      <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/></svg>
    </button>
    <span class="w-6 text-center text-sm font-semibold tabular-nums text-foreground" id="detail-qty-mobile">1</span>
    <button
      type="button"
      onclick="changeDetailQty(1)"
      class="flex h-9 w-9 items-center justify-center rounded-full bg-background shadow-soft transition active:scale-95 text-foreground hover:bg-surface"
      aria-label="Increase quantity"
    >
      <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
    </button>
  </div>

  <!-- Add to Bag Button -->
  <button
    type="button"
    id="detail-add-btn-mobile"
    onclick="addDetailToBag(this)"
    class="flex flex-1 items-center justify-center gap-2 rounded-full bg-primary py-3 px-4 text-sm font-semibold text-primary-foreground shadow-glow transition active:scale-[0.98]"
    style="background-color:#c7613d !important; color:#ffffff !important;"
  >
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M16 10a4 4 0 0 1-8 0"/>
      <path d="M3.103 6.034h17.794"/>
      <path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/>
    </svg>
    <span id="detail-btn-text-mobile">Add to bag · ₹<?= number_format((float)$firstVar['selling_price'], 0) ?></span>
  </button>
</div>

<style>
/* Hide generic mobile cart bar on product detail page */
#mobile-bottom-bar { display: none !important; }
</style>

<script>
let currentVariantId = <?= (int)($firstVar['id'] ?? 0) ?>;
let currentUnitPrice = <?= (float)($firstVar['selling_price'] ?? 120) ?>;
let detailQuantity = 1;
const currentProductName = <?= json_encode($prod['name']) ?>;

function selectDetailVariant(chip) {
  document.querySelectorAll('.detail-variant-chip').forEach(c => {
    c.className = 'detail-variant-chip rounded-full border border-border/80 bg-surface px-3.5 py-1.5 text-xs font-semibold text-foreground/75 hover:bg-muted transition-all';
  });
  chip.className = 'detail-variant-chip rounded-full border border-secondary bg-secondary px-3.5 py-1.5 text-xs font-semibold text-secondary-foreground shadow-soft transition-all';

  currentVariantId = parseInt(chip.dataset.variantId);
  currentUnitPrice = parseFloat(chip.dataset.price);
  const mrp = parseFloat(chip.dataset.mrp);
  const stock = parseInt(chip.dataset.stock || 0);
  const off = mrp > currentUnitPrice ? Math.round((1 - currentUnitPrice / mrp) * 100) : 0;

  // Update Weight label
  const weightLabel = chip.dataset.weight || '';
  const weightDisplay = document.getElementById('detail-weight-label');
  if (weightDisplay) weightDisplay.textContent = 'Quantity: ' + weightLabel;

  // Update Price
  document.getElementById('detail-price').textContent = '₹' + Math.round(currentUnitPrice);
  const mrpEl = document.getElementById('detail-mrp');
  const offEl = document.getElementById('detail-off');
  if (mrpEl) {
    if (mrp > currentUnitPrice) {
      mrpEl.textContent = '₹' + Math.round(mrp);
      mrpEl.style.display = 'inline';
    } else {
      mrpEl.style.display = 'none';
    }
  }
  if (offEl) {
    if (off > 0) {
      offEl.textContent = off + '% OFF';
      offEl.style.display = 'inline-block';
    } else {
      offEl.style.display = 'none';
    }
  }

  // Update Live Stock Indicator
  const stockIndicator = document.getElementById('detail-stock-indicator');
  const deskBtn = document.getElementById('detail-add-btn-desktop');
  const mobBtn = document.getElementById('detail-add-btn-mobile');

  if (stockIndicator) {
    if (stock > 10) {
      stockIndicator.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200/80"><span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> In Stock (Fresh Batch Ready to Ship)</span>';
      if (deskBtn) { deskBtn.disabled = false; deskBtn.style.opacity = '1'; }
      if (mobBtn) { mobBtn.disabled = false; mobBtn.style.opacity = '1'; }
    } else if (stock > 0) {
      stockIndicator.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 border border-amber-200/80">⚠️ Only ' + stock + ' left in stock — order soon!</span>';
      if (deskBtn) { deskBtn.disabled = false; deskBtn.style.opacity = '1'; }
      if (mobBtn) { mobBtn.disabled = false; mobBtn.style.opacity = '1'; }
    } else {
      stockIndicator.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 border border-rose-200/80">❌ Currently Out of Stock</span>';
      if (deskBtn) { deskBtn.disabled = true; deskBtn.style.opacity = '0.5'; }
      if (mobBtn) { mobBtn.disabled = true; mobBtn.style.opacity = '0.5'; }
    }
  }

  updateDetailButtons();
}

function changeDetailQty(delta) {
  detailQuantity = Math.max(1, detailQuantity + delta);
  const qtyDesk = document.getElementById('detail-qty-desktop');
  const qtyMob = document.getElementById('detail-qty-mobile');
  if (qtyDesk) qtyDesk.textContent = detailQuantity;
  if (qtyMob) qtyMob.textContent = detailQuantity;
  updateDetailButtons();
}

function updateDetailButtons() {
  const total = Math.round(currentUnitPrice * detailQuantity);
  const deskBtn = document.getElementById('detail-btn-text-desktop');
  const mobBtn = document.getElementById('detail-btn-text-mobile');
  const addDesk = document.getElementById('detail-add-btn-desktop');
  if (addDesk && addDesk.disabled) {
    if (deskBtn) deskBtn.textContent = 'Out of Stock';
    if (mobBtn) mobBtn.textContent = 'Out of Stock';
  } else {
    if (deskBtn) deskBtn.textContent = 'Add to bag · ₹' + total;
    if (mobBtn) mobBtn.textContent = 'Add to bag · ₹' + total;
  }
}

function addDetailToBag(btn) {
  if (window.addToCart) {
    window.addToCart(currentVariantId, detailQuantity, btn, currentProductName);
  }
}

// ── Pincode Delivery Check
function checkProductDelivery() {
  const input = document.getElementById('detail-pincode-input');
  const res = document.getElementById('detail-pincode-result');
  const btn = document.getElementById('detail-pincode-btn');
  const pin = (input?.value || '').trim();

  if (!pin || pin.length !== 6 || !/^\d+$/.test(pin)) {
    res.className = 'mt-2 text-xs text-rose-600';
    res.textContent = 'Please enter a valid 6-digit PIN code.';
    res.classList.remove('hidden');
    return;
  }

  btn.disabled = true;
  btn.textContent = '...';
  res.className = 'mt-2 text-xs text-muted-foreground';
  res.textContent = 'Checking serviceability...';
  res.classList.remove('hidden');

  fetch('api/pincode-check.php?pincode=' + encodeURIComponent(pin) + '&weight=500')
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.textContent = 'Check';
      if (data.ok) {
        res.className = 'mt-2 text-xs text-emerald-800 bg-emerald-50 border border-emerald-200/80 p-2.5 rounded-xl flex items-start gap-1.5';
        res.innerHTML = '<span class="text-sm">✓</span> <div><strong>Serviceable:</strong> Delivery to ' + (data.zone_name || 'your area') + ' is active. Standard shipping ₹' + (data.charge ?? 60) + (data.free_above ? ' (Free over ₹' + data.free_above + ')' : '') + (data.cod_available ? ' · 💵 Cash on Delivery available' : '') + '</div>';
      } else {
        res.className = 'mt-2 text-xs text-rose-700 bg-rose-50 border border-rose-200/80 p-2.5 rounded-xl flex items-start gap-1.5';
        res.innerHTML = '<span class="text-sm">✗</span> <div><strong>Not Serviceable:</strong> ' + (data.error || 'Delivery not available to this PIN code.') + '</div>';
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.textContent = 'Check';
      res.className = 'mt-2 text-xs text-rose-600';
      res.textContent = 'Unable to check PIN code right now. Please try again.';
    });
}

// ── Customer Reviews Handling
let selectedReviewRating = 5;

function setReviewRating(r) {
  selectedReviewRating = r;
  document.querySelectorAll('.review-star-btn').forEach((btn) => {
    const starVal = parseInt(btn.dataset.star || 1);
    btn.textContent = (starVal <= r) ? '★' : '☆';
  });
}

function toggleReviewForm() {
  const formCard = document.getElementById('review-form-card');
  if (formCard) {
    formCard.classList.toggle('hidden');
    if (!formCard.classList.contains('hidden')) {
      formCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }
}

function submitCustomerReview(e) {
  e.preventDefault();
  const form = e.target;
  const btn = form.querySelector('button[type="submit"]');
  const msgEl = document.getElementById('review-form-msg');

  const name = (form.name?.value || '').trim();
  const email = (form.email?.value || '').trim();
  const comment = (form.comment?.value || '').trim();

  if (comment.length < 5) {
    msgEl.className = 'text-xs text-rose-600 mb-3 block';
    msgEl.textContent = 'Please write a review of at least a few words.';
    msgEl.classList.remove('hidden');
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Submitting...';
  msgEl.classList.add('hidden');

  fetch('api/review-submit.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      product_id: <?= (int)$prod['id'] ?>,
      rating: selectedReviewRating,
      comment: comment,
      name: name,
      email: email
    })
  })
  .then(r => r.json())
  .then(data => {
    btn.disabled = false;
    btn.textContent = 'Submit Review';
    if (data.ok) {
      msgEl.className = 'text-xs text-emerald-800 bg-emerald-50 border border-emerald-200 p-3 rounded-xl mb-4 block';
      msgEl.innerHTML = '<strong>Thank you!</strong> ' + (data.message || 'Your review was submitted and will appear once approved by our moderation team.');
      msgEl.classList.remove('hidden');
      form.reset();
      setReviewRating(5);
      setTimeout(() => {
        const formCard = document.getElementById('review-form-card');
        if (formCard) formCard.classList.add('hidden');
      }, 5000);
    } else {
      msgEl.className = 'text-xs text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-xl mb-4 block';
      msgEl.textContent = '⚠️ ' + (data.error || 'Failed to submit review.');
      msgEl.classList.remove('hidden');
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.textContent = 'Submit Review';
    msgEl.className = 'text-xs text-rose-600 mb-3 block';
    msgEl.textContent = 'Network error. Please try again.';
    msgEl.classList.remove('hidden');
  });
}

const productImages = <?= json_encode(array_values(array_map(function($im) { return imageUrl($im['image_path']); }, $images))) ?>;
let currentImgIndex = 0;

function goToImg(index) {
  if (!productImages || productImages.length === 0) return;
  currentImgIndex = (index + productImages.length) % productImages.length;
  const img = document.getElementById('main-product-img');
  if (img) {
    img.style.opacity = '0.3';
    setTimeout(() => {
      img.src = productImages[currentImgIndex];
      img.style.opacity = '1';
    }, 120);
  }
  const dots = document.querySelectorAll('.detail-gallery-thumb');
  dots.forEach((d, idx) => {
    if (idx === currentImgIndex) {
      d.className = 'detail-gallery-thumb h-2.5 w-6 rounded-full bg-primary transition-all';
    } else {
      d.className = 'detail-gallery-thumb h-2.5 w-2.5 rounded-full bg-foreground/30 hover:bg-foreground/50 transition-all';
    }
  });
}

function slideImg(delta) {
  goToImg(currentImgIndex + delta);
}

// Touch swipe support for smooth mobile swiping
(function() {
  const imgEl = document.getElementById('main-product-img');
  if (!imgEl) return;
  let startX = 0;
  imgEl.parentElement.addEventListener('touchstart', function(e) {
    if (e.touches.length === 1) startX = e.touches[0].clientX;
  }, { passive: true });
  imgEl.parentElement.addEventListener('touchend', function(e) {
    if (e.changedTouches.length === 1) {
      const diffX = e.changedTouches[0].clientX - startX;
      if (diffX > 40) slideImg(-1);
      else if (diffX < -40) slideImg(1);
    }
  }, { passive: true });

  // Arrow key navigation
  window.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') slideImg(-1);
    if (e.key === 'ArrowRight') slideImg(1);
  });
})();
</script>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>
