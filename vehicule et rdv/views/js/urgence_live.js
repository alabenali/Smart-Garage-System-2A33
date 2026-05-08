(function () {
    const streamConfig = document.querySelector('[data-urgence-stream]');
    if (!streamConfig) {
        return;
    }

    const streamUrl = streamConfig.getAttribute('data-urgence-stream');
    if (!streamUrl) {
        return;
    }

    const listBody = document.getElementById('rdvListBody');
    const urgentPanel = document.getElementById('urgentRdvPanel');
    const urgentBody = document.getElementById('urgentRdvBody');

    const statusClassMap = {
        'en attente': 'en-attente',
        'confirme': 'confirme',
        'en cours': 'en-cours',
        'termine': 'termine',
        'annule': 'annule',
    };

    function normalizeStatus(value) {
        return String(value || '')
            .toLowerCase()
            .trim()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function formatDateTime(raw) {
        if (!raw) {
            return '-';
        }
        const parts = String(raw).split(' ');
        if (parts.length === 0) {
            return raw;
        }
        const dateParts = parts[0].split('-');
        if (dateParts.length !== 3) {
            return raw;
        }
        const timePart = parts[1] ? parts[1].slice(0, 5) : '';
        return `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}${timePart ? ' ' + timePart : ''}`;
    }

    function urgenceClass(score) {
        if (score >= 7) {
            return 'urgence-high';
        }
        if (score >= 4) {
            return 'urgence-medium';
        }
        return 'urgence-low';
    }

    function buildStatusBadge(statut) {
        const statusKey = normalizeStatus(statut);
        const cls = statusClassMap[statusKey] || 'en-attente';
        const span = document.createElement('span');
        span.className = `status-badge status-${cls}`;
        span.textContent = statut || 'En attente';
        return span;
    }

    function buildUrgenceBadge(score) {
        const span = document.createElement('span');
        span.className = `urgence-badge ${urgenceClass(score)}`;
        span.textContent = `${score}/10`;
        return span;
    }

    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'urgence-toast';
        toast.textContent = message;
        document.body.appendChild(toast);

        requestAnimationFrame(function () {
            toast.classList.add('show');
        });

        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () {
                toast.remove();
            }, 300);
        }, 4000);
    }

    function makeDetailRow(label, value) {
        const div = document.createElement('div');
        const strong = document.createElement('strong');
        strong.textContent = `${label}:`;
        div.appendChild(strong);
        div.appendChild(document.createTextNode(' ' + (value || '-')));
        return div;
    }

    function upsertListRows(item) {
        if (!listBody || !item) {
            return;
        }

        const id = item.id || item.id_rdv;
        if (!id) {
            return;
        }

        const detailId = `rdv-detail-${id}`;
        const existingRow = listBody.querySelector(`[data-rdv-detail-id="${detailId}"]`);
        if (existingRow) {
            const urgenceCell = existingRow.querySelector('.urgence-badge');
            if (urgenceCell) {
                urgenceCell.className = `urgence-badge ${urgenceClass(item.urgence_score || 0)}`;
                urgenceCell.textContent = `${item.urgence_score || 0}/10`;
            }
            const statusBadge = existingRow.querySelector('.status-badge');
            if (statusBadge) {
                const statusKey = normalizeStatus(item.statut || '');
                const cls = statusClassMap[statusKey] || 'en-attente';
                statusBadge.className = `status-badge status-${cls}`;
                statusBadge.textContent = item.statut || 'En attente';
            }
            return;
        }

        const summaryRow = document.createElement('tr');
        summaryRow.className = 'rdv-summary-row';
        summaryRow.dataset.rdvDetailId = detailId;
        summaryRow.dataset.urgenceScore = String(item.urgence_score || 0);
        if ((item.urgence_score || 0) >= 7) {
            summaryRow.classList.add('rdv-urgent');
        }

        const cells = [
            formatDateTime(item.date_heure),
            item.type_intervention || '-',
            item.circonstances_panne || '-',
            item.description_panne || '-',
            Array.isArray(item.temoins_panne) && item.temoins_panne.length > 0 ? item.temoins_panne.join(', ') : '-',
        ];

        cells.forEach(function (text) {
            const td = document.createElement('td');
            td.textContent = text;
            summaryRow.appendChild(td);
        });

        const urgenceTd = document.createElement('td');
        urgenceTd.appendChild(buildUrgenceBadge(item.urgence_score || 0));
        summaryRow.appendChild(urgenceTd);

        const statusTd = document.createElement('td');
        statusTd.appendChild(buildStatusBadge(item.statut));
        summaryRow.appendChild(statusTd);

        const detailRow = document.createElement('tr');
        detailRow.id = detailId;
        detailRow.className = 'rdv-detail-row';
        detailRow.style.display = 'none';

        const detailCell = document.createElement('td');
        detailCell.colSpan = 7;

        const detailBox = document.createElement('div');
        detailBox.className = 'rdv-list-detail-box';
        detailBox.appendChild(makeDetailRow('Type de panne', item.type_intervention || '-'));
        detailBox.appendChild(makeDetailRow('Circonstances', item.circonstances_panne || '-'));
        detailBox.appendChild(makeDetailRow('Symptomes', item.description_panne || '-'));
        detailBox.appendChild(makeDetailRow('Temoins', Array.isArray(item.temoins_panne) && item.temoins_panne.length > 0 ? item.temoins_panne.join(', ') : '-'));
        detailBox.appendChild(makeDetailRow('Urgence', `${item.urgence_score || 0}/10`));
        detailBox.appendChild(makeDetailRow('Images de panne', '-'));

        detailCell.appendChild(detailBox);
        detailRow.appendChild(detailCell);

        listBody.prepend(detailRow);
        listBody.prepend(summaryRow);
    }

    function upsertUrgentRow(item) {
        if (!urgentBody || !item) {
            return;
        }

        const id = item.id || item.id_rdv;
        if (!id) {
            return;
        }

        const existingRow = urgentBody.querySelector(`[data-rdv-id="${id}"]`);
        const score = item.urgence_score || 0;

        if (existingRow) {
            const urgenceCell = existingRow.querySelector('.urgence-badge');
            if (urgenceCell) {
                urgenceCell.className = `urgence-badge ${urgenceClass(score)}`;
                urgenceCell.textContent = `${score}/10`;
            }
            return;
        }

        const row = document.createElement('tr');
        row.dataset.rdvId = String(id);

        const dateTd = document.createElement('td');
        dateTd.textContent = formatDateTime(item.date_heure);
        row.appendChild(dateTd);

        const typeTd = document.createElement('td');
        typeTd.textContent = item.type_intervention || '-';
        row.appendChild(typeTd);

        const statusTd = document.createElement('td');
        statusTd.appendChild(buildStatusBadge(item.statut));
        row.appendChild(statusTd);

        const urgenceTd = document.createElement('td');
        urgenceTd.appendChild(buildUrgenceBadge(score));
        row.appendChild(urgenceTd);

        if (urgentBody.children.length > 0) {
            urgentBody.prepend(row);
        } else {
            urgentBody.appendChild(row);
        }
    }

    async function loadUrgents() {
        if (!urgentPanel || !urgentBody) {
            return;
        }

        const url = urgentPanel.getAttribute('data-urgents-url');
        if (!url) {
            return;
        }

        try {
            const response = await fetch(url);
            const payload = await response.json();
            const data = Array.isArray(payload.data) ? payload.data : [];

            urgentBody.innerHTML = '';
            if (data.length === 0) {
                const emptyRow = document.createElement('tr');
                const emptyCell = document.createElement('td');
                emptyCell.colSpan = 4;
                emptyCell.style.textAlign = 'center';
                emptyCell.style.color = 'var(--text-muted)';
                emptyCell.style.padding = '1.5rem';
                emptyCell.textContent = 'Aucun RDV urgent.';
                emptyRow.appendChild(emptyCell);
                urgentBody.appendChild(emptyRow);
                return;
            }

            data.forEach(function (item) {
                upsertUrgentRow(item);
            });
        } catch (error) {
            urgentBody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:1.5rem;">Erreur chargement</td></tr>';
        }
    }

    loadUrgents();

    const eventSource = new EventSource(streamUrl);
    eventSource.addEventListener('rdv_urgence_updated', function (event) {
        let payload;
        try {
            payload = JSON.parse(event.data);
        } catch (error) {
            return;
        }

        const eventData = payload.data || payload;
        const rdv = eventData.rdv || eventData;
        if (!rdv) {
            return;
        }

        rdv.urgence_score = eventData.urgence_score || rdv.urgence_score || 0;
        rdv.urgence_details = eventData.urgence_details || rdv.urgence_details || {};

        upsertListRows(rdv);
        upsertUrgentRow(rdv);
        showToast('Nouveau RDV urgent detecte');
    });
})();
