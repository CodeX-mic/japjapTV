<?php
/**
 * Pagination controls.
 * Expects $page (current) and $totalPages. Uses the current query string.
 */

declare(strict_types=1);

$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));

if ($totalPages < 2) {
    return;
}

$pages = [1, $totalPages];
for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
    $pages[] = $i;
}
$pages = array_values(array_unique($pages));
sort($pages);
?><nav class="pagination" aria-label="Pagination">
    <?php if ($page > 1): ?>
        <a class="page-link" href="<?= e(query_url(['page' => $page - 1])) ?>" rel="prev" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
    <?php else: ?>
        <span class="page-link is-disabled" aria-hidden="true"><i class="fa-solid fa-chevron-left"></i></span>
    <?php endif; ?>

    <?php $previous = 0; ?>
    <?php foreach ($pages as $number): ?>
        <?php if ($previous !== 0 && $number > $previous + 1): ?>
            <span class="page-ellipsis" aria-hidden="true">…</span>
        <?php endif; ?>
        <?php if ($number === $page): ?>
            <span class="page-link is-current" aria-current="page"><?= $number ?></span>
        <?php else: ?>
            <a class="page-link" href="<?= e(query_url(['page' => $number])) ?>"><?= $number ?></a>
        <?php endif; ?>
        <?php $previous = $number; ?>
    <?php endforeach; ?>

    <?php if ($page < $totalPages): ?>
        <a class="page-link" href="<?= e(query_url(['page' => $page + 1])) ?>" rel="next" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    <?php else: ?>
        <span class="page-link is-disabled" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
    <?php endif; ?>
</nav>
