<?php
/**
 * Panel docente · Estudiantes
 * Cada docente registra, edita y elimina SOLO a sus estudiantes, dentro de sus
 * grupos (experimental o control) y de los grados con los que trabaja.
 * El súper admin ve a todos, puede filtrar por docente y registrar estudiantes
 * A NOMBRE de un docente: elige el docente y su grupo de destino. Nunca se
 * registran estudiantes a nombre del súper admin.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$me = require_admin();
$isSuper = is_super($me);
if (!$isSuper) ensure_default_group(db(), (int) $me['id']);
$myGrades = $isSuper ? ALL_GRADES : teacher_grades($me);
$myGroups = $isSuper ? [] : teacher_groups((int) $me['id']);
$myGroupIds = array_map('intval', array_column($myGroups, 'id'));

/**
 * Grupos de destino para registrar o mover estudiantes.
 *  - Docente: solo sus grupos.
 *  - Súper admin: los grupos de todos los docentes (nunca los suyos).
 * Cada grupo trae su docente, sus grados y su institución.
 */
$destGroups = $isSuper
    ? db_all("SELECT g.*, t.full_name AS teacher_name, t.grades AS teacher_grades, t.institution AS teacher_institution, t.active AS teacher_active
                FROM class_groups g JOIN users t ON t.id = g.teacher_id
               WHERE t.role = 'admin' ORDER BY t.active DESC, t.full_name, g.id")
    : array_map(function ($g) use ($me) {
        return $g + ['teacher_name' => $me['full_name'], 'teacher_grades' => $me['grades'], 'teacher_institution' => $me['institution'], 'teacher_active' => 1];
    }, $myGroups);
$destById = [];
foreach ($destGroups as $g) $destById[(int) $g['id']] = $g;
/** Grados permitidos para un grupo de destino (los del docente dueño). */
$destGrades = function (array $g): array { return teacher_grades(['grades' => $g['teacher_grades']]); };

/** Valida grupo + grado de destino. Devuelve el grupo o null (y agrega errores). */
function check_destination(array $destById, callable $destGrades, $groupId, $grade, array &$errors, bool $isSuper): ?array
{
    $g = $destById[(int) $groupId] ?? null;
    if (!$g) {
        $errors[] = $isSuper ? 'Elige el docente y el grupo al que irán los estudiantes.' : 'Selecciona uno de tus grupos.';
        return null;
    }
    if (!(int) $g['teacher_active']) {
        $errors[] = 'La cuenta de ' . $g['teacher_name'] . ' está deshabilitada. Habilítala antes de registrarle estudiantes.';
        return null;
    }
    if (!in_array((int) $grade, $destGrades($g), true)) {
        $errors[] = $isSuper
            ? $g['teacher_name'] . ' trabaja con ' . grades_label($g['teacher_grades'] ?: '1,2,3,4,5') . '. Elige uno de esos grados.'
            : 'Selecciona un grado con el que trabajas.';
        return null;
    }
    return $g;
}

$errors = [];
$form = ['full_name' => '', 'username' => '', 'grade' => '', 'section' => '', 'password' => '', 'group' => '', 'bulk' => '', 'bulk_grade' => '', 'bulk_section' => '', 'bulk_group' => ''];

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    foreach ($form as $k => $v) $form[$k] = trim((string) ($_POST[$k] ?? ''));

    if ($action === 'create') {
        if (mb_strlen($form['full_name']) < 3) $errors[] = 'Escribe los nombres y apellidos del estudiante.';
        $dest = check_destination($destById, $destGrades, $form['group'], $form['grade'], $errors, $isSuper);
        if (!in_array(strtoupper($form['section']), SECTIONS, true)) $errors[] = 'Selecciona la sección.';
        if ($form['username'] !== '' && !preg_match('/^[a-zA-Z0-9._]{3,30}$/', $form['username'])) $errors[] = 'El usuario debe tener de 3 a 30 caracteres: letras, números, punto o guion bajo.';
        if ($form['username'] !== '' && db_val('SELECT 1 FROM users WHERE username = ?', [$form['username']])) $errors[] = 'Ese nombre de usuario ya existe.';
        if ($form['password'] !== '' && mb_strlen($form['password']) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres (o déjala vacía para generarla).';
        if (!$errors) {
            $c = create_student((int) $dest['teacher_id'], (int) $dest['id'], $form['full_name'], (int) $form['grade'], strtoupper($form['section']), $form['username'] ?: null, $form['password'] ?: null);
            $_SESSION['new_credentials'] = [$c + ['teacher' => $dest['teacher_name'], 'group' => $dest['name'], 'school' => $dest['teacher_institution']]];
            flash('success', $isSuper
                ? 'Estudiante registrado para ' . $dest['teacher_name'] . ' (' . $dest['name'] . ').'
                : 'Estudiante registrado. Entrégale su usuario y contraseña en persona.');
            redirect('admin/students.php#credenciales');
        }
    } elseif ($action === 'bulk') {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $form['bulk']))));
        $dest = check_destination($destById, $destGrades, $form['bulk_group'], $form['bulk_grade'], $errors, $isSuper);
        if (!in_array(strtoupper($form['bulk_section']), SECTIONS, true)) $errors[] = 'Selecciona la sección para la lista.';
        if (!$lines) $errors[] = 'Pega al menos un nombre (uno por línea).';
        if (count($lines) > 60) $errors[] = 'Máximo 60 estudiantes por lista.';
        if (!$errors) {
            $created = [];
            foreach ($lines as $name) {
                if (mb_strlen($name) < 3) continue;
                $created[] = create_student((int) $dest['teacher_id'], (int) $dest['id'], mb_substr($name, 0, 120), (int) $form['bulk_grade'], strtoupper($form['bulk_section']))
                    + ['teacher' => $dest['teacher_name'], 'group' => $dest['name'], 'school' => $dest['teacher_institution']];
            }
            $_SESSION['new_credentials'] = $created;
            flash('success', count($created) . ' estudiantes registrados' . ($isSuper ? ' para ' . $dest['teacher_name'] . ' (' . $dest['name'] . ')' : '') . '. Imprime o copia sus credenciales.');
            redirect('admin/students.php#credenciales');
        }
    } else {
        $sid = (int) ($_POST['user_id'] ?? 0);
        $student = db_one("SELECT * FROM users WHERE id = ? AND role = 'student'", [$sid]);
        if (!can_manage_student($me, $student)) {
            flash('error', 'Ese estudiante no pertenece a tus grupos.');
            redirect('admin/students.php');
        }
        if ($action === 'reset_password') {
            $temp = temp_password();
            db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($temp, PASSWORD_DEFAULT), $sid]);
            $_SESSION['new_credentials'] = [['full_name' => $student['full_name'], 'username' => $student['username'], 'password' => $temp,
                                             'grade' => $student['grade'], 'section' => $student['section']]];
            flash('success', 'Nueva contraseña temporal para ' . $student['username'] . '. Entrégala en persona, nunca por chat.');
            redirect('admin/students.php#credenciales');
        } elseif ($action === 'update') {
            $fn = trim((string) ($_POST['edit_name'] ?? ''));
            $gr = (int) ($_POST['edit_grade'] ?? 0);
            $se = strtoupper(trim((string) ($_POST['edit_section'] ?? '')));
            $gp = (int) ($_POST['edit_group'] ?? 0);
            // Docente: el grupo debe ser suyo. Súper admin: puede mover al estudiante
            // a cualquier grupo de cualquier docente (el estudiante pasa a ese docente).
            $dg = $destById[$gp] ?? null;
            if ($dg && mb_strlen($fn) >= 3 && in_array($gr, ALL_GRADES, true) && in_array($se, SECTIONS, true)) {
                db_exec('UPDATE users SET full_name = ?, grade = ?, section = ?, group_id = ?, teacher_id = ? WHERE id = ?', [$fn, $gr, $se, $gp, (int) $dg['teacher_id'], $sid]);
                $moved = (int) $dg['teacher_id'] !== (int) $student['teacher_id'];
                flash('success', 'Datos de ' . $student['username'] . ' actualizados' . ($moved ? '. Ahora pertenece a ' . $dg['teacher_name'] . ' (' . $dg['name'] . ').' : '.'));
            } else {
                flash('error', 'Revisa el nombre, grado, sección y grupo.');
            }
            redirect('admin/students.php');
        } elseif ($action === 'delete') {
            db_exec('DELETE FROM users WHERE id = ?', [$sid]);
            flash('success', 'Se eliminó la cuenta de ' . $student['username'] . ' y todo su progreso.');
            redirect('admin/students.php');
        }
        redirect('admin/students.php');
    }
}

