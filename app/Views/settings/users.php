<?php /** @var array $users */ ?>
<div class="breadcrumb"><a href="/settings">Instellingen</a> <?= icon('chevron-right', 14) ?> Gebruikers</div>
<div class="page-head"><div class="title"><h1>Gebruikers</h1><p><?= count($users) ?> teamleden</p></div>
    <div class="page-actions"><button class="btn btn-primary" onclick="userModal()"><?= icon('plus', 18) ?> Nieuwe gebruiker</button></div>
</div>

<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>Naam</th><th>E-mail</th><th>Rol</th><th>Status</th><th>Laatst actief</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><div class="flex items-center gap-3"><span class="avatar avatar-sm" style="background:<?= e($u['color']) ?>"><?= e(initials($u['name'])) ?></span><span style="font-weight:700;"><?= e($u['name']) ?></span></div></td>
            <td class="small"><?= e($u['email']) ?></td>
            <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'rose' : ($u['role'] === 'manager' ? 'info' : 'neutral') ?>"><?= e(ucfirst($u['role'])) ?></span></td>
            <td><span class="badge badge-<?= $u['status'] === 'active' ? 'success' : 'danger' ?> badge-dot"><?= $u['status'] === 'active' ? 'Actief' : 'Inactief' ?></span></td>
            <td class="small text-muted"><?= e(time_ago($u['last_login_at'])) ?></td>
            <td>
                <button class="icon-btn" style="width:34px;height:34px;" onclick='userModal(<?= json_encode(["id" => (int) $u["id"], "name" => $u["name"], "email" => $u["email"], "role" => $u["role"], "phone" => $u["phone"], "job_title" => $u["job_title"], "status" => $u["status"]], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><?= icon('edit', 16) ?></button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>

<script>
function userModal(user) {
    user = user || {};
    var isEdit = !!user.id;
    var m = SF.modal.open(`
        <div class="modal-head"><h3>${isEdit ? 'Gebruiker bewerken' : 'Nieuwe gebruiker'}</h3><button class="icon-btn" data-close>&times;</button></div>
        <form id="uf" action="${isEdit ? '/settings/users/' + user.id : '/settings/users'}" method="post" data-method="${isEdit ? 'PUT' : 'POST'}">
            <div class="modal-body">
                <input type="hidden" name="_csrf" value="${SF.csrf()}">
                ${isEdit ? '<input type="hidden" name="_method" value="PUT">' : ''}
                <div class="grid gap-3" style="grid-template-columns:1fr 1fr;">
                    <div class="field"><label class="label">Naam</label><input class="input" name="name" value="${user.name || ''}" required></div>
                    <div class="field"><label class="label">E-mail</label><input class="input" type="email" name="email" value="${user.email || ''}" ${isEdit ? 'readonly' : ''} required></div>
                    <div class="field"><label class="label">Rol</label><select class="select" name="role">
                        <option value="sales" ${user.role === 'sales' ? 'selected' : ''}>Sales</option>
                        <option value="manager" ${user.role === 'manager' ? 'selected' : ''}>Manager</option>
                        <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                    </select></div>
                    <div class="field"><label class="label">Functie</label><input class="input" name="job_title" value="${user.job_title || ''}"></div>
                    <div class="field"><label class="label">Telefoon</label><input class="input" name="phone" value="${user.phone || ''}"></div>
                    ${isEdit ? `<div class="field"><label class="label">Status</label><select class="select" name="status"><option value="active" ${user.status === 'active' ? 'selected' : ''}>Actief</option><option value="suspended" ${user.status === 'suspended' ? 'selected' : ''}>Inactief</option></select></div>` : ''}
                </div>
                <div class="field"><label class="label">${isEdit ? 'Nieuw wachtwoord (optioneel)' : 'Wachtwoord'}</label><input class="input" type="password" name="password" ${isEdit ? '' : 'required'}></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuleren</button><button class="btn btn-primary">${isEdit ? 'Opslaan' : 'Toevoegen'}</button></div>
        </form>`, { large: true });
    SF.bindForm(m.querySelector('#uf'));
}
</script>
