<?php
/**
 * account.php — Customer Account Page
 */
require_once __DIR__ . '/../includes/bootstrap.php';

// Redirect if not logged in (must execute before sending any HTML)
if (!Auth::check()) {
    header('Location: auth.php?redirect=' . urlencode('account.php'));
    exit;
}

$pageTitle = 'My Account — Dabhi Chikki';
require_once __DIR__ . '/partials/_header.php';

$section = $_GET['section'] ?? 'orders';
$user    = null;
$orders  = [];
$addresses = [];

try {
    $user      = Database::fetchOne('SELECT * FROM users WHERE id = ?', [Auth::id()]);
    $orders    = Database::fetchAll(
        'SELECT * FROM orders WHERE user_id = ? ORDER BY placed_at DESC LIMIT 50',
        [Auth::id()]
    );
    $addresses = Database::fetchAll(
        'SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC',
        [Auth::id()]
    );
} catch (\Throwable $e) {}

$statusColors = [
    'placed'            => '#d97706',
    'confirmed'         => '#0284c7',
    'packed'            => '#7c3aed',
    'shipped'           => '#2563eb',
    'out_for_delivery'  => '#9333ea',
    'delivered'         => '#16a34a',
    'cancelled'         => '#dc2626',
    'return_requested'  => '#ea580c',
    'returned'          => '#6b7280',
];
?>

