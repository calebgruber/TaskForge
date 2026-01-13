<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Template Designer';
$db = Database::getInstance();

$message = '';
$error = '';

// Handle template save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    try {
        // Validate and sanitize canvas data
        $canvasData = json_decode($_POST['canvas_data'], true);
        if (!is_array($canvasData)) {
            throw new Exception('Invalid canvas data format');
        }
        
        // Validate each element
        $allowedTypes = ['text', 'barcode', 'shape', 'icon', 'image'];
        foreach ($canvasData as $element) {
            if (!isset($element['type']) || !in_array($element['type'], $allowedTypes)) {
                throw new Exception('Invalid element type in canvas data');
            }
            // Sanitize text content
            if ($element['type'] === 'text' && isset($element['content'])) {
                $element['content'] = htmlspecialchars($element['content'], ENT_QUOTES, 'UTF-8');
            }
        }
        
        $receiptLength = max(100, min(500, (int)($_POST['receipt_length'] ?? 300)));
        
        $sql = "INSERT INTO receipt_templates (
            name, template_type, is_advanced_template, canvas_data, 
            receipt_length, barcode_type
        ) VALUES (?, ?, 1, ?, ?, ?)";
        
        $db->execute($sql, [
            trim($_POST['name']),
            $_POST['template_type'],
            json_encode($canvasData),
            $receiptLength,
            $_POST['barcode_type']
        ]);
        
        $message = 'Advanced template saved successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get icons for palette
$icons = $db->fetchAll("SELECT * FROM icons ORDER BY icon_type, name");

include __DIR__ . '/../../includes/header.php';
?>

<style>
#templateCanvas {
    border: 2px solid #ddd;
    background: white;
    cursor: crosshair;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.canvas-container {
    display: flex;
    justify-content: center;
    background: #f5f7fb;
    padding: 20px;
    border-radius: 8px;
}

.element-palette {
    max-height: 600px;
    overflow-y: auto;
}

.palette-item {
    padding: 10px;
    margin: 5px 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: grab;
    transition: all 0.2s;
}

.palette-item:hover {
    background: #f0f0f0;
    border-color: #206bc4;
}

.palette-item:active {
    cursor: grabbing;
}

.mode-toggle {
    margin-bottom: 20px;
}

