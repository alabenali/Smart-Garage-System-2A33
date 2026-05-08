<!-- ══════════════ AUTOBOT CHATBOT WIDGET ══════════════ -->
<style>
#chatbotBtn {
    position:fixed; bottom:90px; right:24px; z-index:9999;
    width:58px; height:58px; border-radius:50%; border:none;
    background: var(--gradient-1);
    color: var(--text-dark); font-size:1.4rem; cursor:pointer;
    box-shadow:0 6px 24px var(--accent-glow);
    transition:transform 0.3s, box-shadow 0.3s;
    display:flex; align-items:center; justify-content:center;
}
#chatbotBtn:hover { transform:scale(1.1); box-shadow:0 8px 30px var(--accent-glow); }
#chatbotBtn .notif {
    position:absolute; top:-2px; right:-2px; width:14px; height:14px;
    background:var(--danger); border-radius:50%; border:2px solid var(--bg-card);
    animation:notifPulse 2s infinite;
}
@keyframes notifPulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.3)} }

#chatbotBox {
    position:fixed; bottom:158px; right:24px; z-index:9999;
    width:360px; height:500px; border-radius:20px;
    background: var(--bg-card);
    border:1px solid var(--border-color);
    box-shadow: var(--shadow-lg);
    display:flex; flex-direction:column; overflow:hidden;
    transform:scale(0.85) translateY(20px); opacity:0;
    transition:transform 0.3s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s;
    pointer-events:none;
}
#chatbotBox.open { transform:scale(1) translateY(0); opacity:1; pointer-events:all; }

.cb-header {
    padding:14px 18px; display:flex; align-items:center; gap:10px;
    background: var(--bg-secondary);
    border-bottom:1px solid var(--border-color);
}
.cb-avatar {
    width:38px; height:38px; border-radius:50%; flex-shrink:0;
    background: var(--gradient-1);
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; box-shadow:0 0 12px var(--accent-glow);
    color: var(--text-dark);
}
.cb-header h4 { margin:0; color:var(--accent-secondary); font-size:0.92rem; }
.cb-header p  { margin:0; color:var(--text-secondary); font-size:0.72rem; }
.cb-online { width:8px; height:8px; background:var(--success); border-radius:50%; margin-left:auto; box-shadow:0 0 5px var(--success); flex-shrink:0; }
.cb-close { background:none; border:none; color:var(--text-secondary); cursor:pointer; font-size:1.1rem; padding:4px; margin-left:8px; transition:color 0.2s; }
.cb-close:hover { color:var(--accent-secondary); }

.cb-messages { flex:1; overflow-y:auto; padding:14px; display:flex; flex-direction:column; gap:10px; scrollbar-width:thin; scrollbar-color:rgba(0,0,0,0.12) transparent; }

.cb-msg { display:flex; gap:8px; max-width:90%; animation:cbFade 0.3s ease; }
@keyframes cbFade { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }
.cb-msg.user { align-self:flex-end; flex-direction:row-reverse; }
.cb-msg.bot  { align-self:flex-start; }
.cb-msg-av { width:28px; height:28px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:0.75rem; }
.cb-msg.user .cb-msg-av { background:var(--gradient-1); color:var(--text-dark); }
.cb-msg.bot  .cb-msg-av { background:var(--bg-secondary); color:var(--accent); border:1px solid var(--border-color); }
.cb-bubble { padding:9px 12px; border-radius:14px; font-size:0.83rem; line-height:1.5; word-break:break-word; }
.cb-msg.user .cb-bubble { background:var(--gradient-1); color:var(--text-dark); border-bottom-right-radius:4px; }
.cb-msg.bot  .cb-bubble { background:var(--bg-secondary); color:var(--text-primary); border:1px solid var(--border-color); border-bottom-left-radius:4px; }

.cb-typing { display:flex; gap:5px; padding:10px 12px; }
.cb-typing span { width:6px; height:6px; border-radius:50%; background:var(--accent); animation:cbdot 1.2s infinite; }
.cb-typing span:nth-child(2){animation-delay:0.2s}
.cb-typing span:nth-child(3){animation-delay:0.4s}
@keyframes cbdot { 0%,80%,100%{opacity:0.2;transform:scale(0.8)} 40%{opacity:1;transform:scale(1)} }

.cb-suggestions { padding:8px 12px; display:flex; flex-wrap:wrap; gap:6px; border-top:1px solid var(--border-color); background: var(--bg-card); }
.cb-chip { background:var(--info-bg); border:1px solid var(--border-color); color:var(--accent-secondary); padding:4px 10px; border-radius:14px; font-size:0.72rem; cursor:pointer; transition:background 0.2s; white-space:nowrap; }
.cb-chip:hover { background:var(--bg-card-hover); }

.cb-input-bar { padding:10px 12px; border-top:1px solid var(--border-color); display:flex; gap:8px; align-items:flex-end; background:var(--bg-card); }
#cbInput { flex:1; background:var(--bg-primary); border:1px solid var(--border-color); border-radius:10px; padding:9px 12px; color:var(--text-primary); font-size:0.83rem; resize:none; outline:none; max-height:80px; min-height:36px; font-family:inherit; line-height:1.4; }
#cbInput:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-glow); }
#cbInput::placeholder { color:var(--text-secondary); }
#cbSend { width:36px; height:36px; border-radius:10px; border:none; background:var(--gradient-1); color:var(--text-dark); cursor:pointer; font-size:0.85rem; flex-shrink:0; transition:transform 0.2s; }
#cbSend:hover:not(:disabled) { transform:translateY(-2px); }
#cbSend:disabled { opacity:0.4; cursor:not-allowed; }

