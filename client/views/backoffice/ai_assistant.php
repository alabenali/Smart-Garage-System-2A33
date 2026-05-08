<?php
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
    exit;
}

$pageTitle = 'AI Helper';
$currentAction = 'aiHelper';
$extraHead = <<<'CSS'
<style>
    .ai-helper-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 1.25rem;
        align-items: stretch;
        min-height: calc(100vh - 170px);
    }

    .ai-chat-panel,
    .ai-clients-panel {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
    }

    .ai-chat-panel {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .ai-chat-header,
    .ai-clients-header {
        padding: 1.1rem 1.35rem;
        border-bottom: 1px solid var(--border-color);
        background: #FFFFFF;
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .ai-chat-header {
        background: linear-gradient(135deg, #FFFFFF 0%, #FDECEA 100%);
    }

    .ai-clients-header {
        background: var(--info-bg);
    }

    .ai-avatar {
        width: 46px;
        height: 46px;
        border-radius: var(--radius-sm);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--gradient-2);
        color: #FFFFFF;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .ai-header-title {
        margin: 0;
        font-size: 1rem;
        color: var(--accent-secondary);
    }

    .ai-header-subtitle {
        margin: 0.15rem 0 0;
        color: var(--text-secondary);
        font-size: 0.82rem;
    }

    .online-dot {
        width: 10px;
        height: 10px;
        margin-left: auto;
        border-radius: 50%;
        background: var(--success);
        box-shadow: 0 0 0 5px rgba(46, 125, 50, 0.12);
    }

    .chat-messages {
        flex: 1;
        min-height: 360px;
        overflow-y: auto;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
        background: var(--bg-secondary);
    }

    .msg {
        display: flex;
        gap: 0.7rem;
        max-width: min(88%, 840px);
        animation: aiFadeIn 0.25s ease;
    }

    @keyframes aiFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .msg.user {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .msg.ai {
        align-self: flex-start;
    }

    .msg-avatar {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.95rem;
    }

    .msg.user .msg-avatar {
        background: var(--accent);
        color: #FFFFFF;
    }

    .msg.ai .msg-avatar {
        background: var(--accent-secondary);
        color: #FFFFFF;
    }

    .msg-bubble {
        padding: 0.8rem 0.95rem;
        border-radius: 12px;
        line-height: 1.55;
        font-size: 0.9rem;
        word-break: break-word;
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.04);
    }

    .msg.user .msg-bubble {
        background: var(--accent);
        color: #FFFFFF;
        border-bottom-right-radius: 4px;
    }

    .msg.ai .msg-bubble {
        background: #FFFFFF;
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        border-bottom-left-radius: 4px;
    }

    .msg.ai .msg-bubble strong {
        color: var(--accent-secondary);
    }

    .msg.ai .msg-bubble code {
        background: var(--info-bg);
        color: var(--accent-secondary);
        border-radius: 5px;
        padding: 2px 6px;
        font-weight: 700;
    }

    .msg-time {
        color: var(--text-muted);
        font-size: 0.72rem;
        margin-top: 0.3rem;
        padding: 0 0.2rem;
    }

    .typing-dots {
        display: inline-flex;
        gap: 5px;
        padding: 0.2rem;
    }

    .typing-dots span {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--accent);
        animation: aiDot 1.2s infinite;
    }

    .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
    .typing-dots span:nth-child(3) { animation-delay: 0.4s; }

    @keyframes aiDot {
        0%, 80%, 100% { opacity: 0.25; transform: scale(0.8); }
        40% { opacity: 1; transform: scale(1); }
    }

    .suggestions {
        padding: 0.9rem 1rem 0;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        background: #FFFFFF;
    }

    .sug-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border-radius: 999px;
        padding: 0.42rem 0.8rem;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--accent-secondary);
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
        user-select: none;
    }

    .sug-chip:hover {
        border-color: var(--accent);
        color: var(--accent);
        transform: translateY(-1px);
    }

    .voice-bar {
        display: none;
        align-items: center;
        gap: 0.65rem;
        padding: 0.7rem 1rem;
        background: var(--danger-bg);
        color: var(--danger);
        border-top: 1px solid #F6CECA;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .voice-bar.show {
        display: flex;
    }

    .voice-bar.speaking {
        background: var(--info-bg);
        color: var(--accent-secondary);
        border-color: #C9D7E8;
    }

    .vbars {
        display: inline-flex;
        gap: 3px;
        align-items: center;
    }

    .vbars span {
        width: 3px;
        background: currentColor;
        border-radius: 2px;
        animation: aiVbar 0.8s ease-in-out infinite;
    }

    .vbars span:nth-child(1) { height: 8px; animation-delay: 0s; }
    .vbars span:nth-child(2) { height: 14px; animation-delay: 0.15s; }
    .vbars span:nth-child(3) { height: 20px; animation-delay: 0.3s; }
    .vbars span:nth-child(4) { height: 14px; animation-delay: 0.45s; }
    .vbars span:nth-child(5) { height: 8px; animation-delay: 0.6s; }

    @keyframes aiVbar {
        0%, 100% { transform: scaleY(0.45); }
        50% { transform: scaleY(1); }
    }

    .chat-input-bar {
        display: flex;
        gap: 0.7rem;
        align-items: flex-end;
        padding: 1rem;
        border-top: 1px solid var(--border-color);
        background: #FFFFFF;
    }

    .ai-icon-btn {
        width: 44px;
        height: 44px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-color);
        background: #FFFFFF;
        color: var(--accent-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: var(--transition);
        font-size: 1rem;
    }

    .ai-icon-btn:hover {
        border-color: var(--accent);
        color: var(--accent);
        transform: translateY(-1px);
    }

    #micBtn.listen {
        background: var(--danger-bg);
        color: var(--danger);
        border-color: #F6CECA;
        animation: aiPulseMic 1s infinite;
    }

    #micBtn.speak {
        background: var(--info-bg);
        color: var(--accent-secondary);
        border-color: #C9D7E8;
    }

    @keyframes aiPulseMic {
        0%, 100% { box-shadow: 0 0 0 0 rgba(198, 40, 40, 0.25); }
        50% { box-shadow: 0 0 0 8px rgba(198, 40, 40, 0); }
    }

    #chatInput {
        flex: 1;
        min-height: 44px;
        max-height: 130px;
        resize: none;
        outline: none;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        padding: 0.72rem 0.9rem;
        font-family: inherit;
        font-size: 0.92rem;
        color: var(--text-primary);
        background: var(--bg-secondary);
        line-height: 1.45;
        transition: var(--transition);
    }

    #chatInput:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(192, 57, 43, 0.12);
        background: #FFFFFF;
    }

    #sendBtn {
        border: none;
        background: var(--accent);
        color: #FFFFFF;
    }

    #sendBtn:hover:not(:disabled) {
        background: var(--accent-hover);
    }

    #sendBtn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        transform: none;
    }

    .ai-clients-panel {
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .clients-list {
        flex: 1;
        overflow-y: auto;
        padding: 0.85rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        background: var(--bg-secondary);
    }

    .client-card {
        background: #FFFFFF;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        padding: 0.85rem;
        transition: var(--transition);
    }

    .client-card:hover {
        border-color: var(--accent);
        box-shadow: var(--shadow);
        transform: translateY(-1px);
    }

    .client-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.45rem;
    }

    .client-id {
        color: var(--text-muted);
        font-size: 0.76rem;
        font-weight: 700;
    }

    .client-name {
        color: var(--accent-secondary);
        font-weight: 800;
        font-size: 0.92rem;
    }

    .client-meta {
        color: var(--text-secondary);
        font-size: 0.8rem;
        margin-top: 0.28rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        word-break: break-word;
    }

    .empty-list {
        color: var(--text-secondary);
        text-align: center;
        padding: 2.5rem 1rem;
        font-size: 0.9rem;
    }

    .ai-table-mini {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
        min-width: 580px;
    }

    .ai-table-mini th,
    .ai-table-mini td {
        padding: 0.5rem 0.6rem;
        border-bottom: 1px solid var(--border-color);
        text-align: left;
        vertical-align: top;
    }

    .ai-table-mini th {
        color: var(--accent-secondary);
        background: var(--bg-secondary);
        font-size: 0.74rem;
        text-transform: uppercase;
    }

    .ai-table-scroll {
        overflow-x: auto;
        margin-top: 0.35rem;
    }

    @media (max-width: 1100px) {
        .ai-helper-layout {
            grid-template-columns: 1fr;
            min-height: 0;
        }

        .chat-messages {
            min-height: 420px;
        }
    }

    @media (max-width: 768px) {
        .msg {
            max-width: 100%;
        }

        .chat-input-bar {
            gap: 0.5rem;
            padding: 0.85rem;
        }

        .suggestions {
            padding: 0.85rem 0.85rem 0;
        }
    }
