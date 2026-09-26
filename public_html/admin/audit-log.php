<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('superadmin', 'admin');
RBAC::requireCan('view_audit_log');

$activePage  = 'audit-log';
$pageHeading = 'Audit Log';
$pageTitle   = 'Audit Log';

$filterAction = get('action_name', '');
$filterEntity = get('entity_type', '');
$page         = max(1, (int)get('page', '1'));
$limit        = 30;
$offset       = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];

if ($filterAction !== '') {
    $where[]  = 'al.action LIKE ?';
    $params[] = '%' . $filterAction . '%';
}
if ($filterEntity !== '') {
    $where[]  = 'al.entity_type = ?';
    $params[] = $filterEntity;
}

$whereSql = implode(' AND ', $where);

$totalCount = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM audit_logs al WHERE {$whereSql}", $params)['c'] ?? 0);
$totalPages = max(1, (int)ceil($totalCount / $limit));

$logs = Database::fetchAll(
    "SELECT al.*, u.full_name AS operator_name, u.email AS operator_email, u.role AS operator_role
     FROM audit_logs al
     LEFT JOIN users u ON u.id = al.user_id
     WHERE {$whereSql}
     ORDER BY al.id DESC
     LIMIT {$limit} OFFSET {$offset}",
    $params
);

$entityTypes = Database::fetchAll("SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type ASC");

require_once __DIR__ . '/partials/page-start.php';
?>

<div class="admin-content">
  <?php flashRender(); ?>

  <div style="margin-bottom:20px;">
    <p style="margin:0;color:var(--adm-text-muted);font-size:0.9rem;">Complete traceability log of staff operations, status changes, and data modifications.</p>
  </div>

  <!-- Filter -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px 20px;">
      <form method="GET" class="adm-filter-bar">
        <div class="form-group" style="margin:0;flex:1;min-width:180px;">
          <label class="form-label">Search Action</label>
          <input type="text" name="action_name" class="form-control" placeholder="e.g. update, create, delete" value="<?= e($filterAction) ?>">
        </div>
        <div class="form-group" style="margin:0;flex:1;min-width:180px;">
          <label class="form-label">Entity Type</label>
          <select name="entity_type" class="form-control">
            <option value="">All Entities</option>
            <?php foreach ($entityTypes as $et): ?>
              <option value="<?= e($et['entity_type']) ?>" <?= $filterEntity === $et['entity_type'] ? 'selected' : '' ?>>
                <?= ucfirst(e($et['entity_type'])) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="adm-filter-actions">
          <button type="submit" class="btn btn-primary" style="height:42px;">Filter</button>
          <a href="<?= url('admin/audit-log.php') ?>" class="btn btn-secondary" style="height:42px;">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Activity Feed (<?= number_format($totalCount) ?> events)</h3>
    </div>

    <?php if (empty($logs)): ?>
      <p class="text-muted text-center" style="padding:28px;">No audit log records matching the filter.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="dc-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Operator</th>
              <th>Action</th>
              <th>Target Entity</th>
              <th>Change Details</th>
              <th>IP Address</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
              <td style="font-size:0.8rem;white-space:nowrap;color:var(--dc-muted);">
                <?= date('d M Y, g:i:s a', strtotime($log['created_at'])) ?>
              </td>
              <td>
                <div style="font-weight:600;"><?= e($log['operator_name'] ?: 'System') ?></div>
                <div style="font-size:0.75rem;color:var(--dc-muted);">
                  <?= e($log['operator_email'] ?: '') ?> <?= $log['operator_role'] ? '('.e($log['operator_role']).')' : '' ?>
                </div>
              </td>
              <td>
                <span class="badge badge-secondary"><?= e($log['action']) ?></span>
              </td>
              <td>
                <strong><?= ucfirst(e($log['entity_type'])) ?></strong>
                <?= $log['entity_id'] ? '#' . (int)$log['entity_id'] : '' ?>
              </td>
              <td style="max-width:320px;font-size:0.8rem;">
                <?php if ($log['old_value'] || $log['new_value']): ?>
                  <details style="cursor:pointer;">
                    <summary style="color:var(--dc-terracotta);font-weight:500;">View changes</summary>
                    <pre style="background:var(--dc-bg-soft);padding:8px;border-radius:4px;overflow-x:auto;font-size:0.75rem;margin-top:4px;"><?php 
                      if ($log['old_value']) echo "Old:\n" . json_encode(json_decode($log['old_value']), JSON_PRETTY_PRINT) . "\n\n";
                      if ($log['new_value']) echo "New:\n" . json_encode(json_decode($log['new_value']), JSON_PRETTY_PRINT);
                    ?></pre>
                  </details>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:0.75rem;color:var(--dc-muted);"><?= e($log['ip_address'] ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
      <div style="display:flex;justify-content:center;gap:6px;padding:16px;">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a href="?<?= http_build_query(['page' => $p, 'action_name' => $filterAction, 'entity_type' => $filterEntity]) ?>"
             class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-ghost' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
