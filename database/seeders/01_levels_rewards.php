<?php
/**
 * Seeder: niveles de jugador y recompensas canjeables.
 */
return function (PDO $pdo): void {
    $levels = [
        [1, 'Recluta digital',        0,    'bxs-leaf'],
        [2, 'Aprendiz de cazador',    150,  'bx-anchor'],
        [3, 'Explorador de señales',  400,  'bxs-compass'],
        [4, 'Rastreador de anzuelos', 750,  'bxs-search-alt-2'],
        [5, 'Detective de enlaces',   1200, 'bxs-user-detail'],
        [6, 'Guardián de cuentas',    1800, 'bxs-shield'],
        [7, 'Cazador de phishing',    2600, 'bx-target-lock'],
        [8, 'Maestro PhishHunter',    3600, 'bxs-crown'],
    ];
    $st = $pdo->prepare('INSERT INTO levels (level, name, min_xp, icon) VALUES (?,?,?,?)');
    foreach ($levels as $l) $st->execute($l);

    // slug, tipo, nombre, valor, costo, nivel mínimo
    $rewards = [
        // Avatares gratuitos (se asignan al registrarse)
        ['avatar-fox',     'avatar', 'Gato astuto',      'bxs-cat', 0, 1],
        ['avatar-cat',     'avatar', 'Perro fiel',       'bxs-dog', 0, 1],
        ['avatar-owl',     'avatar', 'Robot vigilante',  'bxs-bot', 0, 1],
        ['avatar-panda',   'avatar', 'Hoja tranquila',   'bxs-leaf', 0, 1],
        // Avatares de la tienda
        ['avatar-frog',    'avatar', 'Fantasma sigiloso', 'bxs-ghost', 60, 1],
        ['avatar-turtle',  'avatar', 'Escudo blindado',  'bxs-shield', 80, 2],
        ['avatar-octopus', 'avatar', 'Rayo veloz',       'bxs-bolt', 100, 2],
        ['avatar-robot',   'avatar', 'Cerebro analista', 'bxs-brain', 120, 3],
        ['avatar-lion',    'avatar', 'Corona guardiana', 'bxs-crown', 150, 3],
        ['avatar-alien',   'avatar', 'Invasor espacial', 'bxs-invader', 180, 4],
        ['avatar-ninja',   'avatar', 'Ninja digital',    'bxs-mask', 220, 5],
        ['avatar-dragon',  'avatar', 'Llama firewall',   'bxs-hot', 300, 6],
        ['avatar-unicorn', 'avatar', 'Cohete legendario', 'bxs-rocket', 400, 7],
        // Marcos de avatar (color del borde)
        ['frame-lime',     'frame', 'Marco lima',         '#8BC34A', 50, 1],
        ['frame-sun',      'frame', 'Marco sol',          '#FFC83D', 70, 2],
        ['frame-coral',    'frame', 'Marco coral',        '#FF7A59', 90, 2],
        ['frame-mint',     'frame', 'Marco menta',        '#2BB39A', 110, 3],
        ['frame-berry',    'frame', 'Marco frutos rojos', '#E0457B', 140, 4],
        ['frame-gold',     'frame', 'Marco dorado',       '#D4A017', 250, 6],
        // Títulos que se muestran junto al nombre
        ['title-curioso',  'title', 'El que pregunta',     'Siempre pregunto antes de hacer clic', 40, 1],
        ['title-pausa',    'title', 'Pausa segura',        'Maestro de la pausa segura', 80, 2],
        ['title-lupa',     'title', 'Lupa en mano',        'Lupa en mano', 100, 3],
        ['title-2fa',      'title', 'Doble candado',       'Doble candado activado', 140, 4],
        ['title-anzuelo',  'title', 'Anzuelo roto',        'Rompe-anzuelos', 180, 5],
        ['title-leyenda',  'title', 'Leyenda de Silahua',  'Leyenda de Silahua', 350, 7],
    ];
    $st = $pdo->prepare('INSERT INTO rewards (slug, type, name, value, cost, min_level) VALUES (?,?,?,?,?,?)');
    foreach ($rewards as $r) $st->execute($r);
};