<section class="account-page">
  <div class="container">
    <h1 style="font-size:1.75rem;margin-bottom:1.75rem">My Account</h1>

    <div class="account-grid">
      <!-- Sidebar -->
      <nav class="account-sidebar">
        <div style="text-align:center;padding:1rem 0 1.25rem;border-bottom:1px solid var(--border);margin-bottom:0.75rem">
          <div style="width:56px;height:56px;border-radius:50%;background:var(--primary-light);margin:0 auto 0.625rem;display:flex;align-items:center;justify-content:center">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" width="28" height="28">
              <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <div style="font-weight:700;font-size:0.9375rem"><?= htmlspecialchars($user['full_name'] ?? 'Customer') ?></div>
          <div style="font-size:0.8rem;color:var(--text-muted)"><?= htmlspecialchars($user['email'] ?? '') ?></div>
        </div>

        <?php if (in_array(Auth::role(), ['superadmin', 'admin', 'employee'], true)): ?>
          <a href="admin/index.php" class="account-nav-item" style="color:#541f21;font-weight:700;background:rgba(246,220,148,0.35);border:1px solid rgba(199,97,61,0.3);margin-bottom:0.75rem;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a5 5 0 0 0-5 5v2a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V7a5 5 0 0 0-5-5z"></path><circle cx="12" cy="14" r="2"></circle></svg>
            ⚡ Admin Dashboard
          </a>
        <?php endif; ?>

        <a href="?section=orders" class="account-nav-item <?= $section === 'orders' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
          My Orders
        </a>
        <a href="?section=addresses" class="account-nav-item <?= $section === 'addresses' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
          Saved Addresses
        </a>
        <a href="?section=profile" class="account-nav-item <?= $section === 'profile' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Profile
        </a>
        <hr class="divider" style="margin:0.5rem 0">
        <a href="logout.php" class="account-nav-item" style="color:var(--error)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Sign Out
        </a>
      </nav>

      <!-- Content -->
      <div class="account-content">
        <?php if ($section === 'orders'): ?>
          <div style="background:var(--card-bg);border-radius:var(--radius);padding:1.5rem;box-shadow:var(--shadow-sm)">
            <h3 style="margin-bottom:1.25rem">My Orders</h3>
            <?php if (empty($orders)): ?>
              <div style="text-align:center;padding:3rem 0;color:var(--text-muted)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="56" height="56" style="margin:0 auto 1rem;display:block;opacity:0.3">
                  <path d="M6 2 3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/>
                </svg>
                <p>No orders yet. <a href="index.php" style="color:var(--primary)">Start shopping!</a></p>
              </div>
            <?php else: ?>
              <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:0.9rem">
                  <thead>
                    <tr style="border-bottom:2px solid var(--border)">
                      <th style="padding:0.625rem;text-align:left">Order #</th>
                      <th style="padding:0.625rem;text-align:left">Date</th>
                      <th style="padding:0.625rem;text-align:right">Total</th>
                      <th style="padding:0.625rem;text-align:center">Status</th>
                      <th style="padding:0.625rem"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($orders as $ord):
                      $clr = $statusColors[$ord['status']] ?? '#6b7280';
                    ?>
                      <tr style="border-bottom:1px solid var(--border-light)">
                        <td style="padding:0.875rem 0.625rem;font-weight:700">#<?= htmlspecialchars($ord['order_number']) ?></td>
                        <td style="padding:0.875rem 0.625rem;color:var(--text-muted)"><?= !empty($ord['placed_at']) ? date('d M Y', strtotime($ord['placed_at'])) : '—' ?></td>
                        <td style="padding:0.875rem 0.625rem;text-align:right;font-weight:700">₹<?= number_format((float)($ord['total_amount'] ?? $ord['total'] ?? 0), 2) ?></td>
                        <td style="padding:0.875rem 0.625rem;text-align:center">
                          <span style="background:<?= $clr ?>18;color:<?= $clr ?>;padding:0.25rem 0.75rem;border-radius:var(--radius-full);font-size:0.8rem;font-weight:700;text-transform:capitalize">
                            <?= str_replace('_', ' ', ucfirst($ord['status'])) ?>
                          </span>
                        </td>
                        <td style="padding:0.875rem 0.625rem;text-align:right;white-space:nowrap;">
                          <a href="track.php?order=<?= urlencode($ord['order_number']) ?>" style="color:var(--primary);font-size:0.875rem;font-weight:700;margin-right:0.75rem">Track</a>
                          <a href="invoice.php?order=<?= urlencode($ord['order_number']) ?>" target="_blank" style="color:var(--text-muted);font-size:0.85rem;font-weight:500;">Invoice 🧾</a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

        <?php elseif ($section === 'addresses'): ?>
          <div style="background:var(--card-bg);border-radius:var(--radius);padding:1.5rem;box-shadow:var(--shadow-sm)">
            <h3 style="margin-bottom:1.25rem">Saved Addresses</h3>
            <?php if (empty($addresses)): ?>
              <p style="color:var(--text-muted)">No saved addresses yet. They'll be saved when you place an order.</p>
            <?php else: ?>
              <div style="display:grid;gap:1rem">
                <?php foreach ($addresses as $addr): ?>
                  <div style="border:1.5px solid var(--border);border-radius:var(--radius);padding:1.125rem;position:relative">
                    <?php if ($addr['is_default']): ?>
                      <span class="badge badge-primary" style="position:absolute;top:0.75rem;right:0.75rem">Default</span>
                    <?php endif; ?>
                    <div style="font-weight:600;margin-bottom:0.25rem"><?= htmlspecialchars($addr['full_name']) ?></div>
                    <div style="color:var(--text-muted);font-size:0.9rem;line-height:1.7">
                      <?= htmlspecialchars($addr['line1']) ?>
                      <?php if ($addr['line2']): ?>, <?= htmlspecialchars($addr['line2']) ?><?php endif; ?>
                      <br><?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['state']) ?> — <?= htmlspecialchars($addr['pincode']) ?>
                      <br>📞 <?= htmlspecialchars($addr['phone']) ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        <?php elseif ($section === 'profile'): ?>
          <div style="background:var(--card-bg);border-radius:var(--radius);padding:1.5rem;box-shadow:var(--shadow-sm)">
            <h3 style="margin-bottom:1.25rem">Profile Details</h3>
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" readonly>
            </div>
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
            </div>
            <div class="form-group">
              <label class="form-label">Phone Number</label>
              <input type="tel" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" readonly>
            </div>
            <div class="alert alert-info">Profile editing will be available soon.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>
