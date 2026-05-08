<?php require_once __DIR__ . '/../../config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $styleVersion = @filemtime(__DIR__ . '/../../../vehicule et rdv/views/css/style.css') ?: time(); ?>
    <link rel="stylesheet" href="/integration/vehicule%20et%20rdv/views/css/style.css?v=<?php echo $styleVersion; ?>">
    <link rel="stylesheet" href="/integration/client/views/frontoffice/auth_sg.css?v=<?php echo @filemtime(__DIR__ . '/auth_sg.css') ?: time(); ?>">
    <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY) ?>"></script>
    <?php endif; ?>

    <!-- face-api.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    <style>
        /* ── Face verification overlay ─────────────────────────── */
        #faceModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        #faceModal.active { display: flex; }

        .face-card {
            background: rgba(26,26,26,0.95);
            border: 1px solid var(--accent-glow);
            border-radius: 20px;
            padding: 2rem;
            max-width: 460px;
            width: 95%;
            text-align: center;
            color: #e0e0e0;
        }
        .face-card h3 {
            color: var(--accent);
            margin-bottom: 0.4rem;
            font-size: 1.2rem;
        }
        .face-card p {
            font-size: 0.85rem;
            color: #aaa;
            margin-bottom: 1rem;
        }

        #cameraWrap {
            position: relative;
            display: inline-block;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid var(--accent);
            margin-bottom: 1rem;
        }
        #videoEl {
            display: block;
            width: 320px;
            height: 240px;
            object-fit: cover;
        }
        #overlayCanvas {
            position: absolute;
            top: 0; left: 0;
            width: 320px;
            height: 240px;
            pointer-events: none;
        }

        #faceStatus {
            min-height: 38px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }
        #faceStatus.ok   { color: #00e676; }
        #faceStatus.err  { color: #ff5252; }
        #faceStatus.info { color: var(--accent); }

        #scanProgress {
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        #scanBar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--accent), var(--accent-hover));
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .face-actions { display: flex; gap: 0.8rem; justify-content: center; flex-wrap: wrap; }

        #btnVerify {
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            color: #fff;
            border: none;
            padding: 0.7rem 1.6rem;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        #btnVerify:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px var(--accent-glow); }
        #btnVerify:disabled { opacity: 0.5; cursor: not-allowed; }

        #btnCancel {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            padding: 0.7rem 1.4rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        #btnCancel:hover { background: rgba(255,255,255,0.15); }

        .spinner-icon { animation: spin 1s linear infinite; display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="auth-body">
<div class="client-auth-shell">
<div class="auth-card">
    <div class="auth-logo">
        <i class="fas fa-car" style="font-size:2.5rem;"></i>
        <h2>Smart Garage</h2>
        
    </div>

    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-error">
            <?php foreach($_SESSION['errors'] as $e): ?>
                <div><i class="fas fa-exclamation-circle"></i> <?= $e ?></div>
            <?php endforeach; unset($_SESSION['errors']); ?>
        </div>
        <?php if (!empty($_SESSION['resend_email'])): ?>
        <form method="POST" action="/integration/client/controllers/UserController.php?action=resendVerification" style="text-align:center;margin-bottom:12px;">
            <input type="hidden" name="resend_email" value="<?= htmlspecialchars($_SESSION['resend_email']) ?>">
            <?php unset($_SESSION['resend_email']); ?>
            <button type="submit" style="background:none;border:none;color:var(--accent);cursor:pointer;text-decoration:underline;font-size:0.9rem;">
                <i class="fas fa-paper-plane"></i> Renvoyer l'email de confirmation
            </button>
        </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form id="loginForm" novalidate>
        <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response-login">
        <?php endif; ?>

        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email</label>
            <div class="input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="text" name="email" id="email" placeholder="votre@email.com">
            </div>
            <span class="error-msg" id="emailError"></span>
        </div>
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Mot de passe</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="mot_de_passe" id="password" placeholder="••••••••">
            </div>
            <span class="error-msg" id="passwordError"></span>
        </div>

        <button type="submit" class="btn-primary full" id="loginBtn">
            <i class="fas fa-sign-in-alt"></i> Se connecter
        </button>
    </form>

    <div class="links">
        <p><a href="/integration/client/controllers/UserController.php?action=showForgotPassword"><i class="fas fa-key"></i> Mot de passe oublié ?</a></p>
        <p>Pas de compte ? <a href="/integration/client/views/frontoffice/register.php">S'inscrire</a></p>
    </div>

    <div style="display:flex;align-items:center;gap:10px;margin:18px 0 14px;">
        <div style="flex:1;height:1px;background:rgba(255,255,255,0.12);"></div>
        <span style="color:#888;font-size:0.82rem;white-space:nowrap;">ou continuer avec</span>
        <div style="flex:1;height:1px;background:rgba(255,255,255,0.12);"></div>
    </div>

    <a href="/integration/client/controllers/UserController.php?action=googleLogin"
       style="display:flex;align-items:center;justify-content:center;gap:12px;
              background:#fff;color:#3c4043;border:1px solid #dadce0;border-radius:10px;
              padding:0.7rem 1.2rem;font-size:0.95rem;font-weight:600;text-decoration:none;
              transition:box-shadow 0.2s,background 0.2s;width:100%;box-sizing:border-box;"
       onmouseover="this.style.boxShadow='0 2px 10px rgba(0,0,0,0.25)'"
       onmouseout="this.style.boxShadow='none'">
        <svg width="20" height="20" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
            <path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.6 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.1 8 2.9l5.7-5.7C34.6 6.5 29.6 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 20-8.9 20-20 0-1.3-.1-2.6-.4-3.9z"/>
            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.2 19 12 24 12c3.1 0 5.8 1.1 8 2.9l5.7-5.7C34.6 6.5 29.6 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
            <path fill="#4CAF50" d="M24 44c5.4 0 10.3-2 14-5.2l-6.5-5.5C29.4 35 26.8 36 24 36c-5.2 0-9.7-3.4-11.3-8H6.1C9.4 36.3 16.2 44 24 44z"/>
            <path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.3 4.1-4.2 5.4l6.5 5.5C37 39.1 44 34 44 24c0-1.3-.1-2.6-.4-3.9z"/>
        </svg>
        Se connecter avec Google
    </a>
</div>
 </div>

<!-- ══════════════ FACE VERIFICATION MODAL ══════════════ -->
<div id="faceModal">
    <div class="face-card">
        <h3><i class="fas fa-camera"></i> Vérification de votre identité</h3>
        <p>Regardez la caméra pour confirmer que c'est bien vous</p>

        <div id="cameraWrap">
            <video id="videoEl" autoplay muted playsinline></video>
            <canvas id="overlayCanvas"></canvas>
        </div>

        <div id="scanProgress"><div id="scanBar"></div></div>

        <div id="faceStatus" class="info">
            <i class="fas fa-spinner spinner-icon"></i>
            Chargement des modèles IA...
        </div>

        <div class="face-actions">
            <button id="btnVerify" disabled>
                <i class="fas fa-check-circle"></i> Vérifier mon identité
            </button>
            <button id="btnCancel">
                <i class="fas fa-times"></i> Annuler
            </button>
        </div>
    </div>
</div>

<script src="/integration/client/views/frontoffice/js/validate-login.js"></script>

<script>
// ════════════════════════════════════════════════════════
//  FACE VERIFICATION LOGIC
// ════════════════════════════════════════════════════════

const MODELS_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
// Similarity threshold — 0.5 = strict, 0.6 = tolerant
const THRESHOLD  = 0.55;

let stream        = null;
let modelsLoaded  = false;
let pendingForm   = null;   // FormData to submit after face OK
let profileDescriptor = null;  // Float32Array from profile picture

// ── DOM refs ──────────────────────────────────────────
const faceModal    = document.getElementById('faceModal');
const videoEl      = document.getElementById('videoEl');
const overlayCanvas= document.getElementById('overlayCanvas');
const faceStatus   = document.getElementById('faceStatus');
const scanBar      = document.getElementById('scanBar');
const btnVerify    = document.getElementById('btnVerify');
const btnCancel    = document.getElementById('btnCancel');
const loginForm    = document.getElementById('loginForm');

// ── Load face-api models ──────────────────────────────
async function loadModels() {
    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_URL),
            faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODELS_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODELS_URL),
        ]);
        modelsLoaded = true;
        setStatus('info', '<i class="fas fa-video"></i> Modèles chargés. Activation de la caméra...');
    } catch(e) {
        setStatus('err', '<i class="fas fa-exclamation-triangle"></i> Erreur chargement modèles IA');
        console.error(e);
    }
}

