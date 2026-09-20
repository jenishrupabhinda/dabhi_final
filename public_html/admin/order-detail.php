<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('admin', 'superadmin', 'employee');
RBAC::requireCan('view_orders');

$orderId = (int)get('id');
if (!$orderId) redirect('/admin/orders.php');

$order = Order::getById($orderId);
if (!$order) { flashSet('error','Order not found.'); redirect('/admin/orders.php'); }

// Handle POST actions
if (isPost()) {
    csrfVerify();
    $action = post('action');

    if ($action === 'update_status') {
        RBAC::requireCan('manage_orders');
        $newStatus = post('new_status');
        $remarks   = post('remarks');
        Order::updateStatus($orderId, $newStatus, Auth::id(), $remarks);

        // Auto-confirm payment for COD on delivery
        if ($newStatus === 'delivered' && $order['payment_method'] === 'cod') {
            Database::query("UPDATE orders SET payment_status='paid' WHERE id=?", [$orderId]);
        }
        flashSet('success', 'Order status updated.');
        redirect('/admin/order-detail.php?id=' . $orderId);
    }

    if ($action === 'update_tracking') {
        RBAC::requireCan('manage_orders');
        ShippingLabel::getOrCreate($orderId, Auth::id());
        ShippingLabel::updateTracking($orderId, post('courier_name'), post('tracking_number'));
        flashSet('success', 'Tracking info saved.');
        redirect('/admin/order-detail.php?id=' . $orderId);
    }
}

// Refresh after redirect
$order   = Order::getById($orderId);
$invoice = Database::fetchOne('SELECT * FROM invoices WHERE order_id = ?', [$orderId]);
$label   = Database::fetchOne('SELECT * FROM shipping_labels WHERE order_id = ?', [$orderId]);

$statuses = ['placed','confirmed','packed','shipped','out_for_delivery','delivered','cancelled','return_requested','returned'];

