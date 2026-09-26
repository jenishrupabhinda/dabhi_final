<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('admin', 'superadmin', 'employee');
RBAC::requireCan('manage_products');

if (isPost()) {
    csrfVerify();
    $action    = post('action');
    $reviewId  = (int)post('review_id');

    if ($action === 'approve') {
        Database::query('UPDATE reviews SET is_approved=1 WHERE id=?', [$reviewId]);
        flashSet('success', 'Review approved.');
    }
    if ($action === 'delete') {
        Database::query('DELETE FROM reviews WHERE id=?', [$reviewId]);
        flashSet('success', 'Review deleted.');
    }
    redirect('/admin/reviews.php');
}

$pending = Database::fetchAll(
    "SELECT r.*, u.full_name, u.email, p.name AS product_name
     FROM reviews r
     JOIN users u ON u.id = r.user_id
     JOIN products p ON p.id = r.product_id
     WHERE r.is_approved = 0 ORDER BY r.created_at DESC"
);

$approved = Database::fetchAll(
    "SELECT r.*, u.full_name, u.email, p.name AS product_name
     FROM reviews r
     JOIN users u ON u.id = r.user_id
     JOIN products p ON p.id = r.product_id
     WHERE r.is_approved = 1 ORDER BY r.created_at DESC LIMIT 50"
);

$pageTitle = 'Reviews';
$pageHeading = 'Reviews';
require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <?php if ($pending): ?>
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h3 class="card-title">⏳ Pending Approval (<?= count($pending) ?>)</h3></div>
    <div class="card-body" style="padding:0 20px;">
      <?php foreach ($pending as $r): ?>
      <div style="border-bottom:1px solid var(--adm-border-subtle);padding:16px 0;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
          <div>
            <strong><?= e($r['product_name']) ?></strong> · ⭐ <?= $r['rating'] ?>/5<br>
            <span style="font-size:0.8rem;color:var(--adm-text-muted);"><?= e($r['full_name']) ?> · <?= date('d M Y', strtotime($r['created_at'])) ?></span>
            <?php if ($r['comment']): ?><blockquote style="margin:8px 0 0;font-style:italic;color:var(--adm-text-main);font-size:0.9rem;"><?= e($r['comment']) ?></blockquote><?php endif; ?>
          </div>
          <div style="display:flex;gap:6px;">
            <form method="POST" style="display:inline;">
              <?= csrfField() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="review_id" value="<?= $r['id'] ?>">
              <button type="submit" class="btn btn-primary btn-sm">✅ Approve</button>
            </form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this review?')">
              <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="review_id" value="<?= $r['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">🗑 Delete</button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><h3 class="card-title">✅ Approved Reviews</h3></div>
    <?php if (empty($approved)): ?>
      <p class="text-muted text-center" style="padding:32px;">No approved reviews yet.</p>
    <?php else: ?>
    <div class="card-body" style="padding:0 20px;">
      <?php foreach ($approved as $r): ?>
      <div style="border-bottom:1px solid var(--adm-border-subtle);padding:14px 0;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
        <div>
          <strong><?= e($r['product_name']) ?></strong> · ⭐ <?= $r['rating'] ?>/5<br>
          <span style="font-size:0.78rem;color:var(--adm-text-muted);"><?= e($r['full_name']) ?> · <?= date('d M Y', strtotime($r['created_at'])) ?></span>
          <?php if ($r['comment']): ?><div style="font-size:0.86rem;margin-top:4px;color:var(--adm-text-main);"><?= e($r['comment']) ?></div><?php endif; ?>
        </div>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this review?')">
          <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="review_id" value="<?= $r['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm">🗑</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/partials/page-end.php'; ?>
