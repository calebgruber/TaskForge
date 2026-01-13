<?php
require_once __DIR__ . '/../../config/config.php';
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
            max(100, min(500, (int)($_POST['receipt_length'] ?? 300))) // Validate range
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

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-body">
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"><i class="ti ti-template"></i> Receipt Templates - Easy Mode</h2>
                    <div class="text-muted mt-1">Design custom thermal printer layouts with simple form controls</div>
                </div>
                <div class="col-auto">
                    <a href="/admin/template-designer.php" class="btn btn-primary">
                        <i class="ti ti-palette"></i> Switch to Advanced Mode
                    </a>
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
                
                <!-- Live Preview -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-eye"></i> Live Preview (80mm)</h3>
                    </div>
                    <div class="card-body" style="background: #f5f7fb;">
                        <div style="width: 302px; margin: 0 auto; background: white; padding: 15px; font-family: 'Courier New', monospace; font-size: 12px; border: 1px solid #ddd; box-shadow: 0 2px 8px rgba(0,0,0,0.1); min-height: 300px;" id="receiptPreview">
                            <div style="text-align: center; font-weight: bold; margin-bottom: 10px;" id="previewHeader">
                                TASK QUEST
                            </div>
                            <div style="text-align: center; font-size: 14px; margin: 10px 0;" id="previewTitle">
                                Sample Task Title
                            </div>
                            <div id="previewCategory" style="margin: 5px 0;">Category: Work</div>
                            <div id="previewUrgency" style="margin: 5px 0;">Urgency: Normal</div>
                            <div id="previewXP" style="margin: 5px 0;">XP Reward: 50 XP</div>
                            <div id="previewDueDate" style="margin: 5px 0;">Due: Dec 31, 2024 12:00 PM</div>
                            <div style="text-align: center; margin: 15px 0; padding: 10px; background: #f0f0f0; border: 1px solid #ddd;" id="previewBarcode">
                                <div style="margin-bottom: 5px;">Scan to complete:</div>
                                <img src="" id="previewBarcodeImage" style="max-width: 100%; height: 60px; display: none;">
                                <div style="font-size: 10px; color: #666;" id="previewBarcodeType">CODE128 Barcode</div>
                            </div>
                            <div style="text-align: center; margin-top: 10px;" id="previewFooter">
                                Scan to complete
                            </div>
                            <div id="previewTimestamp" style="text-align: center; margin-top: 10px; font-size: 10px; color: #666;">
                                2024-12-31 12:00:00
                            </div>
                            <div style="text-align: center; margin-top: 15px; color: #999;">
                                <small>Receipt Length: <span id="previewLength">300</span>mm</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Live preview updates
const headerInput = document.querySelector('input[name="header_text"]');
const footerInput = document.querySelector('input[name="footer_text"]');
const barcodeTypeSelect = document.querySelector('select[name="barcode_type"]');
const receiptLengthInput = document.querySelector('input[name="receipt_length"]');
const showTimestamp = document.querySelector('input[name="show_timestamp"]');
const showUrgency = document.querySelector('input[name="show_urgency"]');
const showXP = document.querySelector('input[name="show_xp_value"]');
const showCategory = document.querySelector('input[name="show_category"]');
const showDueDate = document.querySelector('input[name="show_due_date"]');

