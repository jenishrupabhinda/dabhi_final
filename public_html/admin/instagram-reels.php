<?php
/**
 * admin/instagram-reels.php
 * Instagram Video Links & Reels Showcase Manager
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole('admin', 'superadmin', 'employee');

$activePage = 'instagram-reels';
$pageTitle = 'Instagram Reels Manager';
$pageHeading = 'Instagram Reels & Video Links';
$pageBreadcrumbs = [
    ['label' => 'Storefront CMS', 'url' => '/admin/modules.php'],
    ['label' => 'Instagram Reels']
];

// Handle POST actions
if (isPost()) {
    csrfVerify();
    $action = post('action');

    // 1. Update Global Instagram Section Settings
    if ($action === 'save_section_settings') {
        $moduleActive = post('module_instagram') ? '1' : '0';
        $handle       = trim(post('instagram_handle', '@dabhichikki'));
        $tagline      = trim(post('instagram_tagline', 'Follow our craft'));
        $profileUrl   = trim(post('instagram_profile_url', 'https://instagram.com'));
        $btnText      = trim(post('instagram_button_text', 'Follow'));

        setSetting('module_instagram', $moduleActive);
        setSetting('instagram_handle', $handle);
        setSetting('instagram_tagline', $tagline);
        setSetting('instagram_profile_url', $profileUrl);
        setSetting('instagram_button_text', $btnText);

        flashSet('success', 'Instagram section settings saved successfully.');
        redirect('/admin/instagram-reels.php');
    }

    // 2. Add New Reel
    if ($action === 'add_reel') {
        $title        = trim(post('title', ''));
        $tagLabel     = trim(post('tag_label', 'Pure Jaggery'));
        $videoUrl     = trim(post('video_url', ''));
        $imageUrl     = trim(post('image_url', ''));
        $instagramUrl = trim(post('instagram_url', 'https://instagram.com'));
        $sortOrder    = (int) post('sort_order', 0);
        $isActive     = post('is_active') ? 1 : 0;

        // Handle uploaded video file if provided
        if (!empty($_FILES['video_file']['name']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['mp4', 'webm', 'mov'])) {
                $uploadDir = ROOT_PATH . '/public_html/assets/videos';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = 'reel-' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadDir . '/' . $filename)) {
                    $videoUrl = 'assets/videos/' . $filename;
                }
            }
        }

        // Handle uploaded thumbnail image if provided
        if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $uploadDir = ROOT_PATH . '/public_html/assets/images/reels';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = 'reel-' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . '/' . $filename)) {
                    $imageUrl = 'assets/images/reels/' . $filename;
                }
            }
        }

        if (empty($title)) {
            flashSet('error', 'Reel title is required.');
        } elseif (empty($imageUrl) && empty($videoUrl)) {
            flashSet('error', 'Please provide a thumbnail image or video.');
        } else {
            if (empty($imageUrl)) {
                $imageUrl = 'assets/images/reels/reel-1.jpg'; // default placeholder
            }
            Database::query(
                "INSERT INTO `instagram_reels` (title, tag_label, video_url, image_url, instagram_url, sort_order, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$title, $tagLabel, $videoUrl, $imageUrl, $instagramUrl, $sortOrder, $isActive]
            );
            flashSet('success', 'Instagram reel added successfully.');
        }
        redirect('/admin/instagram-reels.php');
    }

    // 3. Edit Reel
    if ($action === 'edit_reel') {
        $reelId       = (int) post('reel_id');
        $title        = trim(post('title', ''));
        $tagLabel     = trim(post('tag_label', 'Pure Jaggery'));
        $videoUrl     = trim(post('video_url', ''));
        $imageUrl     = trim(post('image_url', ''));
        $instagramUrl = trim(post('instagram_url', 'https://instagram.com'));
        $sortOrder    = (int) post('sort_order', 0);
        $isActive     = post('is_active') ? 1 : 0;

        // Handle uploaded video file
        if (!empty($_FILES['video_file']['name']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['mp4', 'webm', 'mov'])) {
                $uploadDir = ROOT_PATH . '/public_html/assets/videos';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = 'reel-' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadDir . '/' . $filename)) {
                    $videoUrl = 'assets/videos/' . $filename;
                }
            }
        }

        // Handle uploaded image file
        if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $uploadDir = ROOT_PATH . '/public_html/assets/images/reels';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = 'reel-' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . '/' . $filename)) {
                    $imageUrl = 'assets/images/reels/' . $filename;
                }
            }
        }

        if (empty($title)) {
            flashSet('error', 'Reel title is required.');
        } else {
            Database::query(
                "UPDATE `instagram_reels` 
                 SET title = ?, tag_label = ?, video_url = ?, image_url = ?, instagram_url = ?, sort_order = ?, is_active = ?
                 WHERE id = ?",
                [$title, $tagLabel, $videoUrl, $imageUrl, $instagramUrl, $sortOrder, $isActive, $reelId]
            );
            flashSet('success', 'Reel updated successfully.');
        }
        redirect('/admin/instagram-reels.php');
    }

    // 4. Toggle Active Status
    if ($action === 'toggle_active') {
        $reelId = (int) post('reel_id');
        Database::query("UPDATE `instagram_reels` SET is_active = 1 - is_active WHERE id = ?", [$reelId]);
        flashSet('success', 'Reel status updated.');
        redirect('/admin/instagram-reels.php');
    }

    // 5. Delete Reel
    if ($action === 'delete_reel') {
        $reelId = (int) post('reel_id');
        Database::query("DELETE FROM `instagram_reels` WHERE id = ?", [$reelId]);
        flashSet('success', 'Instagram reel deleted.');
        redirect('/admin/instagram-reels.php');
    }
}

// Fetch all reels
$reels = Database::fetchAll("SELECT * FROM `instagram_reels` ORDER BY sort_order ASC, id ASC");

// Fetch settings
$moduleInstagram    = settingEnabled('module_instagram', '1');
$instagramHandle    = getSetting('instagram_handle', '@dabhichikki');
$instagramTagline   = getSetting('instagram_tagline', 'Follow our craft');
$instagramProfile   = getSetting('instagram_profile_url', 'https://instagram.com');
$instagramButtonText = getSetting('instagram_button_text', 'Follow');

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
            📸 Social Commerce
          </span>
          <span style="font-size: 0.8rem; color: rgba(255,255,255,0.7); font-family: var(--dc-font-mono);">
            <?= count($reels) ?> Active Videos Loaded
          </span>
        </div>
        <h2 style="font-family: var(--dc-font-heading); font-size: 1.5rem; margin: 0 0 6px 0; color: #ffffff;">
          Instagram Video Links &amp; Reels
        </h2>
        <p style="margin: 0; color: rgba(255,255,255,0.8); font-size: 0.9rem; max-width: 620px;">
          Manage the video reels and external Instagram links shown in the storefront carousel. Customers can view the videos directly and tap to visit your official Instagram posts.
        </p>
      </div>
      <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-primary" onclick="openAddReelModal()" style="box-shadow: 0 4px 14px rgba(199, 97, 61, 0.4);">
          <span style="font-size: 1.1rem; line-height: 1;">＋</span> Add New Reel
        </button>
        <a href="<?= url() ?>#reels-section" target="_blank" class="btn btn-secondary" style="border: none;">
          👁️ Preview on Store
        </a>
      </div>
    </div>
  </div>

  <!-- Row: Global Instagram Settings Card -->
  <div class="card" style="margin-bottom: 28px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h3 class="card-title" style="margin: 0; font-size: 1.05rem;">⚙️ Instagram Section Display Settings</h3>
        <span style="font-size: 0.8rem; color: var(--dc-muted);">Control how the social showcase header appears on the homepage</span>
      </div>
      <div>
        <span class="badge <?= $moduleInstagram ? 'badge-success' : 'badge-danger' ?>" style="font-size: 0.82rem; padding: 5px 12px;">
          <?= $moduleInstagram ? '● Section Active on Homepage' : '○ Section Hidden on Homepage' ?>
        </span>
      </div>
    </div>
    <div class="card-body">
      <form method="POST" style="margin: 0;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save_section_settings">
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; align-items: flex-end;">
          <div class="form-group" style="margin: 0;">
            <label class="form-label">Master Showcase Toggle</label>
            <label class="dc-switch-label" style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 8px 0;">
              <input type="checkbox" name="module_instagram" value="1" <?= $moduleInstagram ? 'checked' : '' ?> class="dc-switch-input">
              <span class="dc-switch-slider"></span>
              <span style="font-weight: 600; font-size: 0.9rem; color: var(--dc-black);">Show Reels on Homepage</span>
            </label>
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="instagram_handle">Account Handle / Title</label>
            <input type="text" id="instagram_handle" name="instagram_handle" class="form-control" value="<?= e($instagramHandle) ?>" placeholder="@dabhichikki" required>
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="instagram_tagline">Eyebrow Tag / Subtitle</label>
            <input type="text" id="instagram_tagline" name="instagram_tagline" class="form-control" value="<?= e($instagramTagline) ?>" placeholder="Follow">
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="instagram_profile_url">Instagram Profile URL</label>
            <input type="url" id="instagram_profile_url" name="instagram_profile_url" class="form-control" value="<?= e($instagramProfile) ?>" placeholder="https://instagram.com/dabhichikki">
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="instagram_button_text">Button CTA Label</label>
            <input type="text" id="instagram_button_text" name="instagram_button_text" class="form-control" value="<?= e($instagramButtonText) ?>" placeholder="Follow">
          </div>

          <div style="margin: 0;">
            <button type="submit" class="btn btn-primary" style="width: 100%;">
              💾 Save Settings
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Reels Cards Grid -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <h3 style="margin: 0; font-family: var(--dc-font-heading); font-size: 1.15rem; color: var(--dc-black);">
      Reel Items &amp; Video Links (<?= count($reels) ?>)
    </h3>
    <span style="font-size: 0.85rem; color: var(--dc-muted);">
      Drag or re-number sort order to adjust display sequence
    </span>
  </div>

  <?php if (empty($reels)): ?>
    <div class="card" style="text-align: center; padding: 48px 20px;">
      <p style="font-size: 1.1rem; color: var(--dc-muted); margin-bottom: 16px;">No Instagram reels found.</p>
      <button type="button" class="btn btn-primary" onclick="openAddReelModal()">＋ Add Your First Reel</button>
    </div>
  <?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
      <?php foreach ($reels as $r): ?>
        <div class="card reel-admin-card" style="display: flex; flex-direction: column; overflow: hidden; position: relative; transition: all 0.25s ease;">
          <!-- Card Thumbnail with Aspect Ratio 9:16 Header -->
          <div style="position: relative; width: 100%; aspect-ratio: 9/12; overflow: hidden; background: #210e0d;">
            <img src="<?= asset(e($r['image_url'])) ?>" alt="<?= e($r['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;" class="reel-thumb-img">
            
            <!-- Gradient Overlay -->
            <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.75) 100%);"></div>

            <!-- Top Badges -->
            <div style="position: absolute; top: 12px; left: 12px; display: flex; gap: 6px; z-index: 5;">
              <span class="badge" style="background: rgba(0,0,0,0.6); color: #ffffff; backdrop-filter: blur(4px); font-family: var(--dc-font-mono);">
                #<?= (int)$r['sort_order'] ?>
              </span>
              <span class="badge" style="background: rgba(199, 97, 61, 0.9); color: #ffffff;">
                <?= e($r['tag_label']) ?>
              </span>
            </div>

            <!-- Active Status Pill -->
            <div style="position: absolute; top: 12px; right: 12px; z-index: 5;">
              <?php if ($r['is_active']): ?>
                <span class="badge badge-success" style="box-shadow: 0 2px 6px rgba(0,0,0,0.2);">Active</span>
              <?php else: ?>
                <span class="badge badge-danger" style="box-shadow: 0 2px 6px rgba(0,0,0,0.2);">Hidden</span>
              <?php endif; ?>
            </div>

            <!-- Play Video Modal Trigger -->
            <?php if (!empty($r['video_url'])): ?>
              <button type="button" class="btn-video-preview" onclick="previewReelVideo('<?= asset(e($r['video_url'])) ?>', '<?= e(addslashes($r['title'])) ?>')" aria-label="Play Video Preview" title="Preview video">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="#ffffff" stroke="none"><polygon points="5 3 19 12 5 21 5 3"/></svg>
              </button>
            <?php endif; ?>

            <!-- Caption on Thumbnail -->
            <div style="position: absolute; bottom: 12px; left: 14px; right: 14px; color: #ffffff; z-index: 5;">
              <h4 style="margin: 0; font-family: var(--dc-font-heading); font-size: 1.05rem; line-height: 1.3; color: #ffffff; text-shadow: 0 1px 3px rgba(0,0,0,0.6);">
                <?= e($r['title']) ?>
              </h4>
              <?php if (!empty($r['video_url'])): ?>
                <div style="font-size: 0.72rem; color: rgba(255,255,255,0.75); margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                  <span>🎬</span> <?= basename($r['video_url']) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Card Details & Actions Footer -->
          <div class="card-body" style="padding: 16px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; gap: 12px;">
            <!-- Instagram Link -->
            <div style="background: var(--dc-cream); border-radius: 8px; padding: 8px 10px; font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; border: 1px solid var(--dc-border);">
              <span style="font-weight: 600; color: var(--dc-terracotta);">🔗 IG Link:</span>
              <a href="<?= e($r['instagram_url']) ?>" target="_blank" rel="noreferrer" style="color: var(--dc-black); text-decoration: underline; margin-left: 4px;">
                <?= e($r['instagram_url']) ?>
              </a>
            </div>

            <!-- Action Buttons Row -->
            <div style="display: flex; gap: 8px; align-items: center; justify-content: space-between; pt-2; border-top: 1px solid var(--dc-border);">
              <form method="POST" style="margin: 0;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="reel_id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn btn-sm <?= $r['is_active'] ? 'btn-ghost' : 'btn-outline' ?>" title="Toggle Visibility">
                  <?= $r['is_active'] ? '👁️ Hide' : '✅ Show' ?>
                </button>
              </form>

              <div style="display: flex; gap: 6px;">
                <button type="button" class="btn btn-sm btn-outline" onclick='openEditReelModal(<?= json_encode($r) ?>)'>
                  ✏️ Edit
                </button>
                <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this reel?');">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete_reel">
                  <input type="hidden" name="reel_id" value="<?= $r['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-ghost" style="color: var(--dc-danger);">
                    🗑️
                  </button>
                </form>
              </div>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<!-- ════════════ MODALS ════════════ -->

<!-- 1. Add Reel Modal -->
<div id="addReelModal" class="dc-modal" style="display: none;">
  <div class="dc-modal-overlay" onclick="closeAddReelModal()"></div>
  <div class="dc-modal-content" style="max-width: 580px;">
    <div class="dc-modal-header">
      <h3 style="margin: 0; font-family: var(--dc-font-heading); font-size: 1.25rem;">＋ Add New Instagram Reel</h3>
      <button type="button" class="dc-modal-close" onclick="closeAddReelModal()">&times;</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_reel">
      <div class="dc-modal-body">
        
        <div class="form-group">
          <label class="form-label" for="add_title">Reel Title / Headline <span>*</span></label>
          <input type="text" id="add_title" name="title" class="form-control" placeholder="e.g. Bubbling Liquid Gold" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="add_tag_label">Tag / Category Label <span>*</span></label>
          <input type="text" id="add_tag_label" name="tag_label" class="form-control" value="Pure Jaggery" placeholder="e.g. The Snap, Handcrafted" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="add_instagram_url">Instagram Post / Reel URL <span>*</span></label>
          <input type="url" id="add_instagram_url" name="instagram_url" class="form-control" value="https://instagram.com" placeholder="https://instagram.com/reel/..." required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
          <div class="form-group">
            <label class="form-label" for="add_video_url">Video Path / URL</label>
            <input type="text" id="add_video_url" name="video_url" class="form-control" placeholder="assets/videos/reel-1.mp4">
            <span class="form-hint">Or choose an mp4 file below:</span>
            <input type="file" name="video_file" accept="video/mp4,video/webm" style="font-size: 0.8rem; margin-top: 6px;">
          </div>

          <div class="form-group">
            <label class="form-label" for="add_image_url">Thumbnail Cover Image</label>
            <input type="text" id="add_image_url" name="image_url" class="form-control" placeholder="assets/images/reels/reel-1.jpg">
            <span class="form-hint">Or upload a cover image:</span>
            <input type="file" name="image_file" accept="image/*" style="font-size: 0.8rem; margin-top: 6px;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: center; margin-top: 10px;">
          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="add_sort_order">Sort Order</label>
            <input type="number" id="add_sort_order" name="sort_order" class="form-control" value="10" min="0">
          </div>

          <div class="form-group" style="margin: 0; padding-top: 18px;">
            <label class="form-check">
              <input type="checkbox" name="is_active" value="1" checked>
              <span style="font-weight: 600;">Active immediately</span>
            </label>
          </div>
        </div>

      </div>
      <div class="dc-modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeAddReelModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Reel</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. Edit Reel Modal -->
<div id="editReelModal" class="dc-modal" style="display: none;">
  <div class="dc-modal-overlay" onclick="closeEditReelModal()"></div>
  <div class="dc-modal-content" style="max-width: 580px;">
    <div class="dc-modal-header">
      <h3 style="margin: 0; font-family: var(--dc-font-heading); font-size: 1.25rem;">✏️ Edit Instagram Reel</h3>
      <button type="button" class="dc-modal-close" onclick="closeEditReelModal()">&times;</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit_reel">
      <input type="hidden" id="edit_reel_id" name="reel_id" value="">
      <div class="dc-modal-body">
        
        <div class="form-group">
          <label class="form-label" for="edit_title">Reel Title / Headline <span>*</span></label>
          <input type="text" id="edit_title" name="title" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="edit_tag_label">Tag / Category Label <span>*</span></label>
          <input type="text" id="edit_tag_label" name="tag_label" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="edit_instagram_url">Instagram Post / Reel URL <span>*</span></label>
          <input type="url" id="edit_instagram_url" name="instagram_url" class="form-control" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
          <div class="form-group">
            <label class="form-label" for="edit_video_url">Video Path / URL</label>
            <input type="text" id="edit_video_url" name="video_url" class="form-control">
            <span class="form-hint">Or replace with mp4 file:</span>
            <input type="file" name="video_file" accept="video/mp4,video/webm" style="font-size: 0.8rem; margin-top: 6px;">
          </div>

          <div class="form-group">
            <label class="form-label" for="edit_image_url">Thumbnail Cover Image</label>
            <input type="text" id="edit_image_url" name="image_url" class="form-control">
            <span class="form-hint">Or replace cover image:</span>
            <input type="file" name="image_file" accept="image/*" style="font-size: 0.8rem; margin-top: 6px;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: center; margin-top: 10px;">
          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="edit_sort_order">Sort Order</label>
            <input type="number" id="edit_sort_order" name="sort_order" class="form-control" min="0">
          </div>

          <div class="form-group" style="margin: 0; padding-top: 18px;">
            <label class="form-check">
              <input type="checkbox" id="edit_is_active" name="is_active" value="1">
              <span style="font-weight: 600;">Active &amp; Visible</span>
            </label>
          </div>
        </div>

      </div>
      <div class="dc-modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeEditReelModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Reel</button>
      </div>
    </form>
  </div>
</div>

<!-- 3. Video Playback Preview Modal -->
<div id="videoPreviewModal" class="dc-modal" style="display: none;">
  <div class="dc-modal-overlay" onclick="closeVideoPreviewModal()"></div>
  <div class="dc-modal-content" style="max-width: 420px; background: #1a0a0a; color: #ffffff; padding: 0; overflow: hidden; border-radius: 20px;">
    <div style="padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1);">
      <h4 id="videoModalTitle" style="margin: 0; color: #ffffff; font-size: 1rem; font-family: var(--dc-font-heading);">Video Preview</h4>
      <button type="button" onclick="closeVideoPreviewModal()" style="background: none; border: none; color: #ffffff; font-size: 1.5rem; cursor: pointer; line-height: 1;">&times;</button>
    </div>
    <div style="padding: 16px; display: flex; justify-content: center; background: #000000;">
      <video id="previewVideoPlayer" controls autoplay loop playsinline style="max-width: 100%; max-height: 60vh; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        Your browser does not support HTML5 video.
      </video>
    </div>
  </div>
</div>

<style>
/* Modern styling specific to Instagram Reels Manager */
.btn-video-preview {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(0.9);
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: rgba(199, 97, 61, 0.9);
  backdrop-filter: blur(8px);
  border: 2px solid rgba(255, 255, 255, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 8;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
}
.reel-admin-card:hover .btn-video-preview {
  transform: translate(-50%, -50%) scale(1.05);
  background: var(--dc-terracotta);
}
.btn-video-preview:hover {
  transform: translate(-50%, -50%) scale(1.15) !important;
  background: #a84d2c !important;
}

/* Modals */
.dc-modal {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.dc-modal-overlay {
  position: absolute;
  inset: 0;
  background: rgba(33, 14, 13, 0.7);
  backdrop-filter: blur(4px);
}
.dc-modal-content {
  position: relative;
  z-index: 10;
  width: 100%;
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid var(--dc-border);
  box-shadow: 0 20px 60px rgba(43, 19, 17, 0.25);
  animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes modalPop {
  from { opacity: 0; transform: scale(0.94) translateY(8px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
.dc-modal-header {
  padding: 18px 22px;
  border-bottom: 1px solid var(--dc-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.dc-modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: var(--dc-muted);
  cursor: pointer;
}
.dc-modal-body {
  padding: 22px;
  max-height: 75vh;
  overflow-y: auto;
}
.dc-modal-footer {
  padding: 16px 22px;
  border-top: 1px solid var(--dc-border);
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* iOS-style toggle switches */
.dc-switch-label {
  user-select: none;
}
.dc-switch-input {
  display: none;
}
.dc-switch-slider {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
  background-color: #d1c7bc;
  border-radius: 24px;
  transition: background-color 0.25s ease;
  flex-shrink: 0;
}
.dc-switch-slider::before {
  content: "";
  position: absolute;
  top: 2px;
  left: 2px;
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
function openAddReelModal() {
  document.getElementById('addReelModal').style.display = 'flex';
}
function closeAddReelModal() {
  document.getElementById('addReelModal').style.display = 'none';
}

function openEditReelModal(reel) {
  document.getElementById('edit_reel_id').value = reel.id;
  document.getElementById('edit_title').value = reel.title || '';
  document.getElementById('edit_tag_label').value = reel.tag_label || '';
  document.getElementById('edit_instagram_url').value = reel.instagram_url || '';
  document.getElementById('edit_video_url').value = reel.video_url || '';
  document.getElementById('edit_image_url').value = reel.image_url || '';
  document.getElementById('edit_sort_order').value = reel.sort_order || 0;
  document.getElementById('edit_is_active').checked = parseInt(reel.is_active) === 1;
  document.getElementById('editReelModal').style.display = 'flex';
}
function closeEditReelModal() {
  document.getElementById('editReelModal').style.display = 'none';
}

function previewReelVideo(videoSrc, title) {
  const modal = document.getElementById('videoPreviewModal');
  const player = document.getElementById('previewVideoPlayer');
  document.getElementById('videoModalTitle').innerText = title || 'Video Preview';
  player.src = videoSrc;
  modal.style.display = 'flex';
  player.play().catch(e => console.log('Autoplay prevented', e));
}
function closeVideoPreviewModal() {
  const modal = document.getElementById('videoPreviewModal');
  const player = document.getElementById('previewVideoPlayer');
  player.pause();
  player.src = '';
  modal.style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/partials/page-end.php'; ?>
