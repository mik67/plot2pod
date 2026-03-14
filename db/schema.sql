CREATE DATABASE IF NOT EXISTS plot2pod_com CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE plot2pod_com;

CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    is_admin      TINYINT(1)    NOT NULL DEFAULT 0,
    created_at    DATETIME      NOT NULL DEFAULT NOW()
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS podcasts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    slug        VARCHAR(200) NOT NULL DEFAULT '',
    description TEXT         NOT NULL,
    mp3_path    VARCHAR(500) NOT NULL,
    duration    INT          NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT NOW(),
    published   TINYINT(1)   NOT NULL DEFAULT 1,
    deleted     TINYINT(1)   NOT NULL DEFAULT 0
) ENGINE=InnoDB;
-- Unique slug index (added in migration)
-- ALTER TABLE podcasts ADD UNIQUE KEY uq_podcast_slug (slug);

CREATE TABLE IF NOT EXISTS requests (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    type         ENUM('topic','links','files') NOT NULL,
    content      TEXT,
    file_paths   TEXT,
    status       ENUM('pending','processing','done','rejected','deleted') NOT NULL DEFAULT 'pending',
    podcast_id   INT          NULL DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT NOW(),
    notified_at  DATETIME     NULL DEFAULT NULL,
    reject_reason TEXT        NULL DEFAULT NULL,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (podcast_id) REFERENCES podcasts(id) ON DELETE SET NULL
) ENGINE=InnoDB;
