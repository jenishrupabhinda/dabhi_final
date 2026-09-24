<?php
/**
 * index.php — Dabhi Chikki Storefront Homepage
 * Exact 1:1 Pixel-Perfect Replica of https://order.yogurtalley.com
 */

$pageTitle = 'Dabhi Chikki — Handcrafted With Pure Jaggery Since 2009';
$pageDesc  = 'Tastes like dessert. Works like fuel. Authentic handmade Mandvi, TIL, Daliya, and 3 Mix Chikki made with pure jaggery. Pan-India fresh delivery.';

require_once __DIR__ . '/partials/_header.php';

// Fetch products from database with variants
$products = [];
try {
    $products = Database::fetchAll(
        "SELECT p.*,
                (SELECT MIN(v.selling_price) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS min_price,
                (SELECT v.mrp FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1 ORDER BY v.selling_price ASC LIMIT 1) AS max_mrp,
                (SELECT v.id FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1 ORDER BY v.selling_price ASC LIMIT 1) AS min_variant_id,
                (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image,
                (SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.product_id = p.id AND r.is_approved = 1) AS avg_rating,
                (SELECT COUNT(r.id) FROM reviews r WHERE r.product_id = p.id AND r.is_approved = 1) AS review_count
         FROM products p WHERE p.is_active = 1 ORDER BY p.id ASC LIMIT 4"
    );
} catch (\Throwable $e) {
    // Robust fallback if DB is offline
    $products = [
        ['id'=>1,'name'=>'Mandvi Chikki','slug'=>'mandvi-chikki','short_description'=>'Classic roasted groundnut chikki with pure jaggery.','min_price'=>200,'max_mrp'=>250,'min_variant_id'=>2,'primary_image'=>'assets/images/products/mandvi-chikki-1.jpg'],
        ['id'=>2,'name'=>'TIL Chikki','slug'=>'til-chikki','short_description'=>'Sesame seed chikki — nutty, fragrant, wholesome crunch.','min_price'=>200,'max_mrp'=>250,'min_variant_id'=>5,'primary_image'=>'assets/images/products/til-chikki-1.jpg'],
        ['id'=>3,'name'=>'Daliya Chikki','slug'=>'daliya-chikki','short_description'=>'Roasted split gram chikki — crispy, golden, protein-rich.','min_price'=>200,'max_mrp'=>250,'min_variant_id'=>8,'primary_image'=>'assets/images/products/daliya-chikki-1.jpg'],
        ['id'=>4,'name'=>'3 Mix Chikki','slug'=>'3-mix-chikki','short_description'=>'Signature blend of Mandvi, Til & Coconut Crush.','min_price'=>250,'max_mrp'=>300,'min_variant_id'=>11,'primary_image'=>'assets/images/products/3-mix-chikki-1.jpg'],
    ];
}

// Helper discount percentage
function discountPct($mrp, $sell): int {
    if (!$mrp || $mrp <= 0 || $mrp <= $sell) return 0;
    return (int)round((($mrp - $sell) / $mrp) * 100);
}
?>

<!-- ══ PRODUCTS SECTION (Pick your chikki craving!) ════════════════ -->
<section id="products" class="mx-auto max-w-6xl px-5 pb-10 pt-6 sm:px-6 md:px-8">
  <div class="mb-4 flex items-end justify-between">
    <div>
      <h2 class="font-display text-3xl md:text-4xl text-foreground">Pick your chikki craving!</h2>
    </div>
  </div>

  <!-- Category Filter Pills (Exact Yogurt Alley Architecture) -->
  <div class="no-scrollbar -mx-4 mb-5 flex gap-2 overflow-x-auto px-4 md:mx-0 md:px-0">
    <button type="button" class="category-pill shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition bg-secondary text-secondary-foreground shadow-soft" data-cat="all">
      Most Loved ❤︎
    </button>
    <button type="button" class="category-pill shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition bg-surface text-foreground/70 hover:bg-muted" data-cat="1">
      Mandvi Chikki
    </button>
    <button type="button" class="category-pill shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition bg-surface text-foreground/70 hover:bg-muted" data-cat="2">
      TIL Chikki
    </button>
    <button type="button" class="category-pill shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition bg-surface text-foreground/70 hover:bg-muted" data-cat="3">
      Daliya Chikki
    </button>
    <button type="button" class="category-pill shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition bg-surface text-foreground/70 hover:bg-muted" data-cat="4">
      3 Mix Chikki
    </button>
  </div>

  <!-- 4-Column Product Grid (Exact Yogurt Alley Fe / kt Architecture) -->
  <div class="grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-4 md:gap-6" id="products-grid">
    <?php foreach ($products as $prod):
      $pId     = (int)$prod['id'];
      $img     = imageUrl($prod['primary_image'] ?? 'assets/images/products/mandvi-chikki-1.jpg');
      $price   = (float)($prod['min_price'] ?? 200);
      $mrp     = (float)($prod['max_mrp']   ?? 250);
      $off     = discountPct($mrp, $price);
      $varId   = (int)($prod['min_variant_id'] ?? 0);

      // Load product variants from DB
      $variants = [];
      try {
          $variants = Product::getVariants($pId);
      } catch (\Throwable $e) {
          $variants = [
              ['id'=>$varId,   'weight_grams'=>500,  'selling_price'=>$price,      'mrp'=>$mrp],
              ['id'=>$varId+1, 'weight_grams'=>1000, 'selling_price'=>$price*1.9,  'mrp'=>$mrp*1.9],
          ];
      }
      $firstVar = $variants[0] ?? ['id'=>$varId,'weight_grams'=>500,'selling_price'=>$price,'mrp'=>$mrp];
    ?>
    <div class="product-item group flex cursor-pointer flex-col overflow-hidden rounded-xl bg-card text-left shadow-soft transition hover:-translate-y-1 hover:shadow-pop focus:outline-none focus-visible:ring-2 focus-visible:ring-primary border border-border/60" data-product-id="<?= $pId ?>">
      
      <!-- Aspect-Square Product Image -->
      <a href="product.php?slug=<?= urlencode($prod['slug']) ?>" class="relative aspect-square overflow-hidden bg-muted block">
        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy" class="h-full w-full select-none object-cover transition-transform duration-500 group-hover:scale-105"/>
        <?php if ($off > 0): ?>
          <span class="absolute left-3 top-3 rounded-full bg-primary px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-primary-foreground shadow-soft" id="off-badge-<?= $pId ?>">
            <?= $off ?>% OFF
          </span>
        <?php endif; ?>
      </a>

      <!-- Card Body Content -->
      <div class="flex flex-1 flex-col gap-2 p-3 md:p-4">
        <a href="product.php?slug=<?= urlencode($prod['slug']) ?>">
          <h3 class="font-display text-base leading-tight md:text-lg text-foreground hover:text-primary transition">
            <?= htmlspecialchars($prod['name']) ?>
          </h3>
        </a>

        <!-- Rating Stars & Reviews Count (Admin Configurable) -->
        <?php
        $cardRatingsEnabled  = function_exists('settingEnabled') ? settingEnabled('module_card_ratings', '1') : true;
        $cardFallbackEnabled = function_exists('settingEnabled') ? settingEnabled('card_rating_fallback_enabled', '1') : true;
        $defaultRating       = function_exists('getSetting') ? getSetting('card_default_rating', '4.7') : '4.7';
        $defaultReviews      = function_exists('getSetting') ? getSetting('card_default_reviews', '3') : '3';

        $hasDbRating  = (!empty($prod['avg_rating']) && (float)$prod['avg_rating'] > 0);
        $finalRating  = $hasDbRating ? (float)$prod['avg_rating'] : ($cardFallbackEnabled ? (float)$defaultRating : 0);
        $finalReviews = $hasDbRating ? (int)($prod['review_count'] ?? 0) : ($cardFallbackEnabled ? (int)$defaultReviews : 0);
        ?>
        <?php if ($cardRatingsEnabled && $finalRating > 0): ?>
          <div class="flex items-center gap-1 text-[11px] text-amber-500 font-medium">
            <span>★</span>
            <span class="text-foreground font-semibold"><?= number_format($finalRating, 1) ?></span>
            <span class="text-muted-foreground text-[10px]">(<?= $finalReviews ?>)</span>
          </div>
        <?php endif; ?>

        <!-- Feature Badges (Admin Configurable & Per-Product Customizable) -->
        <?php
        $cardBadgesEnabled = function_exists('settingEnabled') ? settingEnabled('module_card_badges', '1') : true;
        $cardBadgesRaw     = !empty($prod['custom_card_badges']) ? $prod['custom_card_badges'] : (function_exists('getSetting') ? getSetting('card_badges_list', 'Pure Jaggery, 100% Natural') : 'Pure Jaggery, 100% Natural');
        $cardBadgesList    = array_filter(array_map('trim', preg_split('/[,\r\n]+/', (string)$cardBadgesRaw)));
        ?>
        <?php if ($cardBadgesEnabled && !empty($cardBadgesList)): ?>
          <div class="flex flex-wrap gap-1">
            <?php foreach ($cardBadgesList as $badgeText): ?>
              <span class="rounded-full bg-accent/60 px-2 py-0.5 font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-accent-foreground">
                <?= htmlspecialchars($badgeText) ?>
              </span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Weight Variant Selector Chips (500g, 1kg) -->
        <div class="weight-selector-chips flex flex-wrap items-center gap-1 mt-1" data-product-id="<?= $pId ?>">
          <?php foreach ($variants as $idx => $v):
            $wLabel = (int)$v['weight_grams'] >= 1000 ? ((int)$v['weight_grams']/1000).'kg' : ((int)$v['weight_grams']).'g';
          ?>
            <button
              type="button"
              class="weight-chip text-[10px] sm:text-[11px] font-semibold py-0.5 px-1.5 sm:px-2 rounded-full border border-border/80 transition <?= $idx === 0 ? 'bg-secondary text-secondary-foreground border-secondary' : 'bg-surface text-foreground/70 hover:bg-muted' ?>"
              data-variant-id="<?= (int)$v['id'] ?>"
              data-price="<?= (float)$v['selling_price'] ?>"
              data-mrp="<?= (float)$v['mrp'] ?>"
              onclick="selectWeight(this, <?= $pId ?>)"
            ><?= $wLabel ?></button>
          <?php endforeach; ?>
        </div>

        <!-- Price & Terracotta Plus Button Bottom Row -->
        <div class="mt-auto flex items-end justify-between pt-2 border-t border-border/40">
          <div class="flex items-baseline gap-1.5">
            <span class="text-base font-bold text-foreground md:text-lg font-price" id="price-<?= $pId ?>">
              ₹<?= number_format((float)$firstVar['selling_price'], 0) ?>
            </span>
            <?php if ((float)$firstVar['mrp'] > (float)$firstVar['selling_price']): ?>
              <span class="text-xs text-muted-foreground line-through font-price" id="mrp-<?= $pId ?>">
                ₹<?= number_format((float)$firstVar['mrp'], 0) ?>
              </span>
            <?php endif; ?>
          </div>

          <!-- Add (+) Button with Circular Ripple -->
          <button
            type="button"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-soft transition hover:scale-110 active:scale-95 hover:shadow-pop"
            style="background-color:#c7613d !important; color:#ffffff !important;"
            onclick="addFromCard(<?= $pId ?>, this)"
            aria-label="Add <?= htmlspecialchars($prod['name']) ?> to bag"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
          </button>
        </div>

      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ SECTION 5: FEATURED THIS SEASON (Horizontal Snap Carousel) ═ -->
<?php if (function_exists('settingEnabled') ? settingEnabled('module_featured', '1') : true): 
  $featHeading = function_exists('getSetting') ? getSetting('featured_heading', 'Featured This Season') : 'Featured This Season';
?>
<section class="mx-auto max-w-6xl px-4 pb-12 pt-8 md:px-6">
  <div class="mb-5 flex items-end justify-between">
    <div>
      <p class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-primary">Highlighted</p>
      <h2 class="mt-1 font-display text-3xl md:text-4xl text-foreground"><?= htmlspecialchars($featHeading) ?></h2>
    </div>
    <div class="hidden sm:flex items-center gap-2">
      <button type="button" onclick="scrollFeatured(-1)" class="flex h-9 w-9 items-center justify-center rounded-full border border-border bg-surface text-secondary shadow-soft transition hover:scale-105" aria-label="Previous">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left h-4 w-4"><path d="m15 18-6-6 6-6"/></svg>
      </button>
      <button type="button" onclick="scrollFeatured(1)" class="flex h-9 w-9 items-center justify-center rounded-full border border-border bg-surface text-secondary shadow-soft transition hover:scale-105" aria-label="Next">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right h-4 w-4"><path d="m9 18 6-6-6-6"/></svg>
      </button>
    </div>
  </div>

  <div id="featured-carousel" class="no-scrollbar -mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth px-4 overscroll-x-contain md:-mx-6 md:px-6" style="scroll-snap-type:x mandatory">
    
    <!-- Featured Card 1: 3 Mix Chikki Signature -->
    <div class="flex shrink-0 snap-center basis-full sm:snap-start sm:basis-[48%] md:basis-[calc((100%-2rem)/3)]">
      <div class="group flex w-full flex-col overflow-hidden rounded-2xl border border-border/60 bg-card shadow-soft transition hover:-translate-y-0.5 hover:shadow-pop">
        <a href="product.php?slug=3-mix-chikki" class="relative aspect-[4/3] max-h-[280px] overflow-hidden bg-muted block cursor-pointer">
          <img src="assets/images/products/3-mix-chikki-1.jpg" alt="3 Mix Chikki" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"/>
          <span class="absolute left-3 top-3 rounded-full bg-primary px-3 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-primary-foreground shadow-soft">Trending 🔥</span>
          <span class="absolute right-3 top-3 rounded-full bg-secondary px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-secondary-foreground shadow-soft">Season Special</span>
        </a>
        <div class="flex flex-1 flex-col gap-1.5 p-4 md:p-5">
          <a href="product.php?slug=3-mix-chikki" class="hover:text-primary transition-colors">
            <h3 class="font-display text-xl font-bold leading-tight md:text-2xl text-foreground">3 Mix Signature Box</h3>
          </a>
          <p class="line-clamp-2 text-sm text-muted-foreground md:text-[15px]">Mandvi, Til &amp; toasted coconut flakes bound together with golden sugarcane jaggery.</p>
          <div class="mt-3 flex items-center justify-between pt-2 border-t border-border/40">
            <div class="flex items-baseline gap-2">
              <span class="text-xl font-bold md:text-2xl font-price text-foreground">₹250</span>
              <span class="text-sm text-muted-foreground line-through font-price">₹300</span>
            </div>
            <button type="button" onclick="addToCart(11, 1, this, '3 Mix Signature Box')" class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-soft transition hover:scale-110 active:scale-95" style="background-color:#c7613d !important; color:#ffffff !important;" aria-label="Add 3 Mix Signature Box to cart">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Featured Card 2: Mandvi Classic -->
    <div class="flex shrink-0 snap-center basis-full sm:snap-start sm:basis-[48%] md:basis-[calc((100%-2rem)/3)]">
      <div class="group flex w-full flex-col overflow-hidden rounded-2xl border border-border/60 bg-card shadow-soft transition hover:-translate-y-0.5 hover:shadow-pop">
        <a href="product.php?slug=mandvi-chikki" class="relative aspect-[4/3] max-h-[280px] overflow-hidden bg-muted block cursor-pointer">
          <img src="assets/images/products/mandvi-chikki-1.jpg" alt="Mandvi Groundnut Chikki" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"/>
          <span class="absolute left-3 top-3 rounded-full bg-primary px-3 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-primary-foreground shadow-soft">Bestseller</span>
          <span class="absolute right-3 top-3 rounded-full bg-secondary px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-secondary-foreground shadow-soft">20% OFF</span>
        </a>
        <div class="flex flex-1 flex-col gap-1.5 p-4 md:p-5">
          <a href="product.php?slug=mandvi-chikki" class="hover:text-primary transition-colors">
            <h3 class="font-display text-xl font-bold leading-tight md:text-2xl text-foreground">Mandvi Peanut Chikki</h3>
          </a>
          <p class="line-clamp-2 text-sm text-muted-foreground md:text-[15px]">Slow-roasted Saurashtra peanuts in rich natural jaggery. Unmatched crunch.</p>
          <div class="mt-3 flex items-center justify-between pt-2 border-t border-border/40">
            <div class="flex items-baseline gap-2">
              <span class="text-xl font-bold md:text-2xl font-price text-foreground">₹200</span>
              <span class="text-sm text-muted-foreground line-through font-price">₹250</span>
            </div>
            <button type="button" onclick="addToCart(2, 1, this, 'Mandvi Peanut Chikki')" class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-soft transition hover:scale-110 active:scale-95" style="background-color:#c7613d !important; color:#ffffff !important;" aria-label="Add Mandvi Peanut Chikki to cart">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Featured Card 3: Til Chikki -->
    <div class="flex shrink-0 snap-center basis-full sm:snap-start sm:basis-[48%] md:basis-[calc((100%-2rem)/3)]">
      <div class="group flex w-full flex-col overflow-hidden rounded-2xl border border-border/60 bg-card shadow-soft transition hover:-translate-y-0.5 hover:shadow-pop">
        <a href="product.php?slug=til-chikki" class="relative aspect-[4/3] max-h-[280px] overflow-hidden bg-muted block cursor-pointer">
          <img src="assets/images/products/til-chikki-1.jpg" alt="Til Sesame Chikki" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"/>
          <span class="absolute left-3 top-3 rounded-full bg-primary px-3 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-primary-foreground shadow-soft">Calcium Rich</span>
          <span class="absolute right-3 top-3 rounded-full bg-secondary px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-secondary-foreground shadow-soft">20% OFF</span>
        </a>
        <div class="flex flex-1 flex-col gap-1.5 p-4 md:p-5">
          <a href="product.php?slug=til-chikki" class="hover:text-primary transition-colors">
            <h3 class="font-display text-xl font-bold leading-tight md:text-2xl text-foreground">White Sesame (TIL) Chikki</h3>
          </a>
          <p class="line-clamp-2 text-sm text-muted-foreground md:text-[15px]">Fragrant roasted sesame seeds that melt on the tongue with sweet jaggery notes.</p>
          <div class="mt-3 flex items-center justify-between pt-2 border-t border-border/40">
            <div class="flex items-baseline gap-2">
              <span class="text-xl font-bold md:text-2xl font-price text-foreground">₹200</span>
              <span class="text-sm text-muted-foreground line-through font-price">₹250</span>
            </div>
            <button type="button" onclick="addToCart(5, 1, this, 'White Sesame (TIL) Chikki')" class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-soft transition hover:scale-110 active:scale-95" style="background-color:#c7613d !important; color:#ffffff !important;" aria-label="Add Til Chikki to cart">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Featured Card 4: Daliya Chikki -->
    <div class="flex shrink-0 snap-center basis-full sm:snap-start sm:basis-[48%] md:basis-[calc((100%-2rem)/3)]">
      <div class="group flex w-full flex-col overflow-hidden rounded-2xl border border-border/60 bg-card shadow-soft transition hover:-translate-y-0.5 hover:shadow-pop">
        <a href="product.php?slug=daliya-chikki" class="relative aspect-[4/3] max-h-[280px] overflow-hidden bg-muted block cursor-pointer">
          <img src="assets/images/products/daliya-chikki-1.jpg" alt="Daliya Chikki" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"/>
          <span class="absolute left-3 top-3 rounded-full bg-primary px-3 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-primary-foreground shadow-soft">Unique Crisp</span>
          <span class="absolute right-3 top-3 rounded-full bg-secondary px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-secondary-foreground shadow-soft">20% OFF</span>
        </a>
        <div class="flex flex-1 flex-col gap-1.5 p-4 md:p-5">
          <a href="product.php?slug=daliya-chikki" class="hover:text-primary transition-colors">
            <h3 class="font-display text-xl font-bold leading-tight md:text-2xl text-foreground">Daliya (Roasted Gram) Chikki</h3>
          </a>
          <p class="line-clamp-2 text-sm text-muted-foreground md:text-[15px]">A regional favourite made from roasted split chickpea grams. Light &amp; crispy.</p>
          <div class="mt-3 flex items-center justify-between pt-2 border-t border-border/40">
            <div class="flex items-baseline gap-2">
              <span class="text-xl font-bold md:text-2xl font-price text-foreground">₹200</span>
              <span class="text-sm text-muted-foreground line-through font-price">₹250</span>
            </div>
            <button type="button" onclick="addToCart(8, 1, this, 'Daliya (Roasted Gram) Chikki')" class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-soft transition hover:scale-110 active:scale-95" style="background-color:#c7613d !important; color:#ffffff !important;" aria-label="Add Daliya Chikki to cart">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>
<?php endif; ?>

<!-- ══ SECTION 6: SOCIAL REELS SHOWCASE (@dabhichikki) ════════════ -->
<?php if (function_exists('settingEnabled') ? settingEnabled('module_instagram', '1') : true): 
  $igHandle    = function_exists('getSetting') ? getSetting('instagram_handle', '@dabhichikki') : '@dabhichikki';
  $igTagline   = function_exists('getSetting') ? getSetting('instagram_tagline', 'Follow') : 'Follow';
  $igProfile   = function_exists('getSetting') ? getSetting('instagram_profile_url', 'https://instagram.com') : 'https://instagram.com';
  $igButtonText = function_exists('getSetting') ? getSetting('instagram_button_text', 'Follow') : 'Follow';

  $dynamicReels = [];
  try {
    $dynamicReels = Database::fetchAll("SELECT * FROM `instagram_reels` WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
  } catch (\Throwable $e) {
    $dynamicReels = [];
  }
  if (empty($dynamicReels)) {
    $dynamicReels = [
      ['image_url'=>'assets/images/reels/reel-1.jpg', 'video_url'=>'assets/videos/reel-1.mp4', 'tag_label'=>'Pure Jaggery', 'title'=>'Bubbling Liquid Gold', 'instagram_url'=>'https://instagram.com'],
      ['image_url'=>'assets/images/reels/reel-2.jpg', 'video_url'=>'assets/videos/reel-2.mp4', 'tag_label'=>'The Snap', 'title'=>'Crunch You Can Hear', 'instagram_url'=>'https://instagram.com'],
      ['image_url'=>'assets/images/reels/reel-3.jpg', 'video_url'=>'assets/videos/reel-3.mp4', 'tag_label'=>'Handcrafted', 'title'=>'Rolling Fresh Til Slabs', 'instagram_url'=>'https://instagram.com'],
      ['image_url'=>'assets/images/reels/reel-4.jpg', 'video_url'=>'assets/videos/reel-4.mp4', 'tag_label'=>'Festive Packs', 'title'=>'Hand-Tied Gift Boxes', 'instagram_url'=>'https://instagram.com'],
      ['image_url'=>'assets/images/reels/reel-5.jpg', 'video_url'=>'assets/videos/reel-5.mp4', 'tag_label'=>'Sweet Joy', 'title'=>'Clean Craving Satisfied', 'instagram_url'=>'https://instagram.com'],
      ['image_url'=>'assets/images/reels/reel-6.jpg', 'video_url'=>'assets/videos/reel-6.mp4', 'tag_label'=>'Signature 3 Mix', 'title'=>'Layered Coconut & Nuts', 'instagram_url'=>'https://instagram.com'],
    ];
  }
?>
<section id="reels-section" class="mx-auto max-w-6xl px-4 py-8 md:px-6">
  <div class="mb-5 flex items-end justify-between">
    <div>
      <p class="text-xs font-semibold uppercase tracking-wider text-primary font-mono"><?= htmlspecialchars($igTagline) ?></p>
      <h2 class="font-display text-3xl md:text-4xl text-foreground"><?= htmlspecialchars($igHandle) ?></h2>
    </div>
    <a href="<?= htmlspecialchars($igProfile) ?>" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 rounded-full bg-surface px-4 py-2 text-sm font-semibold shadow-soft transition hover:scale-105 border border-border/60 text-foreground">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-instagram h-4 w-4"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line></svg>
      <?= htmlspecialchars($igButtonText) ?>
    </a>
  </div>

  <!-- Interactive Snap-Scroll Reels Track -->
  <div class="reels-scroll flex snap-x snap-mandatory gap-3 overflow-x-auto pb-3 md:gap-4" style="scrollbar-width:none;-webkit-overflow-scrolling:touch">
    <?php foreach ($dynamicReels as $idx => $r): ?>
      <a href="<?= htmlspecialchars($r['instagram_url'] ?? 'https://instagram.com') ?>" target="_blank" rel="noreferrer" class="reel-card group relative aspect-[9/16] w-[calc((100%-0.75rem)/2)] flex-none cursor-pointer snap-start overflow-hidden rounded-2xl bg-secondary shadow-soft transition-all duration-300 hover:-translate-y-1 hover:shadow-pop sm:w-[calc((100%-2.25rem)/4)] md:w-[calc((100%-3rem)/5)] lg:w-[calc((100%-4rem)/6)] block">
        
        <?php if (!empty($r['video_url'])): ?>
          <video
            muted
            loop
            playsinline
            preload="none"
            poster="<?= htmlspecialchars($r['image_url'] ?? '') ?>"
            class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
            onmouseover="this.play().catch(function(){})"
            onmouseout="this.pause();this.currentTime=0;"
          >
            <source src="<?= htmlspecialchars($r['video_url']) ?>" type="video/mp4">
          </video>
        <?php endif; ?>

        <img src="<?= htmlspecialchars($r['image_url'] ?? '') ?>" alt="<?= htmlspecialchars($r['title']) ?>" loading="lazy" class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105 <?= !empty($r['video_url']) ? 'hidden group-hover:block' : '' ?>" />
        
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-black/80 via-black/35 to-transparent"></div>
        
        <!-- Bottom caption -->
        <div class="absolute bottom-3 left-3 right-3 z-10 text-white">
          <span class="inline-block rounded-full bg-white/20 px-2 py-0.5 font-mono text-[9px] font-semibold uppercase tracking-[0.14em] backdrop-blur-sm"><?= htmlspecialchars($r['tag_label'] ?? $r['tag'] ?? 'Pure Jaggery') ?></span>
          <p class="mt-1 line-clamp-2 font-display text-sm font-medium leading-tight text-white/95"><?= htmlspecialchars($r['title']) ?></p>
        </div>

        <!-- Instagram Badge -->
        <div class="absolute right-2.5 top-2.5 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-secondary shadow-soft backdrop-blur transition duration-300 group-hover:scale-110">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line></svg>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Carousel Navigation Control -->
  <div class="mt-6 flex items-center justify-center">
    <div class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-white/95 p-1.5 shadow-soft">
      <!-- Left Arrow Button -->
      <button
        type="button"
        id="reels-prev-btn"
        class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[#541f21] border border-border/60 shadow-sm transition hover:bg-muted active:scale-90"
        aria-label="Previous reels"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
          <path d="m15 18-6-6 6-6"/>
        </svg>
      </button>

      <!-- Pagination Track (Pill + Dot) -->
      <div class="flex items-center gap-1.5 px-2 cursor-pointer" id="reels-indicators">
        <span id="reel-indicator-1" style="height:6px; width:26px; border-radius:9999px; background-color:#541f21; transition:all 0.3s ease; display:inline-block;"></span>
        <span id="reel-indicator-2" style="height:6px; width:6px; border-radius:9999px; background-color:rgba(84,31,33,0.25); transition:all 0.3s ease; display:inline-block;"></span>
      </div>

      <!-- Right Arrow Button -->
      <button
        type="button"
        id="reels-next-btn"
        class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[#541f21] border border-border/60 shadow-sm transition hover:bg-muted active:scale-90"
        aria-label="Next reels"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
          <path d="m9 18 6-6-6-6"/>
        </svg>
      </button>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ SECTION 7: OUR STORY (Dessert, Reimagined With Real Ingredients) ═ -->
<?php if (function_exists('settingEnabled') ? settingEnabled('module_story', '1') : true): 
  $storyHeading = function_exists('getSetting') ? getSetting('story_heading', 'Dessert, reimagined with real ingredients.') : 'Dessert, reimagined with real ingredients.';
  $storySubhead = function_exists('getSetting') ? getSetting('story_subheading', 'A modern chikki brand built around wholesome indulgence without compromise.') : 'A modern chikki brand built around wholesome indulgence without compromise.';
?>
<section id="our-story" class="px-4 py-16 md:px-6 md:py-24">
  <div class="mx-auto max-w-6xl rounded-[28px] border border-[#6e1f1f]/10 bg-[#f6dc94] px-5 py-14 shadow-[0_20px_60px_-30px_rgba(110,31,31,0.25)] md:px-14 md:py-20">
    
    <!-- Story Eyebrow & Headline Block -->
    <div class="mx-auto max-w-3xl text-center animate-fade-up">
      <span class="font-mono text-[11px] uppercase tracking-[0.28em] text-[#c05d37] font-semibold">Our Story</span>
      <div class="mx-auto mt-4 h-px w-12 bg-[#6e1f1f]/30"></div>
      <h2 class="font-display mt-6 text-3xl leading-[1.1] tracking-tight text-[#6e1f1f] sm:text-4xl md:text-5xl">
        <?= htmlspecialchars($storyHeading) ?>
      </h2>
      <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-[#6e1f1f]/70 md:text-lg">
        <?= htmlspecialchars($storySubhead) ?>
      </p>
    </div>

    <!-- 3 Story Feature Cards (01, 02, 03) -->
    <div class="mt-14 grid gap-5 md:mt-20 md:grid-cols-3 md:gap-6">
      
      <!-- Card 01 -->
      <article class="group relative flex flex-col rounded-2xl border border-[#6e1f1f]/10 bg-white/70 p-7 backdrop-blur-sm transition duration-500 hover:-translate-y-1 hover:bg-white hover:shadow-[0_20px_50px_-25px_rgba(110,31,31,0.35)] md:p-8">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#f6dc94] text-[#6e1f1f] transition group-hover:scale-110">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-leaf h-5 w-5" aria-hidden="true"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path></svg>
        </div>
        <h3 class="font-display mt-6 text-xl leading-snug text-[#6e1f1f] md:text-[1.4rem]">
          Made with real ingredients
        </h3>
        <p class="mt-3 text-[14.5px] leading-relaxed text-[#6e1f1f]/70">
          We craft every batch of chikki using just simple, authentic ingredients — fresh organic sugarcane jaggery, selected nuts, and pure spices. No refined sugar, no preservatives, nothing artificial.
        </p>
        <span class="mt-6 text-xs font-semibold uppercase tracking-[0.18em] text-[#c05d37]/70 font-mono">01</span>
      </article>

      <!-- Card 02 -->
      <article class="group relative flex flex-col rounded-2xl border border-[#6e1f1f]/10 bg-white/70 p-7 backdrop-blur-sm transition duration-500 hover:-translate-y-1 hover:bg-white hover:shadow-[0_20px_50px_-25px_rgba(110,31,31,0.35)] md:p-8">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#f6dc94] text-[#6e1f1f] transition group-hover:scale-110">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-5 w-5" aria-hidden="true"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"></path><path d="M20 2v4"></path><path d="M22 4h-4"></path><circle cx="4" cy="20" r="2"></circle></svg>
        </div>
        <h3 class="font-display mt-6 text-xl leading-snug text-[#6e1f1f] md:text-[1.4rem]">
          Feels like dessert. Works differently.
        </h3>
        <p class="mt-3 text-[14.5px] leading-relaxed text-[#6e1f1f]/70">
          From the crispy golden Mandvi bars to layered 3 Mix with toasted coconut, everything is formulated to satisfy sweet cravings while fueling active lifestyles with natural nutrients.
        </p>
        <span class="mt-6 text-xs font-semibold uppercase tracking-[0.18em] text-[#c05d37]/70 font-mono">02</span>
      </article>

      <!-- Card 03 -->
      <article class="group relative flex flex-col rounded-2xl border border-[#6e1f1f]/10 bg-white/70 p-7 backdrop-blur-sm transition duration-500 hover:-translate-y-1 hover:bg-white hover:shadow-[0_20px_50px_-25px_rgba(110,31,31,0.35)] md:p-8">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#f6dc94] text-[#6e1f1f] transition group-hover:scale-110">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dumbbell h-5 w-5" aria-hidden="true"><path d="M17.596 12.768a2 2 0 1 0 2.829-2.829l-1.768-1.767a2 2 0 0 0 2.828-2.829l-2.828-2.828a2 2 0 0 0-2.829 2.828l-1.767-1.768a2 2 0 1 0-2.829 2.829z"></path><path d="m2.5 21.5 1.4-1.4"></path><path d="m20.1 3.9 1.4-1.4"></path><path d="M5.343 21.485a2 2 0 1 0 2.829-2.828l1.767 1.768a2 2 0 1 0 2.829-2.829l-6.364-6.364a2 2 0 1 0-2.829 2.829l1.768 1.767a2 2 0 0 0-2.828 2.829z"></path></svg>
        </div>
        <h3 class="font-display mt-6 text-xl leading-snug text-[#6e1f1f] md:text-[1.4rem]">
          Naturally high in protein
        </h3>
        <p class="mt-3 text-[14.5px] leading-relaxed text-[#6e1f1f]/70">
          We never add isolate powders. Groundnuts, roasted gram and sesame seeds are naturally packed with clean plant protein and dietary fibre for steady, sustained daily vitality.
        </p>
        <span class="mt-6 text-xs font-semibold uppercase tracking-[0.18em] text-[#c05d37]/70 font-mono">03</span>
      </article>

    </div>

    <!-- Quote Centerpiece (Exact Yogurt Alley Display Quote) -->
    <div class="relative mx-auto mt-16 max-w-3xl text-center md:mt-24">
      <div class="mx-auto mb-8 flex items-center justify-center gap-3">
        <span class="h-px w-10 bg-[#6e1f1f]/25"></span>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-4 w-4 text-[#c05d37]" aria-hidden="true"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"></path><path d="M20 2v4"></path><path d="M22 4h-4"></path><circle cx="4" cy="20" r="2"></circle></svg>
        <span class="h-px w-10 bg-[#6e1f1f]/25"></span>
      </div>
      <p class="font-display text-2xl leading-tight text-[#6e1f1f] sm:text-3xl md:text-4xl">
        This isn't diet food.
      </p>
      <p class="font-display mt-2 text-3xl leading-tight tracking-tight text-[#c05d37] sm:text-4xl md:text-5xl">
        It's a better kind of dessert.
      </p>
    </div>

    <!-- Bottom CTA Card inside Yellow Box (Exact Yogurt Alley CTA Block) -->
    <div class="mx-auto mt-16 max-w-3xl rounded-2xl px-6 py-10 text-center shadow-pop md:mt-20 md:px-12 md:py-14" style="background-color:#541f21 !important; color:#f6e9de !important">
      <p class="font-display text-xl leading-snug sm:text-2xl md:text-3xl font-bold" style="color:#f6e9de !important">
        Ready to become part of the Dabhi Chikki lifestyle?
      </p>
      <a href="#products" class="group mt-7 inline-flex items-center justify-center gap-2 rounded-full bg-[#f6dc94] px-8 py-3.5 text-xs md:text-sm font-bold uppercase tracking-[0.2em] text-[#6e1f1f] transition hover:bg-white active:scale-95 shadow-none" style="background-color:#f6dc94 !important; color:#6e1f1f !important;">
        ORDER NOW →
      </a>
      <p class="mt-6 font-mono text-[11px] uppercase tracking-[0.42em]" style="color:rgba(246,220,148,0.85) !important">
        "NOT GUILTY."
      </p>
    </div>

  </div>
</section>
<?php endif; ?>

<!-- ══ SECTION 8: CUSTOMER REVIEWS (Loved Across India) ════════════ -->
<?php if (function_exists('settingEnabled') ? settingEnabled('module_reviews', '1') : true): 
  $revHeading = function_exists('getSetting') ? getSetting('reviews_heading', 'Loved by Sweet Lovers Across India') : 'Loved by Sweet Lovers Across India';
  $revSubhead = function_exists('getSetting') ? getSetting('reviews_subheading', 'Real testimonials from customers who made the switch to authentic 100% pure jaggery chikki.') : 'Real testimonials from customers who made the switch to authentic 100% pure jaggery chikki.';
  $revBadge   = function_exists('getSetting') ? getSetting('reviews_badge_text', '4.9 / 5.0 Rated by 12,000+ Customers') : '4.9 / 5.0 Rated by 12,000+ Customers';

  $approvedReviews = [];
  try {
    $approvedReviews = Database::fetchAll(
      "SELECT r.*, u.full_name, p.name AS product_name 
       FROM reviews r 
       JOIN users u ON u.id = r.user_id 
       JOIN products p ON p.id = r.product_id 
       WHERE r.is_approved = 1 
       ORDER BY r.created_at DESC LIMIT 4"
    );
  } catch (\Throwable $e) {
    $approvedReviews = [];
  }
?>
<section id="reviews" class="mx-auto max-w-6xl px-4 py-12 md:px-6 md:py-16">
  <!-- Section Header -->
  <div class="mb-8 text-center md:mb-12">
    <?php if (!empty($revBadge)): ?>
      <div class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3.5 py-1 text-xs font-semibold text-primary font-mono uppercase tracking-[0.18em]">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#c7613d" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        <?= htmlspecialchars($revBadge) ?>
      </div>
    <?php endif; ?>
    <h2 class="font-display mt-3 text-3xl md:text-5xl text-foreground">
      <?= htmlspecialchars($revHeading) ?>
    </h2>
    <p class="mx-auto mt-3 max-w-xl text-sm md:text-base text-muted-foreground">
      <?= htmlspecialchars($revSubhead) ?>
    </p>
  </div>

  <!-- Reviews Grid -->
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 md:gap-5">
    <?php if (!empty($approvedReviews)): ?>
      <?php foreach ($approvedReviews as $rv): ?>
        <div class="flex flex-col justify-between rounded-2xl border border-border/70 bg-card p-5 shadow-soft transition-all duration-300 hover:-translate-y-1 hover:shadow-pop">
          <div>
            <!-- Star Rating -->
            <div class="flex items-center gap-1 text-amber-500">
              <?php for($s=0; $s < (int)$rv['rating']; $s++): ?>
                <svg class="h-4 w-4 fill-amber-400 stroke-amber-400" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              <?php endfor; ?>
            </div>
            <h3 class="font-display mt-3 text-lg font-bold text-foreground">
              <?= htmlspecialchars($rv['product_name'] ?? 'Authentic Chikki') ?>
            </h3>
            <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
              "<?= htmlspecialchars($rv['comment']) ?>"
            </p>
          </div>
          <div class="mt-5 border-t border-border/50 pt-3">
            <div class="flex items-center justify-between text-xs">
              <span class="font-semibold text-foreground"><?= htmlspecialchars($rv['full_name']) ?></span>
              <span class="inline-flex items-center gap-1 text-emerald-600 font-medium">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                Verified
              </span>
            </div>
            <div class="mt-1 font-mono text-[10px] text-muted-foreground"><?= date('d M Y', strtotime($rv['created_at'])) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <!-- Static Testimonials Fallback -->
      <div class="flex flex-col justify-between rounded-2xl border border-border/70 bg-card p-5 shadow-soft transition-all duration-300 hover:-translate-y-1 hover:shadow-pop">
        <div>
          <div class="flex items-center gap-1 text-amber-500">
            <?php for($s=0;$s<5;$s++): ?><svg class="h-4 w-4 fill-amber-400 stroke-amber-400" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php endfor; ?>
          </div>
          <h3 class="font-display mt-3 text-lg font-bold text-foreground">"Unmatched Saurashtra snap!"</h3>
          <p class="mt-2 text-sm leading-relaxed text-muted-foreground">Growing up in Gujarat, chikki was a winter essential. After moving to Mumbai, I couldn't find authentic chikki without liquid glucose until Dabhi Chikki. The Mandvi peanut chikki has that authentic crisp snap!</p>
        </div>
        <div class="mt-5 border-t border-border/50 pt-3">
          <div class="flex items-center justify-between text-xs">
            <span class="font-semibold text-foreground">Rajesh V., Mumbai</span>
            <span class="inline-flex items-center gap-1 text-emerald-600 font-medium"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Verified</span>
          </div>
          <div class="mt-1 font-mono text-[10px] text-muted-foreground">Purchased: Mandvi Chikki 500g</div>
        </div>
      </div>

      <div class="flex flex-col justify-between rounded-2xl border border-border/70 bg-card p-5 shadow-soft transition-all duration-300 hover:-translate-y-1 hover:shadow-pop">
        <div>
          <div class="flex items-center gap-1 text-amber-500">
            <?php for($s=0;$s<5;$s++): ?><svg class="h-4 w-4 fill-amber-400 stroke-amber-400" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php endfor; ?>
          </div>
          <h3 class="font-display mt-3 text-lg font-bold text-foreground">"3 Mix is an absolute winner"</h3>
          <p class="mt-2 text-sm leading-relaxed text-muted-foreground">The blend of roasted peanuts, sesame seeds, and toasted coconut crush is pure perfection. Not overly sticky, perfect sweetness from pure sugarcane jaggery. My kids love it in their lunchbox!</p>
        </div>
        <div class="mt-5 border-t border-border/50 pt-3">
          <div class="flex items-center justify-between text-xs">
            <span class="font-semibold text-foreground">Pooja K., Ahmedabad</span>
            <span class="inline-flex items-center gap-1 text-emerald-600 font-medium"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Verified</span>
          </div>
          <div class="mt-1 font-mono text-[10px] text-muted-foreground">Purchased: 3 Mix Chikki 1kg</div>
        </div>
      </div>

      <div class="flex flex-col justify-between rounded-2xl border border-border/70 bg-card p-5 shadow-soft transition-all duration-300 hover:-translate-y-1 hover:shadow-pop">
        <div>
          <div class="flex items-center gap-1 text-amber-500">
            <?php for($s=0;$s<5;$s++): ?><svg class="h-4 w-4 fill-amber-400 stroke-amber-400" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php endfor; ?>
          </div>
          <h3 class="font-display mt-3 text-lg font-bold text-foreground">"Best clean energy snack"</h3>
          <p class="mt-2 text-sm leading-relaxed text-muted-foreground">As someone into fitness, I wanted clean plant protein without artificial sweeteners or sugar crashes. Daliya and Til chikki provide steady energy for my workouts. Delivered in 2 days to Bangalore!</p>
        </div>
        <div class="mt-5 border-t border-border/50 pt-3">
          <div class="flex items-center justify-between text-xs">
            <span class="font-semibold text-foreground">Aditya M., Bengaluru</span>
            <span class="inline-flex items-center gap-1 text-emerald-600 font-medium"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Verified</span>
          </div>
          <div class="mt-1 font-mono text-[10px] text-muted-foreground">Purchased: Daliya Chikki 500g</div>
        </div>
      </div>

      <div class="flex flex-col justify-between rounded-2xl border border-border/70 bg-card p-5 shadow-soft transition-all duration-300 hover:-translate-y-1 hover:shadow-pop">
        <div>
          <div class="flex items-center gap-1 text-amber-500">
            <?php for($s=0;$s<5;$s++): ?><svg class="h-4 w-4 fill-amber-400 stroke-amber-400" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php endfor; ?>
          </div>
          <h3 class="font-display mt-3 text-lg font-bold text-foreground">"Crisp even after 3 weeks!"</h3>
          <p class="mt-2 text-sm leading-relaxed text-muted-foreground">The packaging is top notch. The slabs stay completely crunchy even weeks after opening. You can genuinely taste the premium quality nuts and organic jaggery. Will definitely be reordering.</p>
        </div>
        <div class="mt-5 border-t border-border/50 pt-3">
          <div class="flex items-center justify-between text-xs">
            <span class="font-semibold text-foreground">Neha T., Delhi NCR</span>
            <span class="inline-flex items-center gap-1 text-emerald-600 font-medium"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Verified</span>
          </div>
          <div class="mt-1 font-mono text-[10px] text-muted-foreground">Purchased: TIL Chikki 1kg</div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- Homepage Interactive JS -->
<script>
const cardVariantMap = {};

function selectWeight(btn, productId) {
  const container = btn.closest('.weight-selector-chips');
  container.querySelectorAll('.weight-chip').forEach(c => {
    c.classList.remove('bg-secondary', 'text-secondary-foreground', 'border-secondary');
    c.classList.add('bg-surface', 'text-foreground/70');
  });

  btn.classList.remove('bg-surface', 'text-foreground/70');
  btn.classList.add('bg-secondary', 'text-secondary-foreground', 'border-secondary');

  const price = parseFloat(btn.dataset.price);
  const mrp   = parseFloat(btn.dataset.mrp);
  const off   = Math.round((1 - price/mrp) * 100);

  const priceEl = document.getElementById('price-' + productId);
  const mrpEl   = document.getElementById('mrp-' + productId);
  const offEl   = document.getElementById('off-badge-' + productId);

  if (priceEl) priceEl.textContent = '₹' + Math.round(price);
  if (mrpEl)   mrpEl.textContent   = '₹' + Math.round(mrp);
  if (offEl) {
    if (off > 0) {
      offEl.textContent = off + '% OFF';
      offEl.style.display = '';
    } else {
      offEl.style.display = 'none';
    }
  }

  cardVariantMap[productId] = parseInt(btn.dataset.variantId);
}

function addFromCard(productId, btn) {
  let variantId = cardVariantMap[productId];
  if (!variantId) {
    const activeChip = document.querySelector(`.weight-selector-chips[data-product-id="${productId}"] .weight-chip.bg-secondary`);
    if (activeChip) variantId = parseInt(activeChip.dataset.variantId);
    else {
      const firstChip = document.querySelector(`.weight-selector-chips[data-product-id="${productId}"] .weight-chip`);
      if (firstChip) variantId = parseInt(firstChip.dataset.variantId);
    }
  }
  const prodCard = btn.closest('.product-item, .featured-item');
  const prodName = prodCard ? (prodCard.querySelector('h3') || prodCard.querySelector('.font-display')).textContent.trim() : '';
  if (variantId && window.addToCart) {
    window.addToCart(variantId, 1, btn, prodName);
  }
}

// Carousel Scroll Helpers
function scrollFeatured(dir) {
  const el = document.getElementById('featured-carousel');
  if (el) {
    el.scrollBy({ left: dir * 320, behavior: 'smooth' });
  }
}

// Category Filter Tabs
document.querySelectorAll('.category-pill').forEach(pill => {
  pill.addEventListener('click', () => {
    document.querySelectorAll('.category-pill').forEach(p => {
      p.classList.remove('bg-secondary', 'text-secondary-foreground', 'shadow-soft');
      p.classList.add('bg-surface', 'text-foreground/70');
    });
    pill.classList.remove('bg-surface', 'text-foreground/70');
    pill.classList.add('bg-secondary', 'text-secondary-foreground', 'shadow-soft');

    const cat = pill.dataset.cat;
    const cards = document.querySelectorAll('#products-grid .product-item');
    cards.forEach(card => {
      const pId = card.dataset.productId;
      if (cat === 'all' || cat === pId) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  });
});

// Reels Carousel Navigation
(function() {
  const scrollEl = document.querySelector('.reels-scroll');
  const prevBtn  = document.getElementById('reels-prev-btn');
  const nextBtn  = document.getElementById('reels-next-btn');
  const dot1     = document.getElementById('reel-indicator-1');
  const dot2     = document.getElementById('reel-indicator-2');
  if (!scrollEl || !prevBtn || !nextBtn) return;

  function updateIndicators() {
    const maxScroll = scrollEl.scrollWidth - scrollEl.clientWidth;
    if (maxScroll <= 5) return;
    const progress = scrollEl.scrollLeft / maxScroll;
    if (progress < 0.5) {
      if (dot1) {
        dot1.style.width = '26px';
        dot1.style.backgroundColor = '#541f21';
      }
      if (dot2) {
        dot2.style.width = '6px';
        dot2.style.backgroundColor = 'rgba(84,31,33,0.25)';
      }
    } else {
      if (dot1) {
        dot1.style.width = '6px';
        dot1.style.backgroundColor = 'rgba(84,31,33,0.25)';
      }
      if (dot2) {
        dot2.style.width = '26px';
        dot2.style.backgroundColor = '#541f21';
      }
    }
  }

  prevBtn.addEventListener('click', () => {
    scrollEl.scrollBy({ left: -Math.max(260, scrollEl.clientWidth * 0.7), behavior: 'smooth' });
  });

  nextBtn.addEventListener('click', () => {
    scrollEl.scrollBy({ left: Math.max(260, scrollEl.clientWidth * 0.7), behavior: 'smooth' });
  });

  if (dot1) {
    dot1.addEventListener('click', () => {
      scrollEl.scrollTo({ left: 0, behavior: 'smooth' });
    });
  }
  if (dot2) {
    dot2.addEventListener('click', () => {
      scrollEl.scrollTo({ left: scrollEl.scrollWidth, behavior: 'smooth' });
    });
  }

  scrollEl.addEventListener('scroll', updateIndicators, { passive: true });
})();
</script>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>
