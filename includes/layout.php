<?php
/**
 * PhishHunter · Plantilla común (cabecera, navegación y pie)
 */

const BOXICONS_VERSION = '2.1.4';

/** Hoja de estilos de Boxicons: copia local (sin internet) o CDN. */
function boxicons_url(): string
{
    if (is_file(APP_ROOT . '/assets/vendor/boxicons/css/boxicons.min.css')) {
        return asset('vendor/boxicons/css/boxicons.min.css');
    }
    return 'https://cdn.jsdelivr.net/npm/boxicons@' . BOXICONS_VERSION . '/css/boxicons.min.css';
}

/**
 * Icono de Boxicons. $name: «bxs-trophy», «bx-link», «bxl-whatsapp»…
 * $extra: clases de animación (bx-tada, bx-burst, bx-spin, bx-flashing, bx-fade-up, *-hover).
 */
function icon(?string $name, string $extra = ''): string
{
    $name = trim((string) $name);
    if ($name === '') return '';
    if (!preg_match('/^bx[sl]?-[a-z0-9-]+( bx-[a-z0-9-]+)*$/', $name)) {
        return '<span class="legacy-icon">' . e($name) . '</span>'; // datos antiguos
    }
    return '<i class="bx ' . $name . ($extra !== '' ? ' ' . e($extra) : '') . '" aria-hidden="true"></i>';
}

