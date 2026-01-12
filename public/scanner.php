<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = 'Barcode Scanner';
$user = getCurrentUser();

include 'includes/header.php';
?>

<!-- Canvas Confetti Library -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>

<!-- HTML5 QR Code / Barcode Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<style>
#cameraReader {
    width: 100%;
    max-width: 500px;
    margin: 0 auto;
}
#cameraReader video {
    border-radius: 8px;
}
.camera-controls {
    margin-top: 15px;
}
</style>

<div class="page-body">
    <div class="container-xl">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">
                            <i class="ti ti-scan"></i> Barcode Scanner
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Scanner Mode Toggle -->
                        <div class="mb-3">
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="scannerMode" id="modeKeyboard" checked autocomplete="off">
                                <label class="btn btn-outline-primary" for="modeKeyboard">
                                    <i class="ti ti-keyboard"></i> Keyboard Scanner
                                </label>
                                
                                <input type="radio" class="btn-check" name="scannerMode" id="modeCamera" autocomplete="off">
                                <label class="btn btn-outline-primary" for="modeCamera">
                                    <i class="ti ti-camera"></i> Camera Scanner
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" id="infoKeyboard">
                            <div class="d-flex">
                                <div><i class="ti ti-info-circle"></i></div>
                                <div class="ms-2">
                                    <h4 class="alert-title">Scanner Ready</h4>
                                    <div class="text-muted">
                                        Scan a task barcode to instantly complete it and earn XP!
                                        Your Zebra DS81XX-HC scanner will automatically input the barcode.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" id="infoCamera" style="display: none;">
                            <div class="d-flex">
                                <div><i class="ti ti-info-circle"></i></div>
                                <div class="ms-2">
                                    <h4 class="alert-title">Camera Scanner</h4>
                                    <div class="text-muted">
                                        Point your phone camera at the task barcode. The scanner will detect and process it automatically.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Keyboard Scanner Section -->
                        <div id="keyboardScanner">
                            <div class="mb-4 text-center">
                                <div class="scanner-ready barcode-display py-5" id="scannerStatus">
                                    <i class="ti ti-scan" style="font-size: 4rem;"></i>
                                    <div class="h3 mt-3">Ready to scan</div>
                                    <div class="text-muted">Point scanner at task barcode</div>
                                </div>
                            </div>
                            
                            <form id="scannerForm">
                                <div class="mb-3">
                                    <label class="form-label">Barcode Input</label>
                                    <input type="text" 
                                           id="barcodeInput" 
                                           name="barcode" 
                                           class="form-control form-control-lg" 
                                           placeholder="Focus here and scan barcode"
                                           autocomplete="off"
                                           autofocus>
                                    <small class="form-hint">Scanner will automatically input here</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ti ti-check"></i> Complete Task Manually
                                </button>
                            </form>
                        </div>
                        
                        <!-- Camera Scanner Section -->
                        <div id="cameraScanner" style="display: none;">
                            <div id="cameraReader"></div>
                            <div class="camera-controls text-center">
                                <button type="button" class="btn btn-success" id="startCamera">
                                    <i class="ti ti-camera"></i> Start Camera
                                </button>
                                <button type="button" class="btn btn-danger" id="stopCamera" style="display: none;">
                                    <i class="ti ti-camera-off"></i> Stop Camera
                                </button>
                            </div>
                        </div>
                        
                        <div id="resultContainer" class="mt-4"></div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Recent Scans</h3>
                    </div>
                    <div class="card-body">
                        <div id="recentScans" class="list-group list-group-flush">
                            <div class="text-muted text-center py-3">No scans yet</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$pageScript = <<<'SCRIPT'
const barcodeInput = document.getElementById('barcodeInput');
const scannerForm = document.getElementById('scannerForm');
const resultContainer = document.getElementById('resultContainer');
const recentScans = document.getElementById('recentScans');

// Scanner mode elements
const modeKeyboard = document.getElementById('modeKeyboard');
const modeCamera = document.getElementById('modeCamera');
const keyboardScanner = document.getElementById('keyboardScanner');
const cameraScanner = document.getElementById('cameraScanner');
const infoKeyboard = document.getElementById('infoKeyboard');
const infoCamera = document.getElementById('infoCamera');
const startCameraBtn = document.getElementById('startCamera');
const stopCameraBtn = document.getElementById('stopCamera');

let scanHistory = [];
let html5QrCode = null;
let isCameraActive = false;

// Scanner mode toggle
modeKeyboard.addEventListener('change', () => {
    if (modeKeyboard.checked) {
        keyboardScanner.style.display = 'block';
        cameraScanner.style.display = 'none';
        infoKeyboard.style.display = 'block';
        infoCamera.style.display = 'none';
        stopCameraScanner();
        barcodeInput.focus();
    }
});

modeCamera.addEventListener('change', () => {
    if (modeCamera.checked) {
        keyboardScanner.style.display = 'none';
        cameraScanner.style.display = 'block';
        infoKeyboard.style.display = 'none';
        infoCamera.style.display = 'block';
    }
});

// Camera scanner functions
startCameraBtn.addEventListener('click', async () => {
    try {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("cameraReader");
        }
        
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0,
            formatsToSupport: [
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.QR_CODE
            ]
        };
        
        await html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanError
        );
        
        isCameraActive = true;
        startCameraBtn.style.display = 'none';
        stopCameraBtn.style.display = 'inline-block';
    } catch (err) {
        showResult(`
            <div class="alert alert-danger">
                <h4 class="alert-title">
                    <i class="ti ti-alert-circle"></i> Camera Error
                </h4>
                <div>Failed to start camera: ${err.message || err}</div>
                <div class="small mt-2">Make sure to allow camera permissions in your browser.</div>
            </div>
        `, 'danger');
    }
});

