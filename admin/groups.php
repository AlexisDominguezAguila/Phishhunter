<?php
/**
 * Panel docente · Grupos de trabajo
 *  - Experimental: juega las misiones (intervención gamificada).
 *  - Control: solo responde pretest y postest; el docente habilita el postest.
 * Aquí el docente también elige los grados con los que trabaja (1.º a 5.º).
 * El súper admin no tiene grupos propios: elige un docente (?docente=ID) y
 * administra los grupos, grados e institución de ese docente.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$me  = require_admin();
$isSuper = is_super($me);
$mid = (int) $me['id'];
$teacher = $me;
if ($isSuper) {
    $tid = (int) ($_POST['docente'] ?? $_GET['docente'] ?? 0);
    $teacher = $tid ? db_one("SELECT * FROM users WHERE id = ? AND role = 'admin'", [$tid]) : null;
    if (!$teacher) {
        // Selector de docente
        $list = db_all("SELECT t.id, t.full_name, t.username, t.institution, t.grades, t.active,
                (SELECT COUNT(*) FROM class_groups g WHERE g.teacher_id = t.id) AS groups_n,
                (SELECT COUNT(*) FROM users u WHERE u.teacher_id = t.id AND u.role = 'student') AS students_n
              FROM users t WHERE t.role = 'admin' ORDER BY t.active DESC, t.full_name");
        page_header('Grupos de docentes', 'groups');
        ?>
<div class="section-head">
    <div>
        <h1><?= icon('bxs-layer') ?> Grupos de docentes</h1>
        <p class="muted">Como súper admin no tienes grupos propios. Elige un docente para administrar sus grupos, sus grados y su institución.</p>
    </div>
    <a class="btn btn-soft" href="<?= e(url('super/index.php#nuevo-docente')) ?>"><?= icon('bxs-user-badge') ?> Crear docente</a>
</div>
<?php if (!$list): ?>
    <section class="card"><p class="muted">Aún no hay docentes. Crea uno desde <a href="<?= e(url('super/index.php#nuevo-docente')) ?>">Docentes</a>.</p></section>
<?php else: ?>
<div class="group-grid">
    <?php foreach ($list as $t): ?>
    <a class="card group-card teacher-pick" href="<?= e(url('admin/groups.php?docente=' . (int) $t['id'])) ?>">
        <div class="card-head">
            <div>
                <span class="status-pill <?= $t['active'] ? 'st-respondido' : 'st-archivado' ?>"><?= $t['active'] ? 'Activo' : 'Deshabilitado' ?></span>
                <h2><?= e($t['full_name']) ?></h2>
            </div>
            <span class="gc-count"><?= icon('bxs-group') ?> <?= (int) $t['students_n'] ?></span>
        </div>
        <p class="muted small"><?= icon('bxs-school') ?> <?= e($t['institution'] ?: 'Sin institución') ?></p>
        <div class="group-stats">
            <div><b><?= (int) $t['groups_n'] ?></b><span>grupos</span></div>
            <div><b><?= e(grades_label($t['grades'])) ?></b><span>grados</span></div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif;
        page_footer();
        exit;
    }
}
$tid = (int) $teacher['id'];
ensure_default_group(db(), $tid);
$back = 'admin/groups.php' . ($isSuper ? '?docente=' . $tid : '');

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $gid = (int) ($_POST['group_id'] ?? 0);
    $group = $gid ? db_one('SELECT * FROM class_groups WHERE id = ? AND teacher_id = ?', [$gid, $tid]) : null;
    $name = trim((string) ($_POST['name'] ?? ''));
    $type = ($_POST['type'] ?? '') === 'control' ? 'control' : 'experimental';
    $desc = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 255);

    if ($action === 'institution') {
        $inst = mb_substr(trim((string) ($_POST['institution'] ?? '')), 0, 150);
        db_exec('UPDATE users SET institution = ? WHERE id = ?', [$inst !== '' ? $inst : null, $tid]);
        flash('success', $inst !== '' ? 'Institución guardada: «' . $inst . '».' : 'Se quitó la institución.');
    } elseif ($action === 'grades') {
        $csv = grades_to_csv((array) ($_POST['grades'] ?? []));
        if (!$csv) {
            flash('error', 'Elige al menos un grado.');
        } else {
            db_exec('UPDATE users SET grades = ? WHERE id = ?', [$csv, $tid]);
            flash('success', 'Guardado: trabajas con ' . grades_label($csv) . '.');
        }
    } elseif ($action === 'create') {
        if (mb_strlen($name) < 2) {
            flash('error', 'Escribe un nombre para el grupo.');
        } else {
            db_exec('INSERT INTO class_groups (teacher_id, name, type, description) VALUES (?,?,?,?)', [$tid, mb_substr($name, 0, 80), $type, $desc ?: null]);
            flash('success', 'Grupo «' . $name . '» creado.');
        }
    } elseif ($group && $action === 'update') {
        if (mb_strlen($name) >= 2) {
            db_exec('UPDATE class_groups SET name = ?, type = ?, description = ? WHERE id = ?', [mb_substr($name, 0, 80), $type, $desc ?: null, $gid]);
            flash('success', 'Grupo actualizado.');
        }
    } elseif ($group && $action === 'postest') {
        $open = (int) !$group['postest_open'];
        db_exec('UPDATE class_groups SET postest_open = ? WHERE id = ?', [$open, $gid]);
        flash('success', $open ? 'Postest habilitado para «' . $group['name'] . '».' : 'Postest cerrado para «' . $group['name'] . '».');
    } elseif ($group && $action === 'delete') {
        $n = (int) db_val("SELECT COUNT(*) FROM users WHERE group_id = ? AND role = 'student'", [$gid]);
        $count = (int) db_val('SELECT COUNT(*) FROM class_groups WHERE teacher_id = ?', [$tid]);
        if ($n > 0) {
            flash('error', 'El grupo tiene ' . $n . ' estudiantes. Muévelos a otro grupo antes de eliminarlo.');
        } elseif ($count <= 1) {
            flash('error', 'Necesitas al menos un grupo.');
        } else {
            db_exec('DELETE FROM class_groups WHERE id = ?', [$gid]);
            flash('success', 'Grupo eliminado.');
        }
    }
    redirect($back);
}

$me = current_user(true);
$teacher = $isSuper ? db_one('SELECT * FROM users WHERE id = ?', [$tid]) : $me;
$myGrades = teacher_grades($teacher);
$hid = $isSuper ? '<input type="hidden" name="docente" value="' . $tid . '">' : '';
$groups = db_all("SELECT g.*,
        (SELECT COUNT(*) FROM users u WHERE u.group_id = g.id AND u.role = 'student') AS students,
        (SELECT COUNT(*) FROM users u JOIN assessments a ON a.user_id = u.id AND a.type = 'pretest' WHERE u.group_id = g.id) AS pre,
        (SELECT COUNT(*) FROM users u JOIN assessments a ON a.user_id = u.id AND a.type = 'postest' WHERE u.group_id = g.id) AS post,
        (SELECT GROUP_CONCAT(DISTINCT u.grade ORDER BY u.grade) FROM users u WHERE u.group_id = g.id AND u.role = 'student') AS grades_in
      FROM class_groups g WHERE g.teacher_id = ? ORDER BY g.id", [$tid]);

page_header($isSuper ? 'Grupos de ' . $teacher['full_name'] : 'Mis grupos', 'groups');
?>
<div class="section-head">
    <div>
        <?php if ($isSuper): ?>
        <a class="muted small" href="<?= e(url('admin/groups.php')) ?>"><?= icon('bx-arrow-back') ?> Todos los docentes</a>
        <h1><?= icon('bxs-layer') ?> Grupos de <?= e($teacher['full_name']) ?></h1>
        <p class="muted">Estás administrando los grupos de este docente<?= $teacher['active'] ? '' : ' (cuenta <b>deshabilitada</b>)' ?>. Los cambios los verá en su panel.</p>
        <?php else: ?>
        <h1><?= icon('bxs-layer') ?> Mis grupos</h1>
        <p class="muted">Organiza a tus estudiantes para el diseño de tu estudio: el grupo <b>experimental</b> juega las misiones y el grupo <b>control</b> solo responde el pretest y el postest.</p>
        <?php endif; ?>
    </div>
    <a class="btn btn-soft" href="<?= e(url('admin/students.php' . ($isSuper ? '?docente=' . $tid . '#nuevo' : ''))) ?>"><?= icon('bxs-group') ?> <?= $isSuper ? 'Registrar sus estudiantes' : 'Estudiantes' ?></a>
</div>

<section class="card">
    <h2><?= icon('bxs-school') ?> <?= $isSuper ? 'Institución educativa del docente' : 'Mi institución educativa' ?></h2>
    <form method="post" class="grades-form">
        <?= csrf_field() ?><?= $hid ?><input type="hidden" name="action" value="institution">
        <input class="inst-input" type="text" name="institution" value="<?= e($teacher['institution'] ?? '') ?>" maxlength="150" placeholder="Ej.: I.E. San Martín, Frías – Piura">
        <button class="btn btn-primary btn-sm" type="submit"><?= icon('bx-check') ?> Guardar</button>
        <p class="muted small"><?= $isSuper
            ? 'Aparece en el pie de página del docente y de sus estudiantes.'
            : 'Aparece en el pie de página para ti y para tus estudiantes.' ?></p>
    </form>
</section>

<section class="card">
    <h2><?= icon('bxs-graduation') ?> <?= $isSuper ? 'Grados con los que trabaja' : 'Grados con los que trabajo' ?></h2>
    <form method="post" class="grades-form">
        <?= csrf_field() ?><?= $hid ?><input type="hidden" name="action" value="grades">
        <div class="grade-checks">
            <?php foreach (ALL_GRADES as $g): ?>
                <label class="grade-check"><input type="checkbox" name="grades[]" value="<?= $g ?>" <?= in_array($g, $myGrades, true) ? 'checked' : '' ?>><span><?= $g ?>.º</span></label>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-primary btn-sm" type="submit"><?= icon('bx-check') ?> Guardar grados</button>
        <p class="muted small"><?= $isSuper ? 'Solo se podrán registrar estudiantes de estos grados para este docente.' : 'Solo podrás registrar estudiantes de estos grados de secundaria.' ?></p>
    </form>
</section>

<div class="group-grid">
    <?php foreach ($groups as $g): $isControl = $g['type'] === 'control'; ?>
    <article class="card group-card <?= $isControl ? 'is-control' : '' ?>">
        <div class="card-head">
            <div>
                <span class="group-tag gt-<?= e($g['type']) ?>"><?= $isControl ? 'Grupo control' : 'Grupo experimental' ?></span>
                <h2><?= e($g['name']) ?></h2>
            </div>
            <span class="gc-count"><?= icon('bxs-group') ?> <?= (int) $g['students'] ?></span>
        </div>
        <?php if ($g['description']): ?><p class="muted small"><?= e($g['description']) ?></p><?php endif; ?>
        <div class="group-stats">
            <div><b><?= (int) $g['pre'] ?>/<?= (int) $g['students'] ?></b><span>pretest</span></div>
            <div><b><?= (int) $g['post'] ?>/<?= (int) $g['students'] ?></b><span>postest</span></div>
            <div><b><?= e(grades_label($g['grades_in'])) ?></b><span>grados</span></div>
        </div>
        <?php if ($isControl): ?>
            <form method="post" class="postest-toggle">
                <?= csrf_field() ?><?= $hid ?><input type="hidden" name="action" value="postest"><input type="hidden" name="group_id" value="<?= (int) $g['id'] ?>">
                <span><?= $g['postest_open'] ? icon('bxs-lock-open') . ' Postest <b>habilitado</b>' : icon('bxs-lock-alt') . ' Postest <b>cerrado</b>' ?></span>
                <button class="btn <?= $g['postest_open'] ? 'btn-soft' : 'btn-primary' ?> btn-sm" type="submit"><?= $g['postest_open'] ? 'Cerrar postest' : 'Habilitar postest' ?></button>
            </form>
            <p class="muted small">Tus estudiantes de control solo ven sus evaluaciones. Al terminar el postest se les abre el juego para que también aprendan.</p>
        <?php else: ?>
            <p class="muted small">El postest se habilita solo a cada estudiante cuando completa las 6 misiones.</p>
        <?php endif; ?>
        <div class="group-actions">
            <a class="btn btn-soft btn-sm" href="<?= e(url('admin/students.php?' . ($isSuper ? 'docente=' . $tid . '&' : '') . 'grupo=' . (int) $g['id'])) ?>"><?= icon('bx-list-ul') ?> Ver estudiantes</a>
            <a class="btn btn-soft btn-sm" href="<?= e(url('admin/index.php?' . ($isSuper ? 'docente=' . $tid . '&' : '') . 'grupo=' . (int) $g['id'])) ?>"><?= icon('bxs-bar-chart-alt-2') ?> Resultados</a>
            <details class="row-edit">
                <summary class="btn btn-ghost btn-sm"><?= icon('bxs-edit') ?> Editar</summary>
                <form method="post" class="row-edit-form">
                    <?= csrf_field() ?><?= $hid ?><input type="hidden" name="action" value="update"><input type="hidden" name="group_id" value="<?= (int) $g['id'] ?>">
                    <input type="text" name="name" value="<?= e($g['name']) ?>" required maxlength="80">
                    <select name="type"><?php foreach (GROUP_TYPES as $k => $label): ?><option value="<?= $k ?>" <?= $g['type'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
                    <input type="text" name="description" value="<?= e($g['description']) ?>" maxlength="255" placeholder="Descripción (opcional)">
                    <button class="btn btn-primary btn-sm" type="submit">Guardar</button>
                </form>
            </details>
            <form method="post" class="inline" onsubmit="return confirm('¿Eliminar el grupo «<?= e($g['name']) ?>»?')">
                <?= csrf_field() ?><?= $hid ?><input type="hidden" name="action" value="delete"><input type="hidden" name="group_id" value="<?= (int) $g['id'] ?>">
                <button class="btn btn-ghost btn-sm btn-del" type="submit" title="Eliminar grupo"><?= icon('bxs-trash') ?></button>
            </form>
        </div>
    </article>
    <?php endforeach; ?>

    <article class="card group-card new-group">
        <h2><?= icon('bx-plus-circle') ?> Nuevo grupo</h2>
        <form method="post" class="form">
            <?= csrf_field() ?><?= $hid ?><input type="hidden" name="action" value="create">
            <label>Nombre<input type="text" name="name" required maxlength="80" placeholder="Ej.: 3.º A · Control"></label>
            <label>Tipo
                <select name="type"><?php foreach (GROUP_TYPES as $k => $label): ?><option value="<?= $k ?>"><?= e($label) ?></option><?php endforeach; ?></select>
            </label>
            <label>Descripción <span class="muted">(opcional)</span><input type="text" name="description" maxlength="255"></label>
            <button class="btn btn-primary btn-block" type="submit"><?= icon('bx-check') ?> Crear grupo</button>
        </form>
    </article>
</div>
<?php page_footer();
