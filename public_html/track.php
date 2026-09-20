<?php
/**
 * track.php — Order Tracking Page for Dabhi Chikki
 * Real-time order tracking connected to admin shipping, order status, courier info, and invoices.
 */
$pageTitle = 'Track Your Order — Dabhi Chikki';
require_once __DIR__ . '/partials/_header.php';

$queryInput = trim($_GET['order'] ?? $_GET['id'] ?? '');
$order      = null;
$error      = '';

if ($queryInput !== '') {
    try {
        if (is_numeric($queryInput)) {
            $order = Order::getById((int)$queryInput);
        }
        if (!$order) {
            $order = Order::getByNumber(strtoupper($queryInput));
        }

        if (!$order) {
            $error = 'No order found with number or ID "' . htmlspecialchars($queryInput) . '". Please verify and try again.';
        } else {
            // If logged in, protect private orders from other users
            if (Auth::check() && $order['user_id'] && (int)$order['user_id'] !== (int)Auth::id()) {
                $userRole = Auth::user()['role'] ?? '';
                if (!in_array($userRole, ['superadmin', 'admin', 'employee'])) {
                    $order = null;
                    $error = 'You do not have permission to view this order.';
                }
            }
        }
    } catch (\Throwable $e) {
        $error = 'Could not load order tracking: ' . $e->getMessage();
    }
}

// Flow of actual statuses used by Dabhi Admin
$flowStatuses = [
    'placed'           => ['label' => 'Order Placed',        'icon' => '📋', 'desc' => 'Order received and confirmed by our system.'],
    'confirmed'        => ['label' => 'Order Confirmed',     'icon' => '✅', 'desc' => 'Order approved and sent to our kitchen.'],
    'packed'           => ['label' => 'Packed & Ready',      'icon' => '📦', 'desc' => 'Handcrafted fresh and securely sealed.'],
    'shipped'          => ['label' => 'Shipped',             'icon' => '🚚', 'desc' => 'Dispatched with our courier partner.'],
    'out_for_delivery' => ['label' => 'Out for Delivery',    'icon' => '🏍️', 'desc' => 'Arriving today at your doorstep.'],
    'delivered'        => ['label' => 'Delivered',           'icon' => '🎉', 'desc' => 'Delivered successfully! Enjoy your fresh chikki.'],
];

$orderStatus = $order['status'] ?? 'placed';
$statusKeys  = array_keys($flowStatuses);
$statusIdx   = array_search($orderStatus, $statusKeys);
if ($statusIdx === false) $statusIdx = 0;

$shippingLabel = $order['shipping_label'] ?? null;
$trackingNum   = $shippingLabel['tracking_number'] ?? '';
$courierName   = $shippingLabel['courier_name'] ?? '';
$placedDate    = !empty($order['placed_at']) ? date('d M Y, g:i A', strtotime($order['placed_at'])) : '';
?>