</style>
CSS;

require __DIR__ . '/layout_header.php';
?>

<div class="client-topline">
    <div>
        <h1 class="page-title"><i class="bi bi-stars"></i> AI Helper - Gestion Clients</h1>
        <p class="page-subtitle">Assistant IA pour rechercher, lister, creer, modifier, bloquer ou supprimer les clients.</p>
    </div>
    <span class="client-admin-chip"><i class="bi bi-person-shield"></i> <?php echo htmlspecialchars($_SESSION['admin_nom'] ?? 'Admin'); ?></span>
</div>

<div class="ai-helper-layout">
    <section class="ai-chat-panel" aria-label="Conversation AI Helper">
        <div class="ai-chat-header">
            <div class="ai-avatar"><i class="bi bi-robot"></i></div>
            <div>
                <h2 class="ai-header-title">AI Helper</h2>
                <p class="ai-header-subtitle">Commandes vocales et texte - FR / EN / Arabe</p>
            </div>
            <span class="online-dot" aria-label="Assistant en ligne"></span>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="msg ai">
                <div class="msg-avatar"><i class="bi bi-robot"></i></div>
                <div>
                    <div class="msg-bubble">
                        Bonjour ! Je suis <strong>AI Helper</strong>, votre assistant IA.<br><br>
                        Je peux <strong>lister, ajouter, modifier, supprimer et bloquer</strong> des clients.<br>
                        Parlez-moi ou tapez votre commande.
                    </div>
                    <div class="msg-time">Maintenant</div>
                </div>
            </div>
        </div>

        <div class="suggestions" id="suggs">
            <span class="sug-chip" onclick="useSugg(this)"><i class="bi bi-list-check"></i> Liste tous les clients</span>
            <span class="sug-chip" onclick="useSugg(this)"><i class="bi bi-search"></i> Cherche un client</span>
            <span class="sug-chip" onclick="useSugg(this)"><i class="bi bi-sort-alpha-down"></i> Trie par nom</span>
            <span class="sug-chip" onclick="useSugg(this)"><i class="bi bi-filter-circle"></i> Trie par statut</span>
            <span class="sug-chip" onclick="useSugg(this)"><i class="bi bi-person-plus"></i> Ajouter un client</span>
            <span class="sug-chip" onclick="useSugg(this)"><i class="bi bi-slash-circle"></i> Bloquer un client</span>
            <span class="sug-chip" onclick="useSugg(this)"><i class="bi bi-chat-dots"></i> Aide</span>
        </div>

        <div class="voice-bar" id="voiceBar">
            <div class="vbars"><span></span><span></span><span></span><span></span><span></span></div>
            <span id="voiceBarText">Ecoute...</span>
            <span onclick="stopVoice()" style="margin-left:auto;cursor:pointer;text-decoration:underline;">Arreter</span>
        </div>

        <div class="chat-input-bar">
            <button type="button" id="micBtn" class="ai-icon-btn" onclick="toggleMic()" title="Parler avec AI Helper"><i class="bi bi-mic-fill"></i></button>
            <textarea id="chatInput" placeholder="Tapez ou cliquez sur le micro pour parler..." rows="1"></textarea>
            <button type="button" id="sendBtn" class="ai-icon-btn" onclick="sendMsg()" title="Envoyer"><i class="bi bi-send-fill"></i></button>
        </div>
    </section>

    <aside class="ai-clients-panel" aria-label="Clients synchronises">
        <div class="ai-clients-header">
            <div class="ai-avatar"><i class="bi bi-people-fill"></i></div>
            <div>
                <h2 class="ai-header-title">Clients <span id="cCount"></span></h2>
                <p class="ai-header-subtitle">Mise a jour en temps reel</p>
            </div>
        </div>
        <div class="clients-list" id="clientsList">
            <div class="empty-list"><i class="bi bi-arrow-repeat"></i><br>Chargement...</div>
        </div>
    </aside>