#chatbotBtn .notif { border-color: var(--bg-card); }
</style>

<!-- Floating Button -->
<button id="chatbotBtn" onclick="toggleChatbot()" title="Parler avec AutoBot 🚗">
    <i class="fas fa-car" id="cbBtnIcon"></i>
    <span class="notif" id="cbNotif"></span>
</button>

<!-- Chat Box -->
<div id="chatbotBox">
    <div class="cb-header">
        <div class="cb-avatar"><i class="fas fa-robot"></i></div>
        <div>
            <h4>AutoBot 🚗</h4>
            <p>Expert automobile Smart Garage</p>
        </div>
        <div class="cb-online"></div>
        <button class="cb-close" onclick="toggleChatbot()"><i class="fas fa-times"></i></button>
    </div>

    <div class="cb-messages" id="cbMessages">
        <div class="cb-msg bot">
            <div class="cb-msg-av"><i class="fas fa-robot"></i></div>
            <div class="cb-bubble">
                Bonjour ! Je suis <strong>AutoBot</strong> 🤖<br><br>
                Je peux répondre à <strong>toutes vos questions</strong> — voitures, mécanique, cuisine, maths, traduction, informatique, et bien plus encore !<br>
                Demandez-moi n'importe quoi 😊
            </div>
        </div>
    </div>

    <div class="cb-suggestions" id="cbSuggestions">
        <span class="cb-chip" onclick="cbSugg(this)">🚗 Prix révision</span>
        <span class="cb-chip" onclick="cbSugg(this)">🔧 Vidange huile</span>
        <span class="cb-chip" onclick="cbSugg(this)">💡 Conseil achat voiture</span>
        <span class="cb-chip" onclick="cbSugg(this)">🌍 Traduction</span>
        <span class="cb-chip" onclick="cbSugg(this)">🍕 Recette cuisine</span>
        <span class="cb-chip" onclick="cbSugg(this)">💻 Question info</span>
        <span class="cb-chip" onclick="cbSugg(this)">➗ Calcul maths</span>
        <span class="cb-chip" onclick="cbSugg(this)">⚙️ Voyant moteur</span>
    </div>

    <div class="cb-input-bar">
        <textarea id="cbInput" placeholder="Posez votre question..." rows="1"></textarea>
        <button id="cbSend" onclick="cbSend()"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
var cbOpen    = false;
var cbHistory = [];
var cbSending = false;

function toggleChatbot() {
    cbOpen = !cbOpen;
    document.getElementById('chatbotBox').classList.toggle('open', cbOpen);
    document.getElementById('cbNotif').style.display = cbOpen ? 'none' : '';
    document.getElementById('cbBtnIcon').className = cbOpen ? 'fas fa-times' : 'fas fa-car';
    if (cbOpen) document.getElementById('cbInput').focus();
}

function cbSugg(el) {
    document.getElementById('cbInput').value = el.textContent.replace(/^[^\s]+\s/, '').trim();
    cbSend();
}

function cbNow() {
    return new Date().toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});
}

function cbAddMsg(role, text) {
    var html = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>')
        .replace(/`([^`]+)`/g,'<code style="background:var(--accent-glow);color:var(--accent);padding:1px 5px;border-radius:3px;">$1</code>')
        .replace(/\n/g,'<br>');
    var icon = role === 'user' ? 'fas fa-user' : 'fas fa-robot';
    var d = document.createElement('div');
    d.className = 'cb-msg ' + role;
    d.innerHTML = '<div class="cb-msg-av"><i class="' + icon + '"></i></div>' +
        '<div><div class="cb-bubble">' + html + '</div>' +
        '<div style="font-size:0.65rem;color:var(--text-secondary);margin-top:3px;padding:0 3px;">' + cbNow() + '</div></div>';
    document.getElementById('cbMessages').appendChild(d);
    document.getElementById('cbMessages').scrollTop = 99999;
}

function cbShowTyping() {
    var d = document.createElement('div');
    d.id = 'cbTyping'; d.className = 'cb-msg bot';
    d.innerHTML = '<div class="cb-msg-av"><i class="fas fa-robot"></i></div>' +
        '<div class="cb-bubble"><div class="cb-typing"><span></span><span></span><span></span></div></div>';
    document.getElementById('cbMessages').appendChild(d);
    document.getElementById('cbMessages').scrollTop = 99999;
}

function cbHideTyping() {
    var t = document.getElementById('cbTyping');
    if (t) t.remove();
}

document.getElementById('cbInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); cbSend(); }
});
document.getElementById('cbInput').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 80) + 'px';
});

async function cbSend() {
    var msg = document.getElementById('cbInput').value.trim();
    if (!msg || cbSending) return;

    cbAddMsg('user', msg);
    cbHistory.push({role: 'user', content: msg});
    document.getElementById('cbInput').value = '';
    document.getElementById('cbInput').style.height = 'auto';
    document.getElementById('cbSend').disabled = true;
    cbSending = true;
    cbShowTyping();

    try {
        var res  = await fetch('/integration/client/controllers/ChatbotController.php?action=chat', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: msg, history: cbHistory})
        });
        var data = await res.json();
        cbHideTyping();
        var reply = data.reply || "Désolé, je n'ai pas pu répondre.";
        cbAddMsg('bot', reply);
        cbHistory.push({role: 'assistant', content: reply});
        // Garder seulement les 10 derniers messages
        if (cbHistory.length > 10) cbHistory = cbHistory.slice(-10);
    } catch (err) {
        cbHideTyping();
        cbAddMsg('bot', "Erreur de connexion. Veuillez réessayer.");
    }

    document.getElementById('cbSend').disabled = false;
    cbSending = false;
}
</script>
