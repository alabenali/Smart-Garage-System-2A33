const FORMATION_CONTROLLER_URL = `${window.location.origin}/smart_garage/controller/FormationController.php`;
const RELATION_CONTROLLER_URL = `${window.location.origin}/smart_garage/controller/MecanicienController.php`;

let formations = [];
let mecaniciens = [];
let relations = [];

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
  toast.innerHTML = `<i class="fas ${type === "success" ? "fa-check-circle" : "fa-circle-info"}"></i> ${escapeHtml(message)}`;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 2600);
}

function setStatus(id, message = "", type = "") {
  const element = document.getElementById(id);
  if (!element) {
    return;
  }

  element.textContent = message;
  element.className = "message-inline";
  if (type) {
    element.classList.add(type);
  }
}

async function readResponse(response) {
  const text = await response.text();

  try {
    return text ? JSON.parse(text) : { ok: false, error: "Reponse vide du serveur." };
  } catch {
    return { ok: false, error: "Reponse serveur invalide." };
  }
}

async function api(url, action, payload = null, method = "POST") {
  try {
    const options = { method };

    if (payload !== null) {
      options.headers = { "Content-Type": "application/json" };
      options.body = JSON.stringify(payload);
    }

    const response = await fetch(`${url}?action=${encodeURIComponent(action)}`, options);
    return await readResponse(response);
  } catch (error) {
    return {
      ok: false,
      error: `Impossible de contacter le serveur: ${String(error?.message || error)}`
    };
  }
}

function openFormationModal(mode, formation = null) {
  document.getElementById("formationModal").style.display = "flex";
  document.getElementById("formationModalTitle").innerHTML =
    mode === "edit"
      ? '<i class="fas fa-graduation-cap"></i> Modifier une formation'
      : '<i class="fas fa-graduation-cap"></i> Ajouter une formation';

  document.getElementById("id_formation").value = formation?.id_formation ?? "";
  document.getElementById("description").value = formation?.description ?? "";
  document.getElementById("duree_heures").value = formation?.duree_heures ?? "";
  document.getElementById("certificat").value = formation?.certificat ?? "";
  document.getElementById("status").value = formation?.status ?? "planifiee";
  setStatus("formationStatus");
}

function closeFormationModal() {
  document.getElementById("formationModal").style.display = "none";
  document.getElementById("formationForm").reset();
  document.getElementById("id_formation").value = "";
  document.getElementById("status").value = "planifiee";
  setStatus("formationStatus");
}