.preview-container {
    background: white;
    border: 2px solid #206bc4;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.receipt-preview {
    width: 302px; /* 80mm = ~302px at 96 DPI */
    margin: 0 auto;
    background: white;
    border: 1px solid #ccc;
    padding: 10px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

.selected-element {
    outline: 2px solid #206bc4;
    outline-offset: 2px;
}

.element-controls {
    position: fixed;
    top: 100px;
    right: 20px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: none;
    z-index: 1000;
}

.shape-preview {
    width: 30px;
    height: 30px;
    display: inline-block;
    margin-right: 5px;
}
</style>

<div class="page-body">
    <div class="container-fluid">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"><i class="ti ti-palette"></i> Advanced Template Designer</h2>
                    <div class="text-muted mt-1">Drag-and-drop canvas editor for receipt templates</div>
                </div>
                <div class="col-auto">
                    <a href="/admin/templates.php" class="btn btn-outline-primary">
                        <i class="ti ti-arrow-left"></i> Back to Easy Mode
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
            <!-- Left Sidebar - Element Palette -->
            <div class="col-lg-3">
                <div class="card element-palette">
                    <div class="card-header">
                        <h3 class="card-title">Elements</h3>
                    </div>
                    <div class="card-body">
                        <h4 class="mb-3">Variables</h4>
                        <small class="text-muted d-block mb-2">These will be replaced with actual task data when printing</small>
                        <div class="palette-item" draggable="true" data-type="text" data-content="{{task_title}}">
                            <i class="ti ti-file-text"></i> Task Title
                        </div>
                        <div class="palette-item" draggable="true" data-type="text" data-content="{{due_date}}">
                            <i class="ti ti-calendar"></i> Due Date
                        </div>
                        <div class="palette-item" draggable="true" data-type="text" data-content="{{category}}">
                            <i class="ti ti-folder"></i> Category
                        </div>
                        <div class="palette-item" draggable="true" data-type="text" data-content="{{urgency}}">
                            <i class="ti ti-alert-triangle"></i> Urgency Level
                        </div>
                        <div class="palette-item" draggable="true" data-type="text" data-content="{{xp_value}}">
                            <i class="ti ti-trophy"></i> XP Value
                        </div>
                        <div class="palette-item" draggable="true" data-type="text" data-content="{{timestamp}}">
                            <i class="ti ti-clock"></i> Timestamp
                        </div>
                        <div class="palette-item" draggable="true" data-type="text" data-content="{{barcode}}">
                            <i class="ti ti-barcode"></i> Barcode Text
                        </div>
                        <div class="palette-item" draggable="true" data-type="text" data-content="{{user_name}}">
                            <i class="ti ti-user"></i> User Name
                        </div>
                        <div class="palette-item" draggable="true" data-type="text" data-content="{{user_level}}">
                            <i class="ti ti-star"></i> User Level
                        </div>
                        
                        <h4 class="mt-4 mb-3">Text</h4>
                        <div class="palette-item" draggable="true" data-type="text" data-content="Sample Text">
                            <i class="ti ti-typography"></i> Text Block
                        </div>
                        
                        <h4 class="mt-4 mb-3">Barcodes</h4>
                        <div class="palette-item" draggable="true" data-type="barcode" data-barcode-type="CODE128">
                            <i class="ti ti-barcode"></i> CODE128
                        </div>
                        <div class="palette-item" draggable="true" data-type="barcode" data-barcode-type="CODE39">
                            <i class="ti ti-barcode"></i> CODE39
                        </div>
                        <div class="palette-item" draggable="true" data-type="barcode" data-barcode-type="AZTEC">
                            <i class="ti ti-qrcode"></i> Aztec
                        </div>
                        <div class="palette-item" draggable="true" data-type="barcode" data-barcode-type="PDF417">
                            <i class="ti ti-qrcode"></i> PDF417
                        </div>
                        
                        <h4 class="mt-4 mb-3">Shapes</h4>
                        <div class="palette-item" draggable="true" data-type="shape" data-shape="square">
                            <svg class="shape-preview" viewBox="0 0 30 30"><rect width="30" height="30" fill="#333"/></svg>
                            Square
                        </div>
                        <div class="palette-item" draggable="true" data-type="shape" data-shape="circle">
                            <svg class="shape-preview" viewBox="0 0 30 30"><circle cx="15" cy="15" r="15" fill="#333"/></svg>
                            Circle
                        </div>
                        <div class="palette-item" draggable="true" data-type="shape" data-shape="rectangle">
                            <svg class="shape-preview" viewBox="0 0 30 30"><rect width="30" height="15" y="7.5" fill="#333"/></svg>
                            Rectangle
                        </div>
                        <div class="palette-item" draggable="true" data-type="shape" data-shape="triangle">
                            <svg class="shape-preview" viewBox="0 0 30 30"><polygon points="15,0 30,30 0,30" fill="#333"/></svg>
                            Triangle
                        </div>
                        
                        <h4 class="mt-4 mb-3">Icons</h4>
                        <div class="palette-item" draggable="true" data-type="icon" data-icon="tabler:check">
                            <i class="ti ti-check"></i> Checkmark
                        </div>
                        <div class="palette-item" draggable="true" data-type="icon" data-icon="tabler:star">
                            <i class="ti ti-star"></i> Star
                        </div>
                        <div class="palette-item" draggable="true" data-type="icon" data-icon="tabler:target">
                            <i class="ti ti-target"></i> Target
                        </div>
                        <div class="palette-item" draggable="true" data-type="icon" data-icon="tabler:flame">
                            <i class="ti ti-flame"></i> Flame
                        </div>
                        
                        <h4 class="mt-4 mb-3">Images</h4>
                        <div class="palette-item" draggable="true" data-type="image">
                            <i class="ti ti-photo"></i> Upload Image
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Center - Canvas -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center w-100">
                            <div class="col">
                                <h3 class="card-title">80mm Receipt Canvas</h3>
                            </div>
                            <div class="col-auto">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-danger" id="deleteElement">
                                        <i class="ti ti-trash"></i> Delete
                                    </button>
                                    <button type="button" class="btn btn-outline-warning" id="clearCanvas">
                                        <i class="ti ti-eraser"></i> Clear All
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="canvas-container">
                            <canvas id="templateCanvas" width="302" height="600"></canvas>
                        </div>
                        
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Template Name</label>
                                    <input type="text" id="templateName" class="form-control" placeholder="My Custom Template">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Template Type</label>
                                    <select id="templateType" class="form-select">
                                        <option value="task">Task Receipt</option>
                                        <option value="completion">Completion Receipt</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Barcode Type</label>
                                    <select id="barcodeType" class="form-select">
                                        <option value="CODE128">CODE128</option>
                                        <option value="CODE39">CODE39</option>
                                        <option value="AZTEC">Aztec</option>
                                        <option value="PDF417">PDF417</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Receipt Length (mm)</label>
                                    <input type="number" id="receiptLength" class="form-control" value="300" min="100" max="500">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex">
                            <button type="button" class="btn btn-primary" id="saveTemplate">
                                <i class="ti ti-device-floppy"></i> Save Template
                            </button>
                            <button type="button" class="btn btn-outline-secondary ms-auto" id="previewBtn">
                                <i class="ti ti-eye"></i> Preview
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Sidebar - Element Properties -->
            <div class="col-lg-3">
                <div class="card" id="propertiesPanel" style="display: none;">
                    <div class="card-header">
                        <h3 class="card-title">Element Properties</h3>
                    </div>
                    <div class="card-body" id="propertiesContent">
                        <p class="text-muted">Select an element to edit its properties</p>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Instructions</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="ti ti-hand-click text-blue"></i> Drag elements from left palette to canvas</li>
                            <li class="mb-2"><i class="ti ti-hand-move text-green"></i> Click and drag elements to reposition</li>
                            <li class="mb-2"><i class="ti ti-edit text-orange"></i> Click element to edit properties</li>
                            <li class="mb-2"><i class="ti ti-trash text-red"></i> Select and click Delete to remove</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Receipt Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="preview-container">
                    <div class="receipt-preview" id="receiptPreview">
                        <!-- Preview will be rendered here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const canvas = document.getElementById('templateCanvas');
const ctx = canvas.getContext('2d');
let elements = [];
let selectedElement = null;
let isDragging = false;
let dragOffsetX = 0;
let dragOffsetY = 0;

// Barcode sample images
const barcodeImages = {};
const barcodeSamples = {
    'CODE128': 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="60"%3E%3Crect fill="%23fff" width="200" height="60"/%3E%3Cg fill="%23000"%3E%3Crect x="10" width="2" height="50"/%3E%3Crect x="15" width="1" height="50"/%3E%3Crect x="18" width="3" height="50"/%3E%3Crect x="24" width="2" height="50"/%3E%3Crect x="28" width="1" height="50"/%3E%3Crect x="32" width="3" height="50"/%3E%3Crect x="38" width="1" height="50"/%3E%3Crect x="42" width="2" height="50"/%3E%3Crect x="47" width="3" height="50"/%3E%3Crect x="53" width="1" height="50"/%3E%3Crect x="57" width="2" height="50"/%3E%3Crect x="62" width="3" height="50"/%3E%3Crect x="68" width="1" height="50"/%3E%3Crect x="72" width="2" height="50"/%3E%3Crect x="77" width="1" height="50"/%3E%3Crect x="81" width="3" height="50"/%3E%3Crect x="87" width="2" height="50"/%3E%3Crect x="92" width="1" height="50"/%3E%3Crect x="96" width="3" height="50"/%3E%3Crect x="102" width="1" height="50"/%3E%3Crect x="106" width="2" height="50"/%3E%3Crect x="111" width="3" height="50"/%3E%3Crect x="117" width="1" height="50"/%3E%3Crect x="121" width="2" height="50"/%3E%3Crect x="126" width="3" height="50"/%3E%3Crect x="132" width="1" height="50"/%3E%3Crect x="136" width="2" height="50"/%3E%3Crect x="141" width="1" height="50"/%3E%3Crect x="145" width="3" height="50"/%3E%3Crect x="151" width="2" height="50"/%3E%3Crect x="156" width="1" height="50"/%3E%3Crect x="160" width="3" height="50"/%3E%3Crect x="166" width="1" height="50"/%3E%3Crect x="170" width="2" height="50"/%3E%3Crect x="175" width="3" height="50"/%3E%3Crect x="181" width="1" height="50"/%3E%3Crect x="185" width="2" height="50"/%3E%3C/g%3E%3C/svg%3E',
    'CODE39': 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="60"%3E%3Crect fill="%23fff" width="200" height="60"/%3E%3Cg fill="%23000"%3E%3Crect x="10" width="3" height="50"/%3E%3Crect x="18" width="1" height="50"/%3E%3Crect x="22" width="3" height="50"/%3E%3Crect x="30" width="1" height="50"/%3E%3Crect x="35" width="3" height="50"/%3E%3Crect x="43" width="1" height="50"/%3E%3Crect x="48" width="3" height="50"/%3E%3Crect x="56" width="1" height="50"/%3E%3Crect x="61" width="3" height="50"/%3E%3Crect x="69" width="1" height="50"/%3E%3Crect x="74" width="3" height="50"/%3E%3Crect x="82" width="1" height="50"/%3E%3Crect x="87" width="3" height="50"/%3E%3Crect x="95" width="1" height="50"/%3E%3Crect x="100" width="3" height="50"/%3E%3Crect x="108" width="1" height="50"/%3E%3Crect x="113" width="3" height="50"/%3E%3Crect x="121" width="1" height="50"/%3E%3Crect x="126" width="3" height="50"/%3E%3Crect x="134" width="1" height="50"/%3E%3Crect x="139" width="3" height="50"/%3E%3Crect x="147" width="1" height="50"/%3E%3Crect x="152" width="3" height="50"/%3E%3Crect x="160" width="1" height="50"/%3E%3Crect x="165" width="3" height="50"/%3E%3Crect x="173" width="1" height="50"/%3E%3Crect x="178" width="3" height="50"/%3E%3C/g%3E%3C/svg%3E',
    'AZTEC': 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="100"%3E%3Crect fill="%23fff" width="100" height="100"/%3E%3Cg fill="%23000"%3E%3Crect x="20" y="20" width="5" height="5"/%3E%3Crect x="30" y="20" width="5" height="5"/%3E%3Crect x="40" y="20" width="5" height="5"/%3E%3Crect x="50" y="20" width="5" height="5"/%3E%3Crect x="60" y="20" width="5" height="5"/%3E%3Crect x="70" y="20" width="5" height="5"/%3E%3Crect x="20" y="30" width="5" height="5"/%3E%3Crect x="70" y="30" width="5" height="5"/%3E%3Crect x="20" y="40" width="5" height="5"/%3E%3Crect x="30" y="40" width="5" height="5"/%3E%3Crect x="35" y="35" width="25" height="25"/%3E%3Crect x="60" y="40" width="5" height="5"/%3E%3Crect x="70" y="40" width="5" height="5"/%3E%3Crect x="20" y="50" width="5" height="5"/%3E%3Crect x="70" y="50" width="5" height="5"/%3E%3Crect x="20" y="60" width="5" height="5"/%3E%3Crect x="30" y="60" width="5" height="5"/%3E%3Crect x="60" y="60" width="5" height="5"/%3E%3Crect x="70" y="60" width="5" height="5"/%3E%3Crect x="20" y="70" width="5" height="5"/%3E%3Crect x="30" y="70" width="5" height="5"/%3E%3Crect x="40" y="70" width="5" height="5"/%3E%3Crect x="50" y="70" width="5" height="5"/%3E%3Crect x="60" y="70" width="5" height="5"/%3E%3Crect x="70" y="70" width="5" height="5"/%3E%3C/g%3E%3C/svg%3E',
    'PDF417': 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="80"%3E%3Crect fill="%23fff" width="200" height="80"/%3E%3Cg fill="%23000"%3E%3Crect x="10" y="10" width="3" height="15"/%3E%3Crect x="15" y="10" width="1" height="15"/%3E%3Crect x="18" y="10" width="3" height="15"/%3E%3Crect x="23" y="10" width="1" height="15"/%3E%3Crect x="26" y="10" width="3" height="15"/%3E%3Crect x="31" y="10" width="1" height="15"/%3E%3Crect x="34" y="10" width="3" height="15"/%3E%3Crect x="39" y="10" width="1" height="15"/%3E%3Crect x="42" y="10" width="3" height="15"/%3E%3Crect x="47" y="10" width="1" height="15"/%3E%3Crect x="50" y="10" width="3" height="15"/%3E%3Crect x="55" y="10" width="1" height="15"/%3E%3Crect x="10" y="27" width="3" height="15"/%3E%3Crect x="15" y="27" width="1" height="15"/%3E%3Crect x="18" y="27" width="3" height="15"/%3E%3Crect x="23" y="27" width="1" height="15"/%3E%3Crect x="26" y="27" width="3" height="15"/%3E%3Crect x="31" y="27" width="1" height="15"/%3E%3Crect x="34" y="27" width="3" height="15"/%3E%3Crect x="39" y="27" width="1" height="15"/%3E%3Crect x="42" y="27" width="3" height="15"/%3E%3Crect x="47" y="27" width="1" height="15"/%3E%3Crect x="50" y="27" width="3" height="15"/%3E%3Crect x="55" y="27" width="1" height="15"/%3E%3Crect x="10" y="44" width="3" height="15"/%3E%3Crect x="15" y="44" width="1" height="15"/%3E%3Crect x="18" y="44" width="3" height="15"/%3E%3Crect x="23" y="44" width="1" height="15"/%3E%3Crect x="26" y="44" width="3" height="15"/%3E%3Crect x="31" y="44" width="1" height="15"/%3E%3Crect x="34" y="44" width="3" height="15"/%3E%3Crect x="39" y="44" width="1" height="15"/%3E%3Crect x="42" y="44" width="3" height="15"/%3E%3Crect x="47" y="44" width="1" height="15"/%3E%3Crect x="50" y="44" width="3" height="15"/%3E%3Crect x="55" y="44" width="1" height="15"/%3E%3C/g%3E%3C/svg%3E'
};

// Preload barcode images
Object.keys(barcodeSamples).forEach(type => {
    const img = new Image();
    img.src = barcodeSamples[type];
    barcodeImages[type] = img;
});

// Update canvas height based on receipt length
function updateCanvasHeight() {
    const length = parseInt(document.getElementById('receiptLength').value || 300);
    // 80mm width = 302px at 96 DPI, calculate proportional height
    // length in mm / 25.4 * 96 DPI = pixels
    const heightPx = Math.round((length / 25.4) * 96);
    canvas.height = Math.max(400, Math.min(heightPx, 2000)); // Limit between 400-2000px
    redrawCanvas();
}

document.getElementById('receiptLength').addEventListener('input', updateCanvasHeight);

// Initialize canvas
function clearCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
}

