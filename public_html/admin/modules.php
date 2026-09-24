<?php
/**
 * admin/modules.php
 * Storefront Modules & Content Manager (Review and other module ON/OFF & Content Customizer)
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('admin', 'superadmin');

$activePage = 'modules';
$pageTitle = 'Storefront Modules & Content Manager';
$pageHeading = 'Modules & Content Manager';
$pageBreadcrumbs = [
    ['label' => 'Settings', 'url' => '/admin/settings.php'],
    ['label' => 'Modules & Content']
];

// Handle Form Submissions
if (isPost()) {
    csrfVerify();
    $action = post('action');

    if ($action === 'save_all_modules' || $action === 'save_single_module') {
        // 1. Customer Reviews Module
        if (isset($_POST['has_reviews_module'])) {
            setSetting('module_reviews', post('module_reviews') ? '1' : '0');
            setSetting('reviews_allow_submission', post('reviews_allow_submission') ? '1' : '0');
            setSetting('reviews_heading', trim(post('reviews_heading', 'Loved by Sweet Lovers Across India')));
            setSetting('reviews_subheading', trim(post('reviews_subheading', 'Real testimonials from customers who made the switch to authentic pure jaggery chikki.')));
            setSetting('reviews_badge_text', trim(post('reviews_badge_text', '4.9 / 5.0 Rated by 12,000+ Customers')));
        }

        // 2. Instagram Showcase Module
        if (isset($_POST['has_instagram_module'])) {
            setSetting('module_instagram', post('module_instagram') ? '1' : '0');
            setSetting('instagram_handle', trim(post('instagram_handle', '@dabhichikki')));
            setSetting('instagram_tagline', trim(post('instagram_tagline', 'Follow our craft')));
            setSetting('instagram_profile_url', trim(post('instagram_profile_url', 'https://instagram.com')));
            setSetting('instagram_button_text', trim(post('instagram_button_text', 'Follow')));
        }

        // 3. Announcement Top Bar Module
        if (isset($_POST['has_announcement_module'])) {
            setSetting('module_announcement', post('module_announcement') ? '1' : '0');
            setSetting('announcement_text', trim(post('announcement_text', '🎉 FREE shipping on orders above ₹499 | Authentic Rajkot Jaggery Chikki')));
            setSetting('announcement_badge', trim(post('announcement_badge', 'Special Offer')));
            setSetting('announcement_link', trim(post('announcement_link', '#products')));
        }

        // 4. Welcome / Promotional Popup
        if (isset($_POST['has_popup_module'])) {
            setSetting('module_welcome_popup', post('module_welcome_popup') ? '1' : '0');
            setSetting('popup_headline', trim(post('popup_headline', 'Get 10% Off Your First Order')));
            setSetting('popup_subtext', trim(post('popup_subtext', 'Handcrafted pure jaggery chikki delivered fresh from Rajkot.')));
            setSetting('popup_coupon_code', trim(post('popup_coupon_code', 'FIRST10')));
        }

        // 5. Brand Story Section
        if (isset($_POST['has_story_module'])) {
            setSetting('module_story', post('module_story') ? '1' : '0');
            setSetting('story_heading', trim(post('story_heading', 'Dessert, reimagined with real ingredients.')));
            setSetting('story_subheading', trim(post('story_subheading', 'A modern chikki brand built around wholesome indulgence without compromise.')));
        }

        // 6. Featured Products Module
        if (isset($_POST['has_featured_module'])) {
            setSetting('module_featured', post('module_featured') ? '1' : '0');
            setSetting('featured_heading', trim(post('featured_heading', 'Featured This Season')));
            setSetting('featured_subheading', trim(post('featured_subheading', 'Small batch, freshly rolled chikki delivered to your doorstep.')));
        }

        // 7. Pincode Estimator Module
        if (isset($_POST['has_pincode_module'])) {
            setSetting('module_pincode_checker', post('module_pincode_checker') ? '1' : '0');
            setSetting('pincode_helper_text', trim(post('pincode_helper_text', 'Enter 6-digit pincode for delivery estimate')));
        }

        // 8. Cash On Delivery (COD) Module
        if (isset($_POST['has_cod_module'])) {
            setSetting('cod_enabled', post('cod_enabled') ? '1' : '0');
        }

        // 9. GST / Tax Breakdown Module
        if (isset($_POST['has_gst_module'])) {
            setSetting('module_gst_breakdown', post('module_gst_breakdown') ? '1' : '0');
            setSetting('gst_enabled', post('gst_enabled') ? '1' : '0');
        }

        // 10. Product Detail Page Badges & Capsules Module
        if (isset($_POST['has_product_badges_module'])) {
            setSetting('module_product_badges', post('module_product_badges') ? '1' : '0');
            setSetting('product_badges_list', trim(post('product_badges_list', '100% PURE JAGGERY, ZERO REFINED SUGAR, ROASTED NUTS, HIGH PROTEIN, HANDCRAFTED')));
            setSetting('product_supertitle_enabled', post('product_supertitle_enabled') ? '1' : '0');
            setSetting('product_supertitle_text', trim(post('product_supertitle_text', 'CHIKKI')));
            setSetting('product_rating_enabled', post('product_rating_enabled') ? '1' : '0');
        }

        // 11. Homepage Card Badges & Ratings Module
        if (isset($_POST['has_card_badges_module'])) {
            setSetting('module_card_badges', post('module_card_badges') ? '1' : '0');
            setSetting('card_badges_list', trim(post('card_badges_list', 'Pure Jaggery, 100% Natural')));
            setSetting('module_card_ratings', post('module_card_ratings') ? '1' : '0');
            setSetting('card_rating_fallback_enabled', post('card_rating_fallback_enabled') ? '1' : '0');
            setSetting('card_default_rating', trim(post('card_default_rating', '4.7')));
            setSetting('card_default_reviews', trim(post('card_default_reviews', '3')));
        }

        // 12. Tax & HSN Pricing Note Module
        if (isset($_POST['has_tax_note_module'])) {
            setSetting('module_tax_note', post('module_tax_note') ? '1' : '0');
            setSetting('tax_note_text', trim(post('tax_note_text', 'Inclusive of all taxes (GST {rate}% included)')));
            setSetting('tax_hsn_enabled', post('tax_hsn_enabled') ? '1' : '0');
        }

        // 13. Live Inventory & Stock Indicator Module
        if (isset($_POST['has_stock_badge_module'])) {
            setSetting('module_stock_badge', post('module_stock_badge') ? '1' : '0');
            setSetting('stock_in_stock_text', trim(post('stock_in_stock_text', 'In Stock (Fresh Batch Ready to Ship)')));
            setSetting('stock_low_stock_text', trim(post('stock_low_stock_text', '⚠️ Only {qty} left in stock — order soon!')));
            setSetting('stock_out_of_stock_text', trim(post('stock_out_of_stock_text', '❌ Currently Out of Stock')));
        }

        clearSettingCache();
        flashSet('success', 'Modules & storefront contents updated successfully.');
        redirect('/admin/modules.php');
    }
}

// Current values
$mReviews       = settingEnabled('module_reviews', '1');
$mReviewsSub    = settingEnabled('reviews_allow_submission', '1');
$reviewsHeading = getSetting('reviews_heading', 'Loved by Sweet Lovers Across India');
$reviewsSubhead = getSetting('reviews_subheading', 'Real testimonials from customers who made the switch to authentic 100% pure jaggery chikki.');
$reviewsBadge   = getSetting('reviews_badge_text', '4.9 / 5.0 Rated by 12,000+ Customers');

$mInstagram     = settingEnabled('module_instagram', '1');
$igHandle       = getSetting('instagram_handle', '@dabhichikki');
$igTagline      = getSetting('instagram_tagline', 'Follow our craft');
$igProfile      = getSetting('instagram_profile_url', 'https://instagram.com');
$igBtnText      = getSetting('instagram_button_text', 'Follow');

$mAnnouncement  = settingEnabled('module_announcement', '1');
$annText        = getSetting('announcement_text', '🎉 FREE shipping on orders above ₹499 | Authentic Rajkot Jaggery Chikki');
$annBadge       = getSetting('announcement_badge', 'Special Offer');
$annLink        = getSetting('announcement_link', '#products');

$mPopup         = settingEnabled('module_welcome_popup', '1');
$popupHead      = getSetting('popup_headline', 'Get 10% Off Your First Order');
$popupSub       = getSetting('popup_subtext', 'Handcrafted pure jaggery chikki delivered fresh from Rajkot.');
$popupCoupon    = getSetting('popup_coupon_code', 'FIRST10');

$mStory         = settingEnabled('module_story', '1');
$storyHead      = getSetting('story_heading', 'Dessert, reimagined with real ingredients.');
$storySub       = getSetting('story_subheading', 'A modern chikki brand built around wholesome indulgence without compromise.');

$mFeatured      = settingEnabled('module_featured', '1');
$featHead       = getSetting('featured_heading', 'Featured This Season');
$featSub        = getSetting('featured_subheading', 'Small batch, freshly rolled chikki delivered to your doorstep.');

$mPincode       = settingEnabled('module_pincode_checker', '1');
$pincodeHelp    = getSetting('pincode_helper_text', 'Enter 6-digit pincode for delivery estimate');

$mCod           = settingEnabled('cod_enabled', '1');
$mGstBreakdown  = settingEnabled('module_gst_breakdown', '1');
$mGstMaster     = settingEnabled('gst_enabled', '1');

// Modules 10-13 Values
$mProdBadges     = settingEnabled('module_product_badges', '1');
$prodBadgesList  = getSetting('product_badges_list', '100% PURE JAGGERY, ZERO REFINED SUGAR, ROASTED NUTS, HIGH PROTEIN, HANDCRAFTED');
$mProdSuper      = settingEnabled('product_supertitle_enabled', '1');
$prodSuperText   = getSetting('product_supertitle_text', 'CHIKKI');
$mProdRating     = settingEnabled('product_rating_enabled', '1');

$mCardBadges     = settingEnabled('module_card_badges', '1');
$cardBadgesList  = getSetting('card_badges_list', 'Pure Jaggery, 100% Natural');
$mCardRatings    = settingEnabled('module_card_ratings', '1');
$mCardRatingFall = settingEnabled('card_rating_fallback_enabled', '1');
$cardDefRating   = getSetting('card_default_rating', '4.7');
$cardDefReviews  = getSetting('card_default_reviews', '3');

$mTaxNote        = settingEnabled('module_tax_note', '1');
$taxNoteText     = getSetting('tax_note_text', 'Inclusive of all taxes (GST {rate}% included)');
$mTaxHsn         = settingEnabled('tax_hsn_enabled', '1');

$mStockBadge     = settingEnabled('module_stock_badge', '1');
$stockInStock    = getSetting('stock_in_stock_text', 'In Stock (Fresh Batch Ready to Ship)');
$stockLowStock   = getSetting('stock_low_stock_text', '⚠️ Only {qty} left in stock — order soon!');
$stockOutOfStock = getSetting('stock_out_of_stock_text', '❌ Currently Out of Stock');

// Review counts for helpful stats badge
$pendingReviewsCount = (int) (Database::fetchOne("SELECT COUNT(*) AS c FROM reviews WHERE is_approved = 0")['c'] ?? 0);
$totalReviewsCount   = (int) (Database::fetchOne("SELECT COUNT(*) AS c FROM reviews WHERE is_approved = 1")['c'] ?? 0);
$totalReelsCount     = (int) (Database::fetchOne("SELECT COUNT(*) AS c FROM instagram_reels WHERE is_active = 1")['c'] ?? 0);

require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <!-- Top Hero Bar -->
  <div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, #2b1311 0%, #3e1b18 100%); color: #ffffff; border-color: rgba(255,255,255,0.08);">
    <div class="card-body" style="padding: 24px 28px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
      <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
          <span class="badge" style="background: rgba(199, 97, 61, 0.35); color: #f6dc94; border: 1px solid rgba(246, 220, 148, 0.3);">
            🎛️ Storefront CMS
          </span>
          <span style="font-size: 0.8rem; color: rgba(255,255,255,0.7); font-family: var(--dc-font-mono);">
            Real-Time Feature Management
          </span>
        </div>
        <h2 style="font-family: var(--dc-font-heading); font-size: 1.5rem; margin: 0 0 6px 0; color: #ffffff;">
          Storefront Modules &amp; Content Manager
        </h2>
        <p style="margin: 0; color: rgba(255,255,255,0.8); font-size: 0.9rem; max-width: 650px;">
          Easily toggle sections and customer modules ON or OFF sitewide, and personalize all customer-facing headlines, promo codes, badges, and marketing copy.
        </p>
      </div>
      <div style="display: flex; gap: 10px;">
        <button type="button" onclick="document.getElementById('modulesMasterForm').submit();" class="btn btn-primary" style="box-shadow: 0 4px 14px rgba(199, 97, 61, 0.4);">
          💾 Save All Changes
        </button>
        <a href="<?= url() ?>" target="_blank" class="btn btn-secondary" style="border: none;">
          🌐 Open Live Store
        </a>
      </div>
    </div>
  </div>

  <form id="modulesMasterForm" method="POST">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="save_all_modules">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px; margin-bottom: 32px;">

      <!-- ══ MODULE 1: CUSTOMER REVIEWS & RATINGS ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_reviews_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(199, 97, 61, 0.12); color: var(--dc-terracotta);">⭐</div>
            <div>
              <h3 class="card-title" style="margin: 0;">Customer Reviews &amp; Testimonials</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">Homepage reviews showcase &amp; product reviews</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle Reviews Module ON/OFF">
            <input type="checkbox" name="module_reviews" value="1" <?= $mReviews ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <div style="display: flex; align-items: center; justify-content: space-between; background: var(--dc-cream); padding: 10px 14px; border-radius: 10px; margin-bottom: 16px; border: 1px solid var(--dc-border);">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span class="badge badge-success"><?= $totalReviewsCount ?> Approved Reviews</span>
              <?php if ($pendingReviewsCount > 0): ?>
                <span class="badge badge-warning">⏳ <?= $pendingReviewsCount ?> Pending Moderation</span>
              <?php endif; ?>
            </div>
            <a href="<?= url('admin/reviews.php') ?>" class="btn btn-sm btn-outline">
              Review Moderation →
            </a>
          </div>

          <div class="form-group">
            <label class="form-label" for="reviews_heading">Section Main Title</label>
            <input type="text" id="reviews_heading" name="reviews_heading" class="form-control" value="<?= e($reviewsHeading) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="reviews_subheading">Section Subheading / Description</label>
            <textarea id="reviews_subheading" name="reviews_subheading" class="form-control" rows="2"><?= e($reviewsSubhead) ?></textarea>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="form-group" style="margin: 0;">
              <label class="form-label" for="reviews_badge_text">Rating Badge Pill Text</label>
              <input type="text" id="reviews_badge_text" name="reviews_badge_text" class="form-control" value="<?= e($reviewsBadge) ?>">
            </div>

            <div class="form-group" style="margin: 0; padding-top: 24px;">
              <label class="form-check">
                <input type="checkbox" name="reviews_allow_submission" value="1" <?= $mReviewsSub ? 'checked' : '' ?>>
                <span style="font-weight: 600; font-size: 0.85rem;">Allow Customers to Submit Reviews</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ MODULE 2: INSTAGRAM & SOCIAL REELS SHOWCASE ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_instagram_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(199, 97, 61, 0.12); color: var(--dc-terracotta);">📸</div>
            <div>
              <h3 class="card-title" style="margin: 0;">Instagram &amp; Video Reels Showcase</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">Homepage snap-scroll video cards &amp; follow CTA</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle Instagram Module ON/OFF">
            <input type="checkbox" name="module_instagram" value="1" <?= $mInstagram ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <div style="display: flex; align-items: center; justify-content: space-between; background: var(--dc-cream); padding: 10px 14px; border-radius: 10px; margin-bottom: 16px; border: 1px solid var(--dc-border);">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span class="badge badge-success"><?= $totalReelsCount ?> Videos Configured</span>
            </div>
            <a href="<?= url('admin/instagram-reels.php') ?>" class="btn btn-sm btn-outline">
              Manage Video Links →
            </a>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="form-group">
              <label class="form-label" for="instagram_handle">Account Handle</label>
              <input type="text" id="instagram_handle" name="instagram_handle" class="form-control" value="<?= e($igHandle) ?>">
            </div>

            <div class="form-group">
              <label class="form-label" for="instagram_tagline">Eyebrow Tag</label>
              <input type="text" id="instagram_tagline" name="instagram_tagline" class="form-control" value="<?= e($igTagline) ?>">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px;">
            <div class="form-group" style="margin: 0;">
              <label class="form-label" for="instagram_profile_url">Profile URL Link</label>
              <input type="url" id="instagram_profile_url" name="instagram_profile_url" class="form-control" value="<?= e($igProfile) ?>">
            </div>

            <div class="form-group" style="margin: 0;">
              <label class="form-label" for="instagram_button_text">Button Label</label>
              <input type="text" id="instagram_button_text" name="instagram_button_text" class="form-control" value="<?= e($igBtnText) ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- ══ MODULE 3: ANNOUNCEMENT BAR ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_announcement_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(246, 220, 148, 0.35); color: #8a481c;">📢</div>
            <div>
              <h3 class="card-title" style="margin: 0;">Announcement Top Banner</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">Sticky top bar for offers &amp; free shipping alerts</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle Announcement Bar ON/OFF">
            <input type="checkbox" name="module_announcement" value="1" <?= $mAnnouncement ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="announcement_text">Announcement Banner Text</label>
            <input type="text" id="announcement_text" name="announcement_text" class="form-control" value="<?= e($annText) ?>" placeholder="e.g. 🎉 Free Shipping on orders above ₹499" required>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="form-group" style="margin: 0;">
              <label class="form-label" for="announcement_badge">Badge Tag Text</label>
              <input type="text" id="announcement_badge" name="announcement_badge" class="form-control" value="<?= e($annBadge) ?>" placeholder="e.g. Special Offer">
            </div>

            <div class="form-group" style="margin: 0;">
              <label class="form-label" for="announcement_link">Redirect Link</label>
              <input type="text" id="announcement_link" name="announcement_link" class="form-control" value="<?= e($annLink) ?>" placeholder="#products or /product.php?slug=...">
            </div>
          </div>
        </div>
      </div>

      <!-- ══ MODULE 4: WELCOME / PROMOTIONAL POPUP ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_popup_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(27, 138, 90, 0.12); color: #1b8a5a;">🎁</div>
            <div>
              <h3 class="card-title" style="margin: 0;">Welcome / Discount Popup Modal</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">First-visit discount modal with instant promo code</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle Welcome Popup ON/OFF">
            <input type="checkbox" name="module_welcome_popup" value="1" <?= $mPopup ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="popup_headline">Popup Headline</label>
            <input type="text" id="popup_headline" name="popup_headline" class="form-control" value="<?= e($popupHead) ?>" placeholder="e.g. Get 10% Off Your First Order">
          </div>

          <div class="form-group">
            <label class="form-label" for="popup_subtext">Offer Subtext</label>
            <input type="text" id="popup_subtext" name="popup_subtext" class="form-control" value="<?= e($popupSub) ?>" placeholder="Handcrafted pure jaggery chikki delivered fresh.">
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="popup_coupon_code">Coupon Code to Present</label>
            <input type="text" id="popup_coupon_code" name="popup_coupon_code" class="form-control" style="font-family: var(--dc-font-mono); font-weight: 700; letter-spacing: 0.1em;" value="<?= e($popupCoupon) ?>" placeholder="FIRST10">
          </div>
        </div>
      </div>

      <!-- ══ MODULE 5: BRAND STORY & HERITAGE ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_story_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(199, 97, 61, 0.12); color: var(--dc-terracotta);">📖</div>
            <div>
              <h3 class="card-title" style="margin: 0;">Brand Story &amp; Craftsmanship</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">Homepage yellow feature box with 3 pillar cards</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle Brand Story Section ON/OFF">
            <input type="checkbox" name="module_story" value="1" <?= $mStory ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="story_heading">Story Headline</label>
            <input type="text" id="story_heading" name="story_heading" class="form-control" value="<?= e($storyHead) ?>">
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="story_subheading">Story Tagline / Lead Paragraph</label>
            <textarea id="story_subheading" name="story_subheading" class="form-control" rows="2"><?= e($storySub) ?></textarea>
          </div>
        </div>
      </div>

      <!-- ══ MODULE 6: FEATURED PRODUCTS CAROUSEL ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_featured_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(199, 97, 61, 0.12); color: var(--dc-terracotta);">🥜</div>
            <div>
              <h3 class="card-title" style="margin: 0;">Featured Products Carousel</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">Homepage spotlight for signature chikki bars</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle Featured Products Section ON/OFF">
            <input type="checkbox" name="module_featured" value="1" <?= $mFeatured ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="featured_heading">Section Title</label>
            <input type="text" id="featured_heading" name="featured_heading" class="form-control" value="<?= e($featHead) ?>">
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="featured_subheading">Section Subheading</label>
            <input type="text" id="featured_subheading" name="featured_subheading" class="form-control" value="<?= e($featSub) ?>">
          </div>
        </div>
      </div>

      <!-- ══ MODULE 7: PINCODE DELIVERY CHECKER ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_pincode_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(43, 112, 168, 0.12); color: #2b70a8;">📍</div>
            <div>
              <h3 class="card-title" style="margin: 0;">Pincode Delivery Estimator</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">Product page pincode validation &amp; ETA widget</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle Pincode Estimator ON/OFF">
            <input type="checkbox" name="module_pincode_checker" value="1" <?= $mPincode ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="pincode_helper_text">Prompt / Helper Label</label>
            <input type="text" id="pincode_helper_text" name="pincode_helper_text" class="form-control" value="<?= e($pincodeHelp) ?>">
          </div>
        </div>
      </div>

      <!-- ══ MODULE 8: CASH ON DELIVERY (COD) & CHECKOUT ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_cod_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(27, 138, 90, 0.12); color: #1b8a5a;">💵</div>
            <div>
              <h3 class="card-title" style="margin: 0;">Cash on Delivery (COD)</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">Accept cash / UPI upon parcel arrival</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle COD ON/OFF">
            <input type="checkbox" name="cod_enabled" value="1" <?= $mCod ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <p style="margin: 0; font-size: 0.88rem; color: var(--dc-muted); line-height: 1.5;">
            When active, buyers can choose "Cash on Delivery" at checkout. When disabled, only prepaid online payment methods are shown.
          </p>
        </div>
      </div>

      <!-- ══ MODULE 9: GST BREAKDOWN DISPLAY ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_gst_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(43, 112, 168, 0.12); color: #2b70a8;">🧾</div>
            <div>
              <h3 class="card-title" style="margin: 0;">GST / Tax Breakdown Display</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">Show itemized CGST + SGST or IGST tax tags</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle Tax Breakdown ON/OFF">
            <input type="checkbox" name="module_gst_breakdown" value="1" <?= $mGstBreakdown ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <p style="margin: 0; font-size: 0.88rem; color: var(--dc-muted); line-height: 1.5;">
            Displays transparent GST breakdowns on product cards, cart drawer, and order confirmation slips.
          </p>
          <div style="margin-top: 10px;">
            <a href="<?= url('admin/gst-settings.php') ?>" class="btn btn-sm btn-outline">
              Configure GST Rates →
            </a>
          </div>
        </div>
      </div>

      <!-- ══ MODULE 10: PRODUCT DETAIL BADGES & TRUST CAPSULES ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_product_badges_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(199, 97, 61, 0.12); color: var(--dc-terracotta);">🏷️</div>
            <div>
              <h3 class="card-title" style="margin: 0;">Product Detail Badges &amp; Capsules</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">Feature capsules, category supertitle &amp; review stars on product page</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle Product Page Badges ON/OFF">
            <input type="checkbox" name="module_product_badges" value="1" <?= $mProdBadges ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="product_badges_list">
              Feature Badges (Capsules)
              <span style="font-size:0.75rem;font-weight:normal;color:var(--dc-muted);">(Separated by commas)</span>
            </label>
            <textarea id="product_badges_list" name="product_badges_list" class="form-control" rows="2" placeholder="100% PURE JAGGERY, ZERO REFINED SUGAR, ROASTED NUTS, HIGH PROTEIN, HANDCRAFTED"><?= e($prodBadgesList) ?></textarea>
            <p class="form-hint">Displayed as uppercase pills below price on the product page (e.g. <code>100% PURE JAGGERY</code>, <code>ROASTED NUTS</code>, etc.).</p>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="form-group" style="margin: 0;">
              <label class="form-label" for="product_supertitle_text">Category Eyebrow / Supertitle</label>
              <input type="text" id="product_supertitle_text" name="product_supertitle_text" class="form-control" value="<?= e($prodSuperText) ?>" placeholder="CHIKKI">
            </div>

            <div class="form-group" style="margin: 0; padding-top: 24px;">
              <label class="form-check">
                <input type="checkbox" name="product_supertitle_enabled" value="1" <?= $mProdSuper ? 'checked' : '' ?>>
                <span style="font-weight: 600; font-size: 0.85rem;">Show Category Supertitle</span>
              </label>
            </div>
          </div>

          <div class="form-group" style="margin-top: 14px; margin-bottom: 0;">
            <label class="form-check">
              <input type="checkbox" name="product_rating_enabled" value="1" <?= $mProdRating ? 'checked' : '' ?>>
              <span style="font-weight: 600; font-size: 0.85rem;">Show Star Rating &amp; Review Count on Product Detail Page</span>
            </label>
          </div>
        </div>
      </div>

      <!-- ══ MODULE 11: HOMEPAGE PRODUCT CARDS (RATINGS & BADGES) ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_card_badges_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(246, 220, 148, 0.35); color: #8a481c;">🌟</div>
            <div>
              <h3 class="card-title" style="margin: 0;">Homepage Product Card Badges &amp; Ratings</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">Customizable mini badges (Pure Jaggery) &amp; star ratings</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle Card Badges ON/OFF">
            <input type="checkbox" name="module_card_badges" value="1" <?= $mCardBadges ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="card_badges_list">
              Homepage Feature Badges
              <span style="font-size:0.75rem;font-weight:normal;color:var(--dc-muted);">(Separated by commas)</span>
            </label>
            <input type="text" id="card_badges_list" name="card_badges_list" class="form-control" value="<?= e($cardBadgesList) ?>" placeholder="Pure Jaggery, 100% Natural">
            <p class="form-hint">Displayed beneath each product title on homepage cards (e.g. <code>Pure Jaggery</code>, <code>100% Natural</code>).</p>
          </div>

          <div class="form-group" style="margin-top: 14px;">
            <label class="form-check">
              <input type="checkbox" name="module_card_ratings" value="1" <?= $mCardRatings ? 'checked' : '' ?>>
              <span style="font-weight: 600; font-size: 0.85rem;">Show Star Ratings on Homepage Product Cards</span>
            </label>
          </div>

          <div style="background: var(--dc-cream); padding: 12px 14px; border-radius: 10px; border: 1px solid var(--dc-border); margin-top: 12px;">
            <label class="form-check" style="margin-bottom: 8px;">
              <input type="checkbox" name="card_rating_fallback_enabled" value="1" <?= $mCardRatingFall ? 'checked' : '' ?>>
              <span style="font-weight: 600; font-size: 0.85rem;">Display Default Rating (e.g. 4.7 ★) when no reviews exist yet</span>
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
              <div class="form-group" style="margin: 0;">
                <label class="form-label" for="card_default_rating">Default Rating (e.g. 4.7)</label>
                <input type="text" id="card_default_rating" name="card_default_rating" class="form-control" value="<?= e($cardDefRating) ?>" placeholder="4.7">
              </div>
              <div class="form-group" style="margin: 0;">
                <label class="form-label" for="card_default_reviews">Default Review Count (e.g. 3)</label>
                <input type="text" id="card_default_reviews" name="card_default_reviews" class="form-control" value="<?= e($cardDefReviews) ?>" placeholder="3">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ MODULE 12: TAX & HSN PRICING NOTICE ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_tax_note_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(27, 138, 90, 0.12); color: #1b8a5a;">💰</div>
            <div>
              <h3 class="card-title" style="margin: 0;">Tax &amp; HSN Pricing Notice</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">"Inclusive of all taxes (GST 5% included) · HSN: 1704" note</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle Tax Note ON/OFF">
            <input type="checkbox" name="module_tax_note" value="1" <?= $mTaxNote ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="tax_note_text">Tax Notice Text Template</label>
            <input type="text" id="tax_note_text" name="tax_note_text" class="form-control" value="<?= e($taxNoteText) ?>" placeholder="Inclusive of all taxes (GST {rate}% included)">
            <p class="form-hint">Use <code>{rate}</code> as placeholder for the product's GST percentage (e.g. 5).</p>
          </div>

          <div class="form-group" style="margin: 0; padding-top: 8px;">
            <label class="form-check">
              <input type="checkbox" name="tax_hsn_enabled" value="1" <?= $mTaxHsn ? 'checked' : '' ?>>
              <span style="font-weight: 600; font-size: 0.85rem;">Display HSN Code tag (e.g. · HSN: 1704) if product has HSN set</span>
            </label>
          </div>
        </div>
      </div>

      <!-- ══ MODULE 13: LIVE INVENTORY & STOCK BADGES ══ -->
      <div class="card module-box">
        <input type="hidden" name="has_stock_badge_module" value="1">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
          <div style="display: flex; gap: 12px; align-items: center;">
            <div class="module-icon" style="background: rgba(43, 112, 168, 0.12); color: #2b70a8;">📦</div>
            <div>
              <h3 class="card-title" style="margin: 0;">Live Inventory &amp; Stock Badges</h3>
              <span style="font-size: 0.8rem; color: var(--dc-muted);">Fresh batch stock status indicator on product pages</span>
            </div>
          </div>
          <!-- Toggle -->
          <label class="dc-switch-label" title="Toggle Stock Indicator ON/OFF">
            <input type="checkbox" name="module_stock_badge" value="1" <?= $mStockBadge ? 'checked' : '' ?> class="dc-switch-input" onchange="updateCardStatus(this)">
            <span class="dc-switch-slider"></span>
          </label>
        </div>

        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="stock_in_stock_text">In Stock Label Text</label>
            <input type="text" id="stock_in_stock_text" name="stock_in_stock_text" class="form-control" value="<?= e($stockInStock) ?>" placeholder="In Stock (Fresh Batch Ready to Ship)">
            <p class="form-hint">Shown in green pulse badge when stock &gt; 10 units.</p>
          </div>

          <div class="form-group">
            <label class="form-label" for="stock_low_stock_text">Low Stock Alert Text</label>
            <input type="text" id="stock_low_stock_text" name="stock_low_stock_text" class="form-control" value="<?= e($stockLowStock) ?>" placeholder="⚠️ Only {qty} left in stock — order soon!">
            <p class="form-hint">Use <code>{qty}</code> as placeholder for remaining stock units.</p>
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="stock_out_of_stock_text">Out of Stock Label Text</label>
            <input type="text" id="stock_out_of_stock_text" name="stock_out_of_stock_text" class="form-control" value="<?= e($stockOutOfStock) ?>" placeholder="❌ Currently Out of Stock">
          </div>
        </div>
      </div>

    </div>

    <!-- Sticky Bottom Save Bar -->
    <div style="position: sticky; bottom: 16px; background: #ffffff; padding: 14px 24px; border-radius: 16px; border: 1px solid var(--dc-border); box-shadow: 0 10px 30px rgba(43, 19, 17, 0.12); display: flex; justify-content: space-between; align-items: center; z-index: 40;">
      <div style="font-size: 0.88rem; color: var(--dc-muted);">
        💡 Toggle any switch and edit copy above, then click <strong>Save All Changes</strong>.
      </div>
      <div style="display: flex; gap: 12px;">
        <button type="submit" class="btn btn-primary" style="padding: 10px 28px;">
          💾 Save All Changes
        </button>
      </div>
    </div>

  </form>

</div>

<style>
.module-box {
  transition: all 0.25s ease;
  border-radius: 16px;
  overflow: hidden;
}
.module-box:hover {
  box-shadow: 0 8px 28px rgba(43, 19, 17, 0.08);
  border-color: #dcd1c2;
}
.module-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  flex-shrink: 0;
}

/* Switches */
.dc-switch-label {
  user-select: none;
  cursor: pointer;
  display: inline-flex;
}
.dc-switch-input {
  display: none;
}
.dc-switch-slider {
  position: relative;
  display: inline-block;
  width: 46px;
  height: 26px;
  background-color: #d8cebf;
  border-radius: 26px;
  transition: background-color 0.25s ease;
  flex-shrink: 0;
}
.dc-switch-slider::before {
  content: "";
  position: absolute;
  top: 3px;
  left: 3px;
  width: 20px;
  height: 20px;
  background-color: #ffffff;
  border-radius: 50%;
  transition: transform 0.25s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.dc-switch-input:checked + .dc-switch-slider {
  background-color: var(--dc-terracotta);
}
.dc-switch-input:checked + .dc-switch-slider::before {
  transform: translateX(20px);
}
</style>

<script>
function updateCardStatus(checkbox) {
  const card = checkbox.closest('.module-box');
  if (checkbox.checked) {
    card.style.opacity = '1';
  } else {
    card.style.opacity = '0.75';
  }
}
</script>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
