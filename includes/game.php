<?php
/**
 * PhishHunter · Motor de gamificación
 * Experiencia (XP), niveles, monedas, estrellas, rachas, misiones,
 * insignias y logros. La corrección de respuestas se hace aquí, en el
 * servidor, para que nadie pueda ver las soluciones desde el navegador.
 */

const DIMENSIONS = [
    'reconocimiento' => 'Reconocimiento de señales',
    'decisiones'     => 'Toma de decisiones seguras',
    'proteccion'     => 'Prácticas de protección digital',
    'integrador'     => 'Reto integrador',
];

const GAME_TYPES = [
    'classify' => '¿Phishing o legítimo?',
    'spot'     => 'Caza de señales',
    'url'      => 'Inspector de enlaces',
    'scenario' => '¿Qué harías?',
    'chat'     => 'Chat trampa',
    'choice'   => 'Elige la opción segura',
    'sort'     => 'Seguro o riesgoso',
    'quiz'     => 'Mini-prueba',
];

function dimension_label(?string $d): string
{
    return DIMENSIONS[$d] ?? 'General';
}

/* ---------------------------------------------------------------------
 *  Niveles
 * ------------------------------------------------------------------- */

function all_levels(): array
{
    static $levels = null;
    if ($levels === null) {
        $levels = db_all('SELECT * FROM levels ORDER BY level');
    }
    return $levels;
}

function level_info(int $xp): array
{
    $levels  = all_levels();
    $current = $levels[0];
    $next    = null;
    foreach ($levels as $i => $lv) {
        if ($xp >= (int) $lv['min_xp']) {
            $current = $lv;
            $next = $levels[$i + 1] ?? null;
        }
    }
    $progress = 100;
    if ($next) {
        $span = (int) $next['min_xp'] - (int) $current['min_xp'];
        $progress = (int) floor((($xp - (int) $current['min_xp']) / max(1, $span)) * 100);
    }
    return [
        'level'    => (int) $current['level'],
        'name'     => $current['name'],
        'icon'     => $current['icon'],
        'min'      => (int) $current['min_xp'],
        'next'     => $next ? (int) $next['min_xp'] : null,
        'next_name'=> $next['name'] ?? null,
        'progress' => max(0, min(100, $progress)),
    ];
}

/* ---------------------------------------------------------------------
 *  Avatares y recompensas equipadas
 * ------------------------------------------------------------------- */

function reward_by_slug(?string $slug): ?array
{
    static $cache = [];
    if (!$slug) {
        return null;
    }
    if (!array_key_exists($slug, $cache)) {
        $cache[$slug] = db_one('SELECT * FROM rewards WHERE slug = ?', [$slug]);
    }
    return $cache[$slug];
}

function avatar_html(array $user, string $size = 'md'): string
{
    $avatar = reward_by_slug($user['avatar'] ?? null);
    $frame  = reward_by_slug($user['frame'] ?? null);
    $ico    = $avatar['value'] ?? 'bxs-cat';
    $style  = $frame ? ' style="--frame:' . e($frame['value']) . '"' : '';
    $cls    = 'avatar avatar-' . $size . ($frame ? ' has-frame' : '');
    return '<span class="' . $cls . '"' . $style . '>' . icon($ico) . '</span>';
}

function user_title(array $user): string
{
    $t = reward_by_slug($user['title'] ?? null);
    return $t['value'] ?? level_info((int) $user['xp'])['name'];
}

/* ---------------------------------------------------------------------
 *  XP, monedas y rachas
 * ------------------------------------------------------------------- */

/** Suma XP y monedas, registra el historial y devuelve info de subida de nivel. */
function add_xp(int $userId, int $xp, int $coins, string $reason): array
{
    $before = (int) db_val('SELECT xp FROM users WHERE id = ?', [$userId]);
    db_exec('UPDATE users SET xp = xp + ?, coins = coins + ? WHERE id = ?', [$xp, $coins, $userId]);
    if ($xp !== 0 || $coins !== 0) {
        db_exec('INSERT INTO xp_log (user_id, xp, coins, reason) VALUES (?,?,?,?)', [$userId, $xp, $coins, $reason]);
    }
    $old = level_info($before);
    $new = level_info($before + $xp);
    return [
        'leveled_up' => $new['level'] > $old['level'],
        'level'      => $new,
    ];
}

/** Actualiza la racha de días consecutivos jugando. */
function touch_streak(int $userId): int
{
    $u = db_one('SELECT streak, best_streak, last_active_date FROM users WHERE id = ?', [$userId]);
    $today = date('Y-m-d');
    if ($u['last_active_date'] === $today) {
        return (int) $u['streak'];
    }
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $streak = ($u['last_active_date'] === $yesterday) ? (int) $u['streak'] + 1 : 1;
    $best = max($streak, (int) $u['best_streak']);
    db_exec('UPDATE users SET streak = ?, best_streak = ?, last_active_date = ? WHERE id = ?', [$streak, $best, $today, $userId]);
    return $streak;
}

/* ---------------------------------------------------------------------
 *  Misiones y progreso
 * ------------------------------------------------------------------- */

function has_assessment(int $userId, string $type): bool
{
    return (bool) db_val('SELECT 1 FROM assessments WHERE user_id = ? AND type = ?', [$userId, $type]);
}

/** Todas las misiones con el progreso del usuario y su estado de desbloqueo. */
function missions_with_progress(int $userId, bool $previewAll = false): array
{
    $missions = db_all('SELECT * FROM missions ORDER BY order_num');
    $games = db_all(
        'SELECT g.id, g.mission_id, g.is_quiz, COALESCE(p.best_stars,0) AS stars, p.passed_at
           FROM games g
      LEFT JOIN user_game_progress p ON p.game_id = g.id AND p.user_id = ?',
        [$userId]
    );
    $done = array_column(db_all('SELECT mission_id FROM user_missions WHERE user_id = ?', [$userId]), 'mission_id');
    $pretest = has_assessment($userId, 'pretest');

    $prevCompleted = true;
    foreach ($missions as &$m) {
        $mg = array_filter($games, function ($g) use ($m) { return (int) $g['mission_id'] === (int) $m['id']; });
        $m['games_total']  = count($mg);
        $m['games_passed'] = count(array_filter($mg, function ($g) { return $g['passed_at'] !== null; }));
        $m['stars']        = array_sum(array_column($mg, 'stars'));
        $m['max_stars']    = count($mg) * 3;
        $m['completed']    = in_array($m['id'], $done);
        $m['unlocked']     = $previewAll || ($pretest && $prevCompleted);
        $m['objectives']   = json_decode($m['objectives'], true);
        $m['lesson']       = json_decode($m['lesson'], true);
        $prevCompleted     = $m['completed'];
    }
    return $missions;
}

function mission_is_unlocked(array $user, int $missionId): bool
{
    if (!can_play($user)) {
        return false; // grupo control: sin intervención hasta terminar el postest
    }
    foreach (missions_with_progress((int) $user['id'], is_staff($user)) as $m) {
        if ((int) $m['id'] === $missionId) {
            return $m['unlocked'];
        }
    }
    return false;
}

function all_missions_completed(int $userId): bool
{
    $total = (int) db_val('SELECT COUNT(*) FROM missions');
    $done  = (int) db_val('SELECT COUNT(*) FROM user_missions WHERE user_id = ?', [$userId]);
    return $total > 0 && $done >= $total;
}

function stars_for(int $percent, int $pass): int
{
    if ($percent >= 90) return 3;
    if ($percent >= 75) return 2;
    if ($percent >= $pass) return 1;
    return 0;
}

/* ---------------------------------------------------------------------
 *  Partidas
 * ------------------------------------------------------------------- */

