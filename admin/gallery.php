<?php
/**
 * Admin Gallery Manager
 * Mount Excel Boarding School
 * Roles: admin, super_admin only
 */

ob_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/upload_image.php';
require_once __DIR__ . '/includes/auth.php';

requireRole(['admin', 'super_admin']);

$pdo         = getDB();
$currentUser = currentAdmin();
$errors      = [];
$success     = '';

$galleryUploadDir = ROOT_PATH . '/uploads/gallery';

$validAlbums = ['merit', 'excursion', 'art', 'general'];

// ============================================================
// Handle post_max_size overflow (empty $_POST check)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $postMaxSize = ini_get('post_max_size');
    setFlash('error', "Upload failed: the total upload size exceeded the server limit ({$postMaxSize}). Please upload fewer or smaller files.");
    header('Location: gallery.php');
    ob_end_flush();
    exit;
}

// ============================================================
// POST: Upload new photos
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token invalid. Please try again.');
        header('Location: gallery.php');
        ob_end_flush();
        exit;
    }

    $album = in_array($_POST['album'] ?? '', $validAlbums, true) ? $_POST['album'] : 'general';
    $uploaded = 0;
    $uploadErrors = [];

    // Multi-file upload: $_FILES['photos'] will be an array
    if (!empty($_FILES['photos']['name'][0])) {
        $fileCount = count($_FILES['photos']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            $singleFile = [
                'name'     => $_FILES['photos']['name'][$i],
                'type'     => $_FILES['photos']['type'][$i],
                'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                'error'    => $_FILES['photos']['error'][$i],
                'size'     => $_FILES['photos']['size'][$i],
            ];

            if ($singleFile['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            try {
                $filename = processUploadedImage($singleFile, $galleryUploadDir);
                $title    = trim($_POST['title'] ?? '');
                $caption  = trim($_POST['caption'] ?? '');

                $stmt = $pdo->prepare("
                    INSERT INTO gallery_items (album, filename, title, caption, sort_order, is_visible, created_at, updated_at)
                    VALUES (:album, :filename, :title, :caption, 0, 1, NOW(), NOW())
                ");
                $stmt->execute([
                    ':album'    => $album,
                    ':filename' => $filename,
                    ':title'    => $title,
                    ':caption'  => $caption,
                ]);
                $uploaded++;
            } catch (RuntimeException $e) {
                $uploadErrors[] = htmlspecialchars($singleFile['name'], ENT_QUOTES, 'UTF-8') . ': ' . $e->getMessage();
            }
        }
    } else {
        $uploadErrors[] = 'No files were selected.';
    }

    if ($uploaded > 0) {
        logActivity('GALLERY_UPLOAD', "Uploaded {$uploaded} photo(s) to album: {$album}", $currentUser['id']);
        setFlash('success', "{$uploaded} photo(s) uploaded successfully to the '{$album}' album.");
    }
    foreach ($uploadErrors as $err) {
        setFlash('error', $err);
    }

    header('Location: gallery.php');
    ob_end_flush();
    exit;
}

// ============================================================
// POST: Edit existing photo
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token invalid. Please try again.');
        header('Location: gallery.php');
        ob_end_flush();
        exit;
    }

    $id         = (int)($_POST['id'] ?? 0);
    $album      = in_array($_POST['album'] ?? '', $validAlbums, true) ? $_POST['album'] : 'general';
    $title      = trim($_POST['title'] ?? '');
    $caption    = trim($_POST['caption'] ?? '');
    $sortOrder  = max(0, (int)($_POST['sort_order'] ?? 0));
    $isVisible  = isset($_POST['is_visible']) ? 1 : 0;

    // Fetch existing record
    $existing = $pdo->prepare("SELECT * FROM gallery_items WHERE id = :id LIMIT 1");
    $existing->execute([':id' => $id]);
    $item = $existing->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        setFlash('error', 'Photo not found.');
        header('Location: gallery.php');
        ob_end_flush();
        exit;
    }

    $newFilename = $item['filename'];

    // Optional photo replacement
    if (!empty($_FILES['replace_photo']['name'])) {
        try {
            $newFilename = processUploadedImage($_FILES['replace_photo'], $galleryUploadDir);
            // Delete old file
            deleteUploadedImage($item['filename'], 'gallery');
        } catch (RuntimeException $e) {
            setFlash('error', 'Photo replacement failed: ' . $e->getMessage());
            header('Location: gallery.php');
            ob_end_flush();
            exit;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE gallery_items
        SET album = :album, filename = :filename, title = :title, caption = :caption,
            sort_order = :sort_order, is_visible = :is_visible, updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':album'      => $album,
        ':filename'   => $newFilename,
        ':title'      => $title,
        ':caption'    => $caption,
        ':sort_order' => $sortOrder,
        ':is_visible' => $isVisible,
        ':id'         => $id,
    ]);

    logActivity('GALLERY_EDIT', "Edited gallery item ID {$id}", $currentUser['id']);
    setFlash('success', 'Photo updated successfully.');
    header('Location: gallery.php');
    ob_end_flush();
    exit;
}