clearCanvas();

// Drag and drop from palette
document.querySelectorAll('.palette-item').forEach(item => {
    item.addEventListener('dragstart', (e) => {
        e.dataTransfer.setData('elementType', item.dataset.type);
        e.dataTransfer.setData('elementData', JSON.stringify(item.dataset));
    });
});

canvas.addEventListener('dragover', (e) => {
    e.preventDefault();
});

canvas.addEventListener('drop', (e) => {
    e.preventDefault();
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    const type = e.dataTransfer.getData('elementType');
    const data = JSON.parse(e.dataTransfer.getData('elementData'));
    
    const element = {
        id: Date.now(),
        type: type,
        x: x,
        y: y,
        width: 100,
        height: 40,
        ...data
    };
    
    if (type === 'text') {
        element.content = data.content || 'Sample Text';
        element.fontSize = 16;
        element.fontWeight = 'normal';
        element.textAlign = 'left';
    } else if (type === 'barcode') {
        element.barcodeType = data.barcodeType;
        element.height = 60;
        element.width = 200;
    } else if (type === 'shape') {
        element.shape = data.shape;
        element.fillColor = '#000000';
    } else if (type === 'icon') {
        element.icon = data.icon;
        element.width = 48;
        element.height = 48;
    }
    
    elements.push(element);
    redrawCanvas();
});

