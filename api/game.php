<?php
/**
 * API de partidas (JSON).
 *   action=start  game_id                   -> crea la partida y devuelve los casos
 *   action=answer attempt_id item_id answer -> corrige un caso y devuelve la retroalimentación
 *   action=finish attempt_id                -> cierra la partida y reparte recompensas
 */
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_post()) {
    json_response(['ok' => false, 'error' => 'Método no permitido'], 405);
}
$user = require_login();
csrf_check();

$input  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';
$uid    = (int) $user['id'];

try {
    switch ($action) {
        case 'start':
            $game = db_one('SELECT * FROM games WHERE id = ?', [(int) ($input['game_id'] ?? 0)]);
            if (!$game || !mission_is_unlocked($user, (int) $game['mission_id'])) {
                json_response(['ok' => false, 'error' => 'Juego no disponible.'], 403);
            }
            // Descarta partidas abandonadas del mismo juego
            db_exec('DELETE FROM game_attempts WHERE user_id=? AND game_id=? AND status="playing"', [$uid, $game['id']]);
            json_response(['ok' => true] + start_attempt($uid, $game));

        case 'answer':
            $res = answer_attempt($uid, (int) ($input['attempt_id'] ?? 0), (int) ($input['item_id'] ?? 0), $input['answer'] ?? null);
            json_response($res, $res['ok'] ? 200 : 400);

        case 'finish':
            $res = finish_attempt($uid, (int) ($input['attempt_id'] ?? 0));
            if ($res['ok']) {
                $u = current_user(true);
                $res['user'] = ['xp' => (int) $u['xp'], 'coins' => (int) $u['coins'], 'streak' => (int) $u['streak']];
            }
            json_response($res, $res['ok'] ? 200 : 400);

        default:
            json_response(['ok' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => APP_DEBUG ? $e->getMessage() : 'Error interno'], 500);
}
