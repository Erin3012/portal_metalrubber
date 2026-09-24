-- Base exclusiva del Portal Metalrubber.
-- Selecciona qlccl_portal antes de ejecutar este archivo.

CREATE TABLE IF NOT EXISTS roles (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(80) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS modules (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) NOT NULL,
  url VARCHAR(255) NOT NULL,
  available TINYINT(1) NOT NULL DEFAULT 1,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id TINYINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_portal_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_modules (
  role_id TINYINT UNSIGNED NOT NULL,
  module_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, module_id),
  CONSTRAINT fk_portal_role_modules_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_portal_role_modules_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL,
  successful TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_login_attempts_window (email, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  event VARCHAR(80) NOT NULL,
  details VARCHAR(255) NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_portal_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_logs_created (created_at),
  INDEX idx_audit_logs_event (event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (slug, name) VALUES
('admin', 'Administrador general'),
('supervisor', 'Supervisor'),
('operario', 'Operario'),
('payroll', 'Usuario de remuneraciones'),
('quotations', 'Usuario de cotizaciones')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO modules (slug, name, description, url, available, sort_order) VALUES
('maintenance', 'Mantenciones', 'Mantenciones, carros y códigos QR.', 'https://mantenciones.metalrubber.cl', 1, 1),
('payroll', 'Remuneraciones', 'Gestión de empresas, trabajadores y liquidaciones.', 'https://remuneraciones.metalrubber.cl', 1, 2),
('quotations', 'Cotizaciones', 'Solicitudes y cotizaciones Metalrubber.', 'https://cotizaciones.metalrubber.cl', 1, 3)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), url = VALUES(url), available = VALUES(available), sort_order = VALUES(sort_order);

INSERT IGNORE INTO role_modules (role_id, module_id)
SELECT r.id, m.id FROM roles r CROSS JOIN modules m WHERE r.slug = 'admin';
INSERT IGNORE INTO role_modules (role_id, module_id)
SELECT r.id, m.id FROM roles r JOIN modules m ON m.slug = 'maintenance' WHERE r.slug IN ('supervisor', 'operario');
INSERT IGNORE INTO role_modules (role_id, module_id)
SELECT r.id, m.id FROM roles r JOIN modules m ON m.slug = 'payroll' WHERE r.slug = 'payroll';
INSERT IGNORE INTO role_modules (role_id, module_id)
SELECT r.id, m.id FROM roles r JOIN modules m ON m.slug = 'quotations' WHERE r.slug = 'quotations';