// Canvas click and drag
canvas.addEventListener('mousedown', (e) => {
    const rect = canvas.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    selectedElement = elements.find(el => 
        x >= el.x && x <= el.x + el.width &&
        y >= el.y && y <= el.y + el.height
    );
    
    if (selectedElement) {
        isDragging = true;
        dragOffsetX = x - selectedElement.x;
        dragOffsetY = y - selectedElement.y;
        showProperties(selectedElement);
    } else {
        hideProperties();
    }
    
    redrawCanvas();
});

canvas.addEventListener('mousemove', (e) => {
    if (isDragging && selectedElement) {
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        selectedElement.x = x - dragOffsetX;
        selectedElement.y = y - dragOffsetY;
        
        redrawCanvas();
    }
});

canvas.addEventListener('mouseup', () => {
    isDragging = false;
});

// Redraw canvas
function redrawCanvas() {
    clearCanvas();
    
    elements.forEach(el => {
        if (el === selectedElement) {
            ctx.strokeStyle = '#206bc4';
            ctx.lineWidth = 2;
            ctx.strokeRect(el.x - 2, el.y - 2, el.width + 4, el.height + 4);
        }
        
        if (el.type === 'text') {
            ctx.font = `${el.fontWeight} ${el.fontSize}px Arial`;
            ctx.fillStyle = '#000000';
            ctx.textAlign = el.textAlign || 'left';
            // Show sample data for variables
            let displayText = el.content;
            if (displayText === '{{task_title}}') displayText = 'Complete Project Report';
            else if (displayText === '{{due_date}}') displayText = 'Due: 2024-12-31 5:00 PM';
            else if (displayText === '{{category}}') displayText = 'Category: Work';
            else if (displayText === '{{urgency}}') displayText = 'Urgency: High';
            else if (displayText === '{{xp_value}}') displayText = 'XP: 150';
            else if (displayText === '{{timestamp}}') displayText = '2024-12-25 10:30:00';
            else if (displayText === '{{barcode}}') displayText = 'TF20241225ABCD1234';
            else if (displayText === '{{user_name}}') displayText = 'User: John Doe';
            else if (displayText === '{{user_level}}') displayText = 'Level: 12';
            
            ctx.fillText(displayText, el.x, el.y + el.fontSize);
        } else if (el.type === 'barcode') {
            // Draw barcode sample image if available
            const img = barcodeImages[el.barcodeType];
            if (img && img.complete) {
                ctx.drawImage(img, el.x, el.y, el.width, el.height);
            } else {
                // Fallback to gray box with text
                ctx.fillStyle = '#f0f0f0';
                ctx.fillRect(el.x, el.y, el.width, el.height);
                ctx.strokeStyle = '#000';
                ctx.strokeRect(el.x, el.y, el.width, el.height);
                ctx.fillStyle = '#666';
                ctx.font = '10px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(el.barcodeType, el.x + el.width/2, el.y + el.height/2);
            }
        } else if (el.type === 'shape') {
            ctx.fillStyle = el.fillColor || '#000000';
            if (el.shape === 'square' || el.shape === 'rectangle') {
                ctx.fillRect(el.x, el.y, el.width, el.height);
            } else if (el.shape === 'circle') {
                ctx.beginPath();
                ctx.arc(el.x + el.width/2, el.y + el.height/2, el.width/2, 0, 2 * Math.PI);
                ctx.fill();
            } else if (el.shape === 'triangle') {
                ctx.beginPath();
                ctx.moveTo(el.x + el.width/2, el.y);
                ctx.lineTo(el.x + el.width, el.y + el.height);
                ctx.lineTo(el.x, el.y + el.height);
                ctx.closePath();
                ctx.fill();
            }
        } else if (el.type === 'icon') {
            ctx.fillStyle = '#666';
            ctx.font = '14px Arial';
            ctx.fillText('Icon: ' + el.icon.split(':')[1], el.x, el.y + 20);
        }
    });
}

