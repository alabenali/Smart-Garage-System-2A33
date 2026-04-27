const DASHBOARD_MECANICIENS_URL = `${window.location.origin}/smart_garage/controller/MecanicienController.php?action=getAll`;
const DASHBOARD_FORMATIONS_URL = `${window.location.origin}/smart_garage/controller/FormationController.php?action=getAll`;
const DASHBOARD_RELATIONS_URL = `${window.location.origin}/smart_garage/controller/MecanicienController.php?action=getAllWithFormations`;

const dashboardState = {
  mecaniciens: [],
  formations: [],
  relations: []
};

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

async function readJson(url) {
  const response = await fetch(url);
  const payload = await response.json();
  if (!response.ok || !payload.ok) {
    throw new Error(payload.error || "Reponse API invalide");
  }
  return payload.data || [];
}

function renderStats() {
  const relationCount = dashboardState.relations.reduce(
    (total, mecanicien) => total + (Array.isArray(mecanicien.formations) ? mecanicien.formations.length : 0),
    0
  );

  document.getElementById("statsGrid").innerHTML = `
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-user-cog"></i></div>
      <h3>MECANICIENS</h3>
      <div class="value">${dashboardState.mecaniciens.length}</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
      <h3>FORMATIONS</h3>
      <div class="value">${dashboardState.formations.length}</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-link"></i></div>
      <h3>RELATIONS</h3>
      <div class="value">${relationCount}</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-database"></i></div>
      <h3>BASE ACTIVE</h3>
      <div class="value">smart_garage</div>
    </div>
  `;
}

function renderProjectInfo() {
  document.getElementById("projectInfo").innerHTML = `
    <div class="info-row">
      <span class="info-label"><i class="fas fa-sitemap"></i> Modules relies</span>
      <span class="info-value">Gestion des Mecaniciens, Gestion des Formations et Dashboard</span>
    </div>
    <div class="info-row">
      <span class="info-label"><i class="fas fa-code-branch"></i> Jointure active</span>
      <span class="info-value">mecanicien_formation</span>
    </div>
    <div class="info-row">
      <span class="info-label"><i class="fas fa-server"></i> Source</span>
      <span class="info-value">Controllers PHP connectes a smart_garage</span>
    </div>
  `;
}

function renderMecaniciens() {
  const container = document.getElementById("mecaniciensList");
  const message = document.getElementById("mecaniciensMessage");

  if (dashboardState.mecaniciens.length === 0) {
    container.innerHTML = "";
    message.textContent = "Aucun mecanicien disponible.";
    return;
  }

  container.innerHTML = dashboardState.mecaniciens
    .map(
      (mecanicien) => `
        <article class="mecanicien-card">
          <span class="mecanicien-badge">${escapeHtml(mecanicien.specialite)}</span>
          <h4>${escapeHtml(mecanicien.nom)} ${escapeHtml(mecanicien.prenom)}</h4>
          <p><strong>Telephone :</strong> ${escapeHtml(mecanicien.telephone)}</p>
        </article>
      `
    )
    .join("");

  message.textContent = `${dashboardState.mecaniciens.length} mecanicien(s) charges depuis la base.`;
}

function renderFormations() {
  const container = document.getElementById("formationsList");
  const message = document.getElementById("formationsMessage");

  if (dashboardState.formations.length === 0) {
    container.innerHTML = "";
    message.textContent = "Aucune formation disponible.";
    return;
  }

  container.innerHTML = dashboardState.formations
    .map(
      (formation) => `
        <article class="formation-card">
          <span class="formation-badge">${escapeHtml(formation.status)}</span>
          <h4>${escapeHtml(formation.description)}</h4>
          <p><strong>Duree :</strong> ${escapeHtml(formation.duree_heures)} heures</p>
          <p><strong>Certificat :</strong> ${escapeHtml(formation.certificat)}</p>
        </article>
      `
    )
    .join("");

  message.textContent = `${dashboardState.formations.length} formation(s) chargee(s) depuis la base.`;
}

function renderRelationsList() {
  const container = document.getElementById("relationsList");
  const message = document.getElementById("relationsListMessage");

  const cards = [];
  dashboardState.relations.forEach((mecanicien) => {
    (mecanicien.formations || []).forEach((formation) => {
      cards.push(`
        <article class="relation-card">
          <span class="relation-badge">${escapeHtml(mecanicien.specialite)}</span>
          <h4>${escapeHtml(mecanicien.nom)} ${escapeHtml(mecanicien.prenom)}</h4>
          <p><strong>Formation :</strong> ${escapeHtml(formation.description)}</p>
          <p><strong>Note :</strong> ${escapeHtml(formation.note_obtenue ?? "-")}</p>
        </article>
      `);
    });
  });

  if (cards.length === 0) {
    container.innerHTML = "";
    message.textContent = "Aucune relation active pour le moment.";
    return;
  }

  container.innerHTML = cards.join("");
  message.textContent = `${cards.length} relation(s) active(s) affichee(s).`;
}

