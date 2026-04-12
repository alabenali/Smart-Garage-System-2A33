const CONTROLLER_URL = "http://localhost/smart_garage/controller/MecanicienController.php";
const nameRegex = /^[A-Za-zÀ-ÿ\s]+$/;
const phoneRegex = /^[0-9]{8}$/;

let mecaniciens = [];

function showToast(message, type = "success") {
  const toast = document.createElement("div");
  toast.className = "toast";
  toast.innerHTML = `<i class="fas ${type === "success" ? "fa-check-circle" : "fa-info-circle"}"></i> ${message}`;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 2500);
}

function setError(field, message) {
  const el = document.getElementById(`err_${field}`);
  if (!el) return;
  el.textContent = message || "";
  el.style.display = message ? "block" : "none";
}

function clearErrors() {
  ["nom", "prenom", "telephone", "specialite"].forEach((field) => setError(field, ""));
}

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

function sanitizeName(value) {
  return value.replace(/[^A-Za-zÀ-ÿ\s]/g, "").replace(/\s{2,}/g, " ").replace(/^\s+/, "");
}

function sanitizePhone(value) {
  return value.replace(/\D/g, "").slice(0, 8);
}

function validateField(field, value) {
  const trimmed = value.trim();

  if (field === "nom") {
    return trimmed && nameRegex.test(trimmed) ? "" : "Nom invalide";
  }

  if (field === "prenom") {
    return trimmed && nameRegex.test(trimmed) ? "" : "Prenom invalide";
  }

  if (field === "telephone") {
    return phoneRegex.test(trimmed) ? "" : "Telephone invalide";
  }

  if (field === "specialite") {
    return trimmed ? "" : "Specialite obligatoire";
  }

  return "";
}

function validateForm(data) {
  clearErrors();
  const errors = {};

  ["nom", "prenom", "telephone", "specialite"].forEach((field) => {
    const message = validateField(field, data[field] || "");
    if (message) {
      errors[field] = message;
      setError(field, message);
    }
  });

  return errors;
}

async function readResponse(response) {
  const text = await response.text();

  try {
    return text ? JSON.parse(text) : { ok: false, error: "Reponse vide du serveur." };
  } catch {
    return {
      ok: false,
      error: "Reponse serveur non valide.",
      detail: text.slice(0, 600)
    };
  }
}

async function api(action, payload = null, method = "POST") {
  try {
    const options = { method };

    if (payload !== null) {
      options.headers = { "Content-Type": "application/json" };
      options.body = JSON.stringify(payload);
    }

    const response = await fetch(`${CONTROLLER_URL}?action=${encodeURIComponent(action)}`, options);
    return await readResponse(response);
  } catch (error) {
    return {
      ok: false,
      error: "Failed to fetch",
      detail:
        `${String(error?.message || error)}. Verifiez Apache, MySQL, ` +
        "et que le projet est dans htdocs/smart_garage."
    };
  }
}

function renderTable() {
  const tbody = document.getElementById("tableBody");

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
            <button class="btn-icon edit" data-action="edit" data-id="${mecanicien.id_mecanicien}">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn-icon delete" data-action="delete" data-id="${mecanicien.id_mecanicien}">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `
    )
    .join("");
}

function openModal(mode, mecanicien) {
  document.getElementById("modal").style.display = "flex";
  document.getElementById("modalTitle").innerHTML =
    mode === "edit"
      ? '<i class="fas fa-user-cog"></i> Modifier un mecanicien'
      : '<i class="fas fa-user-cog"></i> Ajouter un mecanicien';

  document.getElementById("id_mecanicien").value = mecanicien?.id_mecanicien ?? "";
  document.getElementById("nom").value = mecanicien?.nom ?? "";
  document.getElementById("prenom").value = mecanicien?.prenom ?? "";
  document.getElementById("telephone").value = mecanicien?.telephone ?? "";
  document.getElementById("specialite").value = mecanicien?.specialite ?? "";
  clearErrors();
}

