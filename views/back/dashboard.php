<?php
$pageTitle = 'Dashboard Admin';
$action = 'dashboard';

$interventions = $interventions ?? [];
$statistiques = $statistiques ?? [];

$statusSeries = [
    'Planifiee' => 0,
    'En cours' => 0,
    'Terminee' => 0,
    'Autre' => 0,
];

$typeSeries = [];

foreach ($interventions as $inter) {
    $rawStatus = trim((string)($inter['statut'] ?? 'planifiée'));
    $normalizedStatus = strtolower($rawStatus);
    $normalizedStatus = strtr($normalizedStatus, [
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'à' => 'a',
        'ù' => 'u',
        'ô' => 'o',
        'î' => 'i',
        'ï' => 'i',
        'ç' => 'c',
    ]);

    if (in_array($normalizedStatus, ['planifiee', 'planifie'], true)) {
        $statusSeries['Planifiee']++;
    } elseif (in_array($normalizedStatus, ['en_cours', 'en cours'], true)) {
        $statusSeries['En cours']++;
    } elseif (in_array($normalizedStatus, ['terminee', 'termine'], true)) {
        $statusSeries['Terminee']++;
    } else {
        $statusSeries['Autre']++;
    }

    $typeName = trim((string)($inter['type_nom'] ?? 'Non defini'));
    if ($typeName === '') {
        $typeName = 'Non defini';
    }

    if (!isset($typeSeries[$typeName])) {
        $typeSeries[$typeName] = 0;
    }
    $typeSeries[$typeName]++;
}