// Properties panel
function showProperties(element) {
    const panel = document.getElementById('propertiesPanel');
    const content = document.getElementById('propertiesContent');
    
    let html = '';
    
    if (element.type === 'text') {
        html = `
            <div class="mb-3">
                <label class="form-label">Text Content</label>
                <input type="text" class="form-control" id="propContent" value="${element.content}">
                <small class="form-hint">Use variables like {{task_title}}, {{due_date}}, {{category}}, {{urgency}}, {{xp_value}}, {{timestamp}}, {{barcode}}, {{user_name}}, {{user_level}}</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Font Size</label>
                <input type="number" class="form-control" id="propFontSize" value="${element.fontSize}" min="8" max="48">
            </div>
            <div class="mb-3">
                <label class="form-label">Font Weight</label>
                <select class="form-select" id="propFontWeight">
                    <option value="normal" ${element.fontWeight === 'normal' ? 'selected' : ''}>Normal</option>
                    <option value="bold" ${element.fontWeight === 'bold' ? 'selected' : ''}>Bold</option>
                </select>
            </div>
        `;
    } else if (element.type === 'shape') {
        html = `
            <div class="mb-3">
                <label class="form-label">Fill Color</label>
                <input type="color" class="form-control" id="propFillColor" value="${element.fillColor}">
            </div>
            <div class="mb-3">
                <label class="form-label">Width</label>
                <input type="number" class="form-control" id="propWidth" value="${element.width}">
            </div>
            <div class="mb-3">
                <label class="form-label">Height</label>
                <input type="number" class="form-control" id="propHeight" value="${element.height}">
            </div>
        `;
    } else if (element.type === 'barcode') {
        html = `
            <div class="mb-3">
                <label class="form-label">Barcode Type</label>
                <select class="form-select" id="propBarcodeType">
                    <option value="CODE128" ${element.barcodeType === 'CODE128' ? 'selected' : ''}>CODE128</option>
                    <option value="CODE39" ${element.barcodeType === 'CODE39' ? 'selected' : ''}>CODE39</option>
                    <option value="AZTEC" ${element.barcodeType === 'AZTEC' ? 'selected' : ''}>Aztec</option>
                    <option value="PDF417" ${element.barcodeType === 'PDF417' ? 'selected' : ''}>PDF417</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Width</label>
                <input type="number" class="form-control" id="propWidth" value="${element.width}">
            </div>
        `;
    }
    
    content.innerHTML = html + `
        <button class="btn btn-primary btn-sm w-100 mt-2" onclick="applyProperties()">
            Apply Changes
        </button>
    `;
    
    panel.style.display = 'block';
}

