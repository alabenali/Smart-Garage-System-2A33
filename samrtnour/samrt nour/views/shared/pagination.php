<?php if (!isset($pagination) || (int) ($pagination['total_pages'] ?? 1) <= 1) { return; } ?>
<?php
$currentPage = (int) $pagination['current_page'];
$totalPages = (int) $pagination['total_pages'];
$query = isset($paginationQuery) && is_array($paginationQuery) ? $paginationQuery : [];
$baseAction = isset($paginationAction) ? $paginationAction : '';
$startPage = max(1, $currentPage - 2);
$endPage = min($totalPages, $currentPage + 2);
?>
<div class="pagination-wrap">
    <div class="pagination-summary">
        Affichage <?php echo (int) $pagination['from']; ?> - <?php echo (int) $pagination['to']; ?> sur <?php echo (int) $pagination['total_items']; ?>
    </div>
    <nav class="sg-pagination" aria-label="Pagination">
        <?php
        $prevParams = array_merge($query, ['action' => $baseAction, 'page' => max(1, $currentPage - 1)]);
        $nextParams = array_merge($query, ['action' => $baseAction, 'page' => min($totalPages, $currentPage + 1)]);
        ?>
        <a class="page-link-nav <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>" href="index.php?<?php echo htmlspecialchars(http_build_query($prevParams)); ?>">
            <i class="bi bi-chevron-left"></i> Precedent
        </a>

        <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
            <?php $pageParams = array_merge($query, ['action' => $baseAction, 'page' => $pageNumber]); ?>
            <a class="page-link-number <?php echo $pageNumber === $currentPage ? 'active' : ''; ?>" href="index.php?<?php echo htmlspecialchars(http_build_query($pageParams)); ?>">
                <?php echo $pageNumber; ?>
            </a>
        <?php endfor; ?>

        <a class="page-link-nav <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>" href="index.php?<?php echo htmlspecialchars(http_build_query($nextParams)); ?>">
            Suivant <i class="bi bi-chevron-right"></i>
        </a>
    </nav>
</div>