arsort($typeSeries);
$typeSeries = array_slice($typeSeries, 0, 6, true);
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <h1 class="page-title text-white">Dashboard Admin</h1>
    <p class="page-subtitle text-muted">Vue globale des interventions.</p>

    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card bg-dark border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="text-white-50 small fw-semibold text-uppercase mb-2">Total interventions</div>
                <div class="h4 mb-0 text-white"><?php echo (int)($statistiques['total'] ?? 0); ?></div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-dark border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="text-white-50 small fw-semibold text-uppercase mb-2">Planifiees</div>
                <div class="h4 mb-0 text-primary"><?php echo (int)($statistiques['planifiees'] ?? 0); ?></div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-dark border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="text-white-50 small fw-semibold text-uppercase mb-2">En cours</div>
                <div class="h4 mb-0 text-warning"><?php echo (int)($statistiques['en_cours'] ?? 0); ?></div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-dark border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="text-white-50 small fw-semibold text-uppercase mb-2">Terminees</div>
                <div class="h4 mb-0 text-success"><?php echo (int)($statistiques['terminees'] ?? 0); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="text-white-50 small fw-semibold text-uppercase mb-2">Benefice total</div>
                <div class="h5 mb-0 text-info"><?php echo number_format((float)($statistiques['benefice_total'] ?? 0), 2, '.', ' '); ?> DT</div>
            </div>
        </div>
    </div>

    <div class="card bg-dark border-0 shadow-sm rounded-4 overflow-hidden mb-3 mx-auto" style="max-width: 1450px; width: 100%;">
        <div class="card-header border-0 bg-secondary bg-opacity-10 text-white py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <span>Dernieres interventions</span>
                <div class="w-100 w-md-auto" style="max-width: 380px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-dark text-white border-secondary">
                            <i class="bi bi-search"></i>
                        </span>
                        <input
                            type="search"
                            id="dashboardTableSearch"
                            class="form-control form-control-sm bg-dark text-white border-secondary"
                            placeholder="Rechercher dans le tableau..."
                            autocomplete="off"
                        >
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0" id="dashboardInterventionsTable">
                <thead>
                    <tr>
                        <th class="ps-4" data-sort-index="0" data-sort-type="number">
                            <button type="button" class="btn btn-link btn-sm text-white text-decoration-none p-0 sort-trigger" data-sort-index="0">
                                #ID <span class="sort-indicator text-white-50">↕</span>
                            </button>
                        </th>
                        <th data-sort-index="1" data-sort-type="text">
                            <button type="button" class="btn btn-link btn-sm text-white text-decoration-none p-0 sort-trigger" data-sort-index="1">
                                Immatriculation <span class="sort-indicator text-white-50">↕</span>
                            </button>
                        </th>
                        <th data-sort-index="2" data-sort-type="text">
                            <button type="button" class="btn btn-link btn-sm text-white text-decoration-none p-0 sort-trigger" data-sort-index="2">
                                Type <span class="sort-indicator text-white-50">↕</span>
                            </button>
                        </th>
                        <th data-sort-index="3" data-sort-type="text">
                            <button type="button" class="btn btn-link btn-sm text-white text-decoration-none p-0 sort-trigger" data-sort-index="3">
                                Description travail <span class="sort-indicator text-white-50">↕</span>
                            </button>
                        </th>
                        <th data-sort-index="4" data-sort-type="text">
                            <button type="button" class="btn btn-link btn-sm text-white text-decoration-none p-0 sort-trigger" data-sort-index="4">
                                Statut <span class="sort-indicator text-white-50">↕</span>
                            </button>
                        </th>
                        <th data-sort-index="5" data-sort-type="number">
                            <button type="button" class="btn btn-link btn-sm text-white text-decoration-none p-0 sort-trigger" data-sort-index="5">
                                Cout initial <span class="sort-indicator text-white-50">↕</span>
                            </button>
                        </th>
                        <th data-sort-index="6" data-sort-type="number">
                            <button type="button" class="btn btn-link btn-sm text-white text-decoration-none p-0 sort-trigger" data-sort-index="6">
                                Cout final <span class="sort-indicator text-white-50">↕</span>
                            </button>
                        </th>
                        <th data-sort-index="7" data-sort-type="date">
                            <button type="button" class="btn btn-link btn-sm text-white text-decoration-none p-0 sort-trigger" data-sort-index="7">
                                Date debut <span class="sort-indicator text-white-50">↕</span>
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($interventions)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Aucune intervention.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($interventions, 0, 10) as $inter): ?>
                            <tr>
                                <td class="ps-4">#<?php echo (int)($inter['id_intervention'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($inter['immatriculation'] ?? ('ID ' . (int)($inter['id_vehicule'] ?? 0))); ?></td>
                                <td><?php echo htmlspecialchars($inter['type_nom'] ?? '-'); ?></td>
                                <td><div class="text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($inter['description_travail'] ?? '-'); ?></div></td>
                                <td>
                                    <?php
                                    $rawStatus = trim((string)($inter['statut'] ?? 'planifiée'));
                                    $normalizedStatus = strtolower($rawStatus);
                                    $normalizedStatus = strtr($normalizedStatus, [
                                        'é' => 'e',
                                        'è' => 'e',
                                        'ê' => 'e',
                                        'à' => 'a',
                                        'ù' => 'u',
                                        'ô' => 'o',
                                        'î' => 'i',
                                        'ï' => 'i',
                                        'ç' => 'c',
                                    ]);

                                    $statusLabel = ucfirst(str_replace('_', ' ', $rawStatus));
                                    $statusClass = 'bg-secondary';

                                    if (in_array($normalizedStatus, ['planifiee', 'planifie'], true)) {
                                        $statusLabel = 'Planifiee';
                                        $statusClass = 'bg-primary';
                                    } elseif (in_array($normalizedStatus, ['termine', 'terminee'], true)) {
                                        $statusLabel = 'Terminee';
                                        $statusClass = 'bg-success';
                                    } elseif (in_array($normalizedStatus, ['en_cours', 'en cours'], true)) {
                                        $statusLabel = 'En cours';
                                        $statusClass = 'bg-warning text-dark';
                                    }
                                    ?>
                                    <span class="badge rounded-pill <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                </td>
                                <td><?php echo number_format((float)($inter['cout_initial'] ?? 0), 2, '.', ' '); ?> DT</td>
                                <td><?php echo (($inter['cout_final'] ?? null) !== null) ? number_format((float)$inter['cout_final'], 2, '.', ' ') . ' DT' : '-'; ?></td>
                                <td><?php echo !empty($inter['date_debut']) ? date('d/m/Y', strtotime((string)$inter['date_debut'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card bg-dark border-0 shadow-sm rounded-4 h-100">
                <div class="card-header border-0 bg-secondary bg-opacity-10 text-white py-3">Repartition des interventions par statut</div>
                <div class="card-body">
                    <canvas id="statusChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card bg-dark border-0 shadow-sm rounded-4 h-100">
                <div class="card-header border-0 bg-secondary bg-opacity-10 text-white py-3">Top types d'intervention</div>
                <div class="card-body">
                    <canvas id="typeChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const statusLabels = <?php echo json_encode(array_keys($statusSeries)); ?>;
const statusData = <?php echo json_encode(array_values($statusSeries)); ?>;
const typeLabels = <?php echo json_encode(array_keys($typeSeries)); ?>;
const typeData = <?php echo json_encode(array_values($typeSeries)); ?>;

const dashboardTable = document.getElementById('dashboardInterventionsTable');
const dashboardSearchInput = document.getElementById('dashboardTableSearch');

if (dashboardTable) {
    const tableBody = dashboardTable.querySelector('tbody');
    const sortableRows = Array.from(tableBody.querySelectorAll('tr')).filter((row) => {
        const cells = row.querySelectorAll('td');
        return cells.length > 1;
    });

    const state = {
        sortIndex: null,
        sortDirection: 'asc',
        query: '',
    };

    function parseDateValue(value) {
        const trimmed = value.trim();
        const match = trimmed.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (!match) {
            return NaN;
        }
        const day = Number(match[1]);
        const month = Number(match[2]);
        const year = Number(match[3]);
        return new Date(year, month - 1, day).getTime();
    }

    function parseNumberValue(value) {
        const cleaned = value
            .replace(/\s+/g, '')
            .replace('DT', '')
            .replace('#', '')
            .replace(',', '.');
        const parsed = Number(cleaned);
        return Number.isNaN(parsed) ? NaN : parsed;
    }

    function getCellText(row, index) {
        const cell = row.cells[index];
        return cell ? cell.textContent.trim() : '';
    }

    function getSortableValue(row, index, type) {
        const text = getCellText(row, index);
        if (type === 'number') {
            const num = parseNumberValue(text);
            return Number.isNaN(num) ? Number.NEGATIVE_INFINITY : num;
        }
        if (type === 'date') {
            const date = parseDateValue(text);
            return Number.isNaN(date) ? Number.NEGATIVE_INFINITY : date;
        }
        return text.toLocaleLowerCase();
    }

    function updateSortIndicators() {
        dashboardTable.querySelectorAll('.sort-trigger').forEach((btn) => {
            const index = Number(btn.dataset.sortIndex);
            const indicator = btn.querySelector('.sort-indicator');
            if (!indicator) {
                return;
            }

            if (index !== state.sortIndex) {
                indicator.textContent = '↕';
                indicator.classList.add('text-white-50');
                return;
            }

            indicator.textContent = state.sortDirection === 'asc' ? '▲' : '▼';
            indicator.classList.remove('text-white-50');
        });
    }

    function applyTableState() {
        let rows = [...sortableRows];

        if (state.sortIndex !== null) {
            const th = dashboardTable.querySelector(`th[data-sort-index="${state.sortIndex}"]`);
            const sortType = th ? th.dataset.sortType : 'text';
            rows.sort((a, b) => {
                const aVal = getSortableValue(a, state.sortIndex, sortType);
                const bVal = getSortableValue(b, state.sortIndex, sortType);

                if (aVal < bVal) {
                    return state.sortDirection === 'asc' ? -1 : 1;
                }
                if (aVal > bVal) {
                    return state.sortDirection === 'asc' ? 1 : -1;
                }
                return 0;
            });
        }

        rows.forEach((row) => tableBody.appendChild(row));

        const query = state.query.trim().toLocaleLowerCase();
        rows.forEach((row) => {
            if (!query) {
                row.style.display = '';
                return;
            }

            const rowText = Array.from(row.cells)
                .map((cell) => cell.textContent.toLocaleLowerCase())
                .join(' ');
            row.style.display = rowText.includes(query) ? '' : 'none';
        });

        updateSortIndicators();
    }

    dashboardTable.querySelectorAll('.sort-trigger').forEach((btn) => {
        btn.addEventListener('click', () => {
            const index = Number(btn.dataset.sortIndex);
            if (state.sortIndex === index) {
                state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                state.sortIndex = index;
                state.sortDirection = 'asc';
            }
            applyTableState();
        });
    });

    if (dashboardSearchInput) {
        dashboardSearchInput.addEventListener('input', (event) => {
            state.query = event.target.value || '';
            applyTableState();
        });
    }
}

const commonLegend = {
    labels: {
        color: '#d0d4da',
        boxWidth: 14,
        padding: 14,
    },
};

new Chart(document.getElementById('statusChart'), {
    type: 'bar',
    data: {
        labels: statusLabels,
        datasets: [{
            label: 'Diagnostics',
            data: statusData,
            borderRadius: 8,
            backgroundColor: ['#fbbf24', '#38bdf8', '#3b82f6', '#ef4444', '#22c55e', '#6b7280'],
        }],
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
        },
        scales: {
            x: {
                ticks: { color: '#cfd8e3' },
                grid: { display: false },
            },
            y: {
                beginAtZero: true,
                ticks: { color: '#cfd8e3', precision: 0 },
                grid: { color: 'rgba(255,255,255,0.08)' },
            },
        },
    },
});

new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: typeLabels,
        datasets: [{
            data: typeData,
            backgroundColor: ['#38bdf8', '#22c55e', '#f59e0b', '#ef4444', '#a78bfa', '#6b7280'],
            borderWidth: 0,
        }],
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: commonLegend,
        },
    },
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