// ============================================================
// POST: Delete photo
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token invalid. Please try again.');
        header('Location: gallery.php');
        ob_end_flush();
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM gallery_items WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        deleteUploadedImage($item['filename'], 'gallery');
        $pdo->prepare("DELETE FROM gallery_items WHERE id = :id")->execute([':id' => $id]);
        logActivity('GALLERY_DELETE', "Deleted gallery item ID {$id} (file: {$item['filename']})", $currentUser['id']);
        setFlash('success', 'Photo deleted successfully.');
    } else {
        setFlash('error', 'Photo not found.');
    }

    header('Location: gallery.php');
    ob_end_flush();
    exit;
}

// ============================================================
// GET: Load all items for display
// ============================================================
$items = $pdo->query("
    SELECT * FROM gallery_items ORDER BY album, sort_order ASC, id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Group by album
$byAlbum = [];
foreach ($items as $row) {
    $byAlbum[$row['album']][] = $row;
}

// Edit mode: load single item
$editItem = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($items as $row) {
        if ((int)$row['id'] === $editId) {
            $editItem = $row;
            break;
        }
    }
}

$adminTitle = 'Gallery Manager';
$activeNav  = 'gallery';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="page-header-row">
  <div>
    <h1 class="page-title"><i class="fas fa-images"></i> Gallery Manager</h1>
    <p class="page-subtitle">Upload, organise and manage photo albums shown on the public website.</p>
  </div>
</div>

<!-- ============================================================
     UPLOAD NEW PHOTOS FORM
     ============================================================ -->
<div class="card" style="margin-bottom: 30px;">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-cloud-upload-alt"></i> Upload New Photos</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="gallery.php" enctype="multipart/form-data" id="uploadForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="upload">

      <div class="form-grid-2">
        <div class="form-group">
          <label for="upload_album" class="form-label">Album <span class="text-danger">*</span></label>
          <select name="album" id="upload_album" class="form-control" required>
            <option value="general">General</option>
            <option value="merit">Merit Scholarship</option>
            <option value="excursion">Excursion</option>
            <option value="art">Art Classes</option>
          </select>
        </div>
        <div class="form-group">
          <label for="upload_title" class="form-label">Default Title (optional)</label>
          <input type="text" name="title" id="upload_title" class="form-control" maxlength="150"
                 placeholder="Applied to all uploaded photos" value="">
        </div>
      </div>

      <div class="form-group">
        <label for="upload_caption" class="form-label">Default Caption (optional)</label>
        <textarea name="caption" id="upload_caption" class="form-control" rows="2"
                  placeholder="Applied to all uploaded photos in this batch"></textarea>
      </div>

      <div class="form-group">
        <label for="photos" class="form-label">Select Photos <span class="text-danger">*</span></label>
        <input type="file" name="photos[]" id="photos" class="form-control" accept=".jpg,.jpeg,.png,.webp"
               multiple required>
        <small class="form-hint">JPG, PNG or WebP · Max 8 MB each · Max 40 megapixels · Multiple files allowed</small>
      </div>

      <button type="submit" class="btn btn-primary" id="uploadBtn">
        <i class="fas fa-upload"></i> Upload Photos
      </button>
    </form>
  </div>
</div>

<!-- ============================================================
     EDIT FORM (shown when ?edit=ID)
     ============================================================ -->
