<?php
/**
 * PhishHunter · Lógica de la Zona de refuerzo y la videoteca.
 * Igual que en las misiones, todo se corrige en el servidor: el navegador
 * nunca recibe las parejas, las cartas trampa ni las acciones correctas.
 */

const ARCADE_TYPES = [
    'memory'  => 'Memorama',
    'match'   => 'Relacionar',
    'builder' => 'Puzle de cartas',
    'office'  => 'Simulador',
];

const OFFICE_ACTIONS = ['atender', 'verificar', 'reportar', 'archivar', 'entregar'];

/** ¿Están creadas las tablas de la zona de refuerzo? (instalaciones antiguas) */
function arcade_ready(): bool
{
    static $ready = null;
    if ($ready === null) {
        try {
            $ready = (bool) db_val("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'arcade_games'");
        } catch (Throwable $e) {
            $ready = false;
        }
    }
    return $ready;
}

function completed_mission_orders(int $userId): array
{
    return array_map('intval', array_column(db_all(
        'SELECT m.order_num FROM user_missions um JOIN missions m ON m.id = um.mission_id WHERE um.user_id = ?', [$userId]), 'order_num'));
}

function arcade_games_with_progress(array $user): array
{
    $uid  = (int) $user['id'];
    $done = completed_mission_orders($uid);
    $rows = db_all('SELECT g.*, COALESCE(p.best_percent,0) AS best, COALESCE(p.best_stars,0) AS stars, COALESCE(p.plays,0) AS plays,
                           p.passed_at, p.grand_prize_at
                      FROM arcade_games g
                 LEFT JOIN user_arcade_progress p ON p.game_id = g.id AND p.user_id = ?
                  ORDER BY g.order_num', [$uid]);
    foreach ($rows as &$g) {
        $need = (int) $g['unlock_mission'];
        $g['unlocked'] = is_staff($user) || (can_play($user) && ($need === 0 || in_array($need, $done, true)));
    }
    return $rows;
}

function arcade_game_for(array $user, string $slug): ?array
{
    foreach (arcade_games_with_progress($user) as $g) {
        if ($g['slug'] === $slug) return $g;
    }
    return null;
}

/* ---------------------------------------------------------------------
 *  Utilidades
 * ------------------------------------------------------------------- */

function arcade_token(): string
{
    return bin2hex(random_bytes(4));
}

function norm_text(string $s): string
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'º' => '', '°' => '', 's/' => '', '.' => '', ',' => '', ' ' => '', '-' => '']);
    return $s;
}

