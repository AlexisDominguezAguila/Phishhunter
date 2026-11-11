<?php
/**
 * Seeder: insignias (dominio de habilidades) y logros (metas acumulativas).
 */
return function (PDO $pdo): void {
    // slug, nombre, descripción, icono, color, categoría
    $badges = [
        ['primer-paso',      'Primer paso',            'Completaste la evaluación diagnóstica (pretest).',                          'bxs-flag-alt', '#8BC34A', 'evaluacion'],
        ['graduado',         'Cazador graduado',       'Completaste la evaluación final (postest) y la valoración.',                'bxs-graduation', '#D4A017', 'evaluacion'],
        ['superacion',       'Superación',             'Tu puntaje del postest superó al del pretest.',                            'bx-trending-up', '#2BB39A', 'evaluacion'],

        ['mision-1',         'Conocedor del anzuelo',  'Completaste la misión 1: ¿Qué es el phishing?',                            'bx-anchor', '#8BC34A', 'mision'],
        ['mision-2',         'Radar de señales',       'Completaste la misión 2: Señales de alerta.',                               'bx-radar', '#FFC83D', 'mision'],
        ['mision-3',         'Lupa de enlaces',        'Completaste la misión 3: Detective de enlaces.',                            'bx-search-alt', '#FF7A59', 'mision'],
        ['mision-4',         'Decisión segura',        'Completaste la misión 4: Decide con cabeza fría.',                          'bxs-compass', '#2BB39A', 'mision'],
        ['mision-5',         'Escudo de cuentas',      'Completaste la misión 5: Protege tus cuentas.',                            'bxs-shield', '#E0457B', 'mision'],
        ['mision-6',         'Cazador de phishing',    'Completaste la misión final: Operación Anzuelo Roto.',                      'bx-target-lock', '#D4A017', 'mision'],

        ['sin-anzuelo',      'No muerdo el anzuelo',   'Clasificaste perfecto todos los mensajes de una partida.',                  'bx-water', '#2BB39A', 'habilidad'],
        ['ojo-de-halcon',    'Ojo de halcón',          'Encontraste todas las señales de alerta sin marcar ninguna de más.',        'bxs-binoculars', '#FFC83D', 'habilidad'],
        ['verificador',      'Verificador de URL',     'Identificaste correctamente todos los enlaces de una partida.',             'bx-link', '#FF7A59', 'habilidad'],
        ['mente-fria',       'Mente fría',             'Elegiste la acción segura en todos los escenarios de una partida.',         'bxs-droplet-half', '#8BC34A', 'habilidad'],
        ['inquebrantable',   'Inquebrantable',         'No entregaste datos ni códigos a nadie en el Chat trampa.',                 'bxs-cube', '#E0457B', 'habilidad'],
        ['maestro-claves',   'Maestro de claves',      'Respondiste perfecto el reto de contraseñas y doble verificación.',         'bxs-lock', '#D4A017', 'habilidad'],
        ['guardian-digital', 'Guardián digital',       'Clasificaste perfecto las prácticas seguras y riesgosas.',                  'bxs-hard-hat', '#2BB39A', 'habilidad'],
        ['mente-brillante',  'Mente brillante',        'Obtuviste 90 % o más en todas las mini-pruebas.',                           'bxs-bulb', '#FFC83D', 'habilidad'],

        ['constante',        'Constancia',             'Entrenaste 3 días seguidos.',                                               'bxs-hot', '#FF7A59', 'especial'],
        ['imparable',        'Imparable',              'Entrenaste 7 días seguidos.',                                               'bxs-bolt', '#E0457B', 'especial'],
        ['coleccionista',    'Coleccionista de estrellas', 'Conseguiste 3 estrellas en todos los minijuegos.',                     'bxs-star', '#D4A017', 'especial'],
    ];
    $st = $pdo->prepare('INSERT INTO badges (slug, name, description, icon, color, category) VALUES (?,?,?,?,?,?)');
    foreach ($badges as $b) $st->execute($b);

    // slug, nombre, descripción, icono, métrica, meta, xp, monedas
    $achievements = [
        ['partidas-5',    'Calentando motores',  'Juega 5 partidas.',                     'bxs-joystick', 'games_played', 5,   30, 15],
        ['partidas-20',   'Entrenamiento serio', 'Juega 20 partidas.',                    'bxs-joystick-alt', 'games_played', 20,  80, 40],
        ['partidas-50',   'Veterano del juego',  'Juega 50 partidas.',                    'bxs-landmark', 'games_played', 50,  150, 80],
        ['aciertos-25',   'Buen ojo',            'Acierta 25 casos.',                     'bxs-bullseye', 'correct_answers', 25,  40, 20],
        ['aciertos-100',  'Francotirador',       'Acierta 100 casos.',                    'bx-target-lock', 'correct_answers', 100, 100, 50],
        ['aciertos-250',  'Imbatible',           'Acierta 250 casos.',                    'bxs-diamond', 'correct_answers', 250, 200, 100],
        ['perfecto-1',    'Partida perfecta',    'Termina una partida con 100 %.',        'bxs-magic-wand', 'perfect_games', 1,  30, 15],
        ['perfecto-5',    'Perfeccionista',      'Termina 5 partidas con 100 %.',         'bxs-color', 'perfect_games', 5,  80, 40],
        ['perfecto-15',   'Sin errores',         'Termina 15 partidas con 100 %.',        'bxs-crown', 'perfect_games', 15, 160, 80],
        ['xp-500',        'Medio millar',        'Alcanza 500 XP.',                       'bxs-star', 'xp', 500,  0, 25],
        ['xp-1500',       'Experiencia sólida',  'Alcanza 1500 XP.',                      'bxs-star', 'xp', 1500, 0, 60],
        ['xp-3000',       'Leyenda en progreso', 'Alcanza 3000 XP.',                      'bxs-rocket', 'xp', 3000, 0, 120],
        ['estrellas-10',  'Constelación',        'Reúne 10 estrellas.',                   'bxs-meteor', 'stars', 10, 40, 20],
        ['estrellas-25',  'Galaxia',             'Reúne 25 estrellas.',                   'bxs-planet', 'stars', 25, 80, 40],
        ['estrellas-40',  'Universo completo',   'Reúne 40 estrellas.',                   'bxs-planet', 'stars', 40, 150, 80],
        ['misiones-1',    'Primera misión',      'Completa 1 misión.',                    'bxs-map-alt', 'missions', 1, 20, 10],
        ['misiones-3',    'Mitad del camino',    'Completa 3 misiones.',                  'bx-trending-up', 'missions', 3, 60, 30],
        ['misiones-6',    'Ruta completa',       'Completa las 6 misiones.',              'bxs-flag-checkered', 'missions', 6, 150, 80],
        ['racha-3',       'Tres al hilo',        'Consigue una racha de 3 días.',         'bxs-hot', 'streak', 3, 30, 15],
        ['racha-7',       'Semana de fuego',     'Consigue una racha de 7 días.',         'bxs-meteor', 'streak', 7, 80, 40],
        ['insignias-5',   'Vitrina',             'Gana 5 insignias.',                     'bxs-medal', 'badges', 5, 50, 25],
        ['insignias-12',  'Museo de logros',     'Gana 12 insignias.',                    'bxs-institution', 'badges', 12, 120, 60],
    ];
    $st = $pdo->prepare('INSERT INTO achievements (slug, name, description, icon, metric, goal, xp_reward, coin_reward) VALUES (?,?,?,?,?,?,?,?)');
    foreach ($achievements as $a) $st->execute($a);
};