function hideProperties() {
    document.getElementById('propertiesPanel').style.display = 'none';
    selectedElement = null;
}

function applyProperties() {
    if (!selectedElement) return;
    
    if (selectedElement.type === 'text') {
        selectedElement.content = document.getElementById('propContent').value;
        selectedElement.fontSize = parseInt(document.getElementById('propFontSize').value);
        selectedElement.fontWeight = document.getElementById('propFontWeight').value;
    } else if (selectedElement.type === 'shape') {
        selectedElement.fillColor = document.getElementById('propFillColor').value;
        selectedElement.width = parseInt(document.getElementById('propWidth').value);
        selectedElement.height = parseInt(document.getElementById('propHeight').value);
    } else if (selectedElement.type === 'barcode') {
        selectedElement.barcodeType = document.getElementById('propBarcodeType').value;
        selectedElement.width = parseInt(document.getElementById('propWidth').value);
    }
    
    redrawCanvas();
}

// Delete element
document.getElementById('deleteElement').addEventListener('click', () => {
    if (selectedElement) {
        elements = elements.filter(el => el !== selectedElement);
        selectedElement = null;
        hideProperties();
        redrawCanvas();
    }
});

// Clear canvas
document.getElementById('clearCanvas').addEventListener('click', () => {
    if (confirm('Clear all elements from canvas?')) {
        elements = [];
        selectedElement = null;
        hideProperties();
        redrawCanvas();
    }
});

