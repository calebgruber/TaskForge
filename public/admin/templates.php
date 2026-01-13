<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$pageTitle = 'Receipt Templates';
$db = Database::getInstance();

$message = '';
$error = '';

// Handle template creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_template'])) {
    try {
        $sql = "INSERT INTO receipt_templates (
            name, template_type, header_text, footer_text, 
            show_timestamp, show_urgency, show_xp_value, show_category, show_due_date,
            text_alignment, barcode_type, receipt_length
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $db->execute($sql, [
            trim($_POST['name']),
            $_POST['template_type'],
            trim($_POST['header_text']),
            trim($_POST['footer_text']),
            isset($_POST['show_timestamp']) ? 1 : 0,
            isset($_POST['show_urgency']) ? 1 : 0,
            isset($_POST['show_xp_value']) ? 1 : 0,
            isset($_POST['show_category']) ? 1 : 0,
            isset($_POST['show_due_date']) ? 1 : 0,
            $_POST['text_alignment'],
            $_POST['barcode_type'],
            (int)($_POST['receipt_length'] ?? 300)
        ]);
        
        $message = 'Template created successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle template deletion
if (isset($_GET['delete'])) {
    try {
        $sql = "DELETE FROM receipt_templates WHERE id = ?";
        $db->execute($sql, [(int)$_GET['delete']]);
        $message = 'Template deleted successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all templates
$templates = $db->fetchAll("SELECT * FROM receipt_templates ORDER BY template_type, name");

include __DIR__ . '/../includes/header.php';
?>

<div class="page-body">
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"><i class="ti ti-template"></i> Receipt Templates</h2>
                    <div class="text-muted mt-1">Design custom thermal printer layouts</div>
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
                        <h3 class="card-title">Create New Template</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label required">Template Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Template Type</label>
                                <select name="template_type" class="form-select">
                                    <option value="task">Task Receipt</option>
                                    <option value="completion">Completion Receipt</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Header Text</label>
                                <input type="text" name="header_text" class="form-control" placeholder="TASK QUEST">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Footer Text</label>
                                <input type="text" name="footer_text" class="form-control" placeholder="Scan to complete">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Text Alignment</label>
                                <select name="text_alignment" class="form-select">
                                    <option value="left">Left</option>
                                    <option value="center" selected>Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Barcode Type</label>
                                <select name="barcode_type" class="form-select">
                                    <option value="CODE128" selected>CODE128 (Standard)</option>
                                    <option value="CODE39">CODE39</option>
                                    <option value="EAN13">EAN13</option>
                                    <option value="AZTEC">Aztec (2D)</option>
                                    <option value="PDF417">PDF417 (2D)</option>
                                </select>
                                <small class="form-hint">CODE128 is recommended for standard receipts. Aztec and PDF417 are 2D barcodes for more data.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Receipt Length (mm)</label>
                                <input type="number" name="receipt_length" class="form-control" value="300" min="100" max="500" placeholder="300">
                                <small class="form-hint">Length of receipt before auto-cut. Typical range: 150-400mm</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Display Options</label>
                                <label class="form-check">
                                    <input type="checkbox" name="show_timestamp" class="form-check-input" checked>
                                    <span class="form-check-label">Show timestamp</span>
                                </label>
                                <label class="form-check">
                                    <input type="checkbox" name="show_urgency" class="form-check-input" checked>
                                    <span class="form-check-label">Show urgency level</span>
                                </label>
                                <label class="form-check">
                                    <input type="checkbox" name="show_xp_value" class="form-check-input" checked>
                                    <span class="form-check-label">Show XP value</span>
                                </label>
                                <label class="form-check">
                                    <input type="checkbox" name="show_category" class="form-check-input" checked>
                                    <span class="form-check-label">Show category</span>
                                </label>
                                <label class="form-check">
                                    <input type="checkbox" name="show_due_date" class="form-check-input" checked>
                                    <span class="form-check-label">Show due date</span>
                                </label>
                            </div>
                            
                            <button type="submit" name="create_template" class="btn btn-primary w-100">
                                <i class="ti ti-plus"></i> Create Template
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Templates</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Header</th>
                                    <th>Footer</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><strong><?php echo h($template['name']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-blue">
                                            <?php echo ucfirst($template['template_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo h($template['header_text']); ?></td>
                                    <td><?php echo h($template['footer_text']); ?></td>
                                    <td>
                                        <a href="?delete=<?php echo $template['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this template?')">
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
