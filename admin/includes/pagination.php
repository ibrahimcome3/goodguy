<?php
// This file assumes that the following variables are set in the parent file:
// - $totalPages: The total number of pages.
// - $page: The current page number.
// - $statusFilter: (Optional) The current status filter.
// - $searchTerm: (Optional) The current search term.

if (isset($totalPages) && $totalPages > 1):

    // Build query parameters from context variables for robustness,
    // instead of relying solely on $_GET. This supports hardcoded filters.
    $queryParams = [];
    if (!empty($searchTerm)) {
        $queryParams['search'] = $searchTerm;
    }
    // On order.php, this comes from $_GET. On other pages, it's hardcoded.
    if (!empty($statusFilter)) {
        $queryParams['status'] = $statusFilter;
    }

    // Function to generate a pagination link.
    // It appends the page number and the other existing query parameters.
    function generate_pagination_link(int $pageNum, array $queryParams): string
    {
        $queryParams['page'] = $pageNum;
        return '?' . http_build_query($queryParams);
    }
    ?>
    <nav aria-label="Page navigation">
        <ul class="pagination mb-0">
            <!-- Previous Button -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= generate_pagination_link($page - 1, $queryParams) ?>" aria-label="Previous">
                    <span class="fas fa-chevron-left"></span>
                </a>
            </li>

            <!-- Page Numbers -->
            <?php
            $maxLinks = 5; // Maximum number of page links to show.
            $startPage = max(1, $page - floor($maxLinks / 2));
            $endPage = min($totalPages, $startPage + $maxLinks - 1);

            if ($endPage - $startPage + 1 < $maxLinks) {
                $startPage = max(1, $endPage - $maxLinks + 1);
            }

            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= generate_pagination_link($i, $queryParams) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <!-- Next Button -->
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= generate_pagination_link($page + 1, $queryParams) ?>" aria-label="Next">
                    <span class="fas fa-chevron-right"></span>
                </a>
            </li>
        </ul>
    </nav>
    <?php
endif;
?>