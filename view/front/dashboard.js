const MECANICIENS_URL =
  "http://localhost/smart_garage/controller/MecanicienController.php?action=getAll";

const userData = {
  fullname: "Ahmed Ben Ali",
  email: "ahmed.benali@email.com",
  phone: "+216 12 345 678",
  address: "12 Rue de la Liberte, Tunis",
  joinDate: "15 Janvier 2023"
};

const notifications = [
  { id: 1, message: "Votre rendez-vous du 15/04 est confirme", read: false },
  { id: 2, message: "Rappel : revision dans 7 jours", read: false },
  { id: 3, message: "Nouvelle offre disponible", read: false }
];

function escapeHtml(value) {
  return String(value ?? "").replace(/[&<>"']/g, (char) => {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;"
    };
    return map[char] || char;
  });
}

function showToast(message, type = "success") {
  const toast = document.createElement("div");
  toast.className = "toast";
  toast.innerHTML = `<i class="fas ${type === "success" ? "fa-check-circle" : "fa-info-circle"}"></i> ${escapeHtml(message)}`;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

function animateValue(id, start, end, duration) {
  const element = document.getElementById(id);
  if (!element) return;

  const range = end - start;
  const increment = range / (duration / 16);
  let current = start;

  const timer = setInterval(() => {
    current += increment;
    if (current >= end) {
      element.innerText = String(Math.floor(end));
      clearInterval(timer);
    } else {
      element.innerText = String(Math.floor(current));
    }
  }, 16);
}

function renderStats() {
  const statsGrid = document.getElementById("statsGrid");
  statsGrid.innerHTML = `
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-wrench"></i></div>
      <h3>INTERVENTIONS</h3>
      <div class="value" id="statInterventions">0</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-bell"></i></div>
      <h3>RAPPELS RECUS</h3>
      <div class="value" id="statRappels">0</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
      <h3>DEVIS EN COURS</h3>
      <div class="value" id="statDevis">0</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
      <h3>CLIENT DEPUIS</h3>
      <div class="value" id="statSince">2023</div>
    </div>
  `;

  animateValue("statInterventions", 0, 4, 1000);
  animateValue("statRappels", 0, 6, 1000);
  animateValue("statDevis", 0, 1, 1000);
}

function renderProfile() {
  const profileHtml = `
    <div class="info-row">
      <span class="info-label"><i class="fas fa-user"></i> Nom complet</span>
      <span class="info-value" id="displayFullname">${escapeHtml(userData.fullname)}</span>
    </div>
    <div class="info-row">
      <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
      <span class="info-value" id="displayEmail">${escapeHtml(userData.email)}</span>
    </div>
    <div class="info-row">
      <span class="info-label"><i class="fas fa-phone"></i> Telephone</span>
      <span class="info-value" id="displayPhone">${escapeHtml(userData.phone || "-")}</span>
    </div>
    <div class="info-row">
      <span class="info-label"><i class="fas fa-map-marker-alt"></i> Adresse</span>
      <span class="info-value" id="displayAddress">${escapeHtml(userData.address || "-")}</span>
    </div>
    <div class="info-row">
      <span class="info-label"><i class="fas fa-calendar-alt"></i> Date d'inscription</span>
      <span class="info-value">${escapeHtml(userData.joinDate)}</span>
    </div>
  `;

  document.getElementById("profileInfo").innerHTML = profileHtml;
  document.getElementById("userName").innerText = userData.fullname.split(" ")[0] || userData.fullname;
}

function renderPreferences() {
  const toggleOn = '<i class="fas fa-toggle-on" style="color: #00E5FF; font-size: 1.2rem;"></i> Active';

  const prefsHtml = `
    <div class="info-row">
      <span class="info-label"><i class="fas fa-envelope"></i> Notifications par email</span>
      <span class="info-value">${toggleOn}</span>
    </div>
    <div class="info-row">
      <span class="info-label"><i class="fas fa-sms"></i> Notifications par SMS</span>
      <span class="info-value">${toggleOn}</span>
    </div>
    <div class="info-row">
      <span class="info-label"><i class="fas fa-tools"></i> Rappels d'entretien</span>
      <span class="info-value">${toggleOn}</span>
    </div>
  `;

  document.getElementById("preferencesInfo").innerHTML = prefsHtml;
}

function renderIARecommendation() {
  const firstName = userData.fullname.split(" ")[0] || userData.fullname;
  const recommendations = [
    `Cher ${firstName}, votre derniere intervention date de 3 mois. Nous vous recommandons de planifier un controle periodique.`,
    "D'apres votre historique, une revision du systeme de freinage serait opportune dans les prochaines semaines.",
    "Votre batterie montre des signes de faiblesse. Pensez a la faire verifier."
  ];

  const randomReco = recommendations[Math.floor(Math.random() * recommendations.length)];
  const iaHtml = `
    <h3><i class="fas fa-robot"></i> Recommandation IA personnalisee</h3>
    <p><span class="ia-tag"><i class="fas fa-brain"></i> Analyse predictive</span> ${escapeHtml(randomReco)}</p>
    <p style="margin-top: 0.8rem;">
      <i class="fas fa-calendar-alt"></i> Souhaitez-vous prendre un rendez-vous ?
      <button class="edit-btn" id="btnSchedule" type="button" style="margin-left: 1rem; padding: 0.3rem 1rem;">
        <i class="fas fa-clock"></i> Planifier
      </button>
    </p>
  `;

  document.getElementById("iaRecommendation").innerHTML = iaHtml;
  const scheduleButton = document.getElementById("btnSchedule");
  if (scheduleButton) {
    scheduleButton.addEventListener("click", () => {
      showToast("Redirection vers la prise de rendez-vous...", "info");
    });
  }
}

function renderEcoInfo() {
  const co2Saved = (Math.random() * 5 + 2).toFixed(1);
  const ecoHtml = `
    <h3><i class="fas fa-leaf"></i> Votre engagement durable</h3>
    <p><i class="fas fa-check-circle" style="color: #00E5FF;"></i> Fiche client 100% numerique - zero papier utilise</p>
    <p><i class="fas fa-chart-line"></i> <strong>Votre impact :</strong> ${escapeHtml(co2Saved)} kg de CO2 economises cette annee</p>
    <p><i class="fas fa-tree"></i> Equivalent a ${Math.floor(Number(co2Saved) * 0.5)} arbres plantes</p>
  `;

  document.getElementById("ecoCard").innerHTML = ecoHtml;
}

function updateNotificationBadge() {
  const unreadCount = notifications.filter((notification) => !notification.read).length;
  const badge = document.getElementById("notifBadge");
  if (!badge) return;

  if (unreadCount > 0) {
    badge.style.display = "inline-block";
    badge.innerText = String(unreadCount);
  } else {
    badge.style.display = "none";
  }
}

function openEditModal() {
  document.getElementById("fullname").value = userData.fullname;
  document.getElementById("email").value = userData.email;
  document.getElementById("phone").value = userData.phone || "";
  document.getElementById("address").value = userData.address || "";
  document.getElementById("editModal").style.display = "flex";
}

function closeEditModal() {
  document.getElementById("editModal").style.display = "none";
}

async function getMecaniciens() {
  const response = await fetch(MECANICIENS_URL);
  return await response.json();
}

function renderMecaniciens(items) {
  const container = document.getElementById("mecaniciensList");
  const message = document.getElementById("mecaniciensMessage");

  if (!Array.isArray(items) || items.length === 0) {
    container.innerHTML = "";
    message.textContent = "Aucun mecanicien disponible pour le moment.";
    return;
  }

  container.innerHTML = items
    .map(
      (mecanicien) => `
        <article class="mecanicien-card">
          <span class="mecanicien-badge">Mecanicien #${escapeHtml(mecanicien.id_mecanicien)}</span>
          <h4>${escapeHtml(mecanicien.nom)} ${escapeHtml(mecanicien.prenom)}</h4>
          <p><strong>Telephone :</strong> ${escapeHtml(mecanicien.telephone)}</p>
          <p><strong>Specialite :</strong> ${escapeHtml(mecanicien.specialite)}</p>
        </article>
      `
    )
    .join("");

  message.textContent = `${items.length} mecanicien(s) disponibles.`;
}

async function loadMecaniciens() {
  const message = document.getElementById("mecaniciensMessage");
  message.innerHTML = '<span class="loading"></span> Chargement des mecaniciens...';

  try {
    const payload = await getMecaniciens();
    if (!payload.ok) {
      throw new Error(payload.error || "Reponse API invalide");
    }
    renderMecaniciens(payload.data || []);
  } catch (error) {
    document.getElementById("mecaniciensList").innerHTML = "";
    message.textContent =
      "Impossible de charger les mecaniciens. Verifiez Apache, MySQL et le controleur PHP.";
    console.error("Dashboard mecaniciens detail:", error);
  }
}

function bindEvents() {
  document.getElementById("btnEditProfile").addEventListener("click", openEditModal);
  document.getElementById("btnCancelEdit").addEventListener("click", closeEditModal);

  document.getElementById("btnLogout").addEventListener("click", () => {
    showToast("Deconnexion en cours...", "info");
    setTimeout(() => {
      alert("Redirection vers la page de connexion");
    }, 1000);
  });

  document.getElementById("avatarBtn").addEventListener("click", () => {
    showToast("Menu utilisateur - integration backend", "info");
  });

  window.addEventListener("click", (event) => {
    if (event.target === document.getElementById("editModal")) {
      closeEditModal();
    }
  });

  document.getElementById("editProfileForm").addEventListener("submit", (event) => {
    event.preventDefault();

    userData.fullname = document.getElementById("fullname").value.trim() || userData.fullname;
    userData.email = document.getElementById("email").value.trim() || userData.email;
    userData.phone = document.getElementById("phone").value.trim();
    userData.address = document.getElementById("address").value.trim();

    renderProfile();
    closeEditModal();
    showToast("Profil mis a jour avec succes !");
  });
}

document.addEventListener("DOMContentLoaded", async () => {
  renderStats();
  renderProfile();
  renderPreferences();
  renderIARecommendation();
  renderEcoInfo();
  updateNotificationBadge();
  bindEvents();
  await loadMecaniciens();

  setInterval(() => {
    renderIARecommendation();
  }, 30000);
});
