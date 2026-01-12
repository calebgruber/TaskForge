<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$pageTitle = 'XP Rules';
$db = Database::getInstance();

$message = '';
$error = '';

// Handle rule creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_rule'])) {
    try {
        $sql = "INSERT INTO xp_rules (
            rule_name, base_xp, urgency_multiplier, difficulty_multiplier,
            category_id, overdue_penalty, early_completion_bonus, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $db->execute($sql, [
            trim($_POST['rule_name']),
            (int)$_POST['base_xp'],
            (float)$_POST['urgency_multiplier'],
            (float)$_POST['difficulty_multiplier'],
            (int)$_POST['category_id'] ?: null,
            (int)$_POST['overdue_penalty'],
            (int)$_POST['early_completion_bonus'],
            isset($_POST['is_active']) ? 1 : 0
        ]);
        
        $message = 'XP rule created successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle rule deletion
if (isset($_GET['delete'])) {
    try {
        $sql = "DELETE FROM xp_rules WHERE id = ?";
        $db->execute($sql, [(int)$_GET['delete']]);
        $message = 'XP rule deleted successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all rules and categories
$rules = $db->fetchAll("SELECT xr.*, c.name as category_name 
                        FROM xp_rules xr 
                        LEFT JOIN categories c ON xr.category_id = c.id 
                        ORDER BY xr.rule_name");
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name");

include __DIR__ . '/../includes/header.php';
?>

<div class="page-body">
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"><i class="ti ti-chart-bar"></i> XP Rules</h2>
                    <div class="text-muted mt-1">Configure experience point calculations</div>
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
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Create New XP Rule</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label required">Rule Name</label>
                                <input type="text" name="rule_name" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Category (optional)</label>
                                <select name="category_id" class="form-select">
                                    <option value="">All categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo h($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-hint">Leave empty for default rule</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Base XP</label>
                                        <input type="number" name="base_xp" class="form-control" value="10" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Urgency Multiplier</label>
                                        <input type="number" name="urgency_multiplier" class="form-control" value="1.00" step="0.01" min="0.1" max="10">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Difficulty Multiplier</label>
                                        <input type="number" name="difficulty_multiplier" class="form-control" value="1.00" step="0.01" min="0.1" max="10">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Early Bonus XP</label>
                                        <input type="number" name="early_completion_bonus" class="form-control" value="0" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Overdue Penalty XP</label>
                                <input type="number" name="overdue_penalty" class="form-control" value="0" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" value="1" checked>
                                    <span class="form-check-label">Active</span>
                                </label>
                            </div>
                            
                            <button type="submit" name="create_rule" class="btn btn-primary w-100">
                                <i class="ti ti-plus"></i> Create Rule
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All XP Rules</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Rule Name</th>
                                    <th>Category</th>
                                    <th>Base XP</th>
                                    <th>Multipliers</th>
                                    <th>Status</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rules as $rule): ?>
                                <tr>
                                    <td><strong><?php echo h($rule['rule_name']); ?></strong></td>
                                    <td>
                                        <?php if ($rule['category_name']): ?>
                                            <span class="badge bg-secondary"><?php echo h($rule['category_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Default</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $rule['base_xp']; ?> XP</td>
                                    <td>
                                        <small>
                                            Urgency: <?php echo $rule['urgency_multiplier']; ?>x<br>
                                            Difficulty: <?php echo $rule['difficulty_multiplier']; ?>x
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($rule['is_active']): ?>
                                            <span class="badge bg-green">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?delete=<?php echo $rule['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this rule?')">
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