stopCameraBtn.addEventListener('click', () => {
    stopCameraScanner();
});

function stopCameraScanner() {
    if (html5QrCode && isCameraActive) {
        html5QrCode.stop().then(() => {
            isCameraActive = false;
            startCameraBtn.style.display = 'inline-block';
            stopCameraBtn.style.display = 'none';
        }).catch(err => {
            console.error('Error stopping camera:', err);
        });
    }
}

function onScanSuccess(decodedText, decodedResult) {
    // Process the scanned barcode
    processBarcode(decodedText);
    
    // Optional: Stop camera after successful scan
    // stopCameraScanner();
}

function onScanError(errorMessage) {
    // Handle scan errors silently (too many errors otherwise)
    // console.warn('Scan error:', errorMessage);
}

// Auto-focus input field (keyboard scanner mode only)
setInterval(() => {
    if (modeKeyboard.checked && document.activeElement !== barcodeInput) {
        barcodeInput.focus();
    }
}, 1000);

// Handle form submission (keyboard scanner)
scannerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const barcode = barcodeInput.value.trim();
    if (!barcode) {
        showResult('Please scan or enter a barcode', 'danger');
        return;
    }
    
    // Clear input for next scan
    barcodeInput.value = '';
    
    await processBarcode(barcode);
});

async function processBarcode(barcode) {
    try {
        const response = await fetch('/api/scan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ barcode })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showResult(`
                <div class="alert alert-success">
                    <h4 class="alert-title">
                        <i class="ti ti-check-circle"></i> Quest Complete!
                    </h4>
                    <div><strong>${data.task.title}</strong></div>
                    <div class="mt-2">
                        <span class="badge bg-green me-2">+${data.xp_awarded} XP</span>
                        ${data.xp_result.leveled_up ? '<span class="badge bg-yellow">LEVEL UP! Level ' + data.xp_result.new_level + '</span>' : ''}
                    </div>
                    ${data.bonus_xp > 0 ? '<div class="text-success mt-1">Bonus: +' + data.bonus_xp + ' XP (Early completion!)</div>' : ''}
                    ${data.penalty_xp > 0 ? '<div class="text-warning mt-1">Penalty: -' + data.penalty_xp + ' XP (Late completion)</div>' : ''}
                </div>
            `, 'success');
            
            addToScanHistory(data.task, data.xp_awarded);
            
            // Play success sound (optional)
            playSuccessSound();
            
            // Trigger confetti animation
            triggerConfetti(data.xp_result.leveled_up);
            
            // Level up notification
            if (data.xp_result.leveled_up) {
                setTimeout(() => {
                    alert(`🎉 LEVEL UP! You reached Level ${data.xp_result.new_level}!`);
                }, 500);
            }
        } else {
            showResult(`
                <div class="alert alert-danger">
                    <h4 class="alert-title">
                        <i class="ti ti-alert-circle"></i> Error
                    </h4>
                    <div>${data.message || 'Failed to complete task'}</div>
                </div>
            `, 'danger');
        }
    } catch (error) {
        showResult(`
            <div class="alert alert-danger">
                <h4 class="alert-title">
                    <i class="ti ti-alert-circle"></i> Error
                </h4>
                <div>Failed to process scan. Please try again.</div>
            </div>
        `, 'danger');
    }
}

function showResult(html, type) {
    resultContainer.innerHTML = html;
    setTimeout(() => {
        resultContainer.innerHTML = '';
    }, 5000);
}

function addToScanHistory(task, xp) {
    const timestamp = new Date().toLocaleTimeString();
    const historyItem = `
        <div class="list-group-item">
            <div class="row align-items-center">
                <div class="col">
                    <strong>${task.title}</strong>
                    <div class="text-muted small">${timestamp}</div>
                </div>
                <div class="col-auto">
                    <span class="badge bg-green">+${xp} XP</span>
                </div>
            </div>
        </div>
    `;
    
    if (recentScans.querySelector('.text-muted')) {
        recentScans.innerHTML = '';
    }
    
    recentScans.insertAdjacentHTML('afterbegin', historyItem);
    
    // Keep only last 10 scans
    while (recentScans.children.length > 10) {
        recentScans.removeChild(recentScans.lastChild);
    }
}

function playSuccessSound() {
    // Create a simple beep using Web Audio API
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.2);
    } catch (e) {
        // Audio not supported, skip
    }
}

function triggerConfetti(isLevelUp) {
    // Use canvas-confetti library if available
    if (typeof confetti === 'function') {
        if (isLevelUp) {
            // Extra special confetti for level up
            const duration = 3000;
            const end = Date.now() + duration;
            
            const colors = ['#FFD700', '#FFA500', '#FF6347', '#4169E1', '#32CD32'];
            
            (function frame() {
                confetti({
                    particleCount: 7,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0 },
                    colors: colors
                });
                confetti({
                    particleCount: 7,
                    angle: 120,
                    spread: 55,
                    origin: { x: 1 },
                    colors: colors
                });
                
                if (Date.now() < end) {
                    requestAnimationFrame(frame);
                }
            }());
        } else {
            // Regular completion confetti
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        }
    }
}

// Keep input focused
barcodeInput.focus();
SCRIPT;

include 'includes/footer.php';
?>
