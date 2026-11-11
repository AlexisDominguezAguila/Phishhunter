<?php
/**
 * Utilidades para los seeders de contenido (misiones, juegos y casos).
 */

function seed_json($v): string
{
    return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function seed_mission(PDO $pdo, array $m): int
{
    $st = $pdo->prepare('INSERT INTO missions (slug, order_num, title, subtitle, description, icon, color, dimension, objectives, lesson, xp_reward, coin_reward)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $st->execute([$m['slug'], $m['order'], $m['title'], $m['subtitle'], $m['description'], $m['icon'], $m['color'],
        $m['dimension'], seed_json($m['objectives']), seed_json($m['lesson']), $m['xp'] ?? 100, $m['coins'] ?? 30]);
    return (int) $pdo->lastInsertId();
}

function seed_game(PDO $pdo, int $missionId, array $g): int
{
    $st = $pdo->prepare('INSERT INTO games (mission_id, slug, type, title, description, instructions, icon, order_num, items_per_play, pass_percent, dimension, is_quiz)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $st->execute([$missionId, $g['slug'], $g['type'], $g['title'], $g['description'], $g['instructions'], $g['icon'],
        $g['order'], $g['per_play'], $g['pass'] ?? 60, $g['dimension'], $g['type'] === 'quiz' ? 1 : 0]);
    $gameId = (int) $pdo->lastInsertId();

    $st = $pdo->prepare('INSERT INTO game_items (game_id, content, solution, explanation, difficulty, dimension) VALUES (?,?,?,?,?,?)');
    foreach ($g['items'] as $it) {
        $st->execute([$gameId, seed_json($it['c']), seed_json($it['s']), $it['e'], $it['d'] ?? 1, $it['dim'] ?? $g['dimension']]);
    }
    return $gameId;
}
