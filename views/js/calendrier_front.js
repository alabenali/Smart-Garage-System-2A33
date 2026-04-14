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

    function validateStepThree() {
        clearFormErrors();
        let valid = true;

        const nom = form.querySelector('[name="nom_client"]');
        const prenom = form.querySelector('[name="prenom_client"]');
        const email = form.querySelector('[name="email_client"]');
        const tel = form.querySelector('[name="telephone_client"]');
        const immat = form.querySelector('[name="immatriculation"]');
        const intervention = form.querySelector('[name="type_intervention"]');

        if (!nom.value.trim()) {
            showError(nom, 'Nom obligatoire.');
            valid = false;
        }
        if (!prenom.value.trim()) {
            showError(prenom, 'Prénom obligatoire.');
            valid = false;
        }
        if (!email.value.trim()) {
            showError(email, 'Email obligatoire.');
            valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            showError(email, 'Email invalide. Format : exemple@domaine.com');
            valid = false;
        }
        if (!/^\d{8}$/.test(tel.value.trim())) {
            showError(tel, 'Téléphone tunisien à 8 chiffres requis.');
            valid = false;
        }
        if (!immat.value.trim()) {
            showError(immat, 'Immatriculation obligatoire.');
            valid = false;
        }
        if (!intervention.value.trim()) {
            showError(intervention, 'Type intervention obligatoire.');
            valid = false;
        }

        return valid;
    }

    function updateRecap() {
        const slotDateTime = selectedSlot ? selectedSlot.dataset.slotDatetime : '';
        const intervention = form.querySelector('[name="type_intervention"]').value || '-';
        const immat = form.querySelector('[name="immatriculation"]').value || '-';
        const marque = form.querySelector('[name="marque"]').value || '';
        const modele = form.querySelector('[name="modele"]').value || '';

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
        app.querySelector('[data-recap="vehicle"]').textContent = `${immat} ${marque} ${modele}`.trim();
        app.querySelector('[data-recap="intervention"]').textContent = intervention;
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
        form.submit();
    });

    setStep(1);
})();