// Save template
document.getElementById('saveTemplate').addEventListener('click', () => {
    const name = document.getElementById('templateName').value;
    if (!name) {
        alert('Please enter a template name');
        return;
    }
    
    const templateData = {
        name: name,
        template_type: document.getElementById('templateType').value,
        barcode_type: document.getElementById('barcodeType').value,
        receipt_length: document.getElementById('receiptLength').value,
        canvas_data: JSON.stringify(elements)
    };
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="save_template" value="1">
        <input type="hidden" name="name" value="${templateData.name}">
        <input type="hidden" name="template_type" value="${templateData.template_type}">
        <input type="hidden" name="barcode_type" value="${templateData.barcode_type}">
        <input type="hidden" name="receipt_length" value="${templateData.receipt_length}">
        <input type="hidden" name="canvas_data" value='${templateData.canvas_data}'>
    `;
    document.body.appendChild(form);
    form.submit();
});

// Preview
document.getElementById('previewBtn').addEventListener('click', () => {
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    
    // Generate preview
    const preview = document.getElementById('receiptPreview');
    const length = parseInt(document.getElementById('receiptLength').value || 300);
    const heightPx = Math.round((length / 25.4) * 96);
    preview.style.minHeight = heightPx + 'px';
    preview.innerHTML = '<div style="text-align: center; padding: 20px;">Canvas preview with ' + elements.length + ' elements<br>Receipt length: ' + length + 'mm</div>';
    
    modal.show();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
