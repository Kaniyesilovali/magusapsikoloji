-- Seanslar arası check-in — görüşmecinin haftada bir doldurduğu kısa ölçüm.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- ── Bağlantı istekleri ────────────────────────────────────────────────────
-- Check-in formu GİRİŞ GEREKTİRMEZ: hatırlatmada tek kullanımlık bir bağlantı
-- gider, kişi telefonda otuz saniyede doldurur. Doldurma oranı bu döngünün
-- tek riski; araya giriş ekranı koymak o oranı yarıya böler.
--
-- Düz jeton saklanmaz, yalnız sha256 özeti — davet jetonlarındaki kural (bkz.
-- Auth::createToken). Veritabanı yedeği eline geçen biri, kimsenin adına
-- check-in doldurabilecek bağlantı üretemez.
--
-- sent_at ayrı duruyor çünkü bağlantı e-postasız da üretilebiliyor: terapist
-- görüşmeci sayfasından üretip WhatsApp'tan yollayabilir. NULL = bu bağlantı
-- panelden elle alındı, e-posta çıkmadı.
CREATE TABLE IF NOT EXISTS checkin_requests (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id    INT UNSIGNED NOT NULL,
  token_hash   CHAR(64)     NOT NULL,
  expires_at   DATETIME     NOT NULL,
  sent_at      DATETIME     NULL,
  completed_at DATETIME     NULL,
  created_at   DATETIME     NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_checkin_token (token_hash),
  KEY idx_checkin_req_client (client_id, created_at),
  CONSTRAINT fk_checkin_req_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Check-in kayıtları ────────────────────────────────────────────────────
-- Üç sayı ve isteğe bağlı tek cümle. Sayılar 1–10 arası; PHP tarafında
-- sınırlandırılıyor (Checkins::score).
--
-- Sütun adı `sleep` DEĞİL `sleep_quality`: SLEEP() MySQL'in kendi işlevi,
-- tırnaksız yazılan her sorguda okuyanı tereddüde düşürür.
--
-- Cümle özel nitelikli sağlık verisidir: seans notuyla aynı kuralda, yalnız
-- şifreli saklanır (libsodium secretbox → ciphertext + nonce; bkz. Crypto).
-- Şifreleme kapalıysa alan formda hiç gösterilmez — saklanamayacak bir şeyi
-- yazdırmak, yazanın güvenine ihanet eder.
CREATE TABLE IF NOT EXISTS checkins (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  client_id       INT UNSIGNED  NOT NULL,
  request_id      INT UNSIGNED  NULL,
  mood            TINYINT UNSIGNED NOT NULL,
  sleep_quality   TINYINT UNSIGNED NOT NULL,
  anxiety         TINYINT UNSIGNED NOT NULL,
  note_ciphertext BLOB          NULL,
  note_nonce      VARBINARY(24) NULL,
  created_at      DATETIME      NOT NULL,
  PRIMARY KEY (id),
  KEY idx_checkin_client (client_id, created_at),
  CONSTRAINT fk_checkin_client  FOREIGN KEY (client_id)  REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_checkin_request FOREIGN KEY (request_id) REFERENCES checkin_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
