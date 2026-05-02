(function () {
    const symptomesSuggestions = {
        Vidange: [
            { label: '🛢️ Huile moteur', value: 'vidange huile moteur' },
            { label: '🔁 Filtre à huile', value: 'changement filtre à huile' },
            { label: '🌬️ Filtre air', value: 'changement filtre à air' },
            { label: '⛽ Filtre carburant', value: 'changement filtre carburant' },
            { label: '🧴 Liquides', value: 'contrôle des niveaux' },
        ],
        Révision: [
            { label: '✅ Entretien périodique', value: 'révision périodique' },
            { label: '🔍 Contrôle général', value: 'contrôle général du véhicule' },
            { label: '🛢️ Vidange', value: 'vidange et filtres' },
            { label: '🧯 Sécurité', value: 'contrôle freins et sécurité' },
            { label: '🚗 Avant voyage', value: 'contrôle avant voyage' },
        ],
        'Changement de pneu': [
            { label: '🛞 Pneu crevé', value: 'pneu crevé' },
            { label: '🔁 Remplacement', value: 'changement de pneu' },
            { label: '⚖️ Équilibrage', value: 'équilibrage des roues' },
            { label: '📐 Parallélisme', value: 'parallélisme à contrôler' },
        ],
        Pneumatiques: [
            { label: '🛞 Usure', value: 'pneus usés' },
            { label: '📉 Pression', value: 'pression pneus faible' },
            { label: '📳 Vibrations', value: 'vibrations des roues' },
            { label: '↔️ Parallélisme', value: 'voiture tire d\'un côté' },
            { label: '⚖️ Équilibrage', value: 'équilibrage des roues' },
        ],
        Batterie: [
            { label: '🔋 Batterie faible', value: 'batterie faible' },
            { label: '🚫 Ne démarre pas', value: 'véhicule ne démarre pas' },
            { label: '🔌 Cosses', value: 'cosses batterie à contrôler' },
            { label: '⚡ Alternateur', value: 'alternateur à vérifier' },
        ],
        Moteur: [
            { label: '🔴 Voyant moteur', value: 'voyant moteur allumé' },
            { label: '💨 Fumée', value: 'fumée visible' },
            { label: '🛢️ Fuite d\'huile', value: 'fuite d\'huile' },
            { label: '⚡ Perte de puissance', value: 'perte de puissance' },
            { label: '🔊 Bruit moteur', value: 'bruit anormal moteur' },
            { label: '❄️ Surchauffe', value: 'moteur en surchauffe' },
            { label: '🚫 Démarrage difficile', value: 'démarrage difficile' },
        ],
        Freinage: [
            { label: '🔊 Sifflement', value: 'sifflement au freinage' },
            { label: '📳 Vibrations', value: 'vibrations au freinage' },
            { label: '🦶 Pédale molle', value: 'pédale de frein molle' },
            { label: '⚠️ Voyant ABS', value: 'voyant ABS allumé' },
            { label: '↔️ Tire d\'un côté', value: 'voiture tire d\'un côté' },
            { label: '⏱️ Distance longue', value: 'distance de freinage allongée' },
        ],
        Électronique: [
            { label: '🔋 Batterie', value: 'problème de batterie' },
            { label: '💡 Phares', value: 'problème de phares' },
            { label: '🎵 Tableau de bord', value: 'voyants tableau de bord' },
            { label: '🔑 Démarreur', value: 'démarreur défaillant' },
            { label: '📡 Capteurs', value: 'erreur de capteur' },
        ],
        Climatisation: [
            { label: '🥵 Plus de froid', value: 'climatisation ne refroidit plus' },
            { label: '💧 Fuite eau', value: 'fuite d\'eau habitacle' },
            { label: '🌬️ Mauvaise odeur', value: 'mauvaise odeur ventilation' },
            { label: '🔇 Ventilateur', value: 'ventilateur ne fonctionne pas' },
        ],
        Transmission: [
            { label: '⚙️ Passage difficile', value: 'passage de vitesse difficile' },
            { label: '📳 Vibrations', value: 'vibrations en roulant' },
            { label: '🔊 Bruit boîte', value: 'bruit boîte de vitesse' },
            { label: '⛽ Embrayage', value: 'embrayage qui glisse' },
        ],
        Carrosserie: [
            { label: '💥 Choc', value: 'suite à un choc' },
            { label: '🚪 Porte', value: 'problème de porte' },
            { label: '🪟 Vitre', value: 'vitre cassée ou bloquée' },
            { label: '🔒 Serrure', value: 'serrure défaillante' },
        ],
        'Diagnostic général': [
            { label: '🔊 Bruit', value: 'bruit anormal' },
            { label: '📳 Vibration', value: 'vibration inhabituelle' },
            { label: '💧 Fuite', value: 'fuite à vérifier' },
            { label: '🌬️ Odeur', value: 'odeur inhabituelle' },
            { label: '⚠️ Voyant', value: 'voyant allumé' },
        ],
    };

    const aliases = {
        'Électrique-Batterie': 'Électronique',
        'Boîte de vitesse': 'Transmission',
        'Boite de vitesse': 'Transmission',
        'Changement pneu': 'Changement de pneu',
    };

    const select = document.querySelector('select[name="type_intervention"]');
    const textarea = document.getElementById('description_panne');
    const container = document.getElementById('symptomes-chips-container');

    if (!select || !textarea || !container) {
        return;
    }

    function getSuggestionType(type) {
        return aliases[type] || type;
    }

    function getTextareaValues() {
        return textarea.value
            .split(',')
            .map((item) => item.trim())
            .filter(Boolean);
    }

    function hasValue(value) {
        return getTextareaValues().some((item) => item.toLowerCase() === value.toLowerCase());
    }

    function syncTextarea(values) {
        textarea.value = values.join(', ');
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function addValue(value) {
        const values = getTextareaValues();

        if (!hasValue(value)) {
            values.push(value);
        }

        syncTextarea(values);
    }

    function removeValue(value) {
        const values = getTextareaValues().filter((item) => item.toLowerCase() !== value.toLowerCase());
        syncTextarea(values);
    }

    function setChipState(chip, suggestion) {
        const active = hasValue(suggestion.value);
        chip.className = active
            ? 'badge bg-warning text-dark border border-warning cursor-pointer me-1 mb-1 p-2 chip-active'
            : 'badge bg-light text-dark border cursor-pointer me-1 mb-1 p-2';
        chip.style.cursor = 'pointer';
        chip.textContent = active ? `✓ ${suggestion.label}` : suggestion.label;
        chip.setAttribute('aria-pressed', active ? 'true' : 'false');
    }

    function refreshChipStates() {
        container.querySelectorAll('[data-symptome-value]').forEach((chip) => {
            const suggestion = {
                label: chip.dataset.symptomeLabel || '',
                value: chip.dataset.symptomeValue || '',
            };
            setChipState(chip, suggestion);
        });
    }

    function clearChips() {
        container.innerHTML = '';
    }

    function renderChips() {
        const selectedType = getSuggestionType(select.value);
        const suggestions = symptomesSuggestions[selectedType] || [];

        clearChips();

        if (suggestions.length === 0) {
            return;
        }

        const title = document.createElement('div');
        title.className = 'small text-muted mb-1';
        title.textContent = '💡 Symptômes fréquents — cliquez pour ajouter :';
        container.appendChild(title);

        const chipsWrap = document.createElement('div');
        suggestions.forEach((suggestion) => {
            const chip = document.createElement('span');
            chip.setAttribute('role', 'button');
            chip.setAttribute('tabindex', '0');
            chip.dataset.symptomeLabel = suggestion.label;
            chip.dataset.symptomeValue = suggestion.value;
            setChipState(chip, suggestion);

            chip.addEventListener('click', function () {
                if (hasValue(suggestion.value)) {
                    removeValue(suggestion.value);
                } else {
                    addValue(suggestion.value);
                }
                refreshChipStates();
            });

            chip.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    chip.click();
                }
            });

            chipsWrap.appendChild(chip);
        });

        container.appendChild(chipsWrap);
    }

    select.addEventListener('change', function (event) {
        renderChips();

        if (event.isTrusted) {
            syncTextarea([]);
        }

        refreshChipStates();
    });

    textarea.addEventListener('input', refreshChipStates);

    renderChips();
})();