</div>

<?php
$extraScripts = <<<'JS'
<script>
const chatMessages = document.getElementById('chatMessages');
const chatInput = document.getElementById('chatInput');
const sendBtn = document.getElementById('sendBtn');
const micBtn = document.getElementById('micBtn');
const voiceBar = document.getElementById('voiceBar');
const voiceBarText = document.getElementById('voiceBarText');
const clientsList = document.getElementById('clientsList');
const cCount = document.getElementById('cCount');

chatInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 130) + 'px';
});

chatInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMsg();
    }
});

function useSugg(el) {
    const txt = el.textContent.trim();
    chatInput.value = txt;
    chatInput.dispatchEvent(new Event('input'));
    sendMsg();
}

function nowTime() {
    return new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'});
}

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatMessage(text) {
    return escapeHtml(text)
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/\n/g, '<br>');
}

function addMsg(role, text) {
    const d = document.createElement('div');
    d.className = 'msg ' + role;
    const icon = role === 'user' ? 'bi bi-person-fill' : 'bi bi-robot';
    d.innerHTML = '<div class="msg-avatar"><i class="' + icon + '"></i></div>' +
        '<div><div class="msg-bubble">' + formatMessage(text) + '</div>' +
        '<div class="msg-time">' + nowTime() + '</div></div>';
    chatMessages.appendChild(d);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function showTyping() {
    const d = document.createElement('div');
    d.id = 'typing';
    d.className = 'msg ai';
    d.innerHTML = '<div class="msg-avatar"><i class="bi bi-robot"></i></div>' +
        '<div class="msg-bubble"><div class="typing-dots"><span></span><span></span><span></span></div></div>';
    chatMessages.appendChild(d);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function hideTyping() {
    const t = document.getElementById('typing');
    if (t) t.remove();
}

function statusBadge(statut) {
    const normalized = String(statut || '').toLowerCase();
    const active = normalized === 'actif';
    const cls = active ? 'status-termine' : 'status-annule';
    const label = active ? 'actif' : (normalized || 'inactif');
    return '<span class="status-badge ' + cls + '">' + escapeHtml(label) + '</span>';
}

function renderClients(list) {
    cCount.textContent = '(' + list.length + ')';
    if (!list.length) {
        clientsList.innerHTML = '<div class="empty-list"><i class="bi bi-people"></i><br>Aucun client</div>';
        return;
    }

    clientsList.innerHTML = list.map(function(c) {
        const name = escapeHtml((c.prenom || '') + ' ' + (c.nom || ''));
        const email = escapeHtml(c.email || '-');
        const phone = c.telephone ? '<div class="client-meta"><i class="bi bi-telephone"></i> ' + escapeHtml(c.telephone) + '</div>' : '';
        return '<div class="client-card">' +
            '<div class="client-card-head"><span class="client-id">#' + escapeHtml(c.id) + '</span>' + statusBadge(c.statut) + '</div>' +
            '<div class="client-name">' + name + '</div>' +
            '<div class="client-meta"><i class="bi bi-envelope"></i> ' + email + '</div>' +
            phone +
            '</div>';
    }).join('');
}

function renderTable(list) {
    if (!list.length) {
        addMsg('ai', 'Aucun client trouve.');
        return;
    }

    const rows = list.map(function(c) {
        return '<tr>' +
            '<td>#' + escapeHtml(c.id) + '</td>' +
            '<td><strong>' + escapeHtml((c.prenom || '') + ' ' + (c.nom || '')) + '</strong></td>' +
            '<td>' + escapeHtml(c.email || '-') + '</td>' +
            '<td>' + escapeHtml(c.telephone || '-') + '</td>' +
            '<td>' + statusBadge(c.statut) + '</td>' +
            '</tr>';
    }).join('');

    const tableHtml = '<div class="ai-table-scroll">' +
        '<table class="ai-table-mini">' +
        '<thead><tr><th>ID</th><th>Nom</th><th>Email</th><th>Tel</th><th>Statut</th></tr></thead>' +
        '<tbody>' + rows + '</tbody></table></div>';

    const d = document.createElement('div');
    d.className = 'msg ai';
    d.innerHTML = '<div class="msg-avatar"><i class="bi bi-robot"></i></div>' +
        '<div><div class="msg-bubble">' + tableHtml + '</div>' +
        '<div class="msg-time">' + nowTime() + '</div></div>';
    chatMessages.appendChild(d);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

async function sendMsg() {
    const msg = chatInput.value.trim();
    if (!msg || sendBtn.disabled) return;

    addMsg('user', msg);
    chatInput.value = '';
    chatInput.style.height = 'auto';
    sendBtn.disabled = true;
    document.getElementById('suggs').style.display = 'none';
    showTyping();

    try {
        const res = await fetch('/integration/client/controllers/AIController.php?action=chat', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: msg})
        });
        const data = await res.json();
        hideTyping();

        const reply = data.reply || 'Pas de reponse.';
        addMsg('ai', reply);
        speakText(reply);

        if (data.clients !== undefined) renderClients(data.clients);
        if (data.action === 'list' && data.clients) renderTable(data.clients);
    } catch (err) {
        hideTyping();
        addMsg('ai', 'Erreur reseau. Verifiez votre connexion.');
    }

    sendBtn.disabled = false;
    chatInput.focus();
}

