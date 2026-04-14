(function () {
    const adminCalendar = document.getElementById('adminCalendar');
    if (!adminCalendar) {
        return;
    }

    const detailsUrl = adminCalendar.dataset.detailsUrl;
    const statusUrl = adminCalendar.dataset.statusUrl;
    const sidebar = document.getElementById('slotSidebar');
    const statusClassMap = {
        'En attente': 'en-attente',
        'Confirmé': 'confirme',
        'En cours': 'en-cours',
        'Terminé': 'termine',
        'Annulé': 'annule',
    };

    async function loadSlotDetails(idCreneau) {
        sidebar.innerHTML = '<div class="sidebar-loading">Chargement...</div>';

        try {
            const response = await fetch(`${detailsUrl}&id_creneau=${encodeURIComponent(idCreneau)}`);
            const payload = await response.json();

            if (!payload.success) {
                sidebar.innerHTML = '<div class="sidebar-error">Impossible de charger le détail du créneau.</div>';
                return;
            }

            sidebar.innerHTML = payload.html;
        } catch (error) {
            sidebar.innerHTML = '<div class="sidebar-error">Erreur réseau.</div>';
        }
    }

    async function updateStatus(card, newStatus) {
        const idRdv = card.dataset.rdvId;
        const body = new URLSearchParams();
        body.set('id_rdv', idRdv);
        body.set('statut', newStatus);

        const response = await fetch(statusUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            },
            body: body.toString(),
        });

        const payload = await response.json();
        if (!payload.success) {
            alert('Mise à jour impossible.');
            return;
        }

        const badge = card.querySelector('[data-status-label]');
        if (badge) {
            badge.textContent = newStatus;
            const cls = statusClassMap[newStatus] || 'en-attente';
            badge.className = `status-badge status-${cls}`;
        }
    }

    adminCalendar.addEventListener('click', async function (event) {
        const cell = event.target.closest('.grid-cell[data-id-creneau]');
        if (cell) {
            await loadSlotDetails(cell.dataset.idCreneau);
            return;
        }

        const statusBtn = event.target.closest('.status-action');
        if (statusBtn) {
            const card = statusBtn.closest('.rdv-card[data-rdv-id]');
            if (!card) {
                return;
            }
            await updateStatus(card, statusBtn.dataset.status);
        }
    });
})();
