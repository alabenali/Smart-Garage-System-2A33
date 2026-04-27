const MECANICIEN_CONTROLLER_URL = `${window.location.origin}/smart_garage/controller/MecanicienController.php`;

let mecaniciens = [];

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

function setFormStatus(message = "", type = "") {
  const status = document.getElementById("formStatus");
  if (!status) {
    return;
  }

  status.textContent = message;
  status.className = "message-inline";
  if (type) {
    status.classList.add(type);
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

async function api(action, payload = null, method = "POST") {
  try {
    const options = { method };

    if (payload !== null) {
      options.headers = { "Content-Type": "application/json" };
      options.body = JSON.stringify(payload);
    }

    const response = await fetch(`${MECANICIEN_CONTROLLER_URL}?action=${encodeURIComponent(action)}`, options);
    return await readResponse(response);
  } catch (error) {
    return {
      ok: false,
      error: `Impossible de contacter le serveur: ${String(error?.message || error)}`
    };
  }
}

function renderTable() {
  const tbody = document.getElementById("tableBody");
  if (!tbody) {
    return;
  }

  if (mecaniciens.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6">Aucun mecanicien trouve.</td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = mecaniciens
    .map(
      (mecanicien) => `
        <tr>
          <td>${escapeHtml(mecanicien.id_mecanicien)}</td>
          <td>${escapeHtml(mecanicien.nom)}</td>
          <td>${escapeHtml(mecanicien.prenom)}</td>
          <td>${escapeHtml(mecanicien.telephone)}</td>
          <td>${escapeHtml(mecanicien.specialite)}</td>
          <td class="action-buttons">
            <button class="btn-icon edit" data-action="edit" data-id="${mecanicien.id_mecanicien}" type="button">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn-icon delete" data-action="delete" data-id="${mecanicien.id_mecanicien}" type="button">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `
    )
    .join("");
}

function openModal(mode, mecanicien = null) {
  document.getElementById("modal").style.display = "flex";
  document.getElementById("modalTitle").innerHTML =
    mode === "edit"
      ? '<i class="fas fa-user-cog"></i> Modifier un mecanicien'
      : '<i class="fas fa-user-cog"></i> Ajouter un mecanicien';

  document.getElementById("id_mecanicien").value = mecanicien?.id_mecanicien ?? "";
  document.getElementById("nom").value = mecanicien?.nom ?? "";
  document.getElementById("prenom").value = mecanicien?.prenom ?? "";
  document.getElementById("telephone").value = mecanicien?.telephone ?? "";
  document.getElementById("specialite").value = mecanicien?.specialite ?? "Moteur";
  setFormStatus();
}

function closeModal() {
  document.getElementById("modal").style.display = "none";
  document.getElementById("form").reset();
  document.getElementById("id_mecanicien").value = "";
  document.getElementById("specialite").value = "Moteur";
  setFormStatus();
}

async function refreshMecaniciens() {
  const response = await api("getAll", null, "GET");

  if (!response.ok) {
    mecaniciens = [];
    showToast(response.error || "Erreur de chargement.", "info");
  } else {
    mecaniciens = response.data || [];
  }

  renderTable();
}

document.addEventListener("DOMContentLoaded", async () => {
  document.getElementById("btnAdd").addEventListener("click", () => openModal("add"));
  document.getElementById("btnCancel").addEventListener("click", closeModal);

  window.addEventListener("click", (event) => {
    if (event.target === document.getElementById("modal")) {
      closeModal();
    }
  });

  document.getElementById("tableBody").addEventListener("click", async (event) => {
    const button = event.target.closest("button[data-action]");
    if (!button) {
      return;
    }

    const id = Number(button.getAttribute("data-id"));
    const action = button.getAttribute("data-action");

    if (action === "edit") {
      const mecanicien = mecaniciens.find((item) => Number(item.id_mecanicien) === id);
      if (mecanicien) {
        openModal("edit", mecanicien);
      }
      return;
    }

    if (!window.confirm("Supprimer ce mecanicien ?")) {
      return;
    }

    const response = await api("delete", { id_mecanicien: id });
    if (!response.ok) {
      showToast(response.error || "Suppression impossible.", "info");
      return;
    }

    showToast("Mecanicien supprime.");
    await refreshMecaniciens();
  });

  document.getElementById("form").addEventListener("submit", async (event) => {
    event.preventDefault();
    setFormStatus();

    const payload = {
      id_mecanicien: document.getElementById("id_mecanicien").value,
      nom: document.getElementById("nom").value,
      prenom: document.getElementById("prenom").value,
      telephone: document.getElementById("telephone").value,
      specialite: document.getElementById("specialite").value
    };

    const action = String(payload.id_mecanicien).trim() === "" ? "add" : "update";
    const response = await api(action, payload);

    if (!response.ok) {
      setFormStatus(response.error || "Verification serveur echouee.", "error");
      showToast(response.error || "Verification serveur echouee.", "info");
      return;
    }

    closeModal();
    showToast(action === "add" ? "Mecanicien ajoute." : "Mecanicien modifie.");
    await refreshMecaniciens();
  });

  await refreshMecaniciens();
});
