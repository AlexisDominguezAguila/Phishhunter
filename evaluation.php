<?php
/**
 * Evaluación diagnóstica (pretest) y evaluación final (postest + valoración).
 * Instrumento de 24 ítems de la tesis: se aplica igual antes y después.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$uid  = (int) $user['id'];
$type = ($_GET['type'] ?? 'pretest') === 'postest' ? 'postest' : 'pretest';

if (is_staff($user)) {
    flash('info', 'Las evaluaciones son para estudiantes. Revisa los resultados en el panel docente.');
    redirect('admin/index.php');
}
if (has_assessment($uid, $type)) {
    flash('info', 'Ya completaste esta evaluación. Aquí están tus resultados.');
    redirect('profile.php#evaluacion');
}
if ($type === 'postest' && !postest_available($user)) {
    flash('info', is_control_student($user) ? 'Tu docente habilitará la evaluación final cuando corresponda.' : 'La evaluación final se desbloquea al completar las 6 misiones.');
    redirect('dashboard.php');
}

$prevention   = survey_items('prevention');
$gamification = $type === 'postest' ? survey_items('gamification') : [];
$errors = null;
$values = [];

if (is_post()) {
    csrf_check();
    $values = array_map('intval', (array) ($_POST['q'] ?? []));
    try {
        save_assessment($uid, $type, $values);
        if ($type === 'postest') {
            save_assessment($uid, 'valoracion', $values);
        }
        $cel = [];
        if ($type === 'pretest') {
            add_xp($uid, 30, 10, 'Evaluación diagnóstica completada');
            if ($b = award_badge($uid, 'primer-paso')) $cel[] = $b + ['kicker' => '¡Nueva insignia!', 'reward' => '+25 XP · +10 monedas'];
            flash('success', '¡Evaluación registrada! Tu primera misión ya está desbloqueada.');
        } else {
            add_xp($uid, 100, 50, 'Evaluación final completada');
            if ($b = award_badge($uid, 'graduado')) $cel[] = $b + ['kicker' => '¡Nueva insignia!', 'reward' => '+25 XP · +10 monedas'];
            $pre  = get_assessment($uid, 'pretest');
            $post = get_assessment($uid, 'postest');
            if ($pre && $post && (int) $post['total'] > (int) $pre['total']) {
                if ($b = award_badge($uid, 'superacion')) $cel[] = $b + ['kicker' => '¡Nueva insignia!', 'reward' => '+25 XP · +10 monedas'];
            }
            flash('success', '¡Felicidades, cazador graduado! Mira cuánto avanzaste.');
        }
        foreach (check_achievements($uid) as $a) {
            $cel[] = $a + ['kicker' => '¡Logro desbloqueado!', 'reward' => '+' . $a['xp'] . ' XP · +' . $a['coins'] . ' monedas'];
        }
        foreach ($cel as $c) queue_celebration($c);
        redirect($type === 'pretest' ? 'dashboard.php' : 'profile.php#evaluacion');
    } catch (InvalidArgumentException $e) {
        $errors = $e->getMessage();
    }
}

function likert_block(array $items, array $scale, array $values, int $offset = 0): void
{
    foreach ($items as $i => $it): ?>
        <fieldset class="likert" id="q<?= (int) $it['id'] ?>">
            <legend><span class="q-num"><?= $offset + $i + 1 ?></span> <?= e($it['statement']) ?></legend>
            <div class="likert-opts">
                <?php foreach ($scale as $v => $label): ?>
                    <label>
                        <input type="radio" name="q[<?= (int) $it['id'] ?>]" value="<?= $v ?>" required <?= ($values[$it['id']] ?? 0) === $v ? 'checked' : '' ?>>
                        <span><b><?= $v ?></b><small><?= e($label) ?></small></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    <?php endforeach;
}

$totalQ = count($prevention) + count($gamification);
page_header($type === 'pretest' ? 'Evaluación diagnóstica' : 'Evaluación final', 'dashboard');
?>
<div class="eval">
    <section class="eval-hero card">
        <div class="eh-icon"><?= $type === 'pretest' ? '<i class="bx bxs-flag-alt"></i>' : '<i class="bx bxs-graduation"></i>' ?></div>
        <div>
            <span class="eyebrow"><?= $type === 'pretest' ? 'Antes de las misiones · Pretest' : 'Después de las misiones · Postest' ?></span>
            <h1><?= $type === 'pretest' ? 'Evaluación diagnóstica' : 'Evaluación final' ?></h1>
            <p>Lee cada afirmación y marca <b>con qué frecuencia</b> actúas así en tu vida diaria. Responde con sinceridad: no hay respuestas buenas ni malas y no afecta tu puntaje del juego. Sirve para medir tu avance.</p>
            <?php if ($type === 'postest'): ?><p>Al final encontrarás 9 preguntas para que valores la experiencia con PhishHunter.</p><?php endif; ?>
        </div>
    </section>

    <?php if ($errors): ?><div class="alert alert-error"><?= e($errors) ?></div><?php endif; ?>

    <form method="post" id="eval-form">
        <?= csrf_field() ?>
        <div class="eval-sticky">
            <span>Respondidas: <b id="eval-count">0</b>/<?= $totalQ ?></span>
            <div class="bar"><span id="eval-bar" style="width:0%"></span></div>
        </div>

        <h2 class="eval-part">Parte 1 · Prevención del phishing</h2>
        <p class="muted">Escala: 1 = Nunca · 2 = Casi nunca · 3 = A veces · 4 = Casi siempre · 5 = Siempre</p>
        <?php likert_block($prevention, FREQUENCY_SCALE, $values); ?>

        <?php if ($gamification): ?>
            <h2 class="eval-part">Parte 2 · Valoración de la experiencia gamificada</h2>
            <p class="muted">Escala: 1 = Totalmente en desacuerdo … 5 = Totalmente de acuerdo</p>
            <?php likert_block($gamification, AGREEMENT_SCALE, $values, count($prevention)); ?>
        <?php endif; ?>

        <button class="btn btn-primary btn-lg btn-block" type="submit">Enviar respuestas</button>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('eval-form');
    var total = <?= $totalQ ?>;
    function update() {
        var n = form.querySelectorAll('input[type=radio]:checked').length;
        document.getElementById('eval-count').textContent = n;
        document.getElementById('eval-bar').style.width = Math.round(n / total * 100) + '%';
    }
    form.addEventListener('change', function (e) {
        update();
        var fs = e.target.closest('fieldset');
        if (fs) fs.classList.add('done');
    });
    form.addEventListener('submit', function (e) {
        var missing = Array.prototype.find.call(form.querySelectorAll('fieldset'), function (fs) { return !fs.querySelector('input:checked'); });
        if (missing) {
            e.preventDefault();
            missing.classList.add('missing');
            missing.scrollIntoView({ behavior: 'smooth', block: 'center' });
            PHUI.toast('Te falta responder algunas preguntas.');
        }
    });
    form.querySelectorAll('fieldset').forEach(function (fs) { if (fs.querySelector('input:checked')) fs.classList.add('done'); });
    update();
});
</script>
<?php page_footer();
