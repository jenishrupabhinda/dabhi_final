<?php
/**
 * cart.php — Full Cart Page for Dabhi Chikki
 */
$pageTitle = 'Your Bag — Dabhi Chikki';
$pageDesc  = 'Review your shopping bag and proceed to checkout.';
require_once __DIR__ . '/partials/_header.php';

$cart  = null;
$items = [];
$sum   = ['subtotal'=>0,'total_weight'=>0,'item_count'=>0];

try {
    $cart  = Cart::getOrCreate();
    $items = Cart::getItems((int)$cart['id']);
    $sum   = Cart::getSummary($items);
} catch (\Throwable $e) {
    // DB not ready
}

$freeAbove   = (float)getSetting('free_shipping_threshold', '999');
if ($freeAbove <= 0) $freeAbove = (float)getSetting('free_shipping_above', '999');
$defaultFee  = (float)getSetting('default_shipping_charge', '60');
$shipping    = ($sum['subtotal'] >= $freeAbove && $freeAbove > 0) ? 0 : $defaultFee;
$total       = $sum['subtotal'] + $shipping;
$remaining   = max(0, $freeAbove - $sum['subtotal']);
$gstEnabled  = GST::isEnabled();
?>

<section class="cart-page">
  <div class="container">
    <h1 style="font-size:1.75rem;margin-bottom:1.75rem">Your Bag
      <?php if ($sum['item_count'] > 0): ?>
        <span style="font-size:1rem;color:var(--text-muted);font-weight:400">(<?= $sum['item_count'] ?> item<?= $sum['item_count'] > 1 ? 's' : '' ?>)</span>
      <?php endif; ?>
    </h1>

    <?php if (empty($items)): ?>
      <!-- Empty cart -->
      <div style="text-align:center;padding:5rem 0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="72" height="72" style="color:var(--border);margin:0 auto 1.5rem;display:block">
          <path d="M6 2 3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>
        </svg>
        <h2 style="margin-bottom:0.75rem">Your bag is empty</h2>
        <p style="margin-bottom:1.75rem">Add some chikki to get started!</p>
        <a href="index.php" class="btn btn-primary btn-lg">Shop Now</a>
      </div>
    <?php else: ?>
      <div class="cart-page-grid">
        <!-- Cart Items -->
        <div class="cart-page-items" id="cart-items-list">
          <?php if ($remaining > 0): ?>
            <div class="alert alert-info" style="margin-bottom:1rem">
              🚚 Add ₹<?= number_format($remaining, 0) ?> more to get <strong>FREE shipping!</strong>
            </div>
          <?php else: ?>
            <div class="alert alert-success" style="margin-bottom:1rem">
              🎉 You have <strong>free shipping</strong> on this order!
            </div>
          <?php endif; ?>

          <?php foreach ($items as $item):
            $wLabel = $item['weight_grams'] >= 1000
              ? ($item['weight_grams']/1000) . 'kg'
              : $item['weight_grams'] . 'g';
            $itemImg = imageUrl($item['product_image'] ?? 'assets/images/products/mandvi-chikki-1.jpg');
            $lineTotal = (float)$item['selling_price'] * (int)$item['quantity'];
          ?>
            <div class="cart-page-item" id="cart-item-<?= (int)$item['id'] ?>">
              <img src="<?= htmlspecialchars($itemImg) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="cart-page-item-img" loading="lazy">
              <div class="cart-page-item-info">
                <div class="cart-page-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                <div class="cart-page-item-weight"><?= $wLabel ?></div>
                <div class="flex items-center justify-between" style="flex-wrap:wrap;gap:0.75rem">
                  <div class="qty-control" style="border:1.5px solid var(--border);border-radius:var(--radius);background:transparent">
                    <button class="qty-btn" onclick="cartPageQty(<?= (int)$item['id'] ?>,-1)" type="button" aria-label="Decrease">−</button>
                    <span class="qty-val" id="qty-<?= (int)$item['id'] ?>"><?= (int)$item['quantity'] ?></span>
                    <button class="qty-btn" onclick="cartPageQty(<?= (int)$item['id'] ?>,1)" type="button" aria-label="Increase">+</button>
                  </div>
                  <div style="font-weight:700;font-size:1.0625rem">₹<?= number_format($lineTotal, 0) ?></div>
                  <button onclick="cartPageRemove(<?= (int)$item['id'] ?>)" class="remove-item" type="button">Remove</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Order Summary -->
        <div class="order-summary-card" id="order-summary">
          <h3>Order Summary</h3>
          <div class="summary-row">
            <span>Subtotal</span>
            <span id="page-subtotal" data-value="<?= (float)$sum['subtotal'] ?>">₹<?= number_format((float)$sum['subtotal'], 2) ?></span>
          </div>
          <div class="summary-row">
            <span>Shipping</span>
            <span id="page-shipping"><?= $shipping === 0 ? '<span style="color:var(--success)">FREE</span>' : '₹' . number_format($defaultFee, 0) ?></span>
          </div>
          <div class="summary-row total">
            <span>Total</span>
            <span id="page-total">₹<?= number_format($total, 2) ?></span>
          </div>
          <?php if ($gstEnabled): ?>
            <p style="font-size:0.78rem;color:var(--text-muted);margin-top:0.4rem">Inclusive of all taxes (GST)</p>
          <?php endif; ?>

          <!-- Coupon -->
          <form id="coupon-form" style="margin-top:1.25rem">
            <div class="coupon-input-row">
              <input type="text" id="coupon-code" class="form-control" placeholder="Coupon code" style="text-transform:uppercase" autocomplete="off">
              <button type="submit" class="btn btn-outline">Apply</button>
            </div>
            <div id="coupon-msg" style="font-size:0.85rem;margin-top:0.3rem"></div>
          </form>
          <div id="coupon-discount-row" style="display:none" class="summary-row" style="color:var(--success)">
            <span>Discount</span>
            <span id="coupon-discount"></span>
          </div>
          <input type="hidden" id="applied-coupon" name="coupon">

          <a href="checkout.php" class="btn btn-primary btn-block btn-lg" style="margin-top:1.25rem">
            Proceed to Checkout
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </a>
          <a href="index.php" class="btn btn-ghost btn-block" style="margin-top:0.5rem;color:var(--text-muted)">← Continue Shopping</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