/** Crea una partida con casos aleatorios ordenados de menor a mayor dificultad. */
function start_attempt(int $userId, array $game): array
{
    $items = db_all('SELECT id, content, difficulty FROM game_items WHERE game_id = ? ORDER BY RAND()', [$game['id']]);
    $items = array_slice($items, 0, (int) $game['items_per_play']);
    usort($items, function ($a, $b) { return (int) $a['difficulty'] <=> (int) $b['difficulty']; });

    $ids = array_map('intval', array_column($items, 'id'));
    db_exec('INSERT INTO game_attempts (user_id, game_id, item_ids, total) VALUES (?,?,?,?)',
        [$userId, $game['id'], json_encode($ids), count($ids)]);
    $attemptId = (int) db()->lastInsertId();

    $public = [];
    foreach ($items as $it) {
        $public[] = [
            'id'         => (int) $it['id'],
            'difficulty' => (int) $it['difficulty'],
            'content'    => json_decode($it['content'], true),
        ];
    }
    return ['attempt_id' => $attemptId, 'items' => $public];
}

/**
 * Corrige una respuesta. Devuelve [es_correcta, puntos 0..1, datos de retroalimentación].
 */
function grade_answer(string $type, array $item, $answer): array
{
    $sol = json_decode($item['solution'], true);
    $fb  = ['explanation' => $item['explanation']];

    switch ($type) {
        case 'spot':
            $flags    = array_map('strval', $sol['flags']);
            $selected = array_map('strval', is_array($answer) ? $answer : []);
            $hits     = count(array_intersect($selected, $flags));
            $wrong    = count(array_diff($selected, $flags));
            $points   = max(0, ($hits - 0.5 * $wrong) / max(1, count($flags)));
            $fb['flags']   = $flags;
            $fb['reasons'] = $sol['reasons'] ?? [];
            $fb['hits']    = $hits;
            $fb['missed']  = count($flags) - $hits;
            $fb['wrong']   = $wrong;
            return [$hits === count($flags) && $wrong === 0, round(min(1, $points), 2), $fb];

        case 'classify':
        case 'url':
        case 'sort':
            $ok = is_string($answer) && $answer === $sol['answer'];
            $fb['answer'] = $sol['answer'];
            if (isset($sol['signals']))     $fb['signals']     = $sol['signals'];
            if (isset($sol['real_domain'])) $fb['real_domain'] = $sol['real_domain'];
            return [$ok, $ok ? 1.0 : 0.0, $fb];

        default: // scenario, chat, choice, quiz  -> índice de opción
            $idx = is_numeric($answer) ? (int) $answer : -1;
            $ok  = $idx === (int) $sol['answer'];
            $fb['answer'] = (int) $sol['answer'];
            if (!empty($sol['feedback'][$idx])) {
                $fb['consequence'] = $sol['feedback'][$idx];
            }
            return [$ok, $ok ? 1.0 : 0.0, $fb];
    }
}

function load_attempt(int $userId, int $attemptId): ?array
{
    return db_one(
        'SELECT a.*, g.type, g.slug AS game_slug, g.pass_percent, g.mission_id, g.is_quiz, g.title AS game_title
           FROM game_attempts a JOIN games g ON g.id = a.game_id
          WHERE a.id = ? AND a.user_id = ?',
        [$attemptId, $userId]
    );
}

function answer_attempt(int $userId, int $attemptId, int $itemId, $answer): array
{
    $att = load_attempt($userId, $attemptId);
    if (!$att || $att['status'] !== 'playing') {
        return ['ok' => false, 'error' => 'La partida no está activa.'];
    }
    if (!in_array($itemId, json_decode($att['item_ids'], true), true)) {
        return ['ok' => false, 'error' => 'Caso no válido para esta partida.'];
    }
    $item = db_one('SELECT * FROM game_items WHERE id = ?', [$itemId]);
    [$ok, $points, $fb] = grade_answer($att['type'], $item, $answer);

    try {
        db_exec('INSERT INTO attempt_answers (attempt_id, item_id, answer, is_correct, points) VALUES (?,?,?,?,?)',
            [$attemptId, $itemId, json_encode($answer, JSON_UNESCAPED_UNICODE), $ok ? 1 : 0, $points]);
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => 'Ese caso ya fue respondido.'];
    }
    return ['ok' => true, 'correct' => $ok, 'points' => $points, 'feedback' => $fb];
}