function renderFormationsTable() {
  const tbody = document.getElementById("formationsTableBody");
  if (!tbody) {
    return;
  }

  if (formations.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6">Aucune formation trouvee.</td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = formations
    .map(
      (formation) => `
        <tr>
          <td>${escapeHtml(formation.id_formation)}</td>
          <td>${escapeHtml(formation.description)}</td>
          <td>${escapeHtml(formation.duree_heures)} h</td>
          <td>${escapeHtml(formation.certificat)}</td>
          <td>${escapeHtml(formation.status)}</td>
          <td class="action-buttons">
            <button class="btn-icon edit" data-action="edit" data-id="${formation.id_formation}" type="button">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn-icon delete" data-action="delete" data-id="${formation.id_formation}" type="button">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `
    )
    .join("");
}

function renderRelationsTable() {
  const tbody = document.getElementById("relationsTableBody");
  if (!tbody) {
    return;
  }

  if (relations.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6">Aucune relation enregistree.</td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = relations
    .map(
      (relation) => `
        <tr>
          <td>${escapeHtml(`${relation.mecanicien.nom} ${relation.mecanicien.prenom}`)}</td>
          <td>${escapeHtml(relation.formation.description)}</td>
          <td>${escapeHtml(relation.pivot.date_inscription || "-")}</td>
          <td>${escapeHtml(relation.pivot.date_obtention || "-")}</td>
          <td>${escapeHtml(relation.pivot.note_obtenue ?? "-")}</td>
          <td class="action-buttons">
            <button
              class="btn-icon delete"
              type="button"
              data-action="deleteRelation"
              data-mecanicien="${relation.mecanicien.id_mecanicien}"
              data-formation="${relation.formation.id_formation}"
            >
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `
    )
    .join("");
}

function populateRelationSelects() {
  const mecanicienSelect = document.getElementById("relation_mecanicien");
  const formationSelect = document.getElementById("relation_formation");

  if (mecanicienSelect) {
    mecanicienSelect.innerHTML = mecaniciens.length
      ? mecaniciens
          .map(
            (mecanicien) => `
              <option value="${mecanicien.id_mecanicien}">
                ${escapeHtml(`${mecanicien.nom} ${mecanicien.prenom}`)}
              </option>
            `
          )
          .join("")
      : '<option value="">Aucun mecanicien disponible</option>';
  }

  if (formationSelect) {
    formationSelect.innerHTML = formations.length
      ? formations
          .map(
            (formation) => `
              <option value="${formation.id_formation}">
                ${escapeHtml(formation.description)}
              </option>
            `
          )
          .join("")
      : '<option value="">Aucune formation disponible</option>';
  }
}

async function loadFormations() {
  const response = await api(FORMATION_CONTROLLER_URL, "getAll", null, "GET");
  formations = response.ok ? response.data || [] : [];

  if (!response.ok) {
    showToast(response.error || "Erreur lors du chargement des formations.", "info");
  }

  renderFormationsTable();
  populateRelationSelects();
}

async function loadMecaniciens() {
  const response = await api(RELATION_CONTROLLER_URL, "getAll", null, "GET");
  mecaniciens = response.ok ? response.data || [] : [];

  if (!response.ok) {
    showToast(response.error || "Erreur lors du chargement des mecaniciens.", "info");
  }

  populateRelationSelects();
}

async function loadRelations() {
  const response = await api(RELATION_CONTROLLER_URL, "getRelations", null, "GET");
  relations = response.ok ? response.data || [] : [];

  if (!response.ok) {
    showToast(response.error || "Erreur lors du chargement des relations.", "info");
  }

  renderRelationsTable();
}

document.addEventListener("DOMContentLoaded", async () => {
  document.getElementById("btnAddFormation").addEventListener("click", () => openFormationModal("add"));
  document.getElementById("btnCancelFormation").addEventListener("click", closeFormationModal);

  window.addEventListener("click", (event) => {
    if (event.target === document.getElementById("formationModal")) {
      closeFormationModal();
    }
  });

  document.getElementById("formationsTableBody").addEventListener("click", async (event) => {
    const button = event.target.closest("button[data-action]");
    if (!button) {
      return;
    }

    const id = Number(button.getAttribute("data-id"));
    const action = button.getAttribute("data-action");

    if (action === "edit") {
      const formation = formations.find((item) => Number(item.id_formation) === id);
      if (formation) {
        openFormationModal("edit", formation);
      }
      return;
    }

    if (!window.confirm("Supprimer cette formation ?")) {
      return;
    }

    const response = await api(FORMATION_CONTROLLER_URL, "delete", { id_formation: id });
    if (!response.ok) {
      showToast(response.error || "Suppression impossible.", "info");
      return;
    }

    showToast("Formation supprimee.");
    await Promise.all([loadFormations(), loadRelations()]);
  });

  document.getElementById("relationsTableBody").addEventListener("click", async (event) => {
    const button = event.target.closest("button[data-action='deleteRelation']");
    if (!button) {
      return;
    }

    if (!window.confirm("Supprimer cette relation ?")) {
      return;
    }

    const response = await api(RELATION_CONTROLLER_URL, "deleteRelation", {
      id_mecanicien: button.getAttribute("data-mecanicien"),
      id_formation: button.getAttribute("data-formation")
    });

    if (!response.ok) {
      showToast(response.error || "Suppression de la relation impossible.", "info");
      return;
    }

    showToast("Relation supprimee.");
    await loadRelations();
  });

  document.getElementById("formationForm").addEventListener("submit", async (event) => {
    event.preventDefault();
    setStatus("formationStatus");

    const payload = {
      id_formation: document.getElementById("id_formation").value,
      description: document.getElementById("description").value,
      duree_heures: document.getElementById("duree_heures").value,
      certificat: document.getElementById("certificat").value,
      status: document.getElementById("status").value
    };

    const action = String(payload.id_formation).trim() === "" ? "add" : "update";
    const response = await api(FORMATION_CONTROLLER_URL, action, payload);

    if (!response.ok) {
      setStatus("formationStatus", response.error || "Verification serveur echouee.", "error");
      showToast(response.error || "Verification serveur echouee.", "info");
      return;
    }

    closeFormationModal();
    showToast(action === "add" ? "Formation ajoutee." : "Formation modifiee.");
    await Promise.all([loadFormations(), loadRelations()]);
  });

  document.getElementById("relationForm").addEventListener("submit", async (event) => {
    event.preventDefault();
    setStatus("relationStatus");

    const payload = {
      id_mecanicien: document.getElementById("relation_mecanicien").value,
      id_formation: document.getElementById("relation_formation").value,
      date_inscription: document.getElementById("date_inscription").value,
      date_obtention: document.getElementById("date_obtention").value,
      note_obtenue: document.getElementById("note_obtenue").value
    };

    const response = await api(RELATION_CONTROLLER_URL, "assignFormation", payload);
    if (!response.ok) {
      setStatus("relationStatus", response.error || "Enregistrement impossible.", "error");
      showToast(response.error || "Enregistrement impossible.", "info");
      return;
    }

    document.getElementById("relationForm").reset();
    setStatus("relationStatus", "Relation enregistree avec succes.", "success");
    showToast("Relation enregistree.");
    await loadRelations();
  });

  await Promise.all([loadFormations(), loadMecaniciens(), loadRelations()]);
});
