<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$pageTitle = 'Icons';
$db = Database::getInstance();

$message = '';
$error = '';

// Handle icon upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['icon_file'])) {
    try {
        $name = trim($_POST['name']);
        $iconType = $_POST['icon_type'];
        
        $filename = uploadIcon($_FILES['icon_file'], $iconType);
        
        $sql = "INSERT INTO icons (name, filename, icon_type) VALUES (?, ?, ?)";
        $db->execute($sql, [$name, $filename, $iconType]);
        
        $message = 'Icon uploaded successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle icon deletion
if (isset($_GET['delete'])) {
    try {
        $icon = $db->fetchOne("SELECT * FROM icons WHERE id = ?", [(int)$_GET['delete']]);
        if ($icon) {
            $filepath = PUBLIC_PATH . '/' . $icon['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            $sql = "DELETE FROM icons WHERE id = ?";
            $db->execute($sql, [(int)$_GET['delete']]);
            $message = 'Icon deleted successfully!';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all icons
$icons = $db->fetchAll("SELECT * FROM icons ORDER BY icon_type, name");

include __DIR__ . '/../includes/header.php';
?>

<div class="page-body">
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"><i class="ti ti-photo"></i> Icons</h2>
                    <div class="text-muted mt-1">Manage icons for tasks, urgency levels, and categories</div>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="ti ti-check"></i> <?php echo h($message); ?>
            <a class="btn-close" data-bs-dismiss="alert"></a>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="ti ti-alert-circle"></i> <?php echo h($error); ?>
        </div>
        <?php endif; ?>
        
        <div class="row mt-3">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Upload New Icon</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label required">Icon Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Icon Type</label>
                                <select name="icon_type" class="form-select">
                                    <option value="task">Task Icon</option>
                                    <option value="urgency">Urgency Icon</option>
                                    <option value="level">Level Icon</option>
                                    <option value="category">Category Icon</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label required">Icon File</label>
                                <input type="file" name="icon_file" class="form-control" accept="image/png,image/jpeg,image/gif" required>
                                <small class="form-hint">PNG, JPEG, or GIF (max 5MB). Best size: 48x48px</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-upload"></i> Upload Icon
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Icons</h3>
                    </div>
                    <div class="card-body">
                        <div class="row row-cards">
                            <?php if (empty($icons)): ?>
                            <div class="col-12 text-center text-muted py-4">
                                No icons uploaded yet.
                            </div>
                            <?php else: ?>
                                <?php foreach ($icons as $icon): ?>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card card-sm">
                                        <div class="card-body text-center">
                                            <img src="/<?php echo h($icon['filename']); ?>" 
                                                 alt="<?php echo h($icon['name']); ?>" 
                                                 class="mb-2"
                                                 style="max-width: 48px; max-height: 48px;">
                                            <div class="small fw-bold"><?php echo h($icon['name']); ?></div>
                                            <div class="small text-muted"><?php echo ucfirst($icon['icon_type']); ?></div>
                                            <div class="mt-2">
                                                <a href="?delete=<?php echo $icon['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Delete this icon?')">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