<?php if ($editItem): ?>
<div class="card" style="margin-bottom: 30px; border: 2px solid var(--primary-500);">
  <div class="card-header" style="background: var(--primary-50);">
    <h3 class="card-title"><i class="fas fa-edit"></i> Edit Photo #<?= (int)$editItem['id'] ?></h3>
    <a href="gallery.php" class="btn btn-sm btn-outline">Cancel</a>
  </div>
  <div class="card-body">
    <form method="POST" action="gallery.php" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>">

      <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 20px;">
        <?php 
          $editPreview = (str_starts_with($editItem['filename'], 'images/')) 
            ? '../' . e($editItem['filename']) 
            : '../uploads/gallery/' . e($editItem['filename']);
        ?>
        <img src="<?= $editPreview ?>"
             alt="Current photo" style="width: 150px; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
        <div style="flex: 1;">
          <label class="form-label">Replace Photo (optional)</label>
          <input type="file" name="replace_photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
          <small class="form-hint">Leave blank to keep the current photo.</small>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="edit_album" class="form-label">Album</label>
          <select name="album" id="edit_album" class="form-control">
            <?php foreach ($validAlbums as $alb): ?>
            <option value="<?= e($alb) ?>" <?= $editItem['album'] === $alb ? 'selected' : '' ?>>
              <?= e(ucfirst($alb)) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="edit_sort" class="form-label">Sort Order</label>
          <input type="number" name="sort_order" id="edit_sort" class="form-control"
                 min="0" max="9999" value="<?= (int)$editItem['sort_order'] ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="edit_title" class="form-label">Title</label>
        <input type="text" name="title" id="edit_title" class="form-control" maxlength="150"
               value="<?= e($editItem['title']) ?>">
      </div>

      <div class="form-group">
        <label for="edit_caption" class="form-label">Caption</label>
        <textarea name="caption" id="edit_caption" class="form-control" rows="3"><?= e($editItem['caption'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">
          <input type="checkbox" name="is_visible" value="1" <?= $editItem['is_visible'] ? 'checked' : '' ?>>
          Visible on public website
        </label>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
      <a href="gallery.php" class="btn btn-outline" style="margin-left: 8px;">Cancel</a>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ============================================================
     GALLERY TABLE (grouped by album)
     ============================================================ -->
<?php foreach ($validAlbums as $albumKey):
  $albumItems = $byAlbum[$albumKey] ?? [];
  $albumLabel = match($albumKey) {
      'merit'     => 'Merit Scholarship',
      'excursion' => 'Excursion',
      'art'       => 'Art Classes',
      'general'   => 'General',
      default     => ucfirst($albumKey),
  };
?>
<div class="card" style="margin-bottom: 24px;">
  <div class="card-header">
    <h3 class="card-title">
      <i class="fas fa-folder-open"></i> <?= e($albumLabel) ?>
      <span class="badge" style="background:#e2e8f0;color:#475569;font-size:.75rem;padding:2px 8px;border-radius:12px;margin-left:8px;">
        <?= count($albumItems) ?> photo<?= count($albumItems) !== 1 ? 's' : '' ?>
      </span>
    </h3>
  </div>

  <?php if (empty($albumItems)): ?>
  <div class="card-body" style="color: var(--text-muted); font-style: italic;">No photos in this album yet.</div>
  <?php else: ?>
  <div style="overflow-x: auto;">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:90px;">Preview</th>
          <th>Title</th>
          <th>Caption</th>
          <th style="width:70px;">Order</th>
          <th style="width:80px;">Visible</th>
          <th style="width:140px;">Added</th>
          <th style="width:120px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($albumItems as $row): 
          $thumbUrl = (str_starts_with($row['filename'], 'images/')) 
            ? '../' . e($row['filename']) 
            : '../uploads/gallery/' . e($row['filename']);
        ?>
        <tr>
          <td>
            <img src="<?= $thumbUrl ?>"
                 alt="<?= e($row['title']) ?>"
                 style="width:80px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;">
          </td>
          <td><?= e($row['title'] ?: '—') ?></td>
          <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?= e($row['caption'] ?? '—') ?>
          </td>
          <td><?= (int)$row['sort_order'] ?></td>
          <td>
            <?php if ($row['is_visible']): ?>
              <span class="badge badge-success"><i class="fas fa-eye"></i> Yes</span>
            <?php else: ?>
              <span class="badge badge-secondary"><i class="fas fa-eye-slash"></i> Hidden</span>
            <?php endif; ?>
          </td>
          <td style="font-size:.85rem;color:var(--text-muted);">
            <?= e(date('d M Y', strtotime($row['created_at']))) ?>
          </td>
          <td>
            <a href="gallery.php?edit=<?= (int)$row['id'] ?>#editForm"
               class="btn btn-sm btn-outline" title="Edit">
              <i class="fas fa-edit"></i>
            </a>
            <form method="POST" action="gallery.php" style="display:inline;"
                  onsubmit="return confirm('Delete this photo? This cannot be undone.');">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