// ── Status helper ─────────────────────────────────────
function setStatus(type, html) {
    faceStatus.className = type;
    faceStatus.innerHTML = html;
}

// ── Start camera ──────────────────────────────────────
async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
        videoEl.srcObject = stream;
        await new Promise(r => videoEl.onloadedmetadata = r);
        setStatus('info', '<i class="fas fa-face-smile"></i> Positionnez votre visage dans le cadre');
        btnVerify.disabled = false;
        startLiveDetection();
    } catch(e) {
        setStatus('err', '<i class="fas fa-ban"></i> Accès caméra refusé. Veuillez autoriser l\'accès.');
    }
}

// ── Live detection loop (draws box on canvas) ─────────
let detectionLoop = null;
function startLiveDetection() {
    const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 });
    detectionLoop = setInterval(async () => {
        const det = await faceapi.detectSingleFace(videoEl, opts);
        const ctx = overlayCanvas.getContext('2d');
        ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
        if (det) {
            const { x, y, width, height } = det.box;
            ctx.strokeStyle = (getComputedStyle(document.documentElement).getPropertyValue('--accent') || '#C0392B').trim();
            ctx.lineWidth   = 2;
            ctx.strokeRect(x, y, width, height);
        }
    }, 300);
}

// ── Get profile picture descriptor from server ────────
async function loadProfileDescriptor(email) {
    const res  = await fetch('/integration/client/controllers/UserController.php?action=getFaceImage', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent(email)
    });
    const data = await res.json();
    if (!data.success || !data.image) return null;

    // Load image from base64
    const img = await faceapi.fetchImage('data:image/jpeg;base64,' + data.image);

    const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 });
    const det  = await faceapi.detectSingleFace(img, opts)
                              .withFaceLandmarks(true)
                              .withFaceDescriptor();
    if (!det) return null;
    return det.descriptor;
}