(async function() {
    try {
        const res = await fetch('/integration/client/controllers/AIController.php?action=chat', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: 'liste tous les clients'})
        });
        const data = await res.json();
        if (data.clients) renderClients(data.clients);
    } catch (e) {
        clientsList.innerHTML = '<div class="empty-list">Erreur de chargement</div>';
    }
})();

const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
let recog = null;
let listening = false;

if (!SpeechRec) {
    micBtn.disabled = true;
    micBtn.title = 'Non supporte - utilisez Chrome ou Edge';
    micBtn.style.opacity = '0.45';
}

function toggleMic() {
    if (!SpeechRec) return;
    if (listening) {
        stopVoice();
        return;
    }
    startVoice();
}

function startVoice() {
    if (window.speechSynthesis) window.speechSynthesis.cancel();
    recog = new SpeechRec();
    recog.lang = 'fr-FR';
    recog.continuous = false;
    recog.interimResults = true;

    recog.onstart = function() {
        listening = true;
        micBtn.className = 'ai-icon-btn listen';
        micBtn.innerHTML = '<i class="bi bi-stop-fill"></i>';
        voiceBar.className = 'voice-bar show';
        voiceBarText.textContent = 'Ecoute en cours... parlez !';
    };

    recog.onresult = function(e) {
        let txt = '';
        for (let i = e.resultIndex; i < e.results.length; i++) txt += e.results[i][0].transcript;
        chatInput.value = txt;
        chatInput.dispatchEvent(new Event('input'));
        voiceBarText.textContent = txt;
        if (e.results[e.results.length - 1].isFinal) {
            setTimeout(function() { sendMsg(); }, 400);
        }
    };

    recog.onerror = function(e) {
        const messages = {
            'no-speech': 'Aucune voix detectee.',
            'not-allowed': 'Microphone bloque - autorisez dans le navigateur.',
            'audio-capture': 'Micro introuvable.',
            'network': 'Erreur reseau.'
        };
        voiceBarText.textContent = messages[e.error] || ('Erreur: ' + e.error);
        stopVoice();
    };

    recog.onend = function() {
        if (listening) stopVoice();
    };

    try {
        recog.start();
    } catch (e) {
        voiceBarText.textContent = 'Erreur demarrage micro.';
    }
}

