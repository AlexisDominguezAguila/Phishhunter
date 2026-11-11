-- =====================================================================
--  PhishHunter · Esquema de base de datos
--  Motor: MySQL 5.7+ / MariaDB 10.3+  ·  Charset: utf8mb4
--  Uso: lo ejecuta install.php o database/seed.php automáticamente.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS class_groups;
DROP TABLE IF EXISTS user_videos;
DROP TABLE IF EXISTS videos;
DROP TABLE IF EXISTS user_arcade_progress;
DROP TABLE IF EXISTS arcade_attempts;
DROP TABLE IF EXISTS arcade_items;
DROP TABLE IF EXISTS arcade_games;
DROP TABLE IF EXISTS xp_log;
DROP TABLE IF EXISTS assessment_answers;
DROP TABLE IF EXISTS assessments;
DROP TABLE IF EXISTS survey_items;
DROP TABLE IF EXISTS user_rewards;
DROP TABLE IF EXISTS rewards;
DROP TABLE IF EXISTS user_achievements;
DROP TABLE IF EXISTS achievements;
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS badges;
DROP TABLE IF EXISTS user_missions;
DROP TABLE IF EXISTS user_game_progress;
DROP TABLE IF EXISTS attempt_answers;
DROP TABLE IF EXISTS game_attempts;
DROP TABLE IF EXISTS game_items;
DROP TABLE IF EXISTS games;
DROP TABLE IF EXISTS missions;
DROP TABLE IF EXISTS levels;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
--  Usuarios (estudiantes y docentes)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username         VARCHAR(40)  NOT NULL UNIQUE,
    full_name        VARCHAR(120) NOT NULL,
    email            VARCHAR(150) NULL,
    password_hash    VARCHAR(255) NOT NULL,
    role             ENUM('student','admin') NOT NULL DEFAULT 'student',
    grade            TINYINT UNSIGNED NULL COMMENT '1.º a 5.º de secundaria',
    section          VARCHAR(5) NULL,
    avatar           VARCHAR(40) NOT NULL DEFAULT 'avatar-fox',
    frame            VARCHAR(40) NULL,
    title            VARCHAR(40) NULL,
    xp               INT UNSIGNED NOT NULL DEFAULT 0,
    coins            INT UNSIGNED NOT NULL DEFAULT 0,
    streak           INT UNSIGNED NOT NULL DEFAULT 0,
    best_streak      INT UNSIGNED NOT NULL DEFAULT 0,
    last_active_date DATE NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_xp (xp),
    INDEX idx_users_grade (grade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Niveles de experiencia (rango del jugador)
-- ---------------------------------------------------------------------
CREATE TABLE levels (
    level    TINYINT UNSIGNED PRIMARY KEY,
    name     VARCHAR(60) NOT NULL,
    min_xp   INT UNSIGNED NOT NULL,
    icon     VARCHAR(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Misiones (una por sesión de intervención, 6 en total)
-- ---------------------------------------------------------------------
CREATE TABLE missions (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(60) NOT NULL UNIQUE,
    order_num    TINYINT UNSIGNED NOT NULL,
    title        VARCHAR(120) NOT NULL,
    subtitle     VARCHAR(200) NOT NULL,
    description  TEXT NOT NULL,
    icon         VARCHAR(40) NOT NULL,
    color        VARCHAR(20) NOT NULL,
    dimension    ENUM('reconocimiento','decisiones','proteccion','integrador') NOT NULL,
    objectives   JSON NOT NULL COMMENT 'Lista de objetivos de aprendizaje',
    lesson       JSON NOT NULL COMMENT 'Tarjetas de micro-aprendizaje',
    xp_reward    INT UNSIGNED NOT NULL DEFAULT 100,
    coin_reward  INT UNSIGNED NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Minijuegos de cada misión
-- ---------------------------------------------------------------------
CREATE TABLE games (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mission_id     INT UNSIGNED NOT NULL,
    slug           VARCHAR(60) NOT NULL UNIQUE,
    type           ENUM('classify','spot','url','scenario','chat','choice','sort','quiz') NOT NULL,
    title          VARCHAR(120) NOT NULL,
    description    VARCHAR(255) NOT NULL,
    instructions   TEXT NOT NULL,
    icon           VARCHAR(40) NOT NULL,
    order_num      TINYINT UNSIGNED NOT NULL,
    items_per_play TINYINT UNSIGNED NOT NULL DEFAULT 6,
    pass_percent   TINYINT UNSIGNED NOT NULL DEFAULT 60,
    dimension      ENUM('reconocimiento','decisiones','proteccion') NOT NULL,
    is_quiz        TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = mini-prueba de aprendizaje',
    CONSTRAINT fk_games_mission FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Reactivos / casos de cada minijuego
--  content  -> lo que ve el jugador (público)
--  solution -> respuesta correcta y retroalimentación (solo servidor)
-- ---------------------------------------------------------------------
CREATE TABLE game_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    game_id     INT UNSIGNED NOT NULL,
    content     JSON NOT NULL,
    solution    JSON NOT NULL,
    explanation TEXT NOT NULL,
    difficulty  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    dimension   ENUM('reconocimiento','decisiones','proteccion') NULL,
    CONSTRAINT fk_items_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Partidas jugadas y respuestas
-- ---------------------------------------------------------------------
CREATE TABLE game_attempts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    game_id       INT UNSIGNED NOT NULL,
    item_ids      JSON NOT NULL,
    status        ENUM('playing','finished') NOT NULL DEFAULT 'playing',
    correct       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    total         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    points        DECIMAL(6,2) NOT NULL DEFAULT 0,
    percent       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    stars         TINYINT UNSIGNED NOT NULL DEFAULT 0,
    xp_earned     INT UNSIGNED NOT NULL DEFAULT 0,
    coins_earned  INT UNSIGNED NOT NULL DEFAULT 0,
    started_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at   DATETIME NULL,
    INDEX idx_attempts_user (user_id, game_id),
    CONSTRAINT fk_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_attempts_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attempt_answers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id  INT UNSIGNED NOT NULL,
    item_id     INT UNSIGNED NOT NULL,
    answer      JSON NOT NULL,
    is_correct  TINYINT(1) NOT NULL DEFAULT 0,
    points      DECIMAL(4,2) NOT NULL DEFAULT 0 COMMENT 'Entre 0 y 1',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_attempt_item (attempt_id, item_id),
    CONSTRAINT fk_answers_attempt FOREIGN KEY (attempt_id) REFERENCES game_attempts(id) ON DELETE CASCADE,
    CONSTRAINT fk_answers_item FOREIGN KEY (item_id) REFERENCES game_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_game_progress (
    user_id       INT UNSIGNED NOT NULL,
    game_id       INT UNSIGNED NOT NULL,
    best_percent  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    best_stars    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    plays         INT UNSIGNED NOT NULL DEFAULT 0,
    passed_at     DATETIME NULL,
    PRIMARY KEY (user_id, game_id),
    CONSTRAINT fk_ugp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ugp_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_missions (
    user_id      INT UNSIGNED NOT NULL,
    mission_id   INT UNSIGNED NOT NULL,
    completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, mission_id),
    CONSTRAINT fk_um_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_um_mission FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Insignias (dominio de una habilidad concreta)
-- ---------------------------------------------------------------------
CREATE TABLE badges (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(60) NOT NULL UNIQUE,
    name         VARCHAR(80) NOT NULL,
    description  VARCHAR(255) NOT NULL,
    icon         VARCHAR(40) NOT NULL,
    color        VARCHAR(20) NOT NULL,
    category     ENUM('mision','habilidad','evaluacion','especial') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_badges (
    user_id    INT UNSIGNED NOT NULL,
    badge_id   INT UNSIGNED NOT NULL,
    earned_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_id),
    CONSTRAINT fk_ub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ub_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Logros (metas acumulativas con barra de progreso)
-- ---------------------------------------------------------------------
CREATE TABLE achievements (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug          VARCHAR(60) NOT NULL UNIQUE,
    name          VARCHAR(80) NOT NULL,
    description   VARCHAR(255) NOT NULL,
    icon          VARCHAR(40) NOT NULL,
    metric        ENUM('games_played','correct_answers','perfect_games','xp','stars','missions','streak','badges') NOT NULL,
    goal          INT UNSIGNED NOT NULL,
    xp_reward     INT UNSIGNED NOT NULL DEFAULT 0,
    coin_reward   INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_achievements (
    user_id         INT UNSIGNED NOT NULL,
    achievement_id  INT UNSIGNED NOT NULL,
    unlocked_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, achievement_id),
    CONSTRAINT fk_ua_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ua_ach FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Recompensas canjeables con monedas (avatares, marcos, títulos)
-- ---------------------------------------------------------------------
CREATE TABLE rewards (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(40) NOT NULL UNIQUE,
    type        ENUM('avatar','frame','title') NOT NULL,
    name        VARCHAR(80) NOT NULL,
    value       VARCHAR(80) NOT NULL COMMENT 'Emoji, color o texto',
    cost        INT UNSIGNED NOT NULL DEFAULT 0,
    min_level   TINYINT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_rewards (
    user_id      INT UNSIGNED NOT NULL,
    reward_id    INT UNSIGNED NOT NULL,
    acquired_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, reward_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ur_reward FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Instrumentos de la tesis
--  prevention   -> 24 ítems (Nunca=1 ... Siempre=5)
--  gamification -> 9 ítems  (Totalmente en desacuerdo=1 ... Totalmente de acuerdo=5)
-- ---------------------------------------------------------------------
CREATE TABLE survey_items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instrument   ENUM('prevention','gamification') NOT NULL,
    number       TINYINT UNSIGNED NOT NULL,
    dimension    VARCHAR(40) NOT NULL,
    short_label  VARCHAR(80) NOT NULL,
    statement    VARCHAR(400) NOT NULL,
    UNIQUE KEY uq_instrument_number (instrument, number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE assessments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    type        ENUM('pretest','postest','valoracion') NOT NULL,
    total       SMALLINT UNSIGNED NOT NULL,
    dim1        SMALLINT UNSIGNED NOT NULL,
    dim2        SMALLINT UNSIGNED NOT NULL,
    dim3        SMALLINT UNSIGNED NOT NULL,
    level       ENUM('Bajo','Medio','Alto') NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_type (user_id, type),
    CONSTRAINT fk_assess_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE assessment_answers (
    assessment_id  INT UNSIGNED NOT NULL,
    item_id        INT UNSIGNED NOT NULL,
    value          TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (assessment_id, item_id),
    CONSTRAINT fk_aa_assess FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    CONSTRAINT fk_aa_item FOREIGN KEY (item_id) REFERENCES survey_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Historial de experiencia (tabla semanal y actividad reciente)
-- ---------------------------------------------------------------------
CREATE TABLE xp_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    xp          INT NOT NULL DEFAULT 0,
    coins       INT NOT NULL DEFAULT 0,
    reason      VARCHAR(160) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_xplog_user_date (user_id, created_at),
    INDEX idx_xplog_date (created_at),
    CONSTRAINT fk_xplog_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
