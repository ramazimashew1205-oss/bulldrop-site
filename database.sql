CREATE DATABASE IF NOT EXISTS drop_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE drop_demo;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(32) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'user',
  balance DECIMAL(12,2) NOT NULL DEFAULT 1000.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS game_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  game VARCHAR(20) NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  multiplier DECIMAL(8,2) DEFAULT NULL,
  result VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id),
  CONSTRAINT fk_history_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Если база уже была создана старой версией проекта, выполните один раз:
-- ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER password_hash;
-- Затем для своего аккаунта можно выдать админку:
-- UPDATE users SET role='admin' WHERE username='ВАШ_ЛОГИН';