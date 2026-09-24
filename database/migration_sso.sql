CREATE TABLE IF NOT EXISTS sso_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code_hash CHAR(64) NOT NULL UNIQUE,
  user_id BIGINT UNSIGNED NOT NULL,
  client_id VARCHAR(40) NOT NULL,
  state_value VARCHAR(128) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_portal_sso_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_sso_codes_valid (client_id, expires_at, used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
