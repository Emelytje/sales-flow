<?php /** @var array $channels */ /** @var array $team */ /** @var int $me */ ?>
<div class="page-head"><div class="title"><h1>Chat</h1><p>Intern teamoverleg</p></div></div>

<div class="card" style="overflow:hidden;">
<div class="chat-wrap">
    <aside class="chat-side">
        <div class="chat-side-head">
            <strong>Gesprekken</strong>
            <button class="icon-btn" style="width:32px;height:32px;" onclick="Chat.newGroup()" title="Nieuw kanaal"><?= icon('plus', 18) ?></button>
        </div>
        <div id="channelList" class="chat-channels">
            <?php foreach ($channels as $ch): $name = $ch['is_direct'] ? ($ch['other_name'] ?? 'DM') : $ch['name']; ?>
                <div class="chat-ch" data-ch="<?= (int) $ch['id'] ?>" onclick="Chat.open(<?= (int) $ch['id'] ?>, '<?= e(addslashes($name)) ?>')">
                    <span class="avatar avatar-sm" style="background:<?= e($ch['is_direct'] ? ($ch['other_color'] ?? '#E98CAB') : '#B33B62') ?>"><?= $ch['is_direct'] ? e(initials($name)) : '#' ?></span>
                    <div class="flex-1" style="min-width:0;">
                        <div style="font-weight:700;font-size:var(--fs-sm);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($name) ?></div>
                        <div class="tiny text-muted" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($ch['last_body'] ? mb_substr($ch['last_body'], 0, 30) : 'Nog geen berichten') ?></div>
                    </div>
                    <?php if ($ch['unread']): ?><span class="badge badge-rose"><?= (int) $ch['unread'] ?></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="chat-side-head" style="border-top:1px solid var(--border);border-bottom:none;"><strong class="small text-muted">Start een DM</strong></div>
        <div class="chat-channels">
            <?php foreach ($team as $t): ?>
                <div class="chat-ch" onclick="Chat.dm(<?= (int) $t['id'] ?>, '<?= e(addslashes($t['name'])) ?>')">
                    <span class="avatar avatar-sm" style="background:<?= e($t['color']) ?>"><?= e(initials($t['name'])) ?></span>
                    <div class="flex-1"><div style="font-weight:600;font-size:var(--fs-sm);"><?= e($t['name']) ?></div></div>
                    <?= icon('message', 16) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <section class="chat-main">
        <div id="chatEmpty" class="empty" style="margin:auto;"><?= icon('message', 56) ?><h3>Kies een gesprek</h3><p>Selecteer een kanaal of collega om te chatten.</p></div>
        <div id="chatRoom" class="hidden" style="display:flex;flex-direction:column;height:100%;">
            <div class="chat-room-head"><span class="avatar avatar-sm" id="chatAvatar"></span><strong id="chatTitle"></strong></div>
            <div id="chatMessages" class="chat-messages"></div>
            <div id="typingInd" class="chat-typing hidden"></div>
            <form id="chatForm" class="chat-input" onsubmit="return false;">
                <label class="icon-btn" style="width:40px;height:40px;cursor:pointer;"><?= icon('upload', 18) ?><input type="file" id="chatFile" hidden></label>
                <input class="input" id="chatBody" placeholder="Typ een bericht…" autocomplete="off">
                <button class="btn btn-primary" onclick="Chat.send()"><?= icon('send', 18) ?></button>
            </form>
        </div>
    </section>
</div>
</div>

<script>window.SF_ME = <?= $me ?>;</script>
<script src="/assets/js/chat.js"></script>
