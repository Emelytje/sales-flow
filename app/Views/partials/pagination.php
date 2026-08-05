<?php
/** @var array $result */
/** @var array $baseQuery */
$pages = (int) ($result['pages'] ?? 1);
$current = (int) ($result['page'] ?? 1);
if ($pages <= 1) {
    return;
}
$baseQuery = $baseQuery ?? [];
$link = static fn (int $p): string => '?' . http_build_query(array_merge($baseQuery, ['page' => $p]));
$start = max(1, $current - 2);
$end = min($pages, $current + 2);
?>
<nav class="pagination">
    <?php if ($current > 1): ?>
        <a href="<?= e($link($current - 1)) ?>"><?= icon('chevron-left', 16) ?></a>
    <?php else: ?><span class="disabled"><?= icon('chevron-left', 16) ?></span><?php endif; ?>

    <?php if ($start > 1): ?><a href="<?= e($link(1)) ?>">1</a><?php if ($start > 2): ?><span class="disabled">…</span><?php endif; endif; ?>

    <?php for ($p = $start; $p <= $end; $p++): ?>
        <?php if ($p === $current): ?><span class="current"><?= $p ?></span>
        <?php else: ?><a href="<?= e($link($p)) ?>"><?= $p ?></a><?php endif; ?>
    <?php endfor; ?>

    <?php if ($end < $pages): ?><?php if ($end < $pages - 1): ?><span class="disabled">…</span><?php endif; ?><a href="<?= e($link($pages)) ?>"><?= $pages ?></a><?php endif; ?>

    <?php if ($current < $pages): ?>
        <a href="<?= e($link($current + 1)) ?>"><?= icon('chevron-right', 16) ?></a>
    <?php else: ?><span class="disabled"><?= icon('chevron-right', 16) ?></span><?php endif; ?>
</nav>
