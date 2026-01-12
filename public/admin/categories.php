<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$pageTitle = 'Categories';
$db = Database::getInstance();

$message = '';
$error = '';

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description'] ?? '');
        $color = $_POST['color'] ?? '#000000';
        
        $sql = "INSERT INTO categories (name, description, color) VALUES (?, ?, ?)";
        $db->execute($sql, [$name, $description, $color]);
        $message = 'Category created successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle category deletion
if (isset($_GET['delete'])) {
    try {
        $sql = "DELETE FROM categories WHERE id = ?";
        $db->execute($sql, [(int)$_GET['delete']]);
        $message = 'Category deleted successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all categories
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name");

include __DIR__ . '/../includes/header.php';
?>

<div class="page-body">
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"><i class="ti ti-category"></i> Task Categories</h2>
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
                        <h3 class="card-title">Create New Category</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label required">Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Color</label>
                                <input type="color" name="color" class="form-control form-control-color" value="#2196F3">
                            </div>
                            <button type="submit" name="create_category" class="btn btn-primary w-100">
                                <i class="ti ti-plus"></i> Create Category
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Categories</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Color</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><strong><?php echo h($cat['name']); ?></strong></td>
                                    <td><?php echo h($cat['description']); ?></td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo h($cat['color']); ?>">
                                            <?php echo h($cat['color']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?delete=<?php echo $cat['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this category?')">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
