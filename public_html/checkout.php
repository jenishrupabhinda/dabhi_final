<?php
/**
 * checkout.php — Checkout Page for Dabhi Chikki
 */
$pageTitle    = 'Checkout — Dabhi Chikki';
$pageDesc     = 'Complete your order securely.';
$extraScripts = ['assets/js/checkout.js'];
require_once __DIR__ . '/partials/_header.php';

// Redirect if cart is empty
try {
    $cart  = Cart::getOrCreate();
    $items = Cart::getItems((int)$cart['id']);
    $sum   = Cart::getSummary($items);
    if (empty($items)) {
        header('Location: cart.php');
        exit;
    }
} catch (\Throwable $e) {
    $items = [];
    $sum   = ['subtotal'=>0,'item_count'=>0,'total_weight'=>0];
}

$freeAbove   = (float)getSetting('free_shipping_threshold', '999');
if ($freeAbove <= 0) $freeAbove = (float)getSetting('free_shipping_above', '999');
$defaultFee  = (float)getSetting('default_shipping_charge', '60');
$codEnabled  = getSetting('cod_enabled', '1') === '1';
$gstEnabled  = GST::isEnabled();
$businessState = GST::businessState();

$shipping  = ($sum['subtotal'] >= $freeAbove && $freeAbove > 0) ? 0 : $defaultFee;
$total     = $sum['subtotal'] + $shipping;

// Pre-fill address if logged in
$defaultAddr = null;
try {
    if (Auth::check()) {
        $defaultAddr = Database::fetchOne(
            'SELECT * FROM user_addresses WHERE user_id = ? AND is_default = 1 LIMIT 1',
            [Auth::id()]
        );
    }
} catch (\Throwable $e) {}
?>

