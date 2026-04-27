// ============================================
// Diagnostic Page Script
// Client-side validation + table filters
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');

    function filterTable() {
        const searchValue = (searchInput ? searchInput.value : '').toLowerCase();
        const rows = document.querySelectorAll('#diagnosticTable tr');

        rows.forEach(function (row) {
            const vehicleCell = row.querySelector('td:nth-child(2)');

            if (!vehicleCell) {
                return;
            }

            const vehicleText = vehicleCell.innerText.toLowerCase();
            const matchesSearch = vehicleText.includes(searchValue);

            row.style.display = matchesSearch ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', filterTable);
    }
});
