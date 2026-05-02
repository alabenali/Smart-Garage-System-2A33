(function () {
    const app = document.getElementById('frontCalendarApp');
    if (!app) {
        return;
    }

    let currentStep = 1;
    let selectedDate = app.dataset.selectedDate || '';
    let selectedSlot = null;

    const stepItems = app.querySelectorAll('.step-item');
    const stepPanels = app.querySelectorAll('[data-step-panel]');
    const btnPrev = document.getElementById('prevStep');
    const btnNext = document.getElementById('nextStep');
    const slotsContainer = document.getElementById('slots-container') || document.getElementById('slotsContainer');
    const slotsRecommendationInfo = document.getElementById('slots-recommendation-info');
    const selectedDayLabel = document.getElementById('selectedDayLabel');
    const hiddenSlot = document.getElementById('id_creneau');
    const hiddenDate = document.getElementById('selected_date');
    const hiddenTypePanne = document.getElementById('type_panne_session');
    const form = document.getElementById('rdvForm');
    const confirmBtn = document.getElementById('confirmRdvBtn');
    const interventionField = form ? form.querySelector('[name="type_intervention"]') : null;
    const circonstancesField = form ? form.querySelector('[name="circonstances_panne"]') : null;
    const symptomesField = document.getElementById('description_panne') || (form ? form.querySelector('[name="description_panne"]') : null);
    const temoinsFields = form ? form.querySelectorAll('input[name="temoins_panne[]"]') : [];
    const panneDataField = document.getElementById('panne_data_json');
    const panneSuggestion = document.getElementById('panne-suggestion');
    const photoInput = document.getElementById('pannePhotosInput');
    const photoDropzone = document.getElementById('panneDropzone');
    const photosPreview = document.getElementById('photosPreview');
    const photosError = document.getElementById('photosError');
    const photoCount = document.getElementById('photoCount');

    const MAX_PHOTOS = 5;
    const MAX_PHOTO_SIZE = 10 * 1024 * 1024;
    const PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    const CAN_USE_DATA_TRANSFER = typeof DataTransfer !== 'undefined';
    let selectedPhotos = [];
    let panneAnalysisTimer = null;
    let panneAnalysisRequestId = 0;
    let panneAnalysisController = null;
    let internalTypeSelection = false;
    let manualTypeSelected = interventionField ? interventionField.value.trim() !== '' : false;

    function setStep(step) {
        currentStep = Math.min(4, Math.max(1, step));

        stepItems.forEach((item) => {
            item.classList.toggle('is-active', Number(item.dataset.step) <= currentStep);
        });

        stepPanels.forEach((panel) => {
            panel.style.display = Number(panel.dataset.stepPanel) === currentStep ? 'block' : 'none';
        });

        btnPrev.disabled = currentStep === 1;
        btnNext.style.display = currentStep === 4 ? 'none' : 'inline-flex';
    }

    function showError(input, message) {
        input.classList.add('is-invalid');
        const feedback = input.parentElement.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = message;
            feedback.style.display = 'block';
        }
    }

    function clearFormErrors() {
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach((fb) => {
            fb.textContent = '';
            fb.style.display = 'none';
        });
    }

    function setPhotosError(message) {
        if (!photosError) {
            return;
        }
        photosError.textContent = message;
        photosError.style.display = message ? 'block' : 'none';
    }

    function formatFileSize(bytes) {
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 Ko';
        }

        if (bytes < 1024 * 1024) {
            return `${Math.max(1, Math.round(bytes / 1024))} Ko`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(1).replace('.', ',')} Mo`;
    }

    function updatePhotoState() {
        if (photoCount) {
            photoCount.textContent = `${selectedPhotos.length} / ${MAX_PHOTOS} photos`;
        }

        if (photoDropzone) {
            photoDropzone.classList.toggle('has-files', selectedPhotos.length > 0);
        }
    }

    function syncPhotoInputFiles() {
        if (!photoInput) {
            return;
        }

        if (!CAN_USE_DATA_TRANSFER) {
            return;
        }

        try {
            const transfer = new DataTransfer();
            selectedPhotos.forEach((file) => transfer.items.add(file));
            photoInput.files = transfer.files;
        } catch (error) {
            setPhotosError('Votre navigateur limite la gestion avancée des fichiers.');
        }
    }

    function getPanneData() {
        return {
            typePanne: interventionField ? interventionField.value : '',
            circonstances: circonstancesField ? circonstancesField.value : '',
            symptomes: symptomesField ? symptomesField.value.trim() : '',
            temoins: Array.from(temoinsFields)
                .filter((item) => item.checked)
                .map((item) => item.value),
            photos: selectedPhotos.map((file) => ({
                name: file.name,
                size: file.size,
                type: file.type,
            })),
        };
    }

    function refreshPanneDataField() {
        if (!panneDataField) {
            return;
        }
        panneDataField.value = JSON.stringify(getPanneData());
    }

    function renderPhotos() {
        updatePhotoState();

        if (!photosPreview) {
            return;
        }

        photosPreview.innerHTML = '';
        selectedPhotos.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'photo-thumb';

            const image = document.createElement('img');
            image.alt = file.name;
            image.src = URL.createObjectURL(file);
            image.onload = function () {
                URL.revokeObjectURL(image.src);
            };

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'photo-remove';
            removeBtn.setAttribute('aria-label', 'Supprimer la photo');
            removeBtn.textContent = '×';
            removeBtn.addEventListener('click', function () {
                selectedPhotos = selectedPhotos.filter((_, i) => i !== index);
                syncPhotoInputFiles();
                renderPhotos();
                refreshPanneDataField();
            });

            const meta = document.createElement('div');
            meta.className = 'photo-thumb-meta';

            const name = document.createElement('span');
            name.className = 'photo-thumb-name';
            name.textContent = file.name;

            const size = document.createElement('span');
            size.className = 'photo-thumb-size';
            size.textContent = formatFileSize(file.size);

            meta.appendChild(name);
            meta.appendChild(size);

            item.appendChild(image);
            item.appendChild(removeBtn);
            item.appendChild(meta);
            photosPreview.appendChild(item);
        });
    }

    function handlePhotoFiles(fileList, source) {
        const files = Array.from(fileList || []);
        if (files.length === 0) {
            return;
        }

        if (source === 'drop' && !CAN_USE_DATA_TRANSFER) {
            setPhotosError('Glisser-déposer non supporté sur ce navigateur. Utilisez le clic.');
            return;
        }

        let errorMessage = '';

        files.forEach((file) => {
            if (selectedPhotos.length >= MAX_PHOTOS) {
                errorMessage = `Maximum ${MAX_PHOTOS} photos autorisées.`;
                return;
            }

            if (!PHOTO_TYPES.includes(file.type)) {
                errorMessage = 'Formats acceptés : JPG, PNG, WEBP uniquement.';
                return;
            }

            if (file.size > MAX_PHOTO_SIZE) {
                errorMessage = 'Chaque photo doit faire 10 Mo maximum.';
                return;
            }

            const alreadyAdded = selectedPhotos.some((existing) => (
                existing.name === file.name
                && existing.size === file.size
                && existing.lastModified === file.lastModified
            ));

            if (alreadyAdded) {
                errorMessage = 'Cette photo est déjà ajoutée.';
                return;
            }

            selectedPhotos.push(file);
        });

        setPhotosError(errorMessage);
        syncPhotoInputFiles();
        renderPhotos();
        refreshPanneDataField();
    }

    function validateFormStep() {
        clearFormErrors();
        let valid = true;

        const symptomes = form.querySelector('[name="description_panne"]');

        if (interventionField && !interventionField.value.trim()) {
            showError(interventionField, 'Type de panne obligatoire.');
            valid = false;
        }
        if (!symptomes.value.trim()) {
            showError(symptomes, 'Les symptômes observés sont obligatoires.');
            valid = false;
        }

        refreshPanneDataField();

        return valid;
    }

    function normalizeOptionText(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function getConfidenceLabel(confidence) {
        const labels = {
            high: 'élevée',
            medium: 'moyenne',
            low: 'faible',
        };

        return labels[confidence] || labels.low;
    }

    function getSuggestionBadgeClass(confidence) {
        if (confidence === 'high') {
            return 'badge rounded-pill bg-success';
        }

        if (confidence === 'medium') {
            return 'badge rounded-pill bg-warning text-dark';
        }

        return 'badge rounded-pill bg-secondary';
    }

    function renderPanneSuggestion(result) {
        if (!panneSuggestion) {
            return;
        }

        panneSuggestion.innerHTML = '';

        if (!result || !result.type) {
            return;
        }

        const confidence = ['high', 'medium', 'low'].includes(result.confidence) ? result.confidence : 'low';
        const badge = document.createElement('span');
        badge.className = getSuggestionBadgeClass(confidence);
        badge.textContent = `🔍 Type détecté : ${result.type} (confiance : ${getConfidenceLabel(confidence)})`;

        if (Array.isArray(result.keywords_found) && result.keywords_found.length > 0) {
            badge.title = `Mots-clés : ${result.keywords_found.join(', ')}`;
        }

        panneSuggestion.appendChild(badge);
    }

    function renderPanneAnalysisError() {
        if (!panneSuggestion) {
            return;
        }

        panneSuggestion.innerHTML = '';
        const badge = document.createElement('span');
        badge.className = 'badge rounded-pill bg-secondary';
        badge.textContent = 'Analyse indisponible pour le moment';
        panneSuggestion.appendChild(badge);
    }

    function selectSuggestedIntervention(type) {
        if (!interventionField || manualTypeSelected) {
            return;
        }

        const aliases = {
            'Électronique': ['Électrique-Batterie'],
            Transmission: ['Boîte de vitesse'],
            'Diagnostic général': ['Autre'],
        };
        const candidates = [type].concat(aliases[type] || []).map(normalizeOptionText);
        const option = Array.from(interventionField.options).find((item) => {
            return candidates.includes(normalizeOptionText(item.value)) || candidates.includes(normalizeOptionText(item.textContent));
        });

        if (!option || option.value === interventionField.value) {
            return;
        }

        internalTypeSelection = true;
        interventionField.value = option.value;
        interventionField.dispatchEvent(new Event('change', { bubbles: true }));
        internalTypeSelection = false;
        refreshPanneDataField();
    }

    async function analyzePanneDescription() {
        if (!symptomesField) {
            return;
        }

        const description = symptomesField.value.trim();
        const requestId = ++panneAnalysisRequestId;

        if (panneAnalysisController) {
            panneAnalysisController.abort();
        }

        if (description.length < 3) {
            renderPanneSuggestion(null);
            return;
        }

        panneAnalysisController = typeof AbortController !== 'undefined' ? new AbortController() : null;

        try {
            const response = await fetch('api/analyze-panne.php', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ description }),
                signal: panneAnalysisController ? panneAnalysisController.signal : undefined,
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            if (requestId !== panneAnalysisRequestId) {
                return;
            }

            renderPanneSuggestion(result);

            if (['high', 'medium'].includes(result.confidence)) {
                selectSuggestedIntervention(result.type);
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            if (requestId === panneAnalysisRequestId) {
                renderPanneAnalysisError();
            }
        }
    }

    function schedulePanneAnalysis() {
        window.clearTimeout(panneAnalysisTimer);
        panneAnalysisTimer = window.setTimeout(analyzePanneDescription, 400);
    }

    function updateRecap() {
        const slotDateTime = selectedSlot ? selectedSlot.dataset.slotDatetime : '';
        const intervention = interventionField ? (interventionField.value || '-') : '-';
        const circonstances = (circonstancesField && circonstancesField.value) ? circonstancesField.value : '-';

        let dateLabel = '-';
        let hourLabel = '-';
        let remiseLabel = '0%';

        if (slotDateTime) {
            const dt = new Date(slotDateTime.replace(' ', 'T'));
            if (!Number.isNaN(dt.getTime())) {
                dateLabel = dt.toLocaleDateString('fr-FR');
                hourLabel = dt.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            }
        }

        if (selectedSlot && Number(selectedSlot.dataset.offpeak) === 1) {
            remiseLabel = '15% (heure creuse)';
        }

        app.querySelector('[data-recap="date"]').textContent = dateLabel;
        app.querySelector('[data-recap="heure"]').textContent = hourLabel;
        app.querySelector('[data-recap="intervention"]').textContent = intervention;
        app.querySelector('[data-recap="circonstances"]').textContent = circonstances;
        app.querySelector('[data-recap="remise"]').textContent = remiseLabel;
    }

    function selectSlot(button) {
        app.querySelectorAll('.slot-card, .slot-item').forEach((item) => item.classList.remove('selected'));
        button.classList.add('selected');
        selectedSlot = button;
        hiddenSlot.value = button.dataset.slotId;
    }

    function getCurrentTypePanne() {
        const typeValue = interventionField ? interventionField.value.trim() : '';
        if (hiddenTypePanne) {
            hiddenTypePanne.value = typeValue;
        }

        return typeValue || 'Diagnostic général';
    }

    function setSlotsInfo(payload) {
        if (!slotsRecommendationInfo) {
            return;
        }

        if (!payload || !payload.type_panne) {
            slotsRecommendationInfo.classList.add('d-none');
            slotsRecommendationInfo.textContent = '';
            return;
        }

        slotsRecommendationInfo.classList.remove('d-none');
        slotsRecommendationInfo.textContent = `⏱️ Durée estimée pour ${payload.type_panne} : ${payload.duree_label} — Les créneaux recommandés sont mis en avant`;
    }

    function createBadge(text, className) {
        const badge = document.createElement('span');
        badge.className = className;
        badge.textContent = text;
        return badge;
    }

    function renderSlots(payload) {
        const slots = payload && Array.isArray(payload.slots) ? payload.slots : [];
        setSlotsInfo(payload);

        if (!Array.isArray(slots) || slots.length === 0) {
            slotsContainer.innerHTML = '<div class="empty-inline">Aucun créneau disponible pour cette date.</div>';
            selectedSlot = null;
            hiddenSlot.value = '';
            return;
        }

        slotsContainer.innerHTML = '';
        slots.forEach((slot) => {
            const remaining = Math.max(0, Number(slot.capacite_restante));
            const isFull = remaining <= 0 || Number(slot.capacite_max) <= 0;
            const isRecommended = Boolean(slot.recommande) && !isFull;
            const card = document.createElement('div');
            card.className = isRecommended
                ? 'card border-warning shadow-sm mb-2 slot-card recommended'
                : `card ${isFull ? 'border-light bg-light text-muted is-full' : 'border-light'} mb-1 slot-card`;
            card.dataset.slotId = slot.id_creneau;
            card.dataset.slotDatetime = slot.date_heure || `${selectedDate} ${slot.heure}:00`;
            card.dataset.offpeak = slot.est_heure_creuse ? '1' : '0';
            card.dataset.score = slot.score || 0;
            card.dataset.disabled = isFull ? '1' : '0';
            card.setAttribute('title', slot.raison || '');
            card.setAttribute('role', 'button');
            card.setAttribute('tabindex', isFull ? '-1' : '0');
            card.setAttribute('aria-disabled', isFull ? 'true' : 'false');

            const body = document.createElement('div');
            body.className = 'card-body d-flex justify-content-between align-items-center gap-2';

            const left = document.createElement('div');
            left.className = 'slot-card-main';
            if (isRecommended) {
                left.appendChild(createBadge('⭐ Recommandé', 'badge bg-warning text-dark me-2'));
            }

            const hour = document.createElement('strong');
            hour.textContent = slot.heure;
            left.appendChild(hour);

            const reason = document.createElement('small');
            reason.className = 'text-muted ms-2';
            reason.textContent = slot.raison || '';
            left.appendChild(reason);

            const right = document.createElement('div');
            right.className = 'slot-card-badges text-end';
            right.appendChild(createBadge(`${remaining} place(s)`, isFull ? 'badge bg-secondary' : 'badge bg-success'));
            right.appendChild(createBadge(payload.duree_label || '~1h', 'badge bg-light text-dark ms-1'));

            if (slot.est_heure_creuse) {
                right.appendChild(createBadge('Heure creuse', 'badge bg-info text-dark ms-1'));
            }

            body.appendChild(left);
            body.appendChild(right);
            card.appendChild(body);
            slotsContainer.appendChild(card);
        });
    }

    async function loadSlots(dateValue) {
        selectedSlot = null;
        hiddenSlot.value = '';
        setSlotsInfo(null);
        slotsContainer.innerHTML = '<div class="empty-inline">Chargement des créneaux recommandés...</div>';

        const typePanne = getCurrentTypePanne();
        const response = await fetch(`api/recommend-slot.php?type_panne=${encodeURIComponent(typePanne)}&date=${encodeURIComponent(dateValue)}`);
        const payload = await response.json();
        if (!payload.success && payload.success !== undefined) {
            slotsContainer.innerHTML = '<div class="empty-inline">Impossible de charger les recommandations pour cette date.</div>';
            return;
        }

        renderSlots(payload);
    }

    app.addEventListener('click', async (event) => {
        const dayBtn = event.target.closest('.day-cell[data-date]');
        if (dayBtn) {
            app.querySelectorAll('.day-cell').forEach((cell) => cell.classList.remove('selected'));
            dayBtn.classList.add('selected');

            selectedDate = dayBtn.dataset.date;
            hiddenDate.value = selectedDate;
            selectedDayLabel.textContent = new Date(`${selectedDate}T08:00:00`).toLocaleDateString('fr-FR');

            selectedSlot = null;
            hiddenSlot.value = '';
            setSlotsInfo(null);
            slotsContainer.innerHTML = '<div class="empty-inline">Complétez le formulaire pour afficher les créneaux recommandés.</div>';
            setStep(2);
            return;
        }

        const slotBtn = event.target.closest('.slot-card, .slot-item');
        if (slotBtn && !slotBtn.classList.contains('is-full') && slotBtn.dataset.disabled !== '1') {
            selectSlot(slotBtn);
            return;
        }
    });

    app.addEventListener('keydown', function (event) {
        const slotBtn = event.target.closest('.slot-card, .slot-item');
        if (!slotBtn || slotBtn.classList.contains('is-full') || slotBtn.dataset.disabled === '1') {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            selectSlot(slotBtn);
        }
    });

    btnPrev.addEventListener('click', function () {
        setStep(currentStep - 1);
    });

    btnNext.addEventListener('click', async function () {
        if (currentStep === 2) {
            if (!validateFormStep()) {
                return;
            }

            btnNext.disabled = true;
            try {
                await loadSlots(selectedDate);
                setStep(3);
            } catch (error) {
                slotsContainer.innerHTML = '<div class="empty-inline">Erreur réseau pendant le chargement des créneaux.</div>';
                setStep(3);
            } finally {
                btnNext.disabled = false;
            }
            return;
        }
        if (currentStep === 3) {
            if (!hiddenSlot.value) {
                alert('Veuillez sélectionner un créneau.');
                return;
            }
            updateRecap();
        }
        setStep(currentStep + 1);
    });

    confirmBtn.addEventListener('click', function () {
        if (!hiddenSlot.value) {
            alert('Créneau non sélectionné.');
            setStep(3);
            return;
        }
        if (!validateFormStep()) {
            setStep(2);
            return;
        }
        refreshPanneDataField();
        form.submit();
    });

    if (photoDropzone && photoInput) {
        photoDropzone.addEventListener('click', function () {
            photoInput.click();
        });

        photoDropzone.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                photoInput.click();
            }
        });

        photoDropzone.addEventListener('dragover', function (event) {
            event.preventDefault();
            photoDropzone.classList.add('is-dragover');
        });

        photoDropzone.addEventListener('dragleave', function () {
            photoDropzone.classList.remove('is-dragover');
        });

        photoDropzone.addEventListener('drop', function (event) {
            event.preventDefault();
            photoDropzone.classList.remove('is-dragover');
            handlePhotoFiles(event.dataTransfer ? event.dataTransfer.files : [], 'drop');
        });

        photoInput.addEventListener('change', function () {
            handlePhotoFiles(photoInput.files, 'input');
        });
    }

    if (symptomesField) {
        symptomesField.addEventListener('input', function () {
            refreshPanneDataField();
            schedulePanneAnalysis();
        });
    }
    if (circonstancesField) {
        circonstancesField.addEventListener('change', refreshPanneDataField);
    }
    if (interventionField) {
        interventionField.addEventListener('change', function () {
            if (!internalTypeSelection) {
                manualTypeSelected = interventionField.value.trim() !== '';
            }

            getCurrentTypePanne();
            refreshPanneDataField();

            if (currentStep === 3 && selectedDate) {
                loadSlots(selectedDate).catch(() => {
                    slotsContainer.innerHTML = '<div class="empty-inline">Erreur réseau pendant le chargement des créneaux.</div>';
                });
            }
        });
    }
    if (symptomesField && symptomesField.value.trim() !== '') {
        schedulePanneAnalysis();
    }
    Array.from(temoinsFields).forEach((item) => {
        item.addEventListener('change', refreshPanneDataField);
    });

    window.getPanneData = getPanneData;
    updatePhotoState();
    getCurrentTypePanne();
    refreshPanneDataField();

    setStep(1);
})();