<section class="section" style="padding-top:2.5rem;padding-bottom:4rem;">
  <div class="container" style="max-width:760px;">
    
    <div style="text-align:center;margin-bottom:2.25rem;">
      <h1 class="font-display" style="font-size:2.25rem;color:var(--foreground);margin-bottom:0.5rem;">📦 Track Your Order</h1>
      <p style="color:var(--text-muted);font-size:0.95rem;">Enter your Order Number (e.g. <strong>DC-<?= date('Y') ?>-000001</strong>) or Order ID</p>
    </div>

    <!-- Search Form -->
    <div style="max-width:540px;margin:0 auto 2.5rem;">
      <form method="GET" action="track.php" style="display:flex;gap:0.75rem;">
        <input
          type="text"
          name="order"
          class="form-control"
          placeholder="e.g. DC-2026-000001 or Order ID"
          value="<?= htmlspecialchars($queryInput) ?>"
          required
          style="flex:1;font-size:1rem;padding:0.75rem 1.125rem;border-radius:9999px;"
        >
        <button type="submit" class="btn btn-primary" style="border-radius:9999px;padding:0.75rem 1.5rem;font-weight:600;">
          Track Order
        </button>
      </form>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error" style="border-radius:var(--radius-lg);margin-bottom:2rem;text-align:center;">
        <?= $error ?>
      </div>
    <?php endif; ?>

    <?php if ($order): ?>
      <!-- Order Found Container -->
      <div style="display:flex;flex-direction:column;gap:1.5rem;">

        <!-- Status Card -->
        <div style="background:var(--card-bg);border-radius:var(--radius-lg);padding:1.75rem;box-shadow:var(--shadow);border:1px solid var(--border-light);">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;border-bottom:1px solid var(--border-light);padding-bottom:1.25rem;">
            <div>
              <div style="font-size:0.8125rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;">Order Reference</div>
              <div style="font-size:1.5rem;font-weight:800;color:var(--primary);margin-top:0.15rem;">#<?= htmlspecialchars($order['order_number']) ?></div>
              <?php if ($placedDate): ?>
                <div style="font-size:0.85rem;color:var(--text-muted);margin-top:0.25rem;">Placed on <?= $placedDate ?></div>
              <?php endif; ?>
            </div>
            
            <div style="text-align:right;">
              <div style="font-size:0.8125rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;">Amount &amp; Payment</div>
              <div style="font-size:1.5rem;font-weight:800;color:var(--foreground);margin-top:0.15rem;">₹<?= number_format((float)($order['total_amount'] ?? $order['total'] ?? 0), 2) ?></div>
              <div style="font-size:0.85rem;color:var(--text-muted);margin-top:0.25rem;text-transform:uppercase;font-weight:600;">
                <?= strtoupper($order['payment_method']) ?> · <span style="color:var(--success);"><?= ucfirst($order['payment_status']) ?></span>
              </div>
            </div>
          </div>

          <!-- Courier / Tracking Highlight -->
          <?php if (!empty($trackingNum)): ?>
            <div style="margin-top:1.25rem;padding:1rem 1.25rem;background:var(--bg-alt);border-radius:var(--radius);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;border-left:4px solid var(--primary);">
              <div>
                <span style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;font-weight:600;">Shipment Tracking Number</span>
                <div style="font-size:1.15rem;font-weight:800;color:var(--foreground);font-family:monospace;letter-spacing:0.05em;margin-top:0.15rem;">
                  <?= htmlspecialchars($trackingNum) ?>
                </div>
                <div style="font-size:0.825rem;color:var(--text-muted);margin-top:0.2rem;">
                  Dispatched via <strong><?= htmlspecialchars($courierName ?: 'Express Courier') ?></strong>
                </div>
              </div>
              <a href="invoice.php?order=<?= urlencode($order['order_number']) ?>" target="_blank" class="btn btn-outline btn-sm" style="border-radius:9999px;">
                🧾 View Tax Invoice
              </a>
            </div>
          <?php else: ?>
            <div style="margin-top:1.25rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
              <span style="font-size:0.85rem;color:var(--text-muted);">
                🚚 Tracking number will be updated as soon as package is handed over to our courier partner.
              </span>
              <a href="invoice.php?order=<?= urlencode($order['order_number']) ?>" target="_blank" class="btn btn-outline btn-sm" style="border-radius:9999px;">
                🧾 View Tax Invoice
              </a>
            </div>
          <?php endif; ?>
        </div>

        <!-- Timeline Card -->
        <div style="background:var(--card-bg);border-radius:var(--radius-lg);padding:1.75rem;box-shadow:var(--shadow);border:1px solid var(--border-light);">
          <h3 class="font-display" style="font-size:1.25rem;margin-bottom:1.5rem;color:var(--foreground);">Delivery Progress</h3>

          <?php if ($orderStatus === 'cancelled'): ?>
            <div class="alert alert-error" style="border-radius:var(--radius);margin-bottom:1.5rem;">
              ❌ This order was cancelled. Please contact customer support if you need assistance.
            </div>
          <?php endif; ?>

          <div class="order-timeline">
            <?php foreach ($flowStatuses as $key => $info):
              $keyIdx  = array_search($key, $statusKeys);
              $isDone  = ($orderStatus !== 'cancelled') && ($keyIdx < $statusIdx || ($orderStatus === 'delivered' && $keyIdx <= $statusIdx));
              $isActive= ($orderStatus !== 'cancelled') && ($key === $orderStatus);
            ?>
              <div class="timeline-step <?= $isDone ? 'done' : ($isActive ? 'active' : '') ?>">
                <div class="timeline-dot">
                  <?php if ($isDone): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
                  <?php else: ?>
                    <?= $keyIdx + 1 ?>
                  <?php endif; ?>
                </div>
                <div class="timeline-content">
                  <div class="timeline-label" style="font-weight:700;font-size:0.95rem;">
                    <?= $info['icon'] ?> <?= $info['label'] ?>
                  </div>
                  <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.2rem;">
                    <?= $info['desc'] ?>
                  </div>
                  <?php if ($isActive): ?>
                    <div style="display:inline-block;margin-top:0.35rem;padding:0.2rem 0.6rem;background:var(--primary);color:#fff;font-size:0.75rem;font-weight:700;border-radius:9999px;">
                      Current Status
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Status History Log (From Admin updates) -->
          <?php if (!empty($order['status_history'])): ?>
            <div style="margin-top:2rem;border-top:1px solid var(--border-light);padding-top:1.25rem;">
              <h4 style="font-size:0.9rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);margin-bottom:0.75rem;">Status Updates</h4>
              <div style="display:flex;flex-direction:column;gap:0.75rem;">
                <?php foreach (array_reverse($order['status_history']) as $h): ?>
                  <div style="font-size:0.85rem;display:flex;justify-content:space-between;border-bottom:1px dashed var(--border-light);padding-bottom:0.5rem;">
                    <div>
                      <strong style="text-transform:capitalize;"><?= str_replace('_', ' ', htmlspecialchars($h['status'])) ?></strong>
                      <?php if (!empty($h['remarks'])): ?>
                        <span style="color:var(--text-muted);">— <?= htmlspecialchars($h['remarks']) ?></span>
                      <?php endif; ?>
                    </div>
                    <span style="color:var(--text-muted);font-size:0.8rem;">
                      <?= date('d M Y, g:i A', strtotime($h['changed_at'])) ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Items Ordered Card -->
        <?php if (!empty($order['items'])): ?>
          <div style="background:var(--card-bg);border-radius:var(--radius-lg);padding:1.75rem;box-shadow:var(--shadow);border:1px solid var(--border-light);">
            <h3 class="font-display" style="font-size:1.25rem;margin-bottom:1.25rem;color:var(--foreground);">Items in this Order</h3>
            <?php foreach ($order['items'] as $item):
              $pName = $item['product_name_snapshot'] ?? $item['product_name'] ?? 'Chikki';
              $vLabel= $item['variant_label_snapshot'] ?? $item['variant_label'] ?? '';
              $rate  = (float)($item['unit_price'] ?? $item['selling_price'] ?? 0);
              $qty   = (int)($item['quantity'] ?? 1);
              $lineTotal = (float)($item['line_total'] ?? ($rate * $qty));
            ?>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem 0;border-bottom:1px solid var(--border-light);">
                <div>
                  <div style="font-weight:700;font-size:0.95rem;color:var(--foreground);"><?= htmlspecialchars($pName) ?></div>
                  <div style="font-size:0.825rem;color:var(--text-muted);margin-top:0.15rem;">
                    <?= htmlspecialchars($vLabel) ?> × <?= $qty ?>
                  </div>
                </div>
                <div style="font-weight:700;font-size:0.95rem;color:var(--foreground);">
                  ₹<?= number_format($lineTotal, 2) ?>
                </div>
              </div>
            <?php endforeach; ?>

            <!-- Breakdown -->
            <div style="margin-top:1rem;display:flex;flex-direction:column;gap:0.4rem;font-size:0.88rem;max-width:320px;margin-left:auto;">
              <div style="display:flex;justify-content:space-between;color:var(--text-muted);">
                <span>Subtotal</span>
                <span>₹<?= number_format((float)$order['subtotal'], 2) ?></span>
              </div>
              <?php if ((float)$order['discount_amount'] > 0): ?>
                <div style="display:flex;justify-content:space-between;color:var(--success);">
                  <span>Discount</span>
                  <span>−₹<?= number_format((float)$order['discount_amount'], 2) ?></span>
                </div>
              <?php endif; ?>
              <div style="display:flex;justify-content:space-between;color:var(--text-muted);">
                <span>Shipping</span>
                <span><?= (float)$order['shipping_charge'] == 0 ? '<strong style="color:var(--success);">FREE</strong>' : '₹' . number_format((float)$order['shipping_charge'], 2) ?></span>
              </div>
              <?php if ((float)($order['cod_charge'] ?? 0) > 0): ?>
                <div style="display:flex;justify-content:space-between;color:var(--text-muted);">
                  <span>COD Charge</span>
                  <span>₹<?= number_format((float)$order['cod_charge'], 2) ?></span>
                </div>
              <?php endif; ?>
              <?php if ((float)($order['igst_amount'] ?? 0) > 0): ?>
                <div style="display:flex;justify-content:space-between;color:var(--text-muted);">
                  <span>IGST</span>
                  <span>₹<?= number_format((float)$order['igst_amount'], 2) ?></span>
                </div>
              <?php elseif ((float)($order['cgst_amount'] ?? 0) > 0 || (float)($order['sgst_amount'] ?? 0) > 0): ?>
                <div style="display:flex;justify-content:space-between;color:var(--text-muted);">
                  <span>CGST + SGST</span>
                  <span>₹<?= number_format((float)($order['cgst_amount'] + $order['sgst_amount']), 2) ?></span>
                </div>
              <?php endif; ?>
              <div style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:800;border-top:1.5px solid var(--border);padding-top:0.6rem;color:var(--foreground);">
                <span>Total</span>
                <span>₹<?= number_format((float)($order['total_amount'] ?? $order['total'] ?? 0), 2) ?></span>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Delivery Address Card -->
        <?php if (!empty($order['shipping_address'])): $a = $order['shipping_address']; ?>
          <div style="background:var(--card-bg);border-radius:var(--radius-lg);padding:1.75rem;box-shadow:var(--shadow);border:1px solid var(--border-light);">
            <h3 class="font-display" style="font-size:1.25rem;margin-bottom:0.75rem;color:var(--foreground);">Delivery Address</h3>
            <div style="color:var(--text-muted);line-height:1.75;font-size:0.925rem;">
              <strong style="color:var(--foreground);font-size:1rem;"><?= htmlspecialchars($a['full_name'] ?? $order['full_name']) ?></strong><br>
              <?= htmlspecialchars($a['address_line1'] ?? $a['line1'] ?? '') ?>
              <?= !empty($a['address_line2'] ?? $a['line2']) ? ', ' . htmlspecialchars($a['address_line2'] ?? $a['line2']) : '' ?><br>
              <?= htmlspecialchars($a['city'] ?? '') ?>, <?= htmlspecialchars($a['state'] ?? '') ?> — <?= htmlspecialchars($a['pincode'] ?? '') ?><br>
              📞 <?= htmlspecialchars($a['phone'] ?? $order['phone']) ?>
            </div>
          </div>
        <?php endif; ?>

      </div>
    <?php endif; ?>

  </div>
</section>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>
