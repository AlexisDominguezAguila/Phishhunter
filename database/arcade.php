<?php
/**
 * PhishHunter · Zona de refuerzo (videoteca + minijuegos extra)
 *
 * arcade_install() crea las tablas si no existen y (re)carga el contenido de
 * database/arcade_content.php SIN borrar usuarios ni progreso. La usan:
 *   - database/seeders/11_arcade.php  (instalación nueva)
 *   - upgrade.php / php database/upgrade.php  (instalación existente)
 */

function arcade_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS videos (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug          VARCHAR(60) NOT NULL UNIQUE,
        mission_slug  VARCHAR(60) NULL,
        youtube_id    VARCHAR(20) NOT NULL,
        title         VARCHAR(150) NOT NULL,
        channel       VARCHAR(100) NOT NULL,
        minutes       TINYINT UNSIGNED NOT NULL DEFAULT 2,
        summary       VARCHAR(300) NOT NULL,
        question      JSON NOT NULL COMMENT 'Pregunta de repaso y opciones',
        answer        TINYINT UNSIGNED NOT NULL,
        explanation   VARCHAR(400) NOT NULL,
        order_num     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        INDEX idx_videos_mission (mission_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_videos (
        user_id         INT UNSIGNED NOT NULL,
        video_id        INT UNSIGNED NOT NULL,
        watched_at      DATETIME NULL,
        quiz_correct    TINYINT(1) NOT NULL DEFAULT 0,
        quiz_tries      TINYINT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (user_id, video_id),
        CONSTRAINT fk_uv_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_uv_video FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS arcade_games (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug          VARCHAR(60) NOT NULL UNIQUE,
        type            ENUM('memory','match','builder','office') NOT NULL,
        title           VARCHAR(120) NOT NULL,
        description     VARCHAR(255) NOT NULL,
        instructions    TEXT NOT NULL,
        icon            VARCHAR(40) NOT NULL,
        color           VARCHAR(20) NOT NULL,
        order_num       TINYINT UNSIGNED NOT NULL,
        unlock_mission  TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'N.º de misión a completar (0 = libre)',
        items_per_play  TINYINT UNSIGNED NOT NULL DEFAULT 4,
        pass_percent    TINYINT UNSIGNED NOT NULL DEFAULT 60,
        xp_reward       INT UNSIGNED NOT NULL DEFAULT 60,
        coin_reward     INT UNSIGNED NOT NULL DEFAULT 20,
        badge_slug      VARCHAR(60) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS arcade_items (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        game_id     INT UNSIGNED NOT NULL,
        data        JSON NOT NULL COMMENT 'Contenido completo (solo servidor)',
        explanation TEXT NOT NULL,
        difficulty  TINYINT UNSIGNED NOT NULL DEFAULT 1,
        CONSTRAINT fk_aitems_game FOREIGN KEY (game_id) REFERENCES arcade_games(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS arcade_attempts (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id         INT UNSIGNED NOT NULL,
        game_id     INT UNSIGNED NOT NULL,
        state         JSON NOT NULL COMMENT 'Estado privado de la partida',
        status        ENUM('playing','finished') NOT NULL DEFAULT 'playing',
        percent       TINYINT UNSIGNED NOT NULL DEFAULT 0,
        stars         TINYINT UNSIGNED NOT NULL DEFAULT 0,
        xp_earned     INT UNSIGNED NOT NULL DEFAULT 0,
        coins_earned  INT UNSIGNED NOT NULL DEFAULT 0,
        grand_prize   TINYINT(1) NOT NULL DEFAULT 0,
        started_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        finished_at   DATETIME NULL,
        INDEX idx_aatt_user (user_id, game_id),
        CONSTRAINT fk_aatt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_aatt_game FOREIGN KEY (game_id) REFERENCES arcade_games(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_arcade_progress (
        user_id         INT UNSIGNED NOT NULL,
        game_id     INT UNSIGNED NOT NULL,
        best_percent    TINYINT UNSIGNED NOT NULL DEFAULT 0,
        best_stars      TINYINT UNSIGNED NOT NULL DEFAULT 0,
        plays           INT UNSIGNED NOT NULL DEFAULT 0,
        passed_at       DATETIME NULL,
        grand_prize_at  DATETIME NULL,
        PRIMARY KEY (user_id, game_id),
        CONSTRAINT fk_uap_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_uap_game FOREIGN KEY (game_id) REFERENCES arcade_games(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** Instalaciones antiguas: ensancha columnas de iconos (nombres de Boxicons). */
function icon_columns_upgrade(PDO $pdo): void
{
    foreach (['levels', 'missions', 'games', 'badges', 'achievements', 'arcade_games'] as $t) {
        try { $pdo->exec("ALTER TABLE `$t` MODIFY icon VARCHAR(40) NOT NULL"); } catch (Throwable $e) { /* ya estaba */ }
    }
}

function arcade_seed(PDO $pdo): array
{
    $content = require __DIR__ . '/arcade_content.php';
    $j = function ($v) { return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); };

    /* ---------- Videos ---------- */
    $st = $pdo->prepare('INSERT INTO videos (slug, mission_slug, youtube_id, title, channel, minutes, summary, question, answer, explanation, order_num)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?)
                         ON DUPLICATE KEY UPDATE mission_slug=VALUES(mission_slug), youtube_id=VALUES(youtube_id), title=VALUES(title),
                            channel=VALUES(channel), minutes=VALUES(minutes), summary=VALUES(summary), question=VALUES(question),
                            answer=VALUES(answer), explanation=VALUES(explanation), order_num=VALUES(order_num)');
    $slugs = [];
    foreach ($content['videos'] as $i => $v) {
        $st->execute([$v['slug'], $v['mission'], $v['youtube'], $v['title'], $v['channel'], $v['minutes'], $v['summary'],
            $j(['q' => $v['q'], 'options' => $v['options']]), $v['answer'], $v['explain'], $i + 1]);
        $slugs[] = $v['slug'];
    }
    $in = implode(',', array_fill(0, count($slugs), '?'));
    $pdo->prepare("DELETE FROM videos WHERE slug NOT IN ($in)")->execute($slugs);

    /* ---------- Juegos y casos ---------- */
    $gst = $pdo->prepare('INSERT INTO arcade_games (slug, type, title, description, instructions, icon, color, order_num, unlock_mission,
                              items_per_play, pass_percent, xp_reward, coin_reward, badge_slug)
                          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE type=VALUES(type), title=VALUES(title), description=VALUES(description),
                              instructions=VALUES(instructions), icon=VALUES(icon), color=VALUES(color), order_num=VALUES(order_num),
                              unlock_mission=VALUES(unlock_mission), items_per_play=VALUES(items_per_play), pass_percent=VALUES(pass_percent),
                              xp_reward=VALUES(xp_reward), coin_reward=VALUES(coin_reward), badge_slug=VALUES(badge_slug)');
    $ist = $pdo->prepare('INSERT INTO arcade_items (game_id, data, explanation, difficulty) VALUES (?,?,?,?)');
    $gameSlugs = [];
    $itemCount = 0;
    foreach ($content['games'] as $g) {
        $gst->execute([$g['slug'], $g['type'], $g['title'], $g['description'], $g['instructions'], $g['icon'], $g['color'],
            $g['order'], $g['unlock'], $g['per_play'], $g['pass'], $g['xp'], $g['coins'], $g['badge'] ?? null]);
        $gid = (int) $pdo->query('SELECT id FROM arcade_games WHERE slug = ' . $pdo->quote($g['slug']))->fetchColumn();
        $gameSlugs[] = $g['slug'];
        // Los casos se recargan; se descartan partidas a medio jugar de ese juego.
        $pdo->prepare("DELETE FROM arcade_attempts WHERE game_id = ? AND status = 'playing'")->execute([$gid]);
        $pdo->prepare('DELETE FROM arcade_items WHERE game_id = ?')->execute([$gid]);
        foreach ($g['items'] as $it) {
            $ist->execute([$gid, $j($it), $it['e'] ?? '', $it['d'] ?? 1]);
            $itemCount++;
        }
    }
    $in = implode(',', array_fill(0, count($gameSlugs), '?'));
    $pdo->prepare("DELETE FROM arcade_games WHERE slug NOT IN ($in)")->execute($gameSlugs);

    /* ---------- Insignias nuevas ---------- */
    $badges = [
        ['memoria-de-acero',  'Memoria de acero',     'Terminaste un memorama con 100 %.',                                        'bxs-brain', '#8BC34A', 'habilidad'],
        ['conector',          'Gran conector',        'Relacionaste todo perfecto en «Relaciona y protege».',                     'bx-link', '#FFC83D', 'habilidad'],
        ['arquitecto-claves', 'Arquitecto de claves', 'Armaste contraseñas perfectas sin tocar ninguna carta trampa.',            'bxs-extension', '#2BB39A', 'habilidad'],
        ['empleado-del-ano',  'Empleado del año',     'Ganaste el GRAN PREMIO del Simulador de oficina sin caer en ningún engaño.', 'bxs-trophy', '#5B6CFF', 'especial'],
        ['cinefilo',          'Cinéfilo seguro',      'Viste todos los videos de la videoteca y acertaste sus preguntas.',        'bxs-movie-play', '#E0457B', 'especial'],
    ];
    $bst = $pdo->prepare('INSERT INTO badges (slug, name, description, icon, color, category) VALUES (?,?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), icon=VALUES(icon), color=VALUES(color), category=VALUES(category)');
    foreach ($badges as $b) $bst->execute($b);

    /* ---------- Premios exclusivos de la oficina (no se venden: min_level 99) ---------- */
    $rewards = [
        ['avatar-trofeo',   'avatar', 'Campeón de oficina',     'bxs-trophy', 999, 99],
        ['frame-diamante',  'frame',  'Marco diamante',         '#5B6CFF', 999, 99],
        ['title-oficina',   'title',  'Guardián de la oficina', 'Guardián de la oficina', 999, 99],
    ];
    $rst = $pdo->prepare('INSERT INTO rewards (slug, type, name, value, cost, min_level) VALUES (?,?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE name=VALUES(name), value=VALUES(value), cost=VALUES(cost), min_level=VALUES(min_level)');
    foreach ($rewards as $r) $rst->execute($r);

    return ['videos' => count($content['videos']), 'games' => count($content['games']), 'items' => $itemCount];
}

function arcade_install(PDO $pdo): array
{
    arcade_schema($pdo);
    icon_columns_upgrade($pdo);
    return arcade_seed($pdo);
}
