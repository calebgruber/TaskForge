<?php
/**
 * Tablet Mode - Scanner Page
 */

require_once __DIR__ . '/../../config/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Scanner';
require_once __DIR__ . '/header.php';
?>

<div class="tablet-container">
    <div class="tablet-card" style="text-align: center; padding: 80px 40px;">
        <i class="ti ti-scan icon-huge" style="color: #206bc4; margin-bottom: 30px;"></i>
        <h1 style="font-size: 48px; font-weight: 700; margin-bottom: 20px;">Barcode Scanner</h1>
        <p style="font-size: 24px; color: #64748b; margin-bottom: 50px;">
            Scan a task barcode to complete it
        </p>
        
        <!-- Scanner Mode Tabs -->
        <div style="display: flex; gap: 20px; margin-bottom: 40px; max-width: 800px; margin-left: auto; margin-right: auto;">
            <button onclick="switchMode('keyboard')" id="keyboardBtn" class="btn-huge" style="flex: 1; background: #206bc4; color: white; border: none;">
                <i class="ti ti-keyboard"></i> Keyboard Scanner
            </button>
            <button onclick="switchMode('camera')" id="cameraBtn" class="btn-huge" style="flex: 1; background: white; color: #206bc4; border: 2px solid #206bc4;">
                <i class="ti ti-camera"></i> Camera Scanner
            </button>
        </div>
        
        <!-- Keyboard Scanner Mode -->
        <div id="keyboardMode">
            <input type="text" id="barcodeInput" class="form-control-huge" placeholder="Scan barcode here..." autofocus>
            <p style="font-size: 20px; color: #64748b; margin-top: 30px;">
                Focus will remain on the input field for scanning
            </p>
        </div>
        
        <!-- Camera Scanner Mode -->
        <div id="cameraMode" style="display: none;">
            <div id="readerContainer" style="max-width: 800px; margin: 0 auto;"></div>
            <button onclick="startCamera()" id="startCameraBtn" class="btn-huge" style="background: #10b981; color: white; border: none; margin-top: 30px;">
                <i class="ti ti-camera"></i> Start Camera
            </button>
            <button onclick="stopCamera()" id="stopCameraBtn" class="btn-huge" style="background: #ef4444; color: white; border: none; margin-top: 30px; display: none;">
                <i class="ti ti-camera-off"></i> Stop Camera
            </button>
        </div>
        
        <!-- Result Message -->
        <div id="resultMessage" style="margin-top: 40px;"></div>
    </div>
</div>

<!-- HTML5 QR Code Library -->
<script src="https://unpkg.com/html5-qrcode"></script>
<!-- Confetti -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<script>
let html5QrCode;
let currentMode = 'keyboard';

function switchMode(mode) {
    currentMode = mode;
    if (mode === 'keyboard') {
        document.getElementById('keyboardMode').style.display = 'block';
        document.getElementById('cameraMode').style.display = 'none';
        document.getElementById('keyboardBtn').style.background = '#206bc4';
        document.getElementById('keyboardBtn').style.color = 'white';
        document.getElementById('keyboardBtn').style.border = 'none';
        document.getElementById('cameraBtn').style.background = 'white';
        document.getElementById('cameraBtn').style.color = '#206bc4';
        document.getElementById('cameraBtn').style.border = '2px solid #206bc4';
        document.getElementById('barcodeInput').focus();
        stopCamera();
    } else {
        document.getElementById('keyboardMode').style.display = 'none';
        document.getElementById('cameraMode').style.display = 'block';
        document.getElementById('cameraBtn').style.background = '#206bc4';
        document.getElementById('cameraBtn').style.color = 'white';
        document.getElementById('cameraBtn').style.border = 'none';
        document.getElementById('keyboardBtn').style.background = 'white';
        document.getElementById('keyboardBtn').style.color = '#206bc4';
        document.getElementById('keyboardBtn').style.border = '2px solid #206bc4';
    }
}

// Keyboard scanner
document.getElementById('barcodeInput').addEventListener('change', function() {
    processBarcode(this.value);
    this.value = '';
});

document.getElementById('barcodeInput').addEventListener('blur', function() {
    setTimeout(() => this.focus(), 100);
});

// Camera scanner
function startCamera() {
    html5QrCode = new Html5Qrcode("readerContainer");
    const config = { 
        fps: 10,
        qrbox: { width: 600, height: 300 },
        formatsToSupport: [
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.AZTEC,
            Html5QrcodeSupportedFormats.PDF_417,
            Html5QrcodeSupportedFormats.QR_CODE
        ]
    };
    
    html5QrCode.start(
        { facingMode: "environment" },
        config,
        (decodedText) => {
            processBarcode(decodedText);
        }
    ).then(() => {
        document.getElementById('startCameraBtn').style.display = 'none';
        document.getElementById('stopCameraBtn').style.display = 'block';
    }).catch(err => {
        showMessage('Error starting camera: ' + err, 'error');
    });
}

function stopCamera() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            document.getElementById('startCameraBtn').style.display = 'block';
            document.getElementById('stopCameraBtn').style.display = 'none';
        }).catch(err => {
            console.error('Error stopping camera:', err);
        });
    }
}

function processBarcode(barcode) {
    fetch('../api/scan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ barcode: barcode })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showMessage(`✅ Quest Complete!<br><strong>${data.task_title}</strong><br>+${data.xp_earned} XP`, 'success');
            triggerConfetti();
            
            if (data.level_up) {
                triggerLevelUpConfetti();
                setTimeout(() => {
                    showMessage(`🎉 Level Up!<br>You are now Level ${data.new_level}!`, 'levelup');
                }, 1000);
            }
        } else {
            showMessage('❌ ' + data.message, 'error');
        }
    });
}

function showMessage(msg, type) {
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        levelup: '#8b5cf6'
    };
    
    document.getElementById('resultMessage').innerHTML = `
        <div style="background: white; border: 4px solid ${colors[type]}; border-radius: 20px; padding: 40px; font-size: 28px; font-weight: 700; color: ${colors[type]};">
            ${msg}
        </div>
    `;
    
    setTimeout(() => {
        document.getElementById('resultMessage').innerHTML = '';
    }, 5000);
}

function triggerConfetti() {
    confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 }
    });
}

function triggerLevelUpConfetti() {
    const duration = 3000;
    const end = Date.now() + duration;
    
    (function frame() {
        confetti({
            particleCount: 2,
            angle: 60,
            spread: 55,
            origin: { x: 0 }
        });
        confetti({
            particleCount: 2,
            angle: 120,
            spread: 55,
            origin: { x: 1 }
        });
        
        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    }());
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