<section class="checkout-page">
  <div class="container">
    <nav class="breadcrumb">
      <a href="index.php">Home</a>
      <span class="breadcrumb-sep">›</span>
      <a href="cart.php">Bag</a>
      <span class="breadcrumb-sep">›</span>
      <span>Checkout</span>
    </nav>

    <h1 style="font-size:1.75rem;margin-bottom:1.75rem">Checkout</h1>

    <form id="checkout-form" method="POST">
      <div class="checkout-grid">

        <!-- Left: Address + Payment -->
        <div>
          <!-- Contact (if guest) -->
          <?php if (!Auth::check()): ?>
          <div class="checkout-section">
            <div class="checkout-section-title">Contact Information</div>
            <div class="form-group">
              <label class="form-label" for="guest_email">Email Address</label>
              <input type="email" id="guest_email" name="guest_email" class="form-control" placeholder="you@example.com" required>
            </div>
            <p class="text-sm" style="color:var(--text-muted)">
              Already have an account? <a href="auth.php?redirect=<?= urlencode('checkout.php') ?>" style="color:var(--primary)">Login</a>
            </p>
          </div>
          <?php endif; ?>

          <!-- Delivery Address -->
          <div class="checkout-section">
            <div class="checkout-section-title">Delivery Address</div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="ship_name">Full Name</label>
                <input type="text" id="ship_name" name="ship_name" class="form-control"
                  placeholder="Your full name" required
                  value="<?= htmlspecialchars($defaultAddr['full_name'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label" for="ship_phone">Phone Number</label>
                <input type="tel" id="ship_phone" name="ship_phone" class="form-control"
                  placeholder="10-digit mobile number" required pattern="[6-9][0-9]{9}"
                  value="<?= htmlspecialchars($defaultAddr['phone'] ?? '') ?>">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label" for="ship_line1">Address Line 1</label>
              <input type="text" id="ship_line1" name="ship_line1" class="form-control"
                placeholder="House/Flat no., Building name, Street" required
                value="<?= htmlspecialchars($defaultAddr['line1'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="ship_line2">Address Line 2 <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
              <input type="text" id="ship_line2" name="ship_line2" class="form-control"
                placeholder="Area, Locality, Landmark"
                value="<?= htmlspecialchars($defaultAddr['line2'] ?? '') ?>">
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="pincode">PIN Code</label>
                <input type="text" id="pincode" name="ship_pincode" class="form-control"
                  placeholder="6-digit PIN code" required maxlength="6" inputmode="numeric"
                  value="<?= htmlspecialchars($defaultAddr['pincode'] ?? '') ?>">
                <div id="pincode-msg" class="form-hint"></div>
              </div>
              <div class="form-group">
                <label class="form-label" for="ship_city">City</label>
                <input type="text" id="ship_city" name="ship_city" class="form-control"
                  placeholder="City" required
                  value="<?= htmlspecialchars($defaultAddr['city'] ?? '') ?>">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label" for="ship_state">State</label>
              <select id="ship_state" name="ship_state" class="form-control" required>
                <option value="">Select state</option>
                <?php
                $states = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry'];
                $selectedState = $defaultAddr['state'] ?? '';
                foreach ($states as $s):
                ?>
                  <option value="<?= $s ?>" <?= $s === $selectedState ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <?php if (Auth::check()): ?>
            <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.9rem;color:var(--text-muted);cursor:pointer">
              <input type="checkbox" name="save_address" value="1" style="accent-color:var(--primary)">
              Save this address for future orders
            </label>
            <?php endif; ?>
          </div>

            <!-- Payment Method -->
          <div class="checkout-section">
            <div class="checkout-section-title">Payment Method</div>

            <?php if ($codEnabled): ?>
            <label class="payment-option selected" id="pm-cod">
              <input type="radio" name="payment_method" value="cod" checked>
              <div>
                <div class="payment-option-label">💵 Cash on Delivery (COD)</div>
                <div class="payment-option-desc">Pay when your order arrives at your door.</div>
              </div>
            </label>
            <?php endif; ?>

            <label class="payment-option <?= !$codEnabled ? 'selected' : '' ?>" id="pm-online">
              <input type="radio" name="payment_method" value="cashfree" <?= !$codEnabled ? 'checked' : '' ?>>
              <div>
                <div class="payment-option-label">💳 Pay Online</div>
                <div class="payment-option-desc">UPI, Net Banking, Credit/Debit Card via Cashfree.</div>
              </div>
            </label>

            <div id="pm-online-note" style="<?= !$codEnabled ? 'display:flex;' : 'display:none;' ?>margin-top:0.75rem" class="alert alert-info">
              Online payment via Cashfree — you'll be redirected after placing the order.
            </div>
          </div>

          <div class="checkout-section">
            <div class="checkout-section-title">Order Notes <span style="font-weight:400;color:var(--text-muted)">(optional)</span></div>
            <textarea name="notes" class="form-control" rows="3" placeholder="Special instructions, delivery notes…"></textarea>
          </div>

          <input type="hidden" name="applied_coupon" id="applied-coupon" value="">
        </div>

        <!-- Right: Order Summary -->
        <div>
          <div class="order-summary-card" id="order-summary"
               data-business-state="<?= htmlspecialchars($businessState) ?>"
               data-gst-enabled="<?= $gstEnabled ? '1' : '0' ?>"
               data-free-threshold="<?= $freeAbove ?>"
               data-default-shipping="<?= $defaultFee ?>">
            <h3>Order Summary</h3>
            <?php foreach ($items as $item):
              $wLabel = $item['weight_grams'] >= 1000
                ? ($item['weight_grams']/1000).'kg'
                : $item['weight_grams'].'g';
              $itemImg = imageUrl($item['product_image'] ?? 'assets/images/products/mandvi-chikki-1.jpg');
            ?>
              <div style="display:flex;gap:0.75rem;align-items:center;margin-bottom:0.875rem">
                <img src="<?= htmlspecialchars($itemImg) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>"
                  style="width:52px;height:52px;border-radius:var(--radius);object-fit:cover;background:var(--bg-alt)">
                <div style="flex:1">
                  <div style="font-weight:600;font-size:0.9rem"><?= htmlspecialchars($item['product_name']) ?></div>
                  <div style="font-size:0.8rem;color:var(--text-muted)"><?= $wLabel ?> × <?= (int)$item['quantity'] ?></div>
                </div>
                <div style="font-weight:700;font-size:0.9rem">₹<?= number_format((float)$item['selling_price'] * (int)$item['quantity'], 0) ?></div>
              </div>
            <?php endforeach; ?>

            <hr class="divider">

            <!-- Coupon -->
            <form id="coupon-form" style="margin-bottom:1rem">
              <div class="coupon-input-row">
                <input type="text" id="coupon-code" class="form-control" placeholder="Coupon code" style="text-transform:uppercase" autocomplete="off">
                <button type="submit" class="btn btn-outline btn-sm">Apply</button>
              </div>
              <div id="coupon-msg" style="font-size:0.825rem;margin-top:0.3rem"></div>
            </form>

            <div class="summary-row">
              <span>Subtotal</span>
              <span id="summary-subtotal" data-value="<?= (float)$sum['subtotal'] ?>">₹<?= number_format((float)$sum['subtotal'], 2) ?></span>
            </div>
            <div id="coupon-discount-row" style="display:none" class="summary-row">
              <span style="color:var(--success)">Discount</span>
              <span style="color:var(--success)" id="coupon-discount"></span>
            </div>
            <div class="summary-row">
              <span id="shipping-label">Shipping</span>
              <span id="shipping-charge"><?= $shipping === 0 ? '<span style="color:var(--success)">FREE</span>' : '₹' . number_format($shipping, 0) ?></span>
            </div>
            <div id="cod-fee-row" style="display:none;" class="summary-row">
              <span>COD Handling</span>
              <span id="cod-fee">₹0.00</span>
            </div>
            <div id="gst-breakdown-row" style="display:none;" class="summary-row">
              <span id="gst-breakdown-label">GST</span>
              <span id="gst-breakdown-amount">₹0.00</span>
            </div>
            <div class="summary-row total">
              <span>Total</span>
              <span id="summary-total">₹<?= number_format($total, 2) ?></span>
            </div>
            <?php if ($gstEnabled): ?>
              <p style="font-size:0.78rem;color:var(--text-muted);margin-top:0.5rem" id="tax-note">Inclusive of all taxes (GST)</p>
            <?php endif; ?>

            <button type="submit" form="checkout-form" class="btn btn-primary btn-block btn-lg" style="margin-top:1.25rem;position:relative" id="place-order-btn">
              <span class="spinner"></span>
              <span class="btn-text">Place Order</span>
            </button>

            <p style="text-align:center;font-size:0.78rem;color:var(--text-muted);margin-top:0.875rem">
              🔒 Secure checkout &nbsp;·&nbsp; No spam &nbsp;·&nbsp; Easy returns
            </p>
          </div>
        </div>
      </div>
    </form>
  </div>
</section>

<script>
// Toggle online payment note
document.querySelectorAll('.payment-option').forEach(opt => {
  opt.addEventListener('click', () => {
    document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
    opt.classList.add('selected');
    opt.querySelector('input[type=radio]').checked = true;
    const note = document.getElementById('pm-online-note');
    if (note) note.style.display = opt.id === 'pm-online' ? 'flex' : 'none';
  });
});
</script>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>
