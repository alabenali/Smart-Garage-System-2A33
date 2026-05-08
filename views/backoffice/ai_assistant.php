<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: /projet_final/controllers/AdminController.php?action=showLogin'); exit; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Helper - Smart Garage</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/projet_final/views/backoffice/style.css">
<style>
.ai-wrap { display:flex; gap:24px; height:calc(100vh - 130px); }

.chat-panel { flex:1; display:flex; flex-direction:column; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:16px; overflow:hidden; }

.chat-header { padding:16px 24px; background:linear-gradient(135deg,rgba(167,139,250,0.15),rgba(0,229,255,0.1)); border-bottom:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; gap:12px; }
.chat-header .ai-avatar { width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,#a78bfa,#7c3aed); display:flex; align-items:center; justify-content:center; font-size:1.2rem; box-shadow:0 0 15px rgba(167,139,250,0.4); }
.chat-header h3 { margin:0; color:#e0e0e0; font-size:1rem; }
.chat-header p  { margin:0; color:#888; font-size:0.78rem; }
.online-dot { width:8px; height:8px; background:#00e676; border-radius:50%; margin-left:auto; box-shadow:0 0 6px #00e676; }

.chat-messages { flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:14px; scrollbar-width:thin; }

.msg { display:flex; gap:10px; max-width:88%; animation:fadeIn 0.3s ease; }
@keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
.msg.user  { align-self:flex-end; flex-direction:row-reverse; }
.msg.ai    { align-self:flex-start; }
.msg-avatar { width:32px; height:32px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:0.8rem; }
.msg.user .msg-avatar { background:linear-gradient(135deg,#0ea5e9,#0284c7); color:#fff; }
.msg.ai   .msg-avatar { background:linear-gradient(135deg,#a78bfa,#7c3aed); color:#fff; }
.msg-bubble { padding:10px 14px; border-radius:14px; font-size:0.88rem; line-height:1.55; max-width:100%; word-break:break-word; }
.msg.user .msg-bubble { background:linear-gradient(135deg,#0ea5e9,#0284c7); color:#fff; border-bottom-right-radius:4px; }
.msg.ai   .msg-bubble { background:rgba(255,255,255,0.07); color:#e0e0e0; border:1px solid rgba(255,255,255,0.1); border-bottom-left-radius:4px; }
.msg.ai   .msg-bubble code { background:rgba(0,229,255,0.15); color:#00E5FF; padding:1px 6px; border-radius:4px; }
.msg.ai   .msg-bubble strong { color:#fff; }
.msg-time { font-size:0.68rem; color:#666; margin-top:4px; padding:0 4px; align-self:flex-end; }

.typing-dots { display:flex; gap:5px; padding:12px 14px; }
.typing-dots span { width:7px; height:7px; border-radius:50%; background:#a78bfa; animation:dot 1.2s infinite; }
.typing-dots span:nth-child(2){animation-delay:0.2s}
.typing-dots span:nth-child(3){animation-delay:0.4s}
@keyframes dot { 0%,80%,100%{opacity:0.2;transform:scale(0.8)} 40%{opacity:1;transform:scale(1)} }

.voice-bar { display:none; align-items:center; gap:8px; padding:7px 16px; background:rgba(255,82,82,0.1); border-top:1px solid rgba(255,82,82,0.2); color:#ff5252; font-size:0.8rem; }
.voice-bar.show { display:flex; }
.voice-bar.speaking { background:rgba(0,229,255,0.08); border-color:rgba(0,229,255,0.2); color:#00E5FF; }
.vbars { display:flex; gap:3px; align-items:center; }
.vbars span { width:3px; background:currentColor; border-radius:2px; animation:vbar 0.8s ease-in-out infinite; }
.vbars span:nth-child(1){height:8px;animation-delay:0s}
.vbars span:nth-child(2){height:14px;animation-delay:0.15s}
.vbars span:nth-child(3){height:20px;animation-delay:0.3s}
.vbars span:nth-child(4){height:14px;animation-delay:0.45s}
.vbars span:nth-child(5){height:8px;animation-delay:0.6s}
@keyframes vbar { 0%,100%{transform:scaleY(0.4)} 50%{transform:scaleY(1)} }

.chat-input-bar { padding:12px 16px; border-top:1px solid rgba(255,255,255,0.08); display:flex; gap:8px; align-items:flex-end; background:rgba(0,0,0,0.2); }
#micBtn { width:42px; height:42px; border-radius:12px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.06); color:#aaa; cursor:pointer; font-size:0.95rem; flex-shrink:0; transition:all 0.2s; }
#micBtn:hover  { background:rgba(255,100,100,0.2); color:#ff6b6b; border-color:rgba(255,100,100,0.3); }
#micBtn.listen { background:rgba(255,82,82,0.25); color:#ff5252; border-color:#ff5252; animation:pulseMic 1s infinite; }
#micBtn.speak  { background:rgba(0,229,255,0.15); color:#00E5FF; border-color:#00E5FF; }
@keyframes pulseMic { 0%,100%{box-shadow:0 0 0 0 rgba(255,82,82,0.4)} 50%{box-shadow:0 0 0 8px rgba(255,82,82,0)} }
#chatInput { flex:1; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:11px 15px; color:#e0e0e0; font-size:0.9rem; resize:none; outline:none; max-height:120px; min-height:42px; transition:border 0.2s; font-family:inherit; line-height:1.4; }
#chatInput:focus { border-color:rgba(167,139,250,0.5); }
#chatInput::placeholder { color:#555; }
#sendBtn { width:42px; height:42px; border-radius:12px; border:none; background:linear-gradient(135deg,#a78bfa,#7c3aed); color:#fff; cursor:pointer; font-size:0.95rem; flex-shrink:0; transition:transform 0.2s,box-shadow 0.2s; }
#sendBtn:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 6px 20px rgba(167,139,250,0.4); }
#sendBtn:disabled { opacity:0.4; cursor:not-allowed; }

.suggestions { padding:10px 16px 0; display:flex; flex-wrap:wrap; gap:7px; }
.sug-chip { background:rgba(167,139,250,0.1); border:1px solid rgba(167,139,250,0.25); color:#a78bfa; padding:5px 12px; border-radius:20px; font-size:0.78rem; cursor:pointer; transition:background 0.2s; user-select:none; }
.sug-chip:hover { background:rgba(167,139,250,0.25); }

.clients-panel { width:340px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:16px; overflow:hidden; display:flex; flex-direction:column; }
.cp-header { padding:16px 20px; border-bottom:1px solid rgba(255,255,255,0.08); background:rgba(0,229,255,0.05); }
.cp-header h4 { margin:0; color:#e0e0e0; font-size:0.95rem; }
.cp-header p  { margin:3px 0 0; color:#888; font-size:0.75rem; }
.clients-list { flex:1; overflow-y:auto; padding:10px; display:flex; flex-direction:column; gap:8px; scrollbar-width:thin; }
.client-card { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:10px 13px; transition:border 0.2s; }
.client-card:hover { border-color:rgba(0,229,255,0.3); }
.client-card .cn { color:#e0e0e0; font-size:0.87rem; font-weight:600; }
.client-card .ce { color:#888; font-size:0.75rem; margin-top:2px; }
.client-card .ci { color:#555; font-size:0.72rem; margin-top:2px; }
.badge-actif  { background:rgba(0,230,118,0.15); color:#00e676; padding:2px 8px; border-radius:10px; font-size:0.68rem; float:right; }
.badge-bloque { background:rgba(255,82,82,0.15);  color:#ff5252; padding:2px 8px; border-radius:10px; font-size:0.68rem; float:right; }
.empty-list { text-align:center; color:#555; padding:40px 20px; font-size:0.85rem; }
</style>
</head>
<body>
<aside class="sidebar">
    <div class="logo"><i class="fas fa-car" style="color:#00E5FF;margin-right:8px;"></i><h2>Smart Garage Admin</h2></div>
    <div style="text-align:center;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:10px;">
        <div style="width:58px;height:58px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white;margin:0 auto 7px;border:3px solid #00E5FF;">
            <?= strtoupper(substr($_SESSION['admin_nom']??'A',0,1)) ?>
        </div>
        <div style="color:#ccc;font-size:0.85rem;"><?= htmlspecialchars($_SESSION['admin_nom']??'Admin') ?></div>
    </div>
    <nav><ul>
        <li><a href="/projet_final/controllers/AdminController.php?action=showDashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=listUsers"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="/projet_final/controllers/AIController.php?action=showAssistant" class="active" style="color:#a78bfa;background:rgba(167,139,250,0.1);"><i class="fas fa-robot"></i> AI Helper</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAdminProfile"><i class="fas fa-user-cog"></i> Mon profil</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
    </ul></nav>
</aside>

<main class="main" style="padding:20px;">
    <div class="top-bar" style="margin-bottom:18px;">
        <h1><i class="fas fa-robot" style="color:#a78bfa;"></i> AI Helper — Gestion Clients</h1>
        <span class="admin-badge"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin_nom']??'') ?></span>
    </div>

    <div class="ai-wrap">

        <!-- ══ CHAT ══ -->
        <div class="chat-panel">
            <div class="chat-header">
                <div class="ai-avatar"><i class="fas fa-robot"></i></div>
                <div>
                    <h3>AI Helper</h3>
                    <p>Commandes vocales et texte • FR / EN / Arabe</p>
                </div>
                <div class="online-dot"></div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <div class="msg ai">
                    <div class="msg-avatar"><i class="fas fa-robot"></i></div>
                    <div>
                        <div class="msg-bubble">
                            Bonjour ! Je suis <strong>AI Helper</strong>, votre assistant IA.<br><br>
                            Je peux <strong>lister, ajouter, modifier, supprimer et bloquer</strong> des clients.<br>
                            Parlez-moi ou tapez votre commande 🎤
                        </div>
                        <div class="msg-time">Maintenant</div>
                    </div>
                </div>
            </div>

            <div class="suggestions" id="suggs">
                <span class="sug-chip" onclick="useSugg(this)">📋 Liste tous les clients</span>
                <span class="sug-chip" onclick="useSugg(this)">🔍 Cherche un client</span>
                <span class="sug-chip" onclick="useSugg(this)">🔃 Trie par nom</span>
                <span class="sug-chip" onclick="useSugg(this)">🔃 Trie par statut</span>
                <span class="sug-chip" onclick="useSugg(this)">➕ Ajouter un client</span>
                <span class="sug-chip" onclick="useSugg(this)">🚫 Bloquer un client</span>
                <span class="sug-chip" onclick="useSugg(this)">💬 Aide</span>
            </div>

            <div class="voice-bar" id="voiceBar">
                <div class="vbars"><span></span><span></span><span></span><span></span><span></span></div>
                <span id="voiceBarText">Écoute...</span>
                <span onclick="stopVoice()" style="margin-left:auto;cursor:pointer;font-size:0.8rem;text-decoration:underline;">Arrêter</span>
            </div>

            <div class="chat-input-bar">
                <button id="micBtn" onclick="toggleMic()" title="Parler avec AI Helper"><i class="fas fa-microphone"></i></button>
                <textarea id="chatInput" placeholder="Tapez ou cliquez sur 🎤 pour parler..." rows="1"></textarea>
                <button id="sendBtn" onclick="sendMsg()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>

        <!-- ══ CLIENTS ══ -->
        <div class="clients-panel">
            <div class="cp-header">
                <h4><i class="fas fa-users" style="color:#00E5FF;margin-right:8px;"></i>Clients <span id="cCount" style="color:#00E5FF;"></span></h4>
                <p>Mis à jour en temps réel</p>
            </div>
            <div class="clients-list" id="clientsList">
                <div class="empty-list"><i class="fas fa-spinner fa-spin"></i><br>Chargement...</div>
            </div>
        </div>

    </div>
</main>

<script>
const chatMessages = document.getElementById('chatMessages');
const chatInput    = document.getElementById('chatInput');
const sendBtn      = document.getElementById('sendBtn');
const micBtn       = document.getElementById('micBtn');
const voiceBar     = document.getElementById('voiceBar');
const voiceBarText = document.getElementById('voiceBarText');
const clientsList  = document.getElementById('clientsList');
const cCount       = document.getElementById('cCount');

// ── Auto-resize textarea ──────────────────────────────────────────────────────
chatInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// ── Enter = envoyer (Shift+Enter = nouvelle ligne) ────────────────────────────
chatInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); }
});

// ── Suggestions ───────────────────────────────────────────────────────────────
function useSugg(el) {
    const txt = el.textContent.replace(/^[^\s]+\s/, '').trim();
    chatInput.value = txt;
    chatInput.dispatchEvent(new Event('input'));
    sendMsg();
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function nowTime() {
    return new Date().toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});
}

function addMsg(role, text) {
    const html = text
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>')
        .replace(/`([^`]+)`/g,'<code>$1</code>')
        .replace(/\n/g,'<br>');
    const d = document.createElement('div');
    d.className = 'msg ' + role;
    const icon = role === 'user' ? 'fas fa-user' : 'fas fa-robot';
    d.innerHTML = '<div class="msg-avatar"><i class="' + icon + '"></i></div>' +
        '<div><div class="msg-bubble">' + html + '</div>' +
        '<div class="msg-time">' + nowTime() + '</div></div>';
    chatMessages.appendChild(d);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function showTyping() {
    const d = document.createElement('div');
    d.id = 'typing'; d.className = 'msg ai';
    d.innerHTML = '<div class="msg-avatar"><i class="fas fa-robot"></i></div>' +
        '<div class="msg-bubble"><div class="typing-dots"><span></span><span></span><span></span></div></div>';
    chatMessages.appendChild(d);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function hideTyping() {
    const t = document.getElementById('typing');
    if (t) t.remove();
}

// ── Render client panel ───────────────────────────────────────────────────────
function renderClients(list) {
    cCount.textContent = '(' + list.length + ')';
    if (!list.length) { clientsList.innerHTML = '<div class="empty-list"><i class="fas fa-users-slash"></i><br>Aucun client</div>'; return; }
    clientsList.innerHTML = list.map(function(c) {
        return '<div class="client-card">' +
            '<div><span style="color:#555;font-size:0.7rem;">#' + c.id + '</span>' +
            '<span class="badge-' + c.statut + '">' + c.statut + '</span></div>' +
            '<div class="cn">' + c.prenom + ' ' + c.nom + '</div>' +
            '<div class="ce"><i class="fas fa-envelope" style="width:13px;"></i> ' + c.email + '</div>' +
            (c.telephone ? '<div class="ci"><i class="fas fa-phone" style="width:13px;"></i> ' + c.telephone + '</div>' : '') +
            '</div>';
    }).join('');
}

// ── Render client table in chat ───────────────────────────────────────────────
function renderTable(list) {
    if (!list.length) { addMsg('ai', 'Aucun client trouvé.'); return; }
    const rows = list.map(function(c) {
        var badge = c.statut === 'actif'
            ? '<span style="background:rgba(0,230,118,0.15);color:#00e676;padding:2px 7px;border-radius:8px;font-size:0.7rem;">actif</span>'
            : '<span style="background:rgba(255,82,82,0.15);color:#ff5252;padding:2px 7px;border-radius:8px;font-size:0.7rem;">bloqué</span>';
        return '<tr>' +
            '<td style="padding:5px 8px;color:#aaa;">#' + c.id + '</td>' +
            '<td style="padding:5px 8px;color:#e0e0e0;font-weight:600;">' + c.prenom + ' ' + c.nom + '</td>' +
            '<td style="padding:5px 8px;color:#888;font-size:0.78rem;">' + c.email + '</td>' +
            '<td style="padding:5px 8px;color:#888;font-size:0.78rem;">' + (c.telephone || '-') + '</td>' +
            '<td style="padding:5px 8px;">' + badge + '</td>' +
            '</tr>';
    }).join('');
    const tableHtml = '<div style="overflow-x:auto;margin-top:6px;">' +
        '<table style="width:100%;border-collapse:collapse;font-size:0.8rem;">' +
        '<thead><tr style="border-bottom:1px solid rgba(255,255,255,0.1);">' +
        '<th style="padding:5px 8px;color:#00E5FF;text-align:left;">ID</th>' +
        '<th style="padding:5px 8px;color:#00E5FF;text-align:left;">Nom</th>' +
        '<th style="padding:5px 8px;color:#00E5FF;text-align:left;">Email</th>' +
        '<th style="padding:5px 8px;color:#00E5FF;text-align:left;">Tél</th>' +
        '<th style="padding:5px 8px;color:#00E5FF;text-align:left;">Statut</th>' +
        '</tr></thead><tbody>' + rows + '</tbody></table></div>';
    const d = document.createElement('div');
    d.className = 'msg ai';
    d.innerHTML = '<div class="msg-avatar"><i class="fas fa-robot"></i></div>' +
        '<div><div class="msg-bubble" style="max-width:100%;padding:12px 14px;">' + tableHtml + '</div>' +
        '<div class="msg-time">' + nowTime() + '</div></div>';
    chatMessages.appendChild(d);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// ── Send message ──────────────────────────────────────────────────────────────
async function sendMsg() {
    var msg = chatInput.value.trim();
    if (!msg) return;
    if (sendBtn.disabled) return;

    addMsg('user', msg);
    chatInput.value = '';
    chatInput.style.height = 'auto';
    sendBtn.disabled = true;
    document.getElementById('suggs').style.display = 'none';
    showTyping();

    try {
        var res  = await fetch('/projet_final/controllers/AIController.php?action=chat', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: msg})
        });
        var data = await res.json();
        hideTyping();

        var reply = data.reply || 'Pas de réponse.';
        addMsg('ai', reply);
        speakText(reply);

        if (data.clients !== undefined) renderClients(data.clients);
        if (data.action === 'list' && data.clients) renderTable(data.clients);

    } catch (err) {
        hideTyping();
        addMsg('ai', 'Erreur réseau. Vérifiez votre connexion.');
    }

    sendBtn.disabled = false;
    chatInput.focus();
}

// ── Load clients on page load ─────────────────────────────────────────────────
(async function() {
    try {
        var res  = await fetch('/projet_final/controllers/AIController.php?action=chat', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: 'liste tous les clients'})
        });
        var data = await res.json();
        if (data.clients) renderClients(data.clients);
    } catch (e) {
        clientsList.innerHTML = '<div class="empty-list">Erreur de chargement</div>';
    }
})();

// ══════════════════════════════════════
//  VOICE INPUT (Speech Recognition)
// ══════════════════════════════════════
var SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
var recog     = null;
var listening = false;

if (!SpeechRec) {
    micBtn.disabled = true;
    micBtn.title    = 'Non supporté — utilisez Chrome ou Edge';
    micBtn.style.opacity = '0.3';
}

function toggleMic() {
    if (!SpeechRec) return;
    if (listening) { stopVoice(); return; }
    startVoice();
}

function startVoice() {
    if (window.speechSynthesis) window.speechSynthesis.cancel();
    recog = new SpeechRec();
    recog.lang           = 'fr-FR';
    recog.continuous     = false;
    recog.interimResults = true;

    recog.onstart = function() {
        listening = true;
        micBtn.className = 'listen';
        micBtn.innerHTML = '<i class="fas fa-stop"></i>';
        voiceBar.className = 'voice-bar show';
        voiceBarText.textContent = 'Écoute en cours... parlez !';
    };

    recog.onresult = function(e) {
        var txt = '';
        for (var i = e.resultIndex; i < e.results.length; i++) txt += e.results[i][0].transcript;
        chatInput.value = txt;
        chatInput.dispatchEvent(new Event('input'));
        voiceBarText.textContent = txt;
        if (e.results[e.results.length - 1].isFinal) {
            setTimeout(function() { sendMsg(); }, 400);
        }
    };

    recog.onerror = function(e) {
        var m = {
            'no-speech'    : 'Aucune voix détectée.',
            'not-allowed'  : 'Microphone bloqué — autorisez dans le navigateur.',
            'audio-capture': 'Micro introuvable.',
            'network'      : 'Erreur réseau.'
        };
        voiceBarText.textContent = m[e.error] || ('Erreur: ' + e.error);
        stopVoice();
    };

    recog.onend = function() { if (listening) stopVoice(); };

    try { recog.start(); } catch(e) { voiceBarText.textContent = 'Erreur démarrage micro.'; }
}

function stopVoice() {
    listening = false;
    micBtn.className = '';
    micBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    voiceBar.className = 'voice-bar';
    if (recog) { try { recog.stop(); } catch(e) {} }
}

// ══════════════════════════════════════
//  VOICE OUTPUT (Speech Synthesis)
// ══════════════════════════════════════
function speakText(text) {
    if (!window.speechSynthesis) return;
    // Nettoyer le texte
    var clean = text.replace(/[*_`#]/g, '').replace(/\n/g, ' ').trim();
    if (!clean) return;

    window.speechSynthesis.cancel();
    var utt   = new SpeechSynthesisUtterance(clean);
    utt.lang  = 'fr-FR';
    utt.rate  = 1.05;
    utt.pitch = 1;

    // Trouver voix française
    var voices  = window.speechSynthesis.getVoices();
    var frVoice = voices.find(function(v) { return v.lang.startsWith('fr'); });
    if (frVoice) utt.voice = frVoice;

    utt.onstart = function() {
        micBtn.className = 'speak';
        micBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
        voiceBar.className = 'voice-bar show speaking';
        voiceBarText.textContent = 'AI Helper parle...';
    };
    utt.onend = utt.onerror = function() {
        micBtn.className = '';
        micBtn.innerHTML = '<i class="fas fa-microphone"></i>';
        voiceBar.className = 'voice-bar';
    };

    window.speechSynthesis.speak(utt);
}

// Charger les voix (Chrome les charge en async)
if (window.speechSynthesis) window.speechSynthesis.onvoiceschanged = function() { window.speechSynthesis.getVoices(); };
</script>
<?php require_once __DIR__ . "/darkmode_back.php"; ?>
</body>
</html>