$pageTitle   = 'Order ' . ($order['order_number'] ?? '');
$pageHeading = 'Order ' . ($order['order_number'] ?? '');
$activePage  = 'orders';
require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <!-- Top bar -->
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
      <h2 style="margin:0;"><?= e($order['order_number'] ?? '') ?></h2>
      <div style="font-size:0.82rem;color:var(--dc-muted);"><?= date('d M Y, g:i A', strtotime($order['placed_at'] ?? 'now')) ?></div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a href="<?= url('admin/invoice.php?order_id=' . (int)$orderId) ?>" target="_blank" class="btn btn-ghost btn-sm">🧾 Invoice</a>
      <a href="<?= url('admin/label.php?order_id=' . (int)$orderId) ?>" target="_blank" class="btn btn-ghost btn-sm">🏷 Label</a>
      <a href="<?= url('admin/orders.php') ?>" class="btn btn-ghost btn-sm">← Back</a>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

    <!-- LEFT -->
    <div>

      <!-- Items -->
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><h3 class="card-title">Order Items</h3></div>
        <table class="admin-table">
          <thead><tr><th>Product</th><th>Weight</th><th>Qty</th><th>Rate</th><th>GST</th><th>Total</th></tr></thead>
          <tbody>
          <?php foreach ($order['items'] as $item): ?>
          <tr>
            <td><?= e($item['product_name_snapshot']) ?></td>
            <td><?= e($item['variant_label_snapshot']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td><?= formatINR($item['unit_price']) ?></td>
            <td><?= formatINR($item['gst_amount']) ?></td>
            <td><strong><?= formatINR($item['line_total']) ?></strong></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <!-- Totals -->
        <div style="margin-top:10px;display:flex;flex-direction:column;gap:4px;font-size:0.88rem;max-width:280px;margin-left:auto;">
          <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><span><?= formatINR($order['subtotal']) ?></span></div>
          <?php if ((float)$order['discount_amount'] > 0): ?>
          <div style="display:flex;justify-content:space-between;color:var(--dc-success);"><span>Discount</span><span>−<?= formatINR($order['discount_amount']) ?></span></div>
          <?php endif; ?>
          <div style="display:flex;justify-content:space-between;"><span>Shipping</span><span><?= formatINR($order['shipping_charge']) ?></span></div>
          <?php if ((float)$order['cod_charge'] > 0): ?>
          <div style="display:flex;justify-content:space-between;"><span>COD</span><span><?= formatINR($order['cod_charge']) ?></span></div>
          <?php endif; ?>
          <?php if ((float)$order['igst_amount'] > 0): ?>
          <div style="display:flex;justify-content:space-between;"><span>IGST</span><span><?= formatINR($order['igst_amount']) ?></span></div>
          <?php else: ?>
          <div style="display:flex;justify-content:space-between;"><span>CGST + SGST</span><span><?= formatINR((float)$order['cgst_amount'] + (float)$order['sgst_amount']) ?></span></div>
          <?php endif; ?>
          <div style="display:flex;justify-content:space-between;font-weight:800;border-top:1px solid var(--dc-border);padding-top:4px;"><span>Total</span><span><?= formatINR($order['total_amount']) ?></span></div>
        </div>
      </div>

      <!-- Status timeline -->
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><h3 class="card-title">Status History</h3></div>
        <div class="tracking-timeline">
          <?php foreach (array_reverse($order['status_history']) as $h): ?>
          <div class="tracking-step">
            <div class="tracking-dot"></div>
            <div>
              <div style="font-weight:600;text-transform:capitalize;"><?= str_replace('_',' ',e($h['status'])) ?></div>
              <div style="font-size:0.75rem;color:var(--dc-muted);">
                <?= date('d M Y, g:i A', strtotime($h['changed_at'])) ?>
                <?= $h['changed_by_name'] ? ' by ' . e($h['changed_by_name']) : '' ?>
                <?= $h['remarks'] ? ' · ' . e($h['remarks']) : '' ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Tracking info -->
      <?php if (RBAC::can('manage_orders')): ?>
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><h3 class="card-title">Tracking / Shipping</h3></div>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="update_tracking">
          <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
              <label class="form-label">Courier Name</label>
              <input type="text" name="courier_name" class="form-control" value="<?= e($label['courier_name'] ?? '') ?>" placeholder="e.g. Delhivery">
            </div>
            <div class="form-group">
              <label class="form-label">Tracking Number</label>
              <input type="text" name="tracking_number" class="form-control" value="<?= e($label['tracking_number'] ?? '') ?>">
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">Save Tracking</button>
        </form>
      </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT -->
    <div>
      <!-- Status update -->
      <?php if (RBAC::can('manage_orders')): ?>
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><h3 class="card-title">Update Status</h3></div>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="update_status">
          <div class="form-group">
            <select name="new_status" class="form-control">
              <?php foreach ($statuses as $s): ?>
              <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <input type="text" name="remarks" class="form-control" placeholder="Remarks (optional)">
          </div>
          <button type="submit" class="btn btn-primary btn-block">Update Status</button>
        </form>
      </div>
      <?php endif; ?>

      <!-- Customer -->
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><h3 class="card-title">Customer</h3></div>
        <div style="font-size:0.88rem;">
          <div><strong><?= e($order['full_name']) ?></strong></div>
          <div><?= e($order['email']) ?></div>
          <div><?= e($order['phone']) ?></div>
        </div>
      </div>

      <!-- Delivery address -->
      <?php if ($order['shipping_address']): $a = $order['shipping_address']; ?>
      <div class="card">
        <div class="card-header"><h3 class="card-title">Shipping Address</h3></div>
        <div style="font-size:0.85rem;">
          <strong><?= e($a['full_name']) ?></strong> · <?= e($a['phone']) ?><br>
          <?= e($a['address_line1']) ?><?= $a['address_line2'] ? ', ' . e($a['address_line2']) : '' ?><br>
          <?= e($a['city']) ?>, <?= e($a['state']) ?> – <?= e($a['pincode']) ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/partials/page-end.php'; ?>
