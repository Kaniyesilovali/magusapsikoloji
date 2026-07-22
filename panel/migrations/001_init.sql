-- Mağusa Psikoloji yönetim paneli — başlangıç şeması
-- Çalıştırma:  php panel/migrations/migrate.php
-- veya phpMyAdmin'de bu dosyayı içe aktarın.

SET NAMES utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- Kullanıcılar ve roller
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email                 VARCHAR(190) NOT NULL,
  password_hash         VARCHAR(255) NOT NULL,
  full_name             VARCHAR(150) NOT NULL,
  phone                 VARCHAR(30)  NULL,
  role                  ENUM('super_admin','admin','therapist','client') NOT NULL DEFAULT 'client',
  status                ENUM('active','invited','suspended') NOT NULL DEFAULT 'invited',
  must_change_password  TINYINT(1)   NOT NULL DEFAULT 0,
  failed_attempts       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until          DATETIME     NULL,
  last_login_at         DATETIME     NULL,
  created_by            INT UNSIGNED NULL,
  created_at            DATETIME     NOT NULL,
  updated_at            DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role, status),
  CONSTRAINT fk_users_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Davet ve şifre sıfırlama jetonları. Düz jeton saklanmaz, yalnız SHA-256 özeti.
CREATE TABLE IF NOT EXISTS invites (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  purpose     ENUM('invite','reset') NOT NULL DEFAULT 'invite',
  token_hash  CHAR(64)     NOT NULL,
  expires_at  DATETIME     NOT NULL,
  used_at     DATETIME     NULL,
  created_at  DATETIME     NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_invites_token (token_hash),
  KEY idx_invites_user (user_id, purpose),
  CONSTRAINT fk_invites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Terapist ek bilgileri (sitedeki ekip sayfası için de kaynak olabilir)
CREATE TABLE IF NOT EXISTS therapist_profiles (
  user_id                 INT UNSIGNED NOT NULL,
  title                   VARCHAR(120) NULL,
  bio                     TEXT         NULL,
  specialties             VARCHAR(255) NULL,
  photo_path              VARCHAR(255) NULL,
  default_session_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 50,
  calendar_color          CHAR(7)      NOT NULL DEFAULT '#4A7C6F',
  PRIMARY KEY (user_id),
  CONSTRAINT fk_therapist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Danışanlar ve randevular
-- ─────────────────────────────────────────────────────────────
-- user_id NULL olabilir: panele hiç girmeyen (yalnız kayıtta duran) danışanlar için.
CREATE TABLE IF NOT EXISTS clients (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id              INT UNSIGNED NULL,
  full_name            VARCHAR(150) NOT NULL,
  phone                VARCHAR(30)  NULL,
  email                VARCHAR(190) NULL,
  birth_date           DATE         NULL,
  primary_therapist_id INT UNSIGNED NULL,
  status               ENUM('active','archived') NOT NULL DEFAULT 'active',
  consent_at           DATETIME     NULL,
  consent_version      VARCHAR(20)  NULL,
  created_by           INT UNSIGNED NULL,
  created_at           DATETIME     NOT NULL,
  updated_at           DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_clients_user (user_id),
  KEY idx_clients_therapist (primary_therapist_id, status),
  KEY idx_clients_name (full_name),
  CONSTRAINT fk_clients_user      FOREIGN KEY (user_id)              REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_clients_therapist FOREIGN KEY (primary_therapist_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointments (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id     INT UNSIGNED NOT NULL,
  therapist_id  INT UNSIGNED NOT NULL,
  starts_at     DATETIME     NOT NULL,
  duration_min  SMALLINT UNSIGNED NOT NULL DEFAULT 50,
  status        ENUM('scheduled','confirmed','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
  location      ENUM('office','online') NOT NULL DEFAULT 'office',
  note          VARCHAR(255) NULL,          -- idari not (klinik içerik DEĞİL)
  cancel_reason VARCHAR(255) NULL,
  created_by    INT UNSIGNED NULL,
  created_at    DATETIME     NOT NULL,
  updated_at    DATETIME     NULL,
  PRIMARY KEY (id),
  KEY idx_appt_therapist_time (therapist_id, starts_at),
  KEY idx_appt_client_time (client_id, starts_at),
  CONSTRAINT fk_appt_client    FOREIGN KEY (client_id)    REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_appt_therapist FOREIGN KEY (therapist_id) REFERENCES users(id)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Terapist müsaitliği (haftalık şablon) ve izinler
CREATE TABLE IF NOT EXISTS working_hours (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  therapist_id INT UNSIGNED NOT NULL,
  weekday      TINYINT UNSIGNED NOT NULL,   -- 1=Pazartesi … 7=Pazar (ISO-8601)
  start_time   TIME NOT NULL,
  end_time     TIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_wh_therapist (therapist_id, weekday),
  CONSTRAINT fk_wh_therapist FOREIGN KEY (therapist_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS time_off (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  therapist_id INT UNSIGNED NOT NULL,
  starts_at    DATETIME NOT NULL,
  ends_at      DATETIME NOT NULL,
  reason       VARCHAR(150) NULL,
  PRIMARY KEY (id),
  KEY idx_timeoff_therapist (therapist_id, starts_at),
  CONSTRAINT fk_timeoff_therapist FOREIGN KEY (therapist_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Seans notları — özel nitelikli sağlık verisi
-- İçerik yalnız şifreli (libsodium secretbox) saklanır; veritabanı yedeği
-- ele geçse dahi anahtar olmadan okunamaz. Okuma yetkisi yalnız yazan terapistte.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS session_notes (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  appointment_id INT UNSIGNED NOT NULL,
  therapist_id   INT UNSIGNED NOT NULL,
  ciphertext     BLOB        NOT NULL,
  nonce          VARBINARY(24) NOT NULL,
  created_at     DATETIME    NOT NULL,
  updated_at     DATETIME    NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_note_appointment (appointment_id),
  KEY idx_note_therapist (therapist_id),
  CONSTRAINT fk_note_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
  CONSTRAINT fk_note_therapist   FOREIGN KEY (therapist_id)   REFERENCES users(id)        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Denetim kaydı ve ayarlar
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_log (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_user_id INT UNSIGNED NULL,
  action        VARCHAR(60)  NOT NULL,
  entity_type   VARCHAR(40)  NULL,
  entity_id     INT UNSIGNED NULL,
  ip            VARCHAR(45)  NULL,
  user_agent    VARCHAR(255) NULL,
  meta          JSON         NULL,
  created_at    DATETIME     NOT NULL,
  PRIMARY KEY (id),
  KEY idx_audit_created (created_at),
  KEY idx_audit_actor (actor_user_id, created_at),
  KEY idx_audit_action (action, created_at),
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(60)  NOT NULL,
  setting_value TEXT         NULL,
  updated_at    DATETIME     NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (setting_key, setting_value, updated_at) VALUES
  ('default_session_minutes', '50', NOW()),
  ('office_open',             '09:00', NOW()),
  ('office_close',            '18:00', NOW()),
  ('consent_version',         '1.0', NOW());