// Barcode sample images (data URIs for common barcode types)
const barcodeSamples = {
    'CODE128': 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="60"%3E%3Crect fill="%23fff" width="200" height="60"/%3E%3Cg fill="%23000"%3E%3Crect x="10" width="2" height="50"/%3E%3Crect x="15" width="1" height="50"/%3E%3Crect x="18" width="3" height="50"/%3E%3Crect x="24" width="2" height="50"/%3E%3Crect x="28" width="1" height="50"/%3E%3Crect x="32" width="3" height="50"/%3E%3Crect x="38" width="1" height="50"/%3E%3Crect x="42" width="2" height="50"/%3E%3Crect x="47" width="3" height="50"/%3E%3Crect x="53" width="1" height="50"/%3E%3Crect x="57" width="2" height="50"/%3E%3Crect x="62" width="3" height="50"/%3E%3Crect x="68" width="1" height="50"/%3E%3Crect x="72" width="2" height="50"/%3E%3Crect x="77" width="1" height="50"/%3E%3Crect x="81" width="3" height="50"/%3E%3Crect x="87" width="2" height="50"/%3E%3Crect x="92" width="1" height="50"/%3E%3Crect x="96" width="3" height="50"/%3E%3Crect x="102" width="1" height="50"/%3E%3Crect x="106" width="2" height="50"/%3E%3Crect x="111" width="3" height="50"/%3E%3Crect x="117" width="1" height="50"/%3E%3Crect x="121" width="2" height="50"/%3E%3Crect x="126" width="3" height="50"/%3E%3Crect x="132" width="1" height="50"/%3E%3Crect x="136" width="2" height="50"/%3E%3Crect x="141" width="1" height="50"/%3E%3Crect x="145" width="3" height="50"/%3E%3Crect x="151" width="2" height="50"/%3E%3Crect x="156" width="1" height="50"/%3E%3Crect x="160" width="3" height="50"/%3E%3Crect x="166" width="1" height="50"/%3E%3Crect x="170" width="2" height="50"/%3E%3Crect x="175" width="3" height="50"/%3E%3Crect x="181" width="1" height="50"/%3E%3Crect x="185" width="2" height="50"/%3E%3C/g%3E%3Ctext x="100" y="58" text-anchor="middle" font-size="8" font-family="monospace"%3ETASK12345%3C/text%3E%3C/svg%3E',
    'CODE39': 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="60"%3E%3Crect fill="%23fff" width="200" height="60"/%3E%3Cg fill="%23000"%3E%3Crect x="10" width="3" height="50"/%3E%3Crect x="18" width="1" height="50"/%3E%3Crect x="22" width="3" height="50"/%3E%3Crect x="30" width="1" height="50"/%3E%3Crect x="35" width="3" height="50"/%3E%3Crect x="43" width="1" height="50"/%3E%3Crect x="48" width="3" height="50"/%3E%3Crect x="56" width="1" height="50"/%3E%3Crect x="61" width="3" height="50"/%3E%3Crect x="69" width="1" height="50"/%3E%3Crect x="74" width="3" height="50"/%3E%3Crect x="82" width="1" height="50"/%3E%3Crect x="87" width="3" height="50"/%3E%3Crect x="95" width="1" height="50"/%3E%3Crect x="100" width="3" height="50"/%3E%3Crect x="108" width="1" height="50"/%3E%3Crect x="113" width="3" height="50"/%3E%3Crect x="121" width="1" height="50"/%3E%3Crect x="126" width="3" height="50"/%3E%3Crect x="134" width="1" height="50"/%3E%3Crect x="139" width="3" height="50"/%3E%3Crect x="147" width="1" height="50"/%3E%3Crect x="152" width="3" height="50"/%3E%3Crect x="160" width="1" height="50"/%3E%3Crect x="165" width="3" height="50"/%3E%3Crect x="173" width="1" height="50"/%3E%3Crect x="178" width="3" height="50"/%3E%3C/g%3E%3Ctext x="100" y="58" text-anchor="middle" font-size="8" font-family="monospace"%3E*TASK12345*%3C/text%3E%3C/svg%3E',
    'EAN13': 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="60"%3E%3Crect fill="%23fff" width="200" height="60"/%3E%3Cg fill="%23000"%3E%3Crect x="10" width="2" height="50"/%3E%3Crect x="14" width="1" height="50"/%3E%3Crect x="17" width="2" height="50"/%3E%3Crect x="21" width="1" height="50"/%3E%3Crect x="25" width="2" height="50"/%3E%3Crect x="29" width="1" height="50"/%3E%3Crect x="33" width="2" height="50"/%3E%3Crect x="37" width="1" height="50"/%3E%3Crect x="41" width="2" height="50"/%3E%3Crect x="45" width="1" height="50"/%3E%3Crect x="49" width="2" height="50"/%3E%3Crect x="53" width="1" height="50"/%3E%3Crect x="58" width="2" height="55"/%3E%3Crect x="63" width="2" height="50"/%3E%3Crect x="67" width="1" height="50"/%3E%3Crect x="71" width="2" height="50"/%3E%3Crect x="75" width="1" height="50"/%3E%3Crect x="79" width="2" height="50"/%3E%3Crect x="83" width="1" height="50"/%3E%3Crect x="87" width="2" height="50"/%3E%3Crect x="91" width="1" height="50"/%3E%3Crect x="95" width="2" height="50"/%3E%3Crect x="99" width="1" height="50"/%3E%3Crect x="103" width="2" height="50"/%3E%3Crect x="107" width="1" height="50"/%3E%3Crect x="112" width="2" height="55"/%3E%3Crect x="117" width="2" height="50"/%3E%3Crect x="121" width="1" height="50"/%3E%3Crect x="125" width="2" height="50"/%3E%3Crect x="129" width="1" height="50"/%3E%3Crect x="133" width="2" height="50"/%3E%3Crect x="137" width="1" height="50"/%3E%3Crect x="141" width="2" height="50"/%3E%3Crect x="145" width="1" height="50"/%3E%3Crect x="149" width="2" height="50"/%3E%3Crect x="153" width="1" height="50"/%3E%3Crect x="157" width="2" height="50"/%3E%3Crect x="161" width="1" height="50"/%3E%3Crect x="165" width="2" height="50"/%3E%3Crect x="169" width="1" height="50"/%3E%3Crect x="173" width="2" height="50"/%3E%3Crect x="177" width="1" height="50"/%3E%3Crect x="181" width="2" height="50"/%3E%3C/g%3E%3Ctext x="100" y="58" text-anchor="middle" font-size="8" font-family="monospace"%3E5901234123457%3C/text%3E%3C/svg%3E',
    'AZTEC': 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="100"%3E%3Crect fill="%23fff" width="100" height="100"/%3E%3Cg fill="%23000"%3E%3Crect x="20" y="20" width="5" height="5"/%3E%3Crect x="30" y="20" width="5" height="5"/%3E%3Crect x="40" y="20" width="5" height="5"/%3E%3Crect x="50" y="20" width="5" height="5"/%3E%3Crect x="60" y="20" width="5" height="5"/%3E%3Crect x="70" y="20" width="5" height="5"/%3E%3Crect x="20" y="30" width="5" height="5"/%3E%3Crect x="70" y="30" width="5" height="5"/%3E%3Crect x="20" y="40" width="5" height="5"/%3E%3Crect x="30" y="40" width="5" height="5"/%3E%3Crect x="35" y="35" width="25" height="25"/%3E%3Crect x="60" y="40" width="5" height="5"/%3E%3Crect x="70" y="40" width="5" height="5"/%3E%3Crect x="20" y="50" width="5" height="5"/%3E%3Crect x="70" y="50" width="5" height="5"/%3E%3Crect x="20" y="60" width="5" height="5"/%3E%3Crect x="30" y="60" width="5" height="5"/%3E%3Crect x="60" y="60" width="5" height="5"/%3E%3Crect x="70" y="60" width="5" height="5"/%3E%3Crect x="20" y="70" width="5" height="5"/%3E%3Crect x="30" y="70" width="5" height="5"/%3E%3Crect x="40" y="70" width="5" height="5"/%3E%3Crect x="50" y="70" width="5" height="5"/%3E%3Crect x="60" y="70" width="5" height="5"/%3E%3Crect x="70" y="70" width="5" height="5"/%3E%3C/g%3E%3C/svg%3E',
    'PDF417': 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="80"%3E%3Crect fill="%23fff" width="200" height="80"/%3E%3Cg fill="%23000"%3E%3Crect x="10" y="10" width="3" height="15"/%3E%3Crect x="15" y="10" width="1" height="15"/%3E%3Crect x="18" y="10" width="3" height="15"/%3E%3Crect x="23" y="10" width="1" height="15"/%3E%3Crect x="26" y="10" width="3" height="15"/%3E%3Crect x="31" y="10" width="1" height="15"/%3E%3Crect x="34" y="10" width="3" height="15"/%3E%3Crect x="39" y="10" width="1" height="15"/%3E%3Crect x="42" y="10" width="3" height="15"/%3E%3Crect x="47" y="10" width="1" height="15"/%3E%3Crect x="50" y="10" width="3" height="15"/%3E%3Crect x="55" y="10" width="1" height="15"/%3E%3Crect x="10" y="27" width="3" height="15"/%3E%3Crect x="15" y="27" width="1" height="15"/%3E%3Crect x="18" y="27" width="3" height="15"/%3E%3Crect x="23" y="27" width="1" height="15"/%3E%3Crect x="26" y="27" width="3" height="15"/%3E%3Crect x="31" y="27" width="1" height="15"/%3E%3Crect x="34" y="27" width="3" height="15"/%3E%3Crect x="39" y="27" width="1" height="15"/%3E%3Crect x="42" y="27" width="3" height="15"/%3E%3Crect x="47" y="27" width="1" height="15"/%3E%3Crect x="50" y="27" width="3" height="15"/%3E%3Crect x="55" y="27" width="1" height="15"/%3E%3Crect x="10" y="44" width="3" height="15"/%3E%3Crect x="15" y="44" width="1" height="15"/%3E%3Crect x="18" y="44" width="3" height="15"/%3E%3Crect x="23" y="44" width="1" height="15"/%3E%3Crect x="26" y="44" width="3" height="15"/%3E%3Crect x="31" y="44" width="1" height="15"/%3E%3Crect x="34" y="44" width="3" height="15"/%3E%3Crect x="39" y="44" width="1" height="15"/%3E%3Crect x="42" y="44" width="3" height="15"/%3E%3Crect x="47" y="44" width="1" height="15"/%3E%3Crect x="50" y="44" width="3" height="15"/%3E%3Crect x="55" y="44" width="1" height="15"/%3E%3C/g%3E%3Ctext x="100" y="75" text-anchor="middle" font-size="8" font-family="monospace"%3EPDF417%3C/text%3E%3C/svg%3E'
};

