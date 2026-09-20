<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('buyer');

$userId  = Auth::id();
$errors  = [];
$success = '';

// Handle add/edit
if (isPost()) {
    csrfVerify();
    $action = post('action');

    if ($action === 'save') {
        $id        = (int)post('id');
        $isDefault = post('is_default') ? 1 : 0;

        $fields = [
            'full_name'    => post('full_name'),
            'phone'        => post('phone'),
            'address_line1'=> post('address_line1'),
            'address_line2'=> post('address_line2'),
            'landmark'     => post('landmark'),
            'city'         => post('city'),
            'state'        => post('state'),
            'pincode'      => post('pincode'),
            'address_type' => post('address_type', 'home'),
            'is_default'   => $isDefault,
        ];

        if (!$fields['full_name']) $errors[] = 'Name is required.';
        if (!$fields['phone'])     $errors[] = 'Phone is required.';
        if (!$fields['address_line1']) $errors[] = 'Address is required.';
        if (!$fields['city'])      $errors[] = 'City is required.';
        if (!$fields['state'])     $errors[] = 'State is required.';
        if (!$fields['pincode'])   $errors[] = 'Pincode is required.';

        if (empty($errors)) {
            if ($isDefault) {
                Database::query('UPDATE addresses SET is_default = 0 WHERE user_id = ?', [$userId]);
            }
            if ($id) {
                // Make sure it belongs to this user
                $existing = Database::fetchOne('SELECT id FROM addresses WHERE id=? AND user_id=?', [$id, $userId]);
                if (!$existing) { $errors[] = 'Address not found.'; }
                else {
                    $set = implode(', ', array_map(fn($k) => "$k=?", array_keys($fields)));
                    Database::query("UPDATE addresses SET $set WHERE id=? AND user_id=?",
                        array_merge(array_values($fields), [$id, $userId]));
                    $success = 'Address updated.';
                }
            } else {
                $fields['user_id'] = $userId;
                $cols = implode(', ', array_keys($fields));
                $vals = implode(', ', array_fill(0, count($fields), '?'));
                Database::query("INSERT INTO addresses ($cols) VALUES ($vals)", array_values($fields));
                $success = 'Address added.';
            }

            if ($success) {
                $redir = get('redirect');
                if ($redir === 'checkout') redirect('/checkout.php');
                flashSet('success', $success);
                redirect('/account/addresses.php');
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)post('id');
        Database::query('DELETE FROM addresses WHERE id=? AND user_id=?', [$id, $userId]);
        flashSet('success', 'Address removed.');
        redirect('/account/addresses.php');
    }

    if ($action === 'set_default') {
        $id = (int)post('id');
        Database::query('UPDATE addresses SET is_default=0 WHERE user_id=?', [$userId]);
        Database::query('UPDATE addresses SET is_default=1 WHERE id=? AND user_id=?', [$id, $userId]);
        redirect('/account/addresses.php');
    }
}

$addresses  = Database::fetchAll('SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, id DESC', [$userId]);
$editId     = (int)get('edit');
$editAddr   = $editId ? Database::fetchOne('SELECT * FROM addresses WHERE id=? AND user_id=?', [$editId, $userId]) : null;

$indiaStates = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana',
  'Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya',
  'Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura',
  'Uttar Pradesh','Uttarakhand','West Bengal','Andaman & Nicobar','Chandigarh','DNH & DD','Delhi','Jammu & Kashmir',
  'Ladakh','Lakshadweep','Puducherry'];

$pageTitle = 'My Addresses';
$pageDesc  = 'Manage your saved delivery addresses.';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="container" style="padding-top:var(--dc-space-xl);padding-bottom:var(--dc-space-xl);max-width:760px;">

  <h1 style="margin-bottom:4px;">My Addresses</h1>
  <p class="text-muted" style="margin-bottom:24px;">Manage your saved delivery addresses.</p>

  <?php flashRender(); ?>
  <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= e($e) ?></div><?php endforeach; ?>

  <!-- Add/Edit form -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h3 class="card-title"><?= $editAddr ? 'Edit Address' : 'Add New Address' ?></h3></div>
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $editAddr['id'] ?? 0 ?>">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" value="<?= e($editAddr['full_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone *</label>
          <input type="tel" name="phone" class="form-control" value="<?= e($editAddr['phone'] ?? '') ?>" required>
        </div>
        <div class="form-group" style="grid-column:1/-1;">
          <label class="form-label">Address Line 1 *</label>
          <input type="text" name="address_line1" class="form-control" value="<?= e($editAddr['address_line1'] ?? '') ?>" required>
        </div>
        <div class="form-group" style="grid-column:1/-1;">
          <label class="form-label">Address Line 2</label>
          <input type="text" name="address_line2" class="form-control" value="<?= e($editAddr['address_line2'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Landmark</label>
          <input type="text" name="landmark" class="form-control" value="<?= e($editAddr['landmark'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">City *</label>
          <input type="text" name="city" class="form-control" value="<?= e($editAddr['city'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">State *</label>
          <select name="state" class="form-control" required>
            <option value="">Select State</option>
            <?php foreach ($indiaStates as $st): ?>
            <option value="<?= $st ?>" <?= ($editAddr['state'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Pincode *</label>
          <input type="text" name="pincode" class="form-control" maxlength="6" pattern="\d{6}" value="<?= e($editAddr['pincode'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Type</label>
          <select name="address_type" class="form-control">
            <?php foreach (['home','office','other'] as $t): ?>
            <option value="<?= $t ?>" <?= ($editAddr['address_type'] ?? 'home') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="is_default" value="1"
              <?= ($editAddr['is_default'] ?? 0) ? 'checked' : '' ?> style="accent-color:var(--dc-terracotta);">
            Make this my default address
          </label>
        </div>
      </div>
      <div style="display:flex;gap:8px;margin-top:12px;">
        <button type="submit" class="btn btn-primary"><?= $editAddr ? 'Update Address' : 'Save Address' ?></button>
        <?php if ($editAddr): ?>
        <a href="<?= url('account/addresses.php') ?>" class="btn btn-ghost">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Saved addresses -->
  <?php if (!empty($addresses)): ?>
  <h3 style="margin-bottom:12px;">Saved Addresses</h3>
  <div style="display:flex;flex-direction:column;gap:12px;">
    <?php foreach ($addresses as $addr): ?>
    <div class="card" style="<?= $addr['is_default'] ? 'border:2px solid var(--dc-terracotta);' : '' ?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
        <div style="font-size:0.88rem;">
          <div style="font-weight:700;"><?= e($addr['full_name']) ?> · <?= e($addr['phone']) ?>
            <?php if ($addr['is_default']): ?><span class="badge badge-primary" style="margin-left:6px;">Default</span><?php endif; ?>
          </div>
          <?= e($addr['address_line1']) ?><?= $addr['address_line2'] ? ', ' . e($addr['address_line2']) : '' ?><br>
          <?= $addr['landmark'] ? 'Near ' . e($addr['landmark']) . ', ' : '' ?>
          <?= e($addr['city']) ?>, <?= e($addr['state']) ?> – <?= e($addr['pincode']) ?>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <a href="<?= url('account/addresses.php?edit=' . (int)$addr['id']) ?>" class="btn btn-ghost btn-sm">Edit</a>
          <?php if (!$addr['is_default']): ?>
          <form method="POST" style="display:inline;">
            <?= csrfField() ?><input type="hidden" name="action" value="set_default">
            <input type="hidden" name="id" value="<?= $addr['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm">Set Default</button>
          </form>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this address?')">
            <?= csrfField() ?><input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $addr['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--dc-danger);">Remove</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
