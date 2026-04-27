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
    const slotsContainer = document.getElementById('slotsContainer');
    const selectedDayLabel = document.getElementById('selectedDayLabel');
    const hiddenSlot = document.getElementById('id_creneau');
    const hiddenDate = document.getElementById('selected_date');
    const form = document.getElementById('rdvForm');
    const confirmBtn = document.getElementById('confirmRdvBtn');
    const circonstancesField = form ? form.querySelector('[name="circonstances_panne"]') : null;
    const symptomesField = form ? form.querySelector('[name="description_panne"]') : null;
    const temoinsFields = form ? form.querySelectorAll('input[name="temoins_panne[]"]') : [];
    const panneDataField = document.getElementById('panne_data_json');
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
            typePanne: form.querySelector('[name="type_intervention"]').value || '',
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

    function validateStepThree() {
        clearFormErrors();
        let valid = true;

        const intervention = form.querySelector('[name="type_intervention"]');
        const symptomes = form.querySelector('[name="description_panne"]');

        if (!intervention.value.trim()) {
            showError(intervention, 'Type de panne obligatoire.');
            valid = false;
        }
        if (!symptomes.value.trim()) {
            showError(symptomes, 'Les symptômes observés sont obligatoires.');
            valid = false;
        }

        refreshPanneDataField();

        return valid;
    }

    function updateRecap() {
        const slotDateTime = selectedSlot ? selectedSlot.dataset.slotDatetime : '';
        const intervention = form.querySelector('[name="type_intervention"]').value || '-';
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
        app.querySelectorAll('.slot-item').forEach((item) => item.classList.remove('selected'));
        button.classList.add('selected');
        selectedSlot = button;
        hiddenSlot.value = button.dataset.slotId;
    }

    function renderSlots(slots) {
        if (!Array.isArray(slots) || slots.length === 0) {
            slotsContainer.innerHTML = '<div class="empty-inline">Aucun créneau disponible pour cette date.</div>';
            selectedSlot = null;
            hiddenSlot.value = '';
            return;
        }

        slotsContainer.innerHTML = '';
        slots.forEach((slot) => {
            const remaining = Math.max(0, Number(slot.places_restantes));
            const isFull = remaining <= 0 || Number(slot.capacite_max) <= 0;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `slot-item ${isFull ? 'is-full' : ''}`;
            btn.dataset.slotId = slot.id_creneau;
            btn.dataset.slotDatetime = slot.date_heure;
            btn.dataset.offpeak = slot.est_heure_creuse;
            btn.disabled = isFull;
            btn.innerHTML = `<strong>${slot.date_heure.slice(11, 16)}</strong><span>${remaining} place(s) restante(s)</span>${Number(slot.est_heure_creuse) === 1 ? '<em>🌙 Heure creuse (remise possible)</em>' : ''}`;
            slotsContainer.appendChild(btn);
        });
    }

    async function loadSlots(dateValue) {
        const response = await fetch(`index.php?action=apiDaySlots&date=${encodeURIComponent(dateValue)}`);
        const payload = await response.json();
        if (!payload.success) {
            return;
        }

        renderSlots(payload.data || []);
    }

    app.addEventListener('click', async (event) => {
        const dayBtn = event.target.closest('.day-cell[data-date]');
        if (dayBtn) {
            app.querySelectorAll('.day-cell').forEach((cell) => cell.classList.remove('selected'));
            dayBtn.classList.add('selected');

            selectedDate = dayBtn.dataset.date;
            hiddenDate.value = selectedDate;
            selectedDayLabel.textContent = new Date(`${selectedDate}T08:00:00`).toLocaleDateString('fr-FR');

            await loadSlots(selectedDate);
            setStep(2);
            return;
        }

        const slotBtn = event.target.closest('.slot-item');
        if (slotBtn && !slotBtn.disabled) {
            selectSlot(slotBtn);
            return;
        }
    });

    btnPrev.addEventListener('click', function () {
        setStep(currentStep - 1);
    });

    btnNext.addEventListener('click', function () {
        if (currentStep === 2 && !hiddenSlot.value) {
            alert('Veuillez sélectionner un créneau.');
            return;
        }
        if (currentStep === 3) {
            if (!validateStepThree()) {
                return;
            }
            updateRecap();
        }
        setStep(currentStep + 1);
    });

    confirmBtn.addEventListener('click', function () {
        if (!hiddenSlot.value) {
            alert('Créneau non sélectionné.');
            setStep(2);
            return;
        }
        if (!validateStepThree()) {
            setStep(3);
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
        symptomesField.addEventListener('input', refreshPanneDataField);
    }
    if (circonstancesField) {
        circonstancesField.addEventListener('change', refreshPanneDataField);
    }
    if (form) {
        const panneTypeField = form.querySelector('[name="type_intervention"]');
        if (panneTypeField) {
            panneTypeField.addEventListener('change', refreshPanneDataField);
        }
    }
    Array.from(temoinsFields).forEach((item) => {
        item.addEventListener('change', refreshPanneDataField);
    });

    window.getPanneData = getPanneData;
    updatePhotoState();
    refreshPanneDataField();

    setStep(1);
})();