function stopVoice() {
    listening = false;
    micBtn.className = 'ai-icon-btn';
    micBtn.innerHTML = '<i class="bi bi-mic-fill"></i>';
    voiceBar.className = 'voice-bar';
    if (recog) {
        try { recog.stop(); } catch (e) {}
    }
}

function speakText(text) {
    if (!window.speechSynthesis) return;
    const clean = String(text).replace(/[*_`#]/g, '').replace(/\n/g, ' ').trim();
    if (!clean) return;

    window.speechSynthesis.cancel();
    const utt = new SpeechSynthesisUtterance(clean);
    utt.lang = 'fr-FR';
    utt.rate = 1.05;
    utt.pitch = 1;

    const voices = window.speechSynthesis.getVoices();
    const frVoice = voices.find(function(v) { return v.lang.startsWith('fr'); });
    if (frVoice) utt.voice = frVoice;

    utt.onstart = function() {
        micBtn.className = 'ai-icon-btn speak';
        micBtn.innerHTML = '<i class="bi bi-volume-up-fill"></i>';
        voiceBar.className = 'voice-bar show speaking';
        voiceBarText.textContent = 'AI Helper parle...';
    };

    utt.onend = utt.onerror = function() {
        micBtn.className = 'ai-icon-btn';
        micBtn.innerHTML = '<i class="bi bi-mic-fill"></i>';
        voiceBar.className = 'voice-bar';
    };

    window.speechSynthesis.speak(utt);
}

if (window.speechSynthesis) {
    window.speechSynthesis.onvoiceschanged = function() {
        window.speechSynthesis.getVoices();
    };
}
</script>
JS;

require __DIR__ . '/layout_footer.php';
?>