function closeModal() {
  document.getElementById("modal").style.display = "none";
  document.getElementById("form").reset();
  document.getElementById("id_mecanicien").value = "";
  clearErrors();
}

function bindFieldValidation() {
  const nomInput = document.getElementById("nom");
  const prenomInput = document.getElementById("prenom");
  const telephoneInput = document.getElementById("telephone");
  const specialiteInput = document.getElementById("specialite");

  [nomInput, prenomInput].forEach((input) => {
    input.addEventListener("input", () => {
      input.value = sanitizeName(input.value);
      setError(input.id, validateField(input.id, input.value));
    });
  });

  telephoneInput.addEventListener("input", () => {
    telephoneInput.value = sanitizePhone(telephoneInput.value);
    setError("telephone", validateField("telephone", telephoneInput.value));
  });

  specialiteInput.addEventListener("input", () => {
    setError("specialite", validateField("specialite", specialiteInput.value));
  });
}

async function refresh() {
  const response = await api("getAll", null, "GET");

  if (!response.ok) {
    showToast(response.error || "Erreur de chargement", "info");
    if (response.detail) {
      console.error("Mecaniciens list() detail:", response.detail);
    }
    mecaniciens = [];
  } else {
    mecaniciens = response.data || [];
  }

  renderTable();
}

document.addEventListener("DOMContentLoaded", async () => {
  bindFieldValidation();
  document.getElementById("btnAdd").addEventListener("click", () => openModal("add"));
  document.getElementById("btnCancel").addEventListener("click", closeModal);

  window.addEventListener("click", (event) => {
    if (event.target === document.getElementById("modal")) {
      closeModal();
    }
  });

  document.getElementById("tableBody").addEventListener("click", async (event) => {
    const button = event.target.closest("button[data-action]");
    if (!button) return;

    const id = Number(button.getAttribute("data-id"));
    const action = button.getAttribute("data-action");

    if (action === "edit") {
      const mecanicien = mecaniciens.find((item) => Number(item.id_mecanicien) === id);
      if (mecanicien) openModal("edit", mecanicien);
      return;
    }

    if (action === "delete") {
      if (!confirm("Supprimer ce mecanicien ?")) return;

      const response = await api("delete", { id_mecanicien: id });
      if (!response.ok) {
        showToast(response.error || "Suppression echouee", "info");
      } else {
        showToast("Mecanicien supprime.");
        await refresh();
      }
    }
  });

  document.getElementById("form").addEventListener("submit", async (event) => {
    event.preventDefault();

    const payload = {
      id_mecanicien: document.getElementById("id_mecanicien").value,
      nom: sanitizeName(document.getElementById("nom").value).trim(),
      prenom: sanitizeName(document.getElementById("prenom").value).trim(),
      telephone: sanitizePhone(document.getElementById("telephone").value),
      specialite: document.getElementById("specialite").value.trim()
    };

    document.getElementById("nom").value = payload.nom;
    document.getElementById("prenom").value = payload.prenom;
    document.getElementById("telephone").value = payload.telephone;
    document.getElementById("specialite").value = payload.specialite;

    const errors = validateForm(payload);
    if (Object.keys(errors).length > 0) return;

    const isEdit = String(payload.id_mecanicien || "").trim() !== "";
    const response = await api(isEdit ? "update" : "add", payload);

    if (!response.ok) {
      const serverErrors = response.errors || {};
      Object.entries(serverErrors).forEach(([field, message]) => setError(field, String(message)));

      if (Object.keys(serverErrors).length > 0) {
        showToast("Corrigez les champs.", "info");
      } else {
        showToast(response.error || "Enregistrement echoue.", "info");
      }

      if (response.detail) {
        console.error("Mecaniciens save() detail:", response.detail);
      }

      return;
    }

    closeModal();
    showToast(isEdit ? "Mecanicien modifie." : "Mecanicien ajoute.");
    await refresh();
  });

  await refresh();
});
