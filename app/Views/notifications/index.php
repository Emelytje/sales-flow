<?php /** @var array $notifications */ ?>
<div class="page-head"><div class="title"><h1>Meldingen</h1><p><?= count($notifications) ?> meldingen</p></div>
<?php if (getenv('VAPID_PUBLIC_KEY')): ?><div class="page-actions"><button class="btn btn-outline" onclick="SF.push.enable()"><?= icon('bell', 18) ?> Push inschakelen</button></div><?php endif; ?>
</div>

<div class="card"><div class="card-body">
    <?php if (!$notifications): ?>
        <div class="empty"><?= icon('bell', 56) ?><h3>Geen meldingen</h3><p>Je bent helemaal bij.</p></div>
    <?php else: foreach ($notifications as $n): ?>
        <a class="notif-item <?= $n['read_at'] ? '' : 'unread' ?>" href="<?= e($n['link'] ?: '#') ?>" style="text-decoration:none;">
            <span class="feed-icon bg-rose" style="width:38px;height:38px;"><?= icon($n['icon'] ?: 'bell', 16) ?></span>
            <div class="flex-1">
                <div style="font-weight:700;font-size:var(--fs-sm);"><?= e($n['title']) ?></div>
                <?php if ($n['body']): ?><div class="small text-soft"><?= e($n['body']) ?></div><?php endif; ?>
                <div class="tiny text-muted mt-1"><?= e(time_ago($n['created_at'])) ?></div>
            </div>
        </a>
    <?php endforeach; endif; ?>
</div></div>