function drawRelationGraph(relations) {
  const canvas = document.getElementById("relationsCanvas");
  const message = document.getElementById("relationsMessage");
  const context = canvas.getContext("2d");

  context.fillStyle = "#081627";
  context.fillRect(0, 0, canvas.width, canvas.height);

  if (!Array.isArray(relations) || relations.length === 0) {
    context.fillStyle = "#dce8f8";
    context.font = "20px Poppins, Segoe UI, sans-serif";
    context.fillText("Aucune relation mecanicien ↔ formation disponible.", 180, 250);
    message.textContent = "Aucune relation a afficher.";
    return;
  }

  const formationMap = new Map();
  relations.forEach((mecanicien) => {
    (mecanicien.formations || []).forEach((formation) => {
      if (!formationMap.has(formation.id_formation)) {
        formationMap.set(formation.id_formation, formation);
      }
    });
  });

  const formations = Array.from(formationMap.values());
  const mecanicienSpacing = canvas.height / (relations.length + 1);
  const formationSpacing = canvas.height / (Math.max(formations.length, 1) + 1);
  const mecanicienPositions = new Map();
  const formationPositions = new Map();

  relations.forEach((mecanicien, index) => {
    mecanicienPositions.set(mecanicien.id_mecanicien, {
      x: 150,
      y: mecanicienSpacing * (index + 1)
    });
  });

  formations.forEach((formation, index) => {
    formationPositions.set(formation.id_formation, {
      x: 700,
      y: formationSpacing * (index + 1)
    });
  });

  context.strokeStyle = "#7f8c99";
  context.lineWidth = 2;

  relations.forEach((mecanicien) => {
    (mecanicien.formations || []).forEach((formation) => {
      const mecanicienPosition = mecanicienPositions.get(mecanicien.id_mecanicien);
      const formationPosition = formationPositions.get(formation.id_formation);

      if (!mecanicienPosition || !formationPosition) {
        return;
      }

      context.beginPath();
      context.moveTo(mecanicienPosition.x + 24, mecanicienPosition.y);
      context.lineTo(formationPosition.x - 24, formationPosition.y);
      context.stroke();
    });
  });

  context.fillStyle = "#2d9cdb";
  relations.forEach((mecanicien) => {
    const position = mecanicienPositions.get(mecanicien.id_mecanicien);
    if (!position) {
      return;
    }

    context.beginPath();
    context.arc(position.x, position.y, 24, 0, Math.PI * 2);
    context.fill();

    context.fillStyle = "#ffffff";
    context.font = "14px Poppins, Segoe UI, sans-serif";
    context.fillText(`${mecanicien.nom} ${mecanicien.prenom}`, position.x - 70, position.y + 42);
    context.fillStyle = "#2d9cdb";
  });

  context.fillStyle = "#27ae60";
  formations.forEach((formation) => {
    const position = formationPositions.get(formation.id_formation);
    if (!position) {
      return;
    }

    context.fillRect(position.x - 24, position.y - 24, 48, 48);
    context.fillStyle = "#ffffff";
    context.font = "13px Poppins, Segoe UI, sans-serif";
    context.fillText(formation.description, position.x - 90, position.y + 42);
    context.fillStyle = "#27ae60";
  });

  message.textContent = `${relations.length} mecanicien(s) et ${formations.length} formation(s) visualise(s).`;
}

async function loadDashboardData() {
  try {
    const [mecaniciens, formations, relations] = await Promise.all([
      readJson(DASHBOARD_MECANICIENS_URL),
      readJson(DASHBOARD_FORMATIONS_URL),
      readJson(DASHBOARD_RELATIONS_URL)
    ]);

    dashboardState.mecaniciens = mecaniciens;
    dashboardState.formations = formations;
    dashboardState.relations = relations;

    renderStats();
    renderProjectInfo();
    renderMecaniciens();
    renderFormations();
    renderRelationsList();
    drawRelationGraph(relations);
  } catch (error) {
    document.getElementById("mecaniciensMessage").textContent = "Impossible de charger les mecaniciens.";
    document.getElementById("formationsMessage").textContent = "Impossible de charger les formations.";
    document.getElementById("relationsMessage").textContent = `Erreur: ${error.message}`;
    document.getElementById("relationsListMessage").textContent = "Impossible de charger les relations.";
  }
}

async function loadRelations() {
  try {
    dashboardState.relations = await readJson(DASHBOARD_RELATIONS_URL);
    drawRelationGraph(dashboardState.relations);
    renderRelationsList();
  } catch (error) {
    document.getElementById("relationsMessage").textContent = `Erreur: ${error.message}`;
  }
}

document.addEventListener("DOMContentLoaded", async () => {
  await loadDashboardData();
});