$credentials = $_SESSION['new_credentials'] ?? [];
unset($_SESSION['new_credentials']);

// Filtros
$teacherF = is_super($me) ? (int) ($_GET['docente'] ?? 0) : 0;
$grade = in_array((int) ($_GET['grado'] ?? 0), ALL_GRADES, true) ? (int) $_GET['grado'] : null;
$groupF = (int) ($_GET['grupo'] ?? 0);
$q = trim($_GET['q'] ?? '');
$where = '1=1' . student_scope_sql($me, $teacherF ?: null, $groupF ?: null, $grade);
$params = [];
if ($q !== '') { $where .= ' AND (u.full_name LIKE ? OR u.username LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }

$rows = db_all("SELECT u.*, g.name AS group_name, g.type AS group_type, t.full_name AS teacher_name,
        (SELECT COUNT(*) FROM user_missions um WHERE um.user_id=u.id) missions,
        (SELECT COUNT(*) FROM user_badges b WHERE b.user_id=u.id) badges,
        pre.total pre_total, pre.level pre_level, post.total post_total, post.level post_level,
        (SELECT MAX(created_at) FROM xp_log l WHERE l.user_id=u.id) last_seen
      FROM users u
 LEFT JOIN class_groups g ON g.id = u.group_id
 LEFT JOIN users t ON t.id = u.teacher_id
 LEFT JOIN assessments pre  ON pre.user_id=u.id AND pre.type='pretest'
 LEFT JOIN assessments post ON post.user_id=u.id AND post.type='postest'
     WHERE $where
  ORDER BY u.grade, u.section, u.full_name", $params);
$total = (int) db_val('SELECT COUNT(*) FROM users u WHERE 1=1' . student_scope_sql($me));
$filterGroups = is_super($me) ? db_all('SELECT g.*, t.full_name AS teacher_name FROM class_groups g JOIN users t ON t.id = g.teacher_id ORDER BY t.full_name, g.id') : $myGroups;
$filterGrades = $myGrades;
$teachers = is_super($me) ? all_teachers() : [];
$link = function (array $over) use ($teacherF, $grade, $groupF, $q) {
    $p = array_filter(array_merge(['docente' => $teacherF, 'grado' => $grade, 'grupo' => $groupF, 'q' => $q], $over));
    return url('admin/students.php' . ($p ? '?' . http_build_query($p) : ''));
};
$gradeSelect = function (string $name, string $sel) use ($myGrades) {
    $h = '<select name="' . $name . '" required data-grade-select><option value="">—</option>';
    foreach ($myGrades as $g) $h .= '<option value="' . $g . '"' . ($sel === (string) $g ? ' selected' : '') . '>' . $g . '.º</option>';
    return $h . '</select>';
};
$sectionSelect = function (string $name, string $sel) {
    $h = '<select name="' . $name . '" required><option value="">—</option>';
    foreach (SECTIONS as $s) $h .= '<option' . (strtoupper($sel) === $s ? ' selected' : '') . '>' . $s . '</option>';
    return $h . '</select>';
};
/**
 * Selector del grupo de destino. Para el súper admin agrupa por docente
 * (optgroup) y cada opción lleva los grados del docente (data-grades) para
 * filtrar el selector de grado.
 */
$groupSelect = function (string $name, string $sel, bool $withInactive = false) use ($destGroups, $isSuper, $teacherF) {
    $h = '<select name="' . $name . '" required data-group-select>';
    if ($isSuper) $h .= '<option value="">— Elige docente y grupo —</option>';
    $current = null;
    foreach ($destGroups as $i => $g) {
        if (!$withInactive && !(int) $g['teacher_active']) continue;
        $on = $sel === (string) $g['id']
            || ($sel === '' && !$isSuper && $i === 0)
            || ($sel === '' && $isSuper && $teacherF === (int) $g['teacher_id'] && $current !== (int) $g['teacher_id']);
        if ($isSuper && $current !== (int) $g['teacher_id']) {
            if ($current !== null) $h .= '</optgroup>';
            $h .= '<optgroup label="' . e($g['teacher_name'] . ((int) $g['teacher_active'] ? '' : ' (deshabilitado)')) . '">';
            $current = (int) $g['teacher_id'];
        }
        $label = $isSuper ? $g['teacher_name'] . ' › ' . $g['name'] : $g['name'];
        $h .= '<option value="' . (int) $g['id'] . '" data-grades="' . e($g['teacher_grades'] ?: '1,2,3,4,5') . '"' . ($on ? ' selected' : '') . '>'
            . e($label) . ' (' . ($g['type'] === 'control' ? 'control' : 'experimental') . ')</option>';
    }
    if ($isSuper && $current !== null) $h .= '</optgroup>';
    return $h . '</select>';
};
$activeTeachers = count(array_unique(array_map(function ($g) { return (int) $g['teacher_id']; }, array_filter($destGroups, function ($g) { return (int) $g['teacher_active']; }))));

page_header('Estudiantes', 'students');
?>
<div class="section-head">
    <div><h1><?= icon('bxs-group') ?> Estudiantes</h1><p class="muted"><?php if ($isSuper): ?><?= $total ?> estudiantes en toda la plataforma · Registras estudiantes <b>a nombre de un docente</b>, en uno de sus grupos.<?php else: ?><?= $total ?> estudiantes en tus grupos · Grados con los que trabajas: <b><?= e(grades_label(implode(',', $myGrades))) ?></b><?php endif; ?></p></div>
    <div class="head-actions">
        <a class="btn btn-soft" href="<?= e(url('admin/groups.php' . ($isSuper && $teacherF ? '?docente=' . $teacherF : ''))) ?>"><?= icon('bxs-layer') ?> <?= $isSuper ? 'Grupos de docentes' : 'Mis grupos' ?></a>
        <a class="btn btn-soft" href="<?= e(url('admin/import.php')) ?>"><?= icon('bxs-file-import') ?> Importar desde Excel</a>
        <a class="btn btn-primary" href="#nuevo"><?= icon('bxs-user-plus', 'bx-tada-hover') ?> Registrar estudiantes</a>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<?php if ($credentials): ?>
<section class="card credentials" id="credenciales">
    <div class="card-head">
        <h2><?= icon('bxs-key', 'bx-tada') ?> Credenciales nuevas</h2>
        <button class="btn btn-soft btn-sm no-print" type="button" onclick="window.print()"><?= icon('bxs-printer') ?> Imprimir</button>
    </div>
    <p class="muted small no-print">Se muestran <b>solo esta vez</b>. Imprímelas o cópialas y entrégalas en persona. Los estudiantes pueden cambiar su contraseña desde «Mi progreso».</p>
    <div class="cred-grid">
        <?php foreach ($credentials as $c): ?>
            <div class="cred-card">
                <b><?= e($c['full_name']) ?></b>
                <small><?= (int) $c['grade'] ?>.º <?= e($c['section']) ?> · <?= e(!empty($c['school']) ? $c['school'] : APP_NAME) ?></small>
                <?php if ($isSuper && !empty($c['teacher'])): ?><small><?= icon('bxs-user-badge') ?> <?= e($c['teacher']) ?> · <?= e($c['group']) ?></small><?php endif; ?>
                <div><?= icon('bxs-user') ?> Usuario: <code><?= e($c['username']) ?></code></div>
                <div><?= icon('bxs-lock-alt') ?> Contraseña: <code><?= e($c['password']) ?></code></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($isSuper && !$activeTeachers): ?>
<section class="card no-print" id="nuevo">
    <h2><?= icon('bxs-user-plus') ?> Registrar estudiantes</h2>
    <p class="muted">Los estudiantes siempre se registran a nombre de un docente. Aún no hay docentes activos: crea primero la cuenta del docente y luego vuelve aquí para registrar a sus estudiantes.</p>
    <a class="btn btn-primary" href="<?= e(url('super/index.php#nuevo-docente')) ?>"><?= icon('bxs-user-badge') ?> Crear docente</a>
</section>
<?php else: ?>
<?php if ($isSuper): ?>
<div class="alert alert-info no-print"><?= icon('bxs-info-circle') ?> Como súper admin no tienes estudiantes propios: en <b>Destino</b> elige el docente y el grupo. Los estudiantes aparecerán en el panel de ese docente y verán el colegio de ese docente.</div>
<?php endif; ?>
<div class="grid-2 no-print" id="nuevo">
    <section class="card">
        <h2><?= icon('bxs-user-plus') ?> Registrar un estudiante</h2>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <label>Nombres y apellidos
                <input type="text" name="full_name" value="<?= e($form['full_name']) ?>" required maxlength="120" placeholder="Ej.: Rosa María Pérez Ruiz">
            </label>
            <?php if ($isSuper): ?>
            <label>Destino <span class="muted">(docente › grupo)</span> <?= $groupSelect('group', $form['group']) ?></label>
            <div class="grid-2">
                <label>Grado <?= $gradeSelect('grade', $form['grade']) ?></label>
                <label>Sección <?= $sectionSelect('section', $form['section']) ?></label>
            </div>
            <?php else: ?>
            <div class="grid-3">
                <label>Grado <?= $gradeSelect('grade', $form['grade']) ?></label>
                <label>Sección <?= $sectionSelect('section', $form['section']) ?></label>
                <label>Grupo <?= $groupSelect('group', $form['group']) ?></label>
            </div>
            <?php endif; ?>
            <div class="grid-2">
                <label>Usuario <span class="muted">(opcional)</span>
                    <input type="text" name="username" value="<?= e($form['username']) ?>" maxlength="30" placeholder="Se genera solo">
                </label>
                <label>Contraseña <span class="muted">(opcional)</span>
                    <input type="text" name="password" value="" minlength="8" autocomplete="off" placeholder="Se genera sola">
                </label>
            </div>
            <button class="btn btn-primary btn-block" type="submit"><?= icon('bx-check') ?> Registrar estudiante</button>
        </form>
    </section>
    <section class="card">
        <h2><?= icon('bxs-spreadsheet') ?> Registrar una lista completa</h2>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="bulk">
            <label>Pega los nombres (uno por línea, puedes copiarlos de Excel)
                <textarea name="bulk" rows="6" placeholder="Luis Alberto Chuquihuanga Ramos&#10;María Fernanda Huamán Torres&#10;..."><?= e($form['bulk']) ?></textarea>
            </label>
            <?php if ($isSuper): ?>
            <label>Destino <span class="muted">(docente › grupo)</span> <?= $groupSelect('bulk_group', $form['bulk_group']) ?></label>
            <div class="grid-2">
                <label>Grado <?= $gradeSelect('bulk_grade', $form['bulk_grade']) ?></label>
                <label>Sección <?= $sectionSelect('bulk_section', $form['bulk_section']) ?></label>
            </div>
            <?php else: ?>
            <div class="grid-3">
                <label>Grado <?= $gradeSelect('bulk_grade', $form['bulk_grade']) ?></label>
                <label>Sección <?= $sectionSelect('bulk_section', $form['bulk_section']) ?></label>
                <label>Grupo <?= $groupSelect('bulk_group', $form['bulk_group']) ?></label>
            </div>
            <?php endif; ?>
            <p class="muted small">Se crea un usuario (nombre.apellido) y una contraseña temporal para cada uno.</p>
            <button class="btn btn-primary btn-block" type="submit"><?= icon('bxs-group') ?> Registrar lista</button>
        </form>
    </section>
</div>
<?php endif; ?>

<div class="section-head no-print">
    <h2><?= icon('bx-list-ul') ?> Lista de estudiantes</h2>
    <form class="search" method="get">
        <?php foreach (['docente' => $teacherF, 'grado' => $grade, 'grupo' => $groupF] as $k => $v): if ($v): ?><input type="hidden" name="<?= $k ?>" value="<?= (int) $v ?>"><?php endif; endforeach; ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o usuario">
        <button class="btn btn-soft" type="submit"><?= icon('bx-search') ?> Buscar</button>
    </form>
</div>
<div class="filters no-print">
    <div class="seg-control">
        <a class="<?= !$grade ? 'on' : '' ?>" href="<?= e($link(['grado' => null])) ?>">Todos</a>
        <?php foreach ($filterGrades as $g): ?><a class="<?= $grade === $g ? 'on' : '' ?>" href="<?= e($link(['grado' => $g])) ?>"><?= $g ?>.º</a><?php endforeach; ?>
    </div>
    <form method="get" class="filter-selects">
        <?php if ($grade): ?><input type="hidden" name="grado" value="<?= $grade ?>"><?php endif; ?>
        <?php if (is_super($me)): ?>
        <select name="docente" onchange="this.form.submit()">
            <option value="">Todos los docentes</option>
            <?php foreach ($teachers as $t): ?><option value="<?= (int) $t['id'] ?>" <?= $teacherF === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['full_name']) ?></option><?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select name="grupo" onchange="this.form.submit()">
            <option value="">Todos los grupos</option>
            <?php foreach ($filterGroups as $g): if ($teacherF && (int) $g['teacher_id'] !== $teacherF) continue; ?>
                <option value="<?= (int) $g['id'] ?>" <?= $groupF === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?><?= isset($g['teacher_name']) ? ' · ' . e($g['teacher_name']) : '' ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<div class="card table-wrap no-print">
    <table class="table">
        <thead><tr><th>Estudiante</th><th>Grado</th><th>Grupo</th><th>Nivel · XP</th><th>Misiones</th><th title="Insignias"><?= icon('bxs-medal') ?></th><th>Pretest</th><th>Postest</th><th>Cambio</th><th>Última actividad</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $l = level_info((int) $r['xp']);
            $ownerGrades = $isSuper ? ALL_GRADES : $myGrades;
        ?>
            <tr>
                <td class="who"><?= avatar_html($r, 'sm') ?> <span><b><?= e($r['full_name']) ?></b><small>@<?= e($r['username']) ?><?= is_super($me) && $r['teacher_name'] ? ' · ' . e($r['teacher_name']) : '' ?></small></span></td>
                <td><?= (int) $r['grade'] ?>.º <?= e($r['section']) ?></td>
                <td><span class="group-tag gt-<?= e($r['group_type'] ?? 'experimental') ?>"><?= e($r['group_name'] ?? '—') ?></span></td>
                <td><?= icon($l['icon']) ?> <?= $l['level'] ?> · <?= number_format((int) $r['xp']) ?></td>
                <td><?= (int) $r['missions'] ?>/6</td>
                <td><?= (int) $r['badges'] ?></td>
                <td><?= $r['pre_total'] !== null ? (int) $r['pre_total'] . ' ' . level_badge($r['pre_level']) : '<span class="muted">—</span>' ?></td>
                <td><?= $r['post_total'] !== null ? (int) $r['post_total'] . ' ' . level_badge($r['post_level']) : '<span class="muted">—</span>' ?></td>
                <td><?= $r['pre_total'] !== null && $r['post_total'] !== null ? '<b class="up">' . sprintf('%+d', $r['post_total'] - $r['pre_total']) . '</b>' : '—' ?></td>
                <td class="muted small"><?= $r['last_seen'] ? date('d/m/Y H:i', strtotime($r['last_seen'])) : '—' ?></td>
                <td class="nowrap row-actions">
                    <a class="btn btn-soft btn-sm" href="<?= e(url('profile.php?id=' . (int) $r['id'])) ?>" title="Ver progreso"><?= icon('bxs-show') ?></a>
                    <details class="row-edit">
                        <summary class="btn btn-ghost btn-sm" title="Editar datos"><?= icon('bxs-edit') ?></summary>
                        <form method="post" class="row-edit-form">
                            <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="update">
                            <input type="text" name="edit_name" value="<?= e($r['full_name']) ?>" required>
                            <select name="edit_grade"><?php foreach ($ownerGrades as $g): ?><option value="<?= $g ?>" <?= (int) $r['grade'] === $g ? 'selected' : '' ?>><?= $g ?>.º</option><?php endforeach; ?></select>
                            <select name="edit_section"><?php foreach (SECTIONS as $s): ?><option <?= $r['section'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
                            <?= $groupSelect('edit_group', (string) (int) $r['group_id'], true) ?>
                            <button class="btn btn-primary btn-sm" type="submit">Guardar</button>
                        </form>
                    </details>
                    <form method="post" class="inline" onsubmit="return confirm('¿Generar una contraseña temporal para <?= e($r['username']) ?>?')">
                        <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>">
                        <button class="btn btn-ghost btn-sm" name="action" value="reset_password" title="Restablecer contraseña"><?= icon('bxs-key') ?></button>
                    </form>
                    <form method="post" class="inline" onsubmit="return confirm('¿Eliminar la cuenta de <?= e($r['username']) ?>? Se borrará todo su progreso y sus evaluaciones. No se puede deshacer.')">
                        <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>">
                        <button class="btn btn-ghost btn-sm btn-del" name="action" value="delete" title="Eliminar cuenta"><?= icon('bxs-trash') ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="11" class="muted"><?= $total ? 'No hay estudiantes con esos filtros.' : 'Aún no hay estudiantes. Regístralos con el formulario de arriba.' ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($isSuper): ?>
<script>
// Súper admin: al elegir el destino, el selector de grado muestra solo los grados de ese docente.
document.querySelectorAll('form').forEach(function (f) {
    var dest = f.querySelector('[data-group-select]'), grade = f.querySelector('[data-grade-select]');
    if (!dest || !grade) return;
    function sync() {
        var opt = dest.options[dest.selectedIndex], allowed = (opt && opt.dataset.grades ? opt.dataset.grades : '').split(',');
        Array.prototype.forEach.call(grade.options, function (o) {
            if (!o.value) return;
            var ok = !opt || !opt.value || allowed.indexOf(o.value) !== -1;
            o.hidden = !ok; o.disabled = !ok;
            if (!ok && o.selected) grade.value = '';
        });
    }
    dest.addEventListener('change', sync); sync();
});
</script>
<?php endif; ?>
<?php page_footer();
