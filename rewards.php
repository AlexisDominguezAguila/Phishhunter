<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$uid  = (int) $user['id'];
$lv   = level_info((int) $user['xp']);

if (is_post()) {
    csrf_check();
    $reward = db_one('SELECT * FROM rewards WHERE id = ?', [(int) ($_POST['reward_id'] ?? 0)]);
    $action = $_POST['action'] ?? '';
    $column = ['avatar' => 'avatar', 'frame' => 'frame', 'title' => 'title'];

    if ($action === 'unequip' && in_array($_POST['type'] ?? '', ['frame', 'title'], true)) {
        db_exec('UPDATE users SET ' . $column[$_POST['type']] . ' = NULL WHERE id = ?', [$uid]);
        flash('info', 'Quitaste el elemento equipado.');
    } elseif ($reward) {
        $owned = (int) $reward['cost'] === 0 || db_val('SELECT 1 FROM user_rewards WHERE user_id=? AND reward_id=?', [$uid, $reward['id']]);
        if ($action === 'buy' && !$owned) {
            if ($lv['level'] < (int) $reward['min_level']) {
                flash('error', 'Necesitas el nivel ' . (int) $reward['min_level'] . ' para desbloquear esta recompensa.');
            } elseif ((int) $user['coins'] < (int) $reward['cost']) {
                flash('error', 'Te faltan ' . ((int) $reward['cost'] - (int) $user['coins']) . ' monedas. ¡Sigue jugando!');
            } else {
                db_exec('UPDATE users SET coins = coins - ? WHERE id = ? AND coins >= ?', [$reward['cost'], $uid, $reward['cost']]);
                db_exec('INSERT IGNORE INTO user_rewards (user_id, reward_id) VALUES (?,?)', [$uid, $reward['id']]);
                db_exec('INSERT INTO xp_log (user_id, xp, coins, reason) VALUES (?,?,?,?)', [$uid, 0, -(int) $reward['cost'], 'Canje: ' . $reward['name']]);
                db_exec('UPDATE users SET ' . $column[$reward['type']] . ' = ? WHERE id = ?', [$reward['slug'], $uid]);
                flash('success', '¡Canjeaste «' . $reward['name'] . '» y ya lo tienes equipado!');
            }
        } elseif ($action === 'equip' && $owned) {
            db_exec('UPDATE users SET ' . $column[$reward['type']] . ' = ? WHERE id = ?', [$reward['slug'], $uid]);
            flash('success', 'Equipaste «' . $reward['name'] . '».');
        }
    }
    redirect('rewards.php#' . ($reward['type'] ?? ($_POST['type'] ?? '')));
}

$rewards = db_all('SELECT r.*, (r.cost = 0 OR ur.user_id IS NOT NULL) AS owned
                     FROM rewards r LEFT JOIN user_rewards ur ON ur.reward_id = r.id AND ur.user_id = ?
                 ORDER BY FIELD(r.type,"avatar","frame","title"), r.cost', [$uid]);
$groups = ['avatar' => ['<i class="bx bxs-cat"></i> Avatares', 'Elige cómo te ven los demás cazadores.'],
           'frame'  => ['<i class="bx bxs-image"></i> Marcos', 'Un borde de color para tu avatar.'],
           'title'  => ['<i class="bx bxs-purchase-tag"></i> Títulos', 'Una frase que aparece bajo tu nombre.']];

page_header('Recompensas', 'rewards');
?>
<div class="section-head">
    <div>
        <h1><i class="bx bxs-gift"></i> Tienda de recompensas</h1>
        <p class="muted">Gana monedas jugando, consiguiendo estrellas, insignias y logros. Canjéalas por avatares, marcos y títulos.</p>
    </div>
    <div class="wallet">
        <?= avatar_html($user, 'lg') ?>
        <div><small>Tus monedas</small><b><i class="bx bxs-coin"></i> <?= (int) $user['coins'] ?></b><small>Nivel <?= $lv['level'] ?> · <?= e(user_title($user)) ?></small></div>
    </div>
</div>

<div class="earn-strip">
    <span>¿Cómo gano monedas?</span>
    <span><i class="bx bxs-joystick"></i> +3 por partida</span><span><i class="bx bxs-star"></i> +10 por cada estrella nueva</span><span><i class="bx bxs-badge-check"></i> +5 por partida perfecta</span>
    <span><i class="bx bxs-map-alt"></i> +30 a +50 por misión</span><span><i class="bx bxs-medal"></i> +10 por insignia</span><span><i class="bx bxs-bullseye"></i> logros</span>
</div>

<?php foreach ($groups as $type => $g): ?>
<section class="card" id="<?= $type ?>">
    <div class="card-head">
        <div><h2><?= $g[0] ?></h2><p class="muted small"><?= $g[1] ?></p></div>
        <?php if ($type !== 'avatar' && $user[$type]): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="unequip"><input type="hidden" name="type" value="<?= $type ?>">
                <button class="btn btn-ghost btn-sm" type="submit">Quitar <?= $type === 'frame' ? 'marco' : 'título' ?></button></form>
        <?php endif; ?>
    </div>
    <div class="shop-grid">
        <?php foreach ($rewards as $r): if ($r['type'] !== $type) continue;
            $equipped = $user[$type] === $r['slug'];
            $lockedLv = $lv['level'] < (int) $r['min_level'];
        ?>
            <form method="post" class="shop-item <?= $r['owned'] ? 'owned' : '' ?> <?= $equipped ? 'equipped' : '' ?> <?= !$r['owned'] && $lockedLv ? 'lvl-locked' : '' ?> <?= (int) $r['min_level'] >= 99 ? 'exclusive' : '' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="reward_id" value="<?= (int) $r['id'] ?>">
                <div class="si-preview">
                    <?php if ($type === 'avatar'): ?>
                        <span class="avatar avatar-lg"><?= icon($r['value']) ?></span>
                    <?php elseif ($type === 'frame'): ?>
                        <span class="avatar avatar-lg has-frame" style="--frame: <?= e($r['value']) ?>"><?= icon(reward_by_slug($user['avatar'])['value'] ?? 'bxs-cat') ?></span>
                    <?php else: ?>
                        <span class="title-preview">«<?= e($r['value']) ?>»</span>
                    <?php endif; ?>
                </div>
                <b><?= e($r['name']) ?></b>
                <?php if ($equipped): ?>
                    <span class="tag-good"><i class="bx bx-check"></i> Equipado</span>
                <?php elseif ($r['owned']): ?>
                    <button class="btn btn-soft btn-sm" name="action" value="equip">Equipar</button>
                <?php elseif ((int) $r['min_level'] >= 99): ?>
                    <span class="btn btn-disabled btn-sm" title="Se gana en el Simulador de oficina"><i class="bx bxs-trophy"></i> Gran premio</span>
                <?php elseif ($lockedLv): ?>
                    <span class="btn btn-disabled btn-sm"><i class="bx bxs-lock-alt"></i> Nivel <?= (int) $r['min_level'] ?></span>
                <?php else: ?>
                    <button class="btn btn-primary btn-sm" name="action" value="buy" <?= (int) $user['coins'] < (int) $r['cost'] ? 'disabled' : '' ?>><i class="bx bxs-coin"></i> <?= (int) $r['cost'] ?></button>
                <?php endif; ?>
            </form>
        <?php endforeach; ?>
    </div>
</section>
<?php endforeach; ?>
<?php page_footer();