const CART_API = 'api/cart.php';

async function cartPageQty(itemId, delta) {
  const qtyEl = document.getElementById('qty-' + itemId);
  let qty = parseInt(qtyEl.textContent) + delta;
  if (qty < 1) { cartPageRemove(itemId); return; }

  const res = await fetch(CART_API, {
    method:'POST',
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
    body: JSON.stringify({action:'update',item_id:itemId,quantity:qty})
  });
  const data = await res.json();
  if (data.ok) {
    qtyEl.textContent = data.new_qty;
    refreshSummary(data);
  } else {
    showToast(data.error || 'Could not update.', 'error');
  }
}

async function cartPageRemove(itemId) {
  const res = await fetch(CART_API, {
    method:'POST',
    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
    body: JSON.stringify({action:'remove',item_id:itemId})
  });
  const data = await res.json();
  if (data.ok) {
    const el = document.getElementById('cart-item-' + itemId);
    if (el) el.remove();
    refreshSummary(data);
    showToast('Item removed.','default');
    if (!document.querySelector('.cart-page-item')) location.reload();
  }
}

function refreshSummary(data) {
  const sub = parseFloat(data.subtotal || 0);
  const shipping = sub >= 500 ? 0 : 60;
  const total = sub + shipping;
  const el = document.getElementById('page-subtotal');
  const shEl = document.getElementById('page-shipping');
  const totEl = document.getElementById('page-total');
  if (el) { el.textContent = '₹' + sub.toFixed(2); el.dataset.value = sub; }
  if (shEl) shEl.innerHTML = shipping === 0 ? '<span style="color:var(--success)">FREE</span>' : '₹60';
  if (totEl) totEl.textContent = '₹' + total.toFixed(2);
  // Update badge
  const badges = document.querySelectorAll('.cart-badge');
  badges.forEach(b => {
    const cnt = data.cart_count || 0;
    b.textContent = cnt; b.classList.toggle('visible', cnt > 0);
  });
}
</script>

<?php
$extraScripts = ['assets/js/checkout.js'];
require_once __DIR__ . '/partials/_footer.php';
?>