// ── Capture face from video + compare ────────────────
async function verifyFace() {
    btnVerify.disabled = true;
    setStatus('info', '<i class="fas fa-spinner spinner-icon"></i> Analyse en cours...');
    scanBar.style.width = '30%';

    const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 });
    const det  = await faceapi.detectSingleFace(videoEl, opts)
                              .withFaceLandmarks(true)
                              .withFaceDescriptor();

    scanBar.style.width = '70%';

    if (!det) {
        setStatus('err', '<i class="fas fa-face-frown"></i> Aucun visage détecté. Rapprochez-vous.');
        btnVerify.disabled = false;
        scanBar.style.width = '0%';
        return;
    }

    if (!profileDescriptor) {
        // No profile picture — allow login with a warning
        setStatus('ok', '<i class="fas fa-check-circle"></i> Pas de photo de profil enregistrée. Connexion...');
        scanBar.style.width = '100%';
        setTimeout(submitPendingForm, 800);
        return;
    }

    const distance = faceapi.euclideanDistance(det.descriptor, profileDescriptor);
    scanBar.style.width = '100%';

    if (distance <= THRESHOLD) {
        setStatus('ok', '<i class="fas fa-check-circle"></i> Identité confirmée ! Connexion en cours...');
        setTimeout(submitPendingForm, 900);
    } else {
        setStatus('err', '<i class="fas fa-user-slash"></i> Ce n\'est pas vous ! Connexion refusée.');
        btnVerify.disabled = false;
        scanBar.style.width = '0%';
        // Reset after 3 s
        setTimeout(() => {
            setStatus('info', '<i class="fas fa-redo"></i> Réessayez ou annulez.');
            btnVerify.disabled = false;
        }, 3000);
    }
}

// ── Submit original form to server ───────────────────
function submitPendingForm() {
    stopCamera();
    if (!pendingForm) return;

    const realForm = document.createElement('form');
    realForm.method = 'POST';
    realForm.action = '/integration/client/controllers/UserController.php?action=login';
    for (const [k, v] of pendingForm.entries()) {
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = k;
        inp.value = v;
        realForm.appendChild(inp);
    }
    document.body.appendChild(realForm);
    realForm.submit();
}

// ── Stop camera + close modal ─────────────────────────
function stopCamera() {
    clearInterval(detectionLoop);
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    faceModal.classList.remove('active');
}

// ── Open modal (called after form validation) ─────────
async function openFaceModal(formData, email) {
    pendingForm = formData;
    profileDescriptor = null;
    scanBar.style.width = '0%';
    btnVerify.disabled  = true;
    faceModal.classList.add('active');
    setStatus('info', '<i class="fas fa-spinner spinner-icon"></i> Chargement des modèles IA...');

    if (!modelsLoaded) await loadModels();
    if (!modelsLoaded) return;

    setStatus('info', '<i class="fas fa-spinner spinner-icon"></i> Récupération de votre photo de profil...');
    try {
        profileDescriptor = await loadProfileDescriptor(email);
    } catch(e) {
        profileDescriptor = null;
    }

    await startCamera();
}

// ── Cancel button ─────────────────────────────────────
btnCancel.addEventListener('click', () => {
    stopCamera();
    pendingForm = null;
});

// ── Verify button ─────────────────────────────────────
btnVerify.addEventListener('click', verifyFace);

// ── Intercept login form submit ───────────────────────
loginForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    // Client-side validation (reuse existing logic minimally)
    const email = document.getElementById('email').value.trim();
    const pass  = document.getElementById('password').value.trim();
    let valid   = true;

    document.getElementById('emailError').textContent    = '';
    document.getElementById('passwordError').textContent = '';

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        document.getElementById('emailError').textContent = "Email invalide.";
        valid = false;
    }
    if (!pass || pass.length < 6) {
        document.getElementById('passwordError').textContent = "Mot de passe requis (min. 6 caractères).";
        valid = false;
    }
    if (!valid) return;

    const fd = new FormData(this);
    fd.set('email', email);
    fd.set('mot_de_passe', pass);

    <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
    // Resolve reCAPTCHA first
    try {
        const token = await new Promise((res, rej) => {
            grecaptcha.ready(() =>
                grecaptcha.execute('<?= htmlspecialchars(RECAPTCHA_SITE_KEY) ?>', { action: 'login' })
                    .then(res).catch(rej)
            );
        });
        fd.set('g-recaptcha-response', token);
    } catch(_) {}
    <?php endif; ?>

    // Open face verification modal
    openFaceModal(fd, email);
});
</script>

</body>
</html>