/** Cierra la partida y reparte recompensas. */
function finish_attempt(int $userId, int $attemptId): array
{
    $att = load_attempt($userId, $attemptId);
    if (!$att || $att['status'] !== 'playing') {
        return ['ok' => false, 'error' => 'La partida no está activa.'];
    }
    $sum = db_one('SELECT COUNT(*) AS answered, COALESCE(SUM(is_correct),0) AS correct, COALESCE(SUM(points),0) AS points
                     FROM attempt_answers WHERE attempt_id = ?', [$attemptId]);
    $total   = max(1, (int) $att['total']);
    $percent = (int) round(((float) $sum['points'] / $total) * 100);
    $stars   = stars_for($percent, (int) $att['pass_percent']);
    $passed  = $percent >= (int) $att['pass_percent'];

    $prog = db_one('SELECT * FROM user_game_progress WHERE user_id = ? AND game_id = ?', [$userId, $att['game_id']]);
    $bestPercent = $prog ? (int) $prog['best_percent'] : 0;
    $bestStars   = $prog ? (int) $prog['best_stars'] : 0;
    $firstPass   = $passed && (!$prog || $prog['passed_at'] === null);

    // Los puntos premian el análisis correcto, nunca la rapidez.
    $baseXp = (int) round((float) $sum['points'] * 10);
    $xp     = $percent > $bestPercent ? $baseXp : (int) ceil($baseXp * 0.4);
    if ($firstPass) $xp += 20;
    $coins  = 3 + max(0, $stars - $bestStars) * 10 + ($percent === 100 ? 5 : 0);

    db_exec('UPDATE game_attempts SET status="finished", correct=?, points=?, percent=?, stars=?, xp_earned=?, coins_earned=?, finished_at=NOW() WHERE id=?',
        [(int) $sum['correct'], (float) $sum['points'], $percent, $stars, $xp, $coins, $attemptId]);

    if ($prog) {
        db_exec('UPDATE user_game_progress SET best_percent=GREATEST(best_percent,?), best_stars=GREATEST(best_stars,?), plays=plays+1,
                 passed_at = IF(passed_at IS NULL AND ?, NOW(), passed_at) WHERE user_id=? AND game_id=?',
            [$percent, $stars, $passed ? 1 : 0, $userId, $att['game_id']]);
    } else {
        db_exec('INSERT INTO user_game_progress (user_id, game_id, best_percent, best_stars, plays, passed_at) VALUES (?,?,?,?,1,?)',
            [$userId, $att['game_id'], $percent, $stars, $passed ? date('Y-m-d H:i:s') : null]);
    }

    touch_streak($userId);
    $lv = add_xp($userId, $xp, $coins, 'Partida: ' . $att['game_title']);

    // ¿Se completó la misión?
    $missionDone = null;
    if ($passed) {
        $pending = (int) db_val('SELECT COUNT(*) FROM games g LEFT JOIN user_game_progress p ON p.game_id=g.id AND p.user_id=?
                                  WHERE g.mission_id=? AND p.passed_at IS NULL', [$userId, $att['mission_id']]);
        $already = (bool) db_val('SELECT 1 FROM user_missions WHERE user_id=? AND mission_id=?', [$userId, $att['mission_id']]);
        if ($pending === 0 && !$already) {
            $mission = db_one('SELECT * FROM missions WHERE id = ?', [$att['mission_id']]);
            db_exec('INSERT INTO user_missions (user_id, mission_id) VALUES (?,?)', [$userId, $mission['id']]);
            $lv2 = add_xp($userId, (int) $mission['xp_reward'], (int) $mission['coin_reward'], 'Misión completada: ' . $mission['title']);
            $lv['leveled_up'] = $lv['leveled_up'] || $lv2['leveled_up'];
            $lv['level'] = $lv2['level'];
            $missionDone = ['title' => $mission['title'], 'xp' => (int) $mission['xp_reward'], 'coins' => (int) $mission['coin_reward']];
            $xp += (int) $mission['xp_reward'];
            $coins += (int) $mission['coin_reward'];
        }
    }

    $newBadges = check_badges($userId, $att, $percent);
    $newAch    = check_achievements($userId);

    return [
        'ok'           => true,
        'correct'      => (int) $sum['correct'],
        'total'        => (int) $att['total'],
        'percent'      => $percent,
        'stars'        => $stars,
        'passed'       => $passed,
        'pass_percent' => (int) $att['pass_percent'],
        'xp'           => $xp,
        'coins'        => $coins,
        'improved'     => $percent > $bestPercent,
        'mission'      => $missionDone,
        'badges'       => $newBadges,
        'achievements' => $newAch,
        'level_up'     => $lv['leveled_up'] ? $lv['level'] : null,
        'level'        => $lv['level'],
    ];
}

/* ---------------------------------------------------------------------
 *  Insignias
 * ------------------------------------------------------------------- */

function award_badge(int $userId, string $slug): ?array
{
    $badge = db_one('SELECT * FROM badges WHERE slug = ?', [$slug]);
    if (!$badge) {
        return null;
    }
    $st = db()->prepare('INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?,?)');
    $st->execute([$userId, $badge['id']]);
    if ($st->rowCount() === 0) {
        return null;
    }
    add_xp($userId, 25, 10, 'Insignia: ' . $badge['name']);
    return ['name' => $badge['name'], 'icon' => $badge['icon'], 'color' => $badge['color'], 'description' => $badge['description']];
}

/** Insignias por dominio de habilidades concretas (no por rapidez). */
function check_badges(int $userId, array $att, int $percent): array
{
    $new = [];
    $push = function ($b) use (&$new) { if ($b) $new[] = $b; };

    $skill = [
        'classify' => 'sin-anzuelo',
        'spot'     => 'ojo-de-halcon',
        'url'      => 'verificador',
        'scenario' => 'mente-fria',
        'chat'     => 'inquebrantable',
        'choice'   => 'maestro-claves',
        'sort'     => 'guardian-digital',
    ];
    if ($percent === 100 && isset($skill[$att['type']])) {
        $push(award_badge($userId, $skill[$att['type']]));
    }

    // Insignia por cada misión completada
    $done = db_all('SELECT m.order_num FROM user_missions um JOIN missions m ON m.id = um.mission_id WHERE um.user_id = ?', [$userId]);
    foreach ($done as $d) {
        $push(award_badge($userId, 'mision-' . $d['order_num']));
    }

    // Mini-pruebas: todas con 90 % o más
    $quizStats = db_one('SELECT COUNT(*) AS n, SUM(COALESCE(p.best_percent,0) >= 90) AS ok
                           FROM games g LEFT JOIN user_game_progress p ON p.game_id=g.id AND p.user_id=?
                          WHERE g.is_quiz = 1', [$userId]);
    if ((int) $quizStats['n'] > 0 && (int) $quizStats['ok'] === (int) $quizStats['n']) {
        $push(award_badge($userId, 'mente-brillante'));
    }

    // Tres estrellas en todos los juegos
    $all3 = db_one('SELECT COUNT(*) AS n, SUM(COALESCE(p.best_stars,0) = 3) AS ok
                      FROM games g LEFT JOIN user_game_progress p ON p.game_id=g.id AND p.user_id=?', [$userId]);
    if ((int) $all3['n'] > 0 && (int) $all3['ok'] === (int) $all3['n']) {
        $push(award_badge($userId, 'coleccionista'));
    }

    $streak = (int) db_val('SELECT streak FROM users WHERE id = ?', [$userId]);
    if ($streak >= 3) $push(award_badge($userId, 'constante'));
    if ($streak >= 7) $push(award_badge($userId, 'imparable'));

    return $new;
}

/* ---------------------------------------------------------------------
 *  Logros
 * ------------------------------------------------------------------- */

function user_stats(int $userId): array
{
    $u = db_one('SELECT xp, streak, best_streak FROM users WHERE id = ?', [$userId]);
    return [
        'games_played'    => (int) db_val('SELECT COUNT(*) FROM game_attempts WHERE user_id=? AND status="finished"', [$userId]),
        'correct_answers' => (int) db_val('SELECT COUNT(*) FROM attempt_answers aa JOIN game_attempts a ON a.id=aa.attempt_id WHERE a.user_id=? AND aa.is_correct=1', [$userId]),
        'perfect_games'   => (int) db_val('SELECT COUNT(*) FROM game_attempts WHERE user_id=? AND status="finished" AND percent=100', [$userId]),
        'xp'              => (int) $u['xp'],
        'stars'           => (int) db_val('SELECT COALESCE(SUM(best_stars),0) FROM user_game_progress WHERE user_id=?', [$userId]),
        'missions'        => (int) db_val('SELECT COUNT(*) FROM user_missions WHERE user_id=?', [$userId]),
        'streak'          => (int) $u['best_streak'],
        'badges'          => (int) db_val('SELECT COUNT(*) FROM user_badges WHERE user_id=?', [$userId]),
    ];
}

function achievements_with_progress(int $userId): array
{
    $stats = user_stats($userId);
    $rows = db_all('SELECT a.*, ua.unlocked_at FROM achievements a
                 LEFT JOIN user_achievements ua ON ua.achievement_id=a.id AND ua.user_id=?
                  ORDER BY a.metric, a.goal', [$userId]);
    foreach ($rows as &$r) {
        $r['current']  = min((int) $r['goal'], $stats[$r['metric']] ?? 0);
        $r['progress'] = (int) floor($r['current'] / max(1, (int) $r['goal']) * 100);
    }
    return $rows;
}

function check_achievements(int $userId): array
{
    $new = [];
    // Se repite porque un logro puede dar XP que desbloquea otro logro de XP.
    for ($round = 0; $round < 3; $round++) {
        $found = false;
        foreach (achievements_with_progress($userId) as $a) {
            if ($a['unlocked_at'] === null && $a['current'] >= (int) $a['goal']) {
                db_exec('INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)', [$userId, $a['id']]);
                add_xp($userId, (int) $a['xp_reward'], (int) $a['coin_reward'], 'Logro: ' . $a['name']);
                $new[] = ['name' => $a['name'], 'icon' => $a['icon'], 'description' => $a['description'],
                          'xp' => (int) $a['xp_reward'], 'coins' => (int) $a['coin_reward']];
                $found = true;
            }
        }
        if (!$found) break;
    }
    return $new;
}

/* ---------------------------------------------------------------------
 *  Clasificación
 * ------------------------------------------------------------------- */

function leaderboard(string $period = 'global', ?int $grade = null, int $limit = 50, ?int $teacherId = null): array
{
    $where  = "u.role = 'student' AND u.active = 1";
    $params = [];
    if ($teacherId) {
        $where .= ' AND u.teacher_id = ?';
        $params[] = $teacherId;
    }
    if ($grade) {
        $where .= ' AND u.grade = ?';
        $params[] = $grade;
    }
    if ($period === 'semana') {
        $sql = "SELECT u.id, u.username, u.full_name, u.grade, u.section, u.avatar, u.frame, u.title, u.xp,
                       COALESCE(SUM(l.xp),0) AS score,
                       (SELECT COUNT(*) FROM user_badges b WHERE b.user_id=u.id) AS badges
                  FROM users u
             LEFT JOIN xp_log l ON l.user_id = u.id AND l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 WHERE $where
              GROUP BY u.id
              ORDER BY score DESC, u.xp DESC, u.id ASC
                 LIMIT $limit";
    } else {
        $sql = "SELECT u.id, u.username, u.full_name, u.grade, u.section, u.avatar, u.frame, u.title, u.xp,
                       u.xp AS score,
                       (SELECT COUNT(*) FROM user_badges b WHERE b.user_id=u.id) AS badges
                  FROM users u
                 WHERE $where
              ORDER BY u.xp DESC, u.id ASC
                 LIMIT $limit";
    }
    return db_all($sql, $params);
}

function user_rank(int $userId): ?int
{
    $u = db_one('SELECT xp, role, teacher_id FROM users WHERE id = ?', [$userId]);
    if (!$u || $u['role'] !== 'student') {
        return null;
    }
    // Posición entre los compañeros del mismo docente
    return 1 + (int) db_val("SELECT COUNT(*) FROM users WHERE role='student' AND teacher_id <=> ? AND (xp > ? OR (xp = ? AND id < ?))",
        [$u['teacher_id'], $u['xp'], $u['xp'], $userId]);
}