function updatePreview() {
    document.getElementById('previewHeader').textContent = headerInput.value || 'TASK QUEST';
    document.getElementById('previewFooter').textContent = footerInput.value || 'Scan to complete';
    
    // Update barcode type and show sample
    const barcodeType = barcodeTypeSelect.value;
    document.getElementById('previewBarcodeType').textContent = barcodeType + ' Barcode';
    const barcodeImg = document.getElementById('previewBarcodeImage');
    if (barcodeSamples[barcodeType]) {
        barcodeImg.src = barcodeSamples[barcodeType];
        barcodeImg.style.display = 'block';
    } else {
        barcodeImg.style.display = 'none';
    }
    
    // Update receipt length and adjust height
    const length = parseInt(receiptLengthInput.value || 300);
    document.getElementById('previewLength').textContent = length;
    // 80mm width = 302px, so calculate height proportionally
    // Assume 300mm default = ~1133px at 96 DPI (300mm / 25.4mm * 96dpi)
    const heightPx = Math.round((length / 25.4) * 96);
    document.getElementById('receiptPreview').style.minHeight = heightPx + 'px';
    
    document.getElementById('previewTimestamp').style.display = showTimestamp.checked ? 'block' : 'none';
    document.getElementById('previewUrgency').style.display = showUrgency.checked ? 'block' : 'none';
    document.getElementById('previewXP').style.display = showXP.checked ? 'block' : 'none';
    document.getElementById('previewCategory').style.display = showCategory.checked ? 'block' : 'none';
    document.getElementById('previewDueDate').style.display = showDueDate.checked ? 'block' : 'none';
}

// Add event listeners
headerInput.addEventListener('input', updatePreview);
footerInput.addEventListener('input', updatePreview);
barcodeTypeSelect.addEventListener('change', updatePreview);
receiptLengthInput.addEventListener('input', updatePreview);
showTimestamp.addEventListener('change', updatePreview);
showUrgency.addEventListener('change', updatePreview);
showXP.addEventListener('change', updatePreview);
showCategory.addEventListener('change', updatePreview);
showDueDate.addEventListener('change', updatePreview);

// Initial update
updatePreview();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