function arcade_load(int $userId, int $attemptId): ?array
{
    $a = db_one('SELECT a.*, g.slug AS game_slug, g.type, g.title AS game_title, g.pass_percent, g.xp_reward, g.coin_reward, g.badge_slug, g.items_per_play
                   FROM arcade_attempts a JOIN arcade_games g ON g.id = a.game_id
                  WHERE a.id = ? AND a.user_id = ?', [$attemptId, $userId]);
    if ($a) $a['state'] = json_decode($a['state'], true);
    return $a;
}

function arcade_save_state(int $attemptId, array $state): void
{
    db_exec('UPDATE arcade_attempts SET state = ? WHERE id = ?', [json_encode($state, JSON_UNESCAPED_UNICODE), $attemptId]);
}

/* ---------------------------------------------------------------------
 *  Iniciar partida: construye la versión pública de cada juego
 * ------------------------------------------------------------------- */

function arcade_start(int $userId, array $game): array
{
    $items = db_all('SELECT id, data, explanation, difficulty FROM arcade_items WHERE game_id = ? ORDER BY RAND()', [$game['id']]);
    $items = array_slice($items, 0, max(1, (int) $game['items_per_play']));
    usort($items, function ($a, $b) { return (int) $a['difficulty'] <=> (int) $b['difficulty']; });

    $state  = ['type' => $game['type'], 'items' => array_map('intval', array_column($items, 'id'))];
    $public = [];

    switch ($game['type']) {
        case 'memory':
            $cards = [];
            $state['cards'] = [];
            foreach ($items as $it) {
            $d  = json_decode($it['data'], true);
                foreach (['a', 'b'] as $side) {
                    $t = arcade_token();
                    $state['cards'][$t] = ['item' => (int) $it['id'], 'side' => $side, 'text' => $d[$side]];
                    $cards[] = ['t' => $t, 'side' => $side];
                }
            }
            shuffle($cards);
            $state += ['open' => null, 'matched' => [], 'moves' => 0, 'misses' => 0];
            $public = ['cards' => $cards, 'pairs' => count($items)];
            break;

        case 'match':
            $state['rounds'] = [];
            $rounds = [];
            foreach ($items as $it) {
            $d  = json_decode($it['data'], true);
                $left = $right = $map = [];
                foreach ($d['pairs'] as $p) {
                    $l = 'L' . arcade_token();
                    $r = 'R' . arcade_token();
                    $left[]  = ['id' => $l, 'text' => $p[0]];
                    $right[] = ['id' => $r, 'text' => $p[1]];
                    $map[$l] = $r;
                }
                shuffle($left);
                shuffle($right);
                $state['rounds'][(string) $it['id']] = ['map' => $map, 'done' => false, 'points' => 0];
                $rounds[] = ['id' => (int) $it['id'], 'difficulty' => (int) $it['difficulty'], 'prompt' => $d['prompt'], 'left' => $left, 'right' => $right];
            }
            $public = ['rounds' => $rounds];
            break;

        case 'builder':
            $state['rounds'] = [];
            $rounds = [];
            foreach ($items as $it) {
            $d  = json_decode($it['data'], true);
                $cards = $priv = [];
                foreach ($d['cards'] as $c) {
                    $t = 'c' . arcade_token();
                    $priv[$t] = ['text' => $c[0], 'kind' => $c[1]];
                    $cards[] = ['id' => $t, 'text' => $c[0]];
                }
                shuffle($cards);
                $state['rounds'][(string) $it['id']] = ['cards' => $priv, 'done' => false, 'points' => 0];
                $rounds[] = ['id' => (int) $it['id'], 'difficulty' => (int) $it['difficulty'], 'goal' => $d['goal'], 'profile' => $d['profile'], 'cards' => $cards];
            }
            $public = ['rounds' => $rounds];
            break;

        case 'office':
            $it = $items[0];
            $d  = json_decode($it['data'], true);
            $docs = [];
            foreach ($d['docs'] as $doc) {
                $fields = [];
                foreach ($doc['fields'] as $f) {
                    $fields[] = ['id' => $f['id'], 'label' => $f['label'], 'type' => $f['type'], 'options' => $f['options'] ?? null];
                }
                $docs[] = ['id' => $doc['id'], 'title' => $doc['title'], 'icon' => $doc['icon'], 'fields' => $fields];
            }
            $msgs = [];
            foreach ($d['messages'] as $m) {
                $msgs[] = array_intersect_key($m, array_flip(['id', 'channel', 'from_name', 'from', 'subject', 'body', 'link', 'attachment', 'login']));
            }
            $state += ['item' => (int) $it['id'], 'actions' => [], 'docs' => []];
            $public = ['company' => $d['company'], 'role' => $d['role'], 'boss' => $d['boss'], 'note' => $d['note'],
                       'docs' => $docs, 'messages' => $msgs];
            break;
    }

    db_exec("DELETE FROM arcade_attempts WHERE user_id = ? AND game_id = ? AND status = 'playing'", [$userId, $game['id']]);
    db_exec('INSERT INTO arcade_attempts (user_id, game_id, state) VALUES (?,?,?)', [$userId, $game['id'], json_encode($state, JSON_UNESCAPED_UNICODE)]);

    return ['attempt_id' => (int) db()->lastInsertId(), 'data' => $public];
}

/* ---------------------------------------------------------------------
 *  Jugadas
 * ------------------------------------------------------------------- */

/** Memorama: voltear una carta. El texto solo se revela al voltear. */
function arcade_flip(array $att, string $token): array
{
    $st = $att['state'];
    if (!isset($st['cards'][$token]) || in_array($token, $st['matched'], true) || $st['open'] === $token) {
        return ['ok' => false, 'error' => 'Carta no válida.'];
    }
    $card = $st['cards'][$token];
    $res  = ['ok' => true, 'text' => $card['text'], 'side' => $card['side']];

    if ($st['open'] === null) {
        $st['open'] = $token;
    } else {
        $other = $st['cards'][$st['open']];
        $st['moves']++;
        $match = $other['item'] === $card['item'];
        $res['pair']  = [$st['open'], $token];
        $res['match'] = $match;
        if ($match) {
            $st['matched'][] = $st['open'];
            $st['matched'][] = $token;
            $res['explanation'] = (string) db_val('SELECT explanation FROM arcade_items WHERE id = ?', [$card['item']]);
        } else {
            $st['misses']++;
        }
        $st['open'] = null;
        $res['done'] = count($st['matched']) === count($st['cards']);
        $res['misses'] = $st['misses'];
    }
    arcade_save_state((int) $att['id'], $st);
    return $res;
}

/** Relacionar: corrige una ronda completa. */
function arcade_match_answer(array $att, int $itemId, array $links): array
{
    $st = $att['state'];
    $key = (string) $itemId;
    if (!isset($st['rounds'][$key]) || $st['rounds'][$key]['done']) {
        return ['ok' => false, 'error' => 'Ronda no válida.'];
    }
    $map = $st['rounds'][$key]['map'];
    $hits = 0;
    $result = [];
    foreach ($map as $l => $r) {
        $given = isset($links[$l]) && is_string($links[$l]) ? $links[$l] : null;
        $ok = $given === $r;
        if ($ok) $hits++;
        $result[$l] = ['ok' => $ok, 'right' => $r];
    }
    $points = round($hits / max(1, count($map)), 2);
    $st['rounds'][$key]['done'] = true;
    $st['rounds'][$key]['points'] = $points;
    arcade_save_state((int) $att['id'], $st);
    return ['ok' => true, 'correct' => $hits === count($map), 'points' => $points, 'hits' => $hits, 'total' => count($map),
            'result' => $result, 'explanation' => (string) db_val('SELECT explanation FROM arcade_items WHERE id = ?', [$itemId])];
}

/** Arma tu clave: revisa la contraseña armada con cartas. */
function arcade_builder_answer(array $att, int $itemId, array $cardIds): array
{
    $st = $att['state'];
    $key = (string) $itemId;
    if (!isset($st['rounds'][$key]) || $st['rounds'][$key]['done']) {
        return ['ok' => false, 'error' => 'Ronda no válida.'];
    }
    $cards = $st['rounds'][$key]['cards'];
    $cardIds = array_values(array_unique(array_filter($cardIds, function ($c) use ($cards) { return is_string($c) && isset($cards[$c]); })));
    if (!$cardIds) {
        return ['ok' => false, 'error' => 'Coloca al menos una carta.'];
    }
    $count = ['word' => 0, 'number' => 0, 'symbol' => 0, 'trap' => 0];
    $pw = '';
    $traps = [];
    foreach ($cardIds as $c) {
        $count[$cards[$c]['kind']]++;
        // Una palabra trampa cuenta como palabra (la trampa se penaliza aparte).
        if ($cards[$c]['kind'] === 'trap' && !preg_match('/^\d+$/', $cards[$c]['text'])) $count['word']++;
        $pw .= $cards[$c]['text'];
        if ($cards[$c]['kind'] === 'trap') $traps[] = $c;
    }
    $len = mb_strlen($pw, 'UTF-8');
    $rules = [
        ['ok' => $count['trap'] === 0, 'text' => 'Sin datos personales ni claves débiles'],
        ['ok' => $count['word'] >= 3,  'text' => 'Al menos 3 palabras sin relación'],
        ['ok' => $count['number'] >= 1, 'text' => 'Al menos un número (que no sea un dato tuyo)'],
        ['ok' => $count['symbol'] >= 1, 'text' => 'Al menos un signo'],
        ['ok' => $len >= 14,             'text' => '14 caracteres o más (tiene ' . $len . ')'],
    ];
    $passed = count(array_filter(array_slice($rules, 1), function ($r) { return $r['ok']; }));
    $points = $count['trap'] > 0 ? 0.0 : round($passed / 4, 2);
    $trapInfo = [];
    foreach ($traps as $t) $trapInfo[] = $cards[$t]['text'];

    $st['rounds'][$key]['done'] = true;
    $st['rounds'][$key]['points'] = $points;
    arcade_save_state((int) $att['id'], $st);
    return ['ok' => true, 'correct' => $points >= 1.0, 'points' => $points, 'password' => $pw, 'rules' => $rules,
            'traps' => $traps, 'trap_texts' => $trapInfo];
}

/** Oficina: registrar lo que hizo el jugador con un mensaje. */
function arcade_office_action(array $att, string $msgId, string $action): array
{
    $st = $att['state'];
    if (!in_array($action, OFFICE_ACTIONS, true)) {
        return ['ok' => false, 'error' => 'Acción no válida.'];
    }
    $data = json_decode((string) db_val('SELECT data FROM arcade_items WHERE id = ?', [$st['item']]), true);
    $ids = array_column($data['messages'], 'id');
    if (!in_array($msgId, $ids, true) || isset($st['actions'][$msgId])) {
        return ['ok' => false, 'error' => 'Ese mensaje ya fue atendido.'];
    }
    $st['actions'][$msgId] = $action;
    arcade_save_state((int) $att['id'], $st);
    return ['ok' => true, 'handled' => count($st['actions']), 'total' => count($ids)];
}

/** Oficina: guardar un documento terminado. */
function arcade_office_doc(array $att, string $docId, array $values): array
{
    $st = $att['state'];
    $data = json_decode((string) db_val('SELECT data FROM arcade_items WHERE id = ?', [$st['item']]), true);
    $doc = null;
    foreach ($data['docs'] as $d) if ($d['id'] === $docId) $doc = $d;
    if (!$doc || isset($st['docs'][$docId])) {
        return ['ok' => false, 'error' => 'Documento no válido o ya enviado.'];
    }
    $clean = [];
    foreach ($doc['fields'] as $f) {
        $v = $values[$f['id']] ?? '';
        $clean[$f['id']] = is_string($v) ? mb_substr(trim($v), 0, 80, 'UTF-8') : '';
    }
    $st['docs'][$docId] = $clean;
    arcade_save_state((int) $att['id'], $st);
    return ['ok' => true, 'docs_done' => count($st['docs']), 'docs_total' => count($data['docs'])];
}

/** Calcula el informe completo de la oficina. */
function arcade_office_report(array $st): array
{
    $data = json_decode((string) db_val('SELECT data FROM arcade_items WHERE id = ?', [$st['item']]), true);
    $msgRows = [];
    $msgPts = 0;
    $fell = 0;
    $leaked = false;
    foreach ($data['messages'] as $m) {
        $act = $st['actions'][$m['id']] ?? null;
        $pts = 0.0;
        if ($act === $m['best']) $pts = 1.0;
        elseif ($act && in_array($act, $m['ok'], true)) $pts = 0.6;
        $isTrap = $m['best'] !== 'atender';
        if ($isTrap && in_array($act, ['atender', 'entregar'], true)) $fell++;
        if ($act === 'entregar') $leaked = true;
        $msgPts += $pts;
        $msgRows[] = [
            'id' => $m['id'], 'from_name' => $m['from_name'], 'subject' => $m['subject'] ?? mb_substr($m['body'], 0, 60, 'UTF-8') . '…',
            'channel' => $m['channel'], 'phishing' => $isTrap, 'action' => $act, 'best' => $m['best'], 'points' => $pts,
            'feedback' => $act ? ($m['fb'][$act] ?? '') : 'No atendiste este mensaje.',
        ];
    }
    $docRows = [];
    $docPts = 0;
    $moneyLost = false;
    foreach ($data['docs'] as $d) {
        $vals = $st['docs'][$d['id']] ?? null;
        $ok = 0;
        $fields = [];
        foreach ($d['fields'] as $f) {
            $v = $vals[$f['id']] ?? '';
            $good = $vals !== null && norm_text($v) === norm_text($f['answer']);
            if (!empty($f['trap']) && norm_text($v) === norm_text($f['trap'])) $moneyLost = true;
            if ($good) $ok++;
            $fields[] = ['label' => $f['label'], 'value' => $v, 'answer' => $f['answer'], 'ok' => $good];
        }
        $p = $ok / max(1, count($d['fields']));
        $docPts += $p;
        $docRows[] = ['title' => $d['title'], 'icon' => $d['icon'], 'sent' => $vals !== null, 'points' => round($p, 2), 'fields' => $fields];
    }
    $msgPct = $msgPts / max(1, count($data['messages']));
    $docPct = $docPts / max(1, count($data['docs']));
    $percent = (int) round(($msgPct * 0.7 + $docPct * 0.3) * 100);
    if ($moneyLost) $fell++;
    return ['percent' => $percent, 'messages' => $msgRows, 'docs' => $docRows, 'fell' => $fell, 'leaked' => $leaked,
            'money_lost' => $moneyLost, 'msg_percent' => (int) round($msgPct * 100), 'doc_percent' => (int) round($docPct * 100)];
}

/* ---------------------------------------------------------------------
 *  Terminar partida y repartir recompensas
 * ------------------------------------------------------------------- */

function arcade_finish(int $userId, array $att): array
{
    $st = $att['state'];
    $report = null;
    switch ($att['type']) {
        case 'memory':
            if (count($st['matched']) !== count($st['cards'])) {
                return ['ok' => false, 'error' => 'Aún quedan parejas por encontrar.'];
            }
            $pairs = count($st['cards']) / 2;
            $extra = max(0, (int) $st['misses'] - (int) $pairs); // errores de cortesía = n.º de parejas
            $percent = max(0, 100 - $extra * 8);
            $summary = ['misses' => (int) $st['misses'], 'free' => (int) $pairs, 'moves' => (int) $st['moves']];
            break;
        case 'match':
        case 'builder':
            $pts = array_sum(array_column($st['rounds'], 'points'));
            $percent = (int) round($pts / max(1, count($st['rounds'])) * 100);
            $summary = ['rounds' => count($st['rounds']), 'perfect' => count(array_filter($st['rounds'], function ($r) { return $r['points'] >= 1; }))];
            break;
        case 'office':
            $report = arcade_office_report($st);
            $percent = $report['percent'];
            $summary = [];
            break;
        default:
            return ['ok' => false, 'error' => 'Tipo de juego desconocido.'];
    }

    $pass   = (int) $att['pass_percent'];
    $stars  = stars_for($percent, $pass);
    $passed = $percent >= $pass;

    $prog = db_one('SELECT * FROM user_arcade_progress WHERE user_id = ? AND game_id = ?', [$userId, $att['game_id']]);
    $bestPercent = $prog ? (int) $prog['best_percent'] : 0;
    $bestStars   = $prog ? (int) $prog['best_stars'] : 0;
    $firstPass   = $passed && (!$prog || $prog['passed_at'] === null);

    $baseXp = (int) round((int) $att['xp_reward'] * $percent / 100);
    $xp     = $percent > $bestPercent ? $baseXp : (int) ceil($baseXp * 0.3);
    if ($firstPass) $xp += 15;
    $coins  = 3 + max(0, $stars - $bestStars) * (int) round((int) $att['coin_reward'] / 3) + ($percent === 100 ? 5 : 0);

    // Gran premio del simulador: 85 %+, sin entregar contraseñas, dinero ni caer en engaños.
    $grand = false;
    if ($att['type'] === 'office' && $percent >= $pass && $report['fell'] === 0 && !($prog && $prog['grand_prize_at'])) {
        $grand = true;
        $xp += 300;
        $coins += 150;
    }

    db_exec("UPDATE arcade_attempts SET status='finished', percent=?, stars=?, xp_earned=?, coins_earned=?, grand_prize=?, finished_at=NOW() WHERE id=?",
        [$percent, $stars, $xp, $coins, $grand ? 1 : 0, $att['id']]);
    if ($prog) {
        db_exec('UPDATE user_arcade_progress SET best_percent=GREATEST(best_percent,?), best_stars=GREATEST(best_stars,?), plays=plays+1,
                        passed_at = IF(passed_at IS NULL AND ?, NOW(), passed_at),
                        grand_prize_at = IF(grand_prize_at IS NULL AND ?, NOW(), grand_prize_at)
                  WHERE user_id=? AND game_id=?', [$percent, $stars, $passed ? 1 : 0, $grand ? 1 : 0, $userId, $att['game_id']]);
    } else {
        db_exec('INSERT INTO user_arcade_progress (user_id, game_id, best_percent, best_stars, plays, passed_at, grand_prize_at) VALUES (?,?,?,?,1,?,?)',
            [$userId, $att['game_id'], $percent, $stars, $passed ? date('Y-m-d H:i:s') : null, $grand ? date('Y-m-d H:i:s') : null]);
    }

    touch_streak($userId);
    $lv = add_xp($userId, $xp, $coins, ($grand ? ' GRAN PREMIO: ' : 'Refuerzo:') . $att['game_title']);

    $badges = [];
    if ($att['badge_slug'] && (($att['type'] !== 'office' && $percent === 100) || $grand)) {
        $b = award_badge($userId, $att['badge_slug']);
        if ($b) $badges[] = $b;
    }
    if ($grand) {
        foreach (['avatar-trofeo', 'frame-diamante', 'title-oficina'] as $slug) {
            db_exec('INSERT IGNORE INTO user_rewards (user_id, reward_id) SELECT ?, id FROM rewards WHERE slug = ?', [$userId, $slug]);
        }
    }
    $ach = check_achievements($userId);

    $u = db_one('SELECT xp, coins, streak FROM users WHERE id = ?', [$userId]);
    return [
        'ok' => true, 'percent' => $percent, 'stars' => $stars, 'passed' => $passed, 'pass_percent' => $pass,
        'xp' => $xp, 'coins' => $coins, 'improved' => $percent > $bestPercent, 'summary' => $summary,
        'report' => $report, 'grand_prize' => $grand, 'grand_before' => (bool) ($prog['grand_prize_at'] ?? false),
        'badges' => $badges, 'achievements' => $ach,
        'level_up' => $lv['leveled_up'] ? $lv['level'] : null,
        'user' => ['xp' => (int) $u['xp'], 'coins' => (int) $u['coins'], 'streak' => (int) $u['streak']],
    ];
}

/* ---------------------------------------------------------------------
 *  Videoteca
 * ------------------------------------------------------------------- */

function videos_for(int $userId, ?string $missionSlug = null): array
{
    if (!arcade_ready()) return [];
    $sql = 'SELECT v.*, uv.watched_at, COALESCE(uv.quiz_correct,0) AS quiz_correct, COALESCE(uv.quiz_tries,0) AS quiz_tries
              FROM videos v LEFT JOIN user_videos uv ON uv.video_id = v.id AND uv.user_id = ?';
    $params = [$userId];
    if ($missionSlug !== null) {
        $sql .= ' WHERE v.mission_slug = ?';
        $params[] = $missionSlug;
    }
    $rows = db_all($sql . ' ORDER BY v.order_num', $params);
    foreach ($rows as &$r) {
        $q = json_decode($r['question'], true);
        $r['q'] = $q['q'];
        $r['options'] = $q['options'];
        unset($r['question'], $r['answer'], $r['explanation']); // nunca al navegador
    }
    return $rows;
}

function video_watched(int $userId, int $videoId): array
{
    $v = db_one('SELECT id, title FROM videos WHERE id = ?', [$videoId]);
    if (!$v) return ['ok' => false, 'error' => 'Video no encontrado.'];
    $row = db_one('SELECT watched_at FROM user_videos WHERE user_id = ? AND video_id = ?', [$userId, $videoId]);
    if ($row && $row['watched_at']) return ['ok' => true, 'new' => false];
    db_exec('INSERT INTO user_videos (user_id, video_id, watched_at) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE watched_at = COALESCE(watched_at, NOW())', [$userId, $videoId]);
    add_xp($userId, 10, 3, 'Video visto: ' . $v['title']);
    touch_streak($userId);
    return ['ok' => true, 'new' => true, 'xp' => 10, 'coins' => 3];
}

function video_answer(int $userId, int $videoId, int $answer): array
{
    $v = db_one('SELECT * FROM videos WHERE id = ?', [$videoId]);
    if (!$v) return ['ok' => false, 'error' => 'Video no encontrado.'];
    $row = db_one('SELECT * FROM user_videos WHERE user_id = ? AND video_id = ?', [$userId, $videoId]);
    if ($row && (int) $row['quiz_correct'] === 1) return ['ok' => false, 'error' => 'Ya respondiste bien esta pregunta.'];
    $correct = $answer === (int) $v['answer'];
    $tries = $row ? (int) $row['quiz_tries'] + 1 : 1;
    db_exec('INSERT INTO user_videos (user_id, video_id, quiz_correct, quiz_tries) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE quiz_correct = VALUES(quiz_correct), quiz_tries = VALUES(quiz_tries)',
        [$userId, $videoId, $correct ? 1 : 0, $tries]);
    $xp = $coins = 0;
    if ($correct) {
        $xp = $tries === 1 ? 15 : 5;
        $coins = $tries === 1 ? 5 : 1;
        add_xp($userId, $xp, $coins, 'Repaso de video: ' . $v['title']);
    }
    $badges = [];
    if ($correct) {
        $pending = (int) db_val('SELECT COUNT(*) FROM videos v LEFT JOIN user_videos uv ON uv.video_id = v.id AND uv.user_id = ?
                                  WHERE uv.watched_at IS NULL OR COALESCE(uv.quiz_correct,0) = 0', [$userId]);
        if ($pending === 0) {
            $b = award_badge($userId, 'cinefilo');
            if ($b) $badges[] = $b;
        }
    }
    $ach = $correct ? check_achievements($userId) : [];
    return ['ok' => true, 'correct' => $correct, 'answer' => $correct ? (int) $v['answer'] : null,
            'explanation' => $correct || $tries >= 2 ? $v['explanation'] : 'Revisa el video y vuelve a intentarlo.',
            'xp' => $xp, 'coins' => $coins, 'badges' => $badges, 'achievements' => $ach];
}

/* ---------------------------------------------------------------------
 *  Chispa, la guía
 * ------------------------------------------------------------------- */

function mascot_img(string $mood = 'hola', string $cls = ''): string
{
    $mood = in_array($mood, ['hola', 'feliz', 'piensa', 'alerta', 'fiesta'], true) ? $mood : 'hola';
    return '<img class="mascot ' . e($cls) . '" src="' . e(url('assets/img/mascot/chispa-' . $mood . '.svg')) . '" alt="Chispa" width="120" height="132">';
}

/** Bloque de Chispa con globo de diálogo. */
function mascot_say(string $text, string $mood = 'hola', string $cls = ''): string
{
    return '<div class="chispa-say ' . e($cls) . '">' . mascot_img($mood) .
           '<div class="chispa-bubble"><b>Chispa</b><p>' . $text . '</p></div></div>';
}

/** Tarjeta de video (miniatura que se convierte en reproductor) + pregunta de repaso. */
function video_card_html(array $v): string
{
    $watched = !empty($v['watched_at']);
    $solved  = (int) $v['quiz_correct'] === 1;
    $h  = '<article class="video-card-item ' . ($watched ? 'watched' : '') . ' ' . ($solved ? 'solved' : '') . '" data-video="' . (int) $v['id'] . '" data-yt="' . e($v['youtube_id']) . '">';
    $h .= '<div class="yt-frame"><button type="button" class="yt-facade" aria-label="Reproducir: ' . e($v['title']) . '">';
    $h .= '<img src="https://i.ytimg.com/vi/' . e($v['youtube_id']) . '/hqdefault.jpg" alt="" loading="lazy" onerror="this.style.display=\'none\'">';
    $h .= '<span class="yt-play"><i class="bx bx-play"></i></span><span class="yt-time"><i class="bx bxs-time-five"></i> ' . (int) $v['minutes'] . ' min</span></button></div>';
    $h .= '<div class="vc-body"><div class="vc-head"><h3>' . e($v['title']) . '</h3>';
    $h .= '<span class="vc-state">' . ($solved ? '<i class="bx bxs-check-circle"></i> Completado' : ($watched ? '<i class="bx bxs-show"></i> Visto' : '<i class="bx bxs-star"></i> +10 XP')) . '</span></div>';
    $h .= '<p class="muted small"><i class="bx bxs-tv"></i> ' . e($v['channel']) . ' · ' . e($v['summary']) . '</p>';
    $h .= '<button type="button" class="btn btn-soft btn-sm vc-mark" ' . ($watched ? 'hidden' : 'disabled') . '><i class="bx bxs-show"></i> Ya lo vi</button>';
    $h .= '<div class="vc-quiz" ' . ($watched ? '' : 'hidden') . '><p class="decide-q"><i class="bx bxs-brain"></i> ' . e($v['q']) . '</p><div class="options">';
    foreach ($v['options'] as $i => $o) {
        $h .= '<button type="button" class="option" data-i="' . $i . '" ' . ($solved ? 'disabled' : '') . '><span class="opt-letter">' . 'ABCD'[$i] . '</span><span>' . e($o) . '</span></button>';
    }
    $h .= '</div><div class="vc-fb">' . ($solved ? '<span class="tag-good"><i class="bx bx-check"></i> Respondiste bien esta pregunta</span>' : '') . '</div></div>';
    $h .= '</div></article>';
    return $h;
}