function page_header(string $title, string $active = '', array $opts = []): void
{
    $user = current_user();
    $bare = !empty($opts['bare']);
    $GLOBALS['ph_page'] = ['active' => $active, 'body_class' => $opts['body_class'] ?? '', 'bare' => $bare];
    $bodyClass = $opts['body_class'] ?? '';
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(APP_NAME) ?></title>
    <meta name="description" content="PhishHunter: juego educativo para aprender a prevenir el phishing.">
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(boxicons_url()) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script>window.PH = { base: <?= json_encode(base_url()) ?>, csrf: <?= json_encode(csrf_token()) ?>, urls: <?= json_encode(['api/game.php' => url('api/game.php'), 'api/arcade.php' => url('api/arcade.php'), 'rewards.php' => url('rewards.php')], JSON_UNESCAPED_SLASHES) ?> };</script>
</head>
<body class="<?= e($bodyClass) ?>">
<?php if (!$bare): ?>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="<?= e(url($user ? 'dashboard.php' : 'index.php')) ?>">
            <img src="<?= e(url('assets/img/logo-sm.png')) ?>" alt="PhishHunter" height="44">
        </a>
        <?php if ($user): $lv = level_info((int) $user['xp']); ?>
        <nav class="mainnav" id="mainnav">
            <?php if (is_staff($user)): $newMsg = is_super($user) ? new_messages_count() : 0; ?>
            <a href="<?= e(url('admin/index.php')) ?>" class="<?= $active === 'admin' ? 'active' : '' ?>"><span><i class="bx bxs-bar-chart-alt-2"></i></span> Resultados</a>
            <a href="<?= e(url('admin/students.php')) ?>" class="<?= $active === 'students' ? 'active' : '' ?>"><span><i class="bx bxs-group"></i></span> Estudiantes</a>
            <a href="<?= e(url('admin/groups.php')) ?>" class="<?= $active === 'groups' ? 'active' : '' ?>"><span><i class="bx bxs-layer"></i></span> Grupos</a>
            <a href="<?= e(url('dashboard.php')) ?>" class="<?= in_array($active, ['dashboard', 'arcade'], true) ? 'active' : '' ?>" title="Ver el juego como estudiante"><span><i class="bx bxs-joystick"></i></span> Juego</a>
            <?php if (is_super($user)): ?>
            <a href="<?= e(url('super/index.php')) ?>" class="<?= $active === 'super' ? 'active' : '' ?>"><span><i class="bx bxs-user-badge"></i></span> Docentes</a>
            <a href="<?= e(url('super/inbox.php')) ?>" class="<?= $active === 'inbox' ? 'active' : '' ?>"><span><i class="bx bxs-inbox"></i></span> Bandeja<?= $newMsg ? ' <em class="nav-badge">' . $newMsg . '</em>' : '' ?></a>
            <?php else: ?>
            <a href="<?= e(url('admin/messages.php')) ?>" class="<?= $active === 'messages' ? 'active' : '' ?>"><span><i class="bx bxs-message-rounded-dots"></i></span> Mensajes</a>
            <?php endif; ?>
            <?php else: ?>
            <a href="<?= e(url('dashboard.php')) ?>" class="<?= $active === 'dashboard' ? 'active' : '' ?>"><span><i class="bx bxs-map-alt"></i></span> Misiones</a>
            <?php if (can_play($user)): ?>
            <a href="<?= e(url('arcade.php')) ?>" class="<?= $active === 'arcade' ? 'active' : '' ?>"><span><i class="bx bxs-joystick-alt"></i></span> Refuerzo</a>
            <?php endif; ?>
            <a href="<?= e(url('leaderboard.php')) ?>" class="<?= $active === 'leaderboard' ? 'active' : '' ?>"><span><i class="bx bxs-trophy"></i></span> Clasificación</a>
            <a href="<?= e(url('rewards.php')) ?>" class="<?= $active === 'rewards' ? 'active' : '' ?>"><span><i class="bx bxs-gift"></i></span> Recompensas</a>
            <a href="<?= e(url('profile.php')) ?>" class="<?= $active === 'profile' ? 'active' : '' ?>"><span><i class="bx bxs-medal"></i></span> Mi progreso</a>
            <?php endif; ?>
        </nav>
        <div class="userbox">
            <?php if (!is_staff($user)): ?>
            <span class="chip chip-coins" title="Monedas"><i class="bx bxs-coin"></i> <b><?= (int) $user['coins'] ?></b></span>
            <span class="chip chip-streak" title="Racha de días"><i class="bx bxs-hot"></i> <b><?= (int) $user['streak'] ?></b></span>
            <?php endif; ?>
            <details class="usermenu">
                <summary title="<?= e($user['full_name']) ?>">
                    <span class="um-avatar">
                        <?= avatar_html($user, 'sm') ?>
                        <span class="um-level" title="Nivel <?= $lv['level'] ?>: <?= e($lv['name']) ?>"><?= $lv['level'] ?></span>
                    </span>
                    <i class="bx bx-chevron-down um-caret"></i>
                </summary>
                <div class="usermenu-panel">
                    <div class="um-head">
                        <?= avatar_html($user, 'md') ?>
                        <div>
                            <b><?= e($user['full_name']) ?></b>
                            <small>@<?= e($user['username']) ?> · <?= is_staff($user) ? e(role_label($user)) : ((int) $user['grade'] . '.º ' . e($user['section'])) ?></small>
                        </div>
                    </div>
                    <div class="um-level-row">
                        <span class="level-pill"><?= icon($lv['icon']) ?> Nivel <?= $lv['level'] ?></span>
                        <small class="muted"><?= e($lv['name']) ?></small>
                    </div>
                    <?= progress_bar($lv['progress'], 'bar-xp') ?>
                    <small class="muted um-xp"><?= number_format((int) $user['xp']) ?> XP<?= $lv['next'] ? ' · faltan ' . number_format($lv['next'] - (int) $user['xp']) . ' para subir' : ' · nivel máximo' ?></small>
                    <div class="um-links">
                        <a href="<?= e(url('profile.php')) ?>"><i class="bx bxs-medal"></i> Mi progreso</a>
                        <a href="<?= e(url('rewards.php')) ?>"><i class="bx bxs-palette"></i> Personalizar avatar</a>
                        <a href="<?= e(url('profile.php#cuenta')) ?>"><i class="bx bxs-lock-alt"></i> Cambiar contraseña</a>
                        <a href="<?= e(url('logout.php')) ?>" class="um-logout"><i class="bx bx-log-out"></i> Cerrar sesión</a>
                    </div>
                </div>
            </details>
            <button class="navtoggle" type="button" aria-label="Menú" onclick="document.getElementById('mainnav').classList.toggle('open')"><i class="bx bx-menu"></i></button>
        </div>
        <?php else: ?>
        <div class="userbox">
            <a class="btn btn-primary" href="<?= e(url('login.php')) ?>"><i class="bx bx-log-in"></i> Ingresar</a>
        </div>
        <?php endif; ?>
    </div>
</header>
<?php endif; ?>
<main class="page <?= $bare ? 'page-bare' : '' ?>">
<?php foreach (take_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
<?php
}

function page_footer(array $opts = []): void
{
    $celebrations = take_celebrations();
    ?>
</main>
<?php if (empty($opts['bare'])): ?>
<footer class="footer">
    <div>
        <b><?= e(APP_NAME) ?></b> · <?= e(APP_TAGLINE) ?>
        <span class="muted">— Estrategia gamificada para la prevención del phishing<?php $school = footer_institution(current_user()); if ($school !== ''): ?> · <?= e($school) ?><?php endif; ?></span>
    </div>
    <div class="muted small">Todos los mensajes son simulados con fines educativos. Nunca escribas aquí tus contraseñas reales.</div>
</footer>
<?php endif; ?>
<?php if (empty($opts['bare']) && current_user() && empty($opts['no_guide']) && strpos($GLOBALS['ph_page']['body_class'] ?? '', 'play-body') === false) echo chispa_guide($GLOBALS['ph_page']['active'] ?? ''); ?>
<div id="celebrate" class="modal" hidden></div>
<script>window.PH_CELEBRATIONS = <?= json_encode($celebrations, JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?php foreach ($opts['scripts'] ?? [] as $s): ?>
<script src="<?= e(asset($s)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
<?php
}

/** Estrellas visuales (iconos) */
function stars_html(int $stars, int $max = 3): string
{
    $h = '<span class="stars" aria-label="' . $stars . ' de ' . $max . ' estrellas">';
    for ($i = 1; $i <= $max; $i++) {
        $h .= '<i class="bx bxs-star' . ($i <= $stars ? ' on' : '') . '"></i>';
    }
    return $h . '</span>';
}

function level_badge(string $level): string
{
    $cls = ['Bajo' => 'lv-low', 'Medio' => 'lv-mid', 'Alto' => 'lv-high'][$level] ?? '';
    return '<span class="lvl ' . $cls . '">' . e($level) . '</span>';
}

function progress_bar(int $percent, string $cls = ''): string
{
    $p = max(0, min(100, $percent));
    return '<div class="bar ' . e($cls) . '"><span style="width:' . $p . '%"></span></div>';
}

/** Chispa flotante: consejos según la página. Se puede ocultar. */
function chispa_guide(string $active): string
{
    $tips = [
        'dashboard' => [
            '¡Hola! Soy <b>Chispa</b>, tu guía. Te acompañaré en todas tus misiones.',
            'Cada misión tiene videos cortos en «Aprende». ¡Míralos para ganar XP extra!',
            'Cuando completes una misión, se desbloquea un juego nuevo en la <b>Zona de refuerzo</b>.',
            'Regla de oro: <b>pausa, revisa y verifica</b> antes de hacer clic.',
        ],
        'arcade' => [
            'Aquí repasas jugando. ¡Los juegos no cambian tu ruta de misiones, pero sí suman XP!',
            'El <b>Simulador de oficina</b> es el reto más difícil: si no caes en ningún engaño, ganas el GRAN PREMIO.',
            'En el memorama, cada pareja que encuentras trae un dato nuevo. ¡Léelos!',
        ],
        'leaderboard' => [
            'La tabla premia aprender, no correr: los puntos salen de analizar bien cada caso.',
            'La tabla semanal se reinicia cada 7 días: ¡siempre hay oportunidad de subir!',
        ],
        'rewards' => [
            'Gana monedas con estrellas, insignias y logros, y cámbialas por avatares y marcos.',
            'Hay 3 premios que no se venden: solo se ganan en el Simulador de oficina.',
        ],
        'profile' => [
            'Aquí ves todas tus insignias. Las nuevas de la Zona de refuerzo también cuentan para los logros.',
            'Compara tu evaluación inicial con la final para ver cuánto aprendiste.',
        ],
    ];
    $list = $tips[$active] ?? [
        'Recuerda: ningún banco, colegio ni soporte técnico te pedirá tu contraseña.',
        'Si un mensaje te apura, desconfía: la urgencia es el anzuelo favorito.',
        'Revisa siempre el dominio real: lo que está justo antes de la primera «/».',
    ];
    return '<aside class="chispa-guide" id="chispa-guide" data-tips="' . e(json_encode($list, JSON_UNESCAPED_UNICODE)) . '">' .
        '<div class="cg-bubble" id="cg-bubble" hidden><button type="button" class="cg-close" id="cg-close" aria-label="Ocultar a Chispa">×</button><b>Chispa</b><p id="cg-text"></p>' .
        '<button type="button" class="cg-next" id="cg-next">Otro consejo <i class="bx bx-right-arrow-alt"></i></button></div>' .
        '<button type="button" class="cg-btn" id="cg-btn" aria-label="Consejo de Chispa">' .
        '<img src="' . e(url('assets/img/mascot/chispa-hola.svg')) . '" alt="Chispa"></button></aside>';
}
