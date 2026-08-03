-- Onamın kaydı: kim, ne zaman, hangi yolla, hangi metne onay verdi.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- Bugüne kadar onam tek bir kutucuktu: clients.consent_at + consent_version.
-- Bu üç tablo o kutucuğun cevaplayamadığı üç soruyu cevaplıyor:
--
--   1. Danışan neyi onayladı?  Sürüm numarası bir söz veriyordu ama karşılığı
--      yoktu: metin düzenlenip sürüm yükselince eski metin settings'ten
--      siliniyordu ve 2.0'a onam vermiş kişinin neyi okuduğu geri
--      getirilemiyordu. consent_versions o sözün karşılığı.
--   2. Nasıl onayladı?  Kâğıda imza mı attı, çevrimiçi tik mi işaretledi,
--      online seansta sözlü olarak mı beyan etti. consent_records.method.
--   3. Onam geri alındığında ne oldu?  Kutucuğun işareti kaldırılınca
--      consent_at sessizce NULL'lanıyordu; verilmiş bir onamın izi kalmıyordu.
--      Kayıtlar SİLİNMEZ, revoked_at ile kapanır.

-- ── Sürümlerin metni ──────────────────────────────────────────────────────
-- Onamın ispat değeri "hangi metne" sorusuna verilebilen cevaptır. Metin
-- ayarlarda tek satırda tutulduğu için her sürüm bir öncekini siliyordu.
-- Buradan silinmez: bir sürüm bir kez yazılır ve orada kalır.
--
-- text_en aynı sürümün İngilizce çıktısı — Türkçe okumayan danışana masaya
-- konan kâğıt. NULL olabilir: çeviri sonradan eklenebilir ve her sürümün
-- çevirisi yazılmamış olabilir. Çevrimiçi onam bağlantısı yalnız Türkçedir;
-- onamın hukuki bağı Türkçe metne, İngilizce kâğıt onun çevirisidir.
CREATE TABLE IF NOT EXISTS consent_versions (
  version    VARCHAR(20) NOT NULL,
  text       MEDIUMTEXT  NOT NULL,
  text_en    MEDIUMTEXT  NULL,
  created_at DATETIME    NOT NULL,
  PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Onam bağlantısı istekleri ─────────────────────────────────────────────
-- Check-in bağlantısıyla aynı desen (bkz. 005_checkins.sql): giriş
-- gerektirmeyen, tek kullanımlık, süreli bir jeton. Düz jeton saklanmaz,
-- yalnız sha256 özeti — veritabanı yedeği eline geçen biri kimsenin adına
-- onam veremesin.
--
-- version sütunu bağlantının üretildiği andaki metnin sürümü: kişi metni
-- okuyup tikleyene kadar metin panelden değiştirilmiş olabilir. O durumda
-- gönderim reddedilir; okunmayan bir metne onam yazılmaz.
--
-- sent_at NULL = bağlantı panelden elle alındı, e-posta çıkmadı. Telefonda
-- randevu alan kişiye bağlantı çoğu zaman WhatsApp'tan gidiyor.
CREATE TABLE IF NOT EXISTS consent_requests (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id    INT UNSIGNED NOT NULL,
  token_hash   CHAR(64)     NOT NULL,
  version      VARCHAR(20)  NOT NULL,
  expires_at   DATETIME     NOT NULL,
  sent_at      DATETIME     NULL,
  completed_at DATETIME     NULL,
  created_at   DATETIME     NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_consent_token (token_hash),
  KEY idx_consent_req_client (client_id, created_at),
  CONSTRAINT fk_consent_req_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Onam kayıtları ────────────────────────────────────────────────────────
-- Üç yol, üç yöntem:
--
--   online  Danışan bağlantıdaki metni okuyup en alttaki tiki işaretledi.
--           Tek başına KISMİ onamdır: metnin okunduğunu gösterir, seansta
--           ıslak imza ya da sözlü beyanla tamamlanır.
--   paper   Çıktı alınıp seansta imzalandı. Kâğıdın kendisi dosyada.
--   verbal  Online görüşmede "okudum, onaylıyorum" beyanı kayda alındı.
--           KAYIT DOSYASI PANELDE DEĞİL: merkez kendi klasöründe tutuyor.
--           reference yalnız o dosyanın nerede olduğunu söyleyen kısa bir
--           künye — klinik içerik yazılacak yer değil.
--
-- ip ve user_agent yalnız online kayıtlarda dolu: tiki işaretleyenin panelde
-- oturumu yok, kim olduğunun tek karşılığı bağlantının kendisi ve isteğin izi.
-- paper/verbal'de karşılığı recorded_by — işaretleyen terapist.
CREATE TABLE IF NOT EXISTS consent_records (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id   INT UNSIGNED NOT NULL,
  method      ENUM('online','paper','verbal') NOT NULL,
  version     VARCHAR(20)  NOT NULL,
  approved_at DATETIME     NOT NULL,
  request_id  INT UNSIGNED NULL,
  ip          VARCHAR(45)  NULL,
  user_agent  VARCHAR(255) NULL,
  recorded_by INT UNSIGNED NULL,
  reference   VARCHAR(200) NULL,
  revoked_at  DATETIME     NULL,
  revoked_by  INT UNSIGNED NULL,
  created_at  DATETIME     NOT NULL,
  PRIMARY KEY (id),
  KEY idx_consent_rec_client (client_id, approved_at),
  CONSTRAINT fk_consent_rec_client  FOREIGN KEY (client_id)  REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_consent_rec_request FOREIGN KEY (request_id) REFERENCES consent_requests(id) ON DELETE SET NULL,
  CONSTRAINT fk_consent_rec_by      FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_consent_rec_revoker FOREIGN KEY (revoked_by)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Eldeki sürümü arşive al ───────────────────────────────────────────────
-- Metne dokunmadan ÖNCE: bugün kayıtlı olan metin, bugün geçerli olan sürüm
-- numarasıyla arşive giriyor. Bundan sonra imzalanmış her form geri
-- getirilebilir bir metne bağlı.
--
-- Alt sorgu türetilmiş tablo üzerinden (bkz. 010): MySQL eklenen tabloyu
-- doğrudan okuyan alt sorguya izin veriyor ama aynı deseni sürdürmek okuyanı
-- tereddüde düşürmüyor.
INSERT IGNORE INTO consent_versions (version, text, created_at)
SELECT
  (SELECT s2.setting_value FROM (SELECT setting_key, setting_value FROM settings) AS s2
    WHERE s2.setting_key = 'consent_version'),
  s.setting_value,
  NOW()
  FROM (SELECT setting_key, setting_value FROM settings) AS s
 WHERE s.setting_key = 'consent_text'
   AND COALESCE(s.setting_value, '') <> ''
   AND (SELECT s3.setting_value FROM (SELECT setting_key, setting_value FROM settings) AS s3
         WHERE s3.setting_key = 'consent_version') IS NOT NULL;

-- ── Kayıt yasağına ayrım cümlesi ──────────────────────────────────────────
-- Metin bugüne kadar "ses ve/veya görüntü kaydı almaya izin verilmemektedir"
-- diyordu. Online danışandan onamının sözlü kaydını istemek bu cümleyle
-- doğrudan çelişiyor: kâğıt verdiği sözü tutmuyor olurdu.
--
-- Yasak duruyor — SEANSIN kaydı için. Ayrılan tek şey onamın kendisinin
-- beyanı: seans içeriğinden ayrı, aynı gizlilik kuralıyla saklanan kısa bir
-- kayıt. Metin, koddaki taslakla (Consent::starterText) birebir aynı olmalı;
-- ikisi birlikte değişir.
--
-- Bütün metni yeniden yazmak yerine tek maddeyi değiştiriyoruz: kurumun elle
-- doldurduğu alanlar (saklama süresi, iletişim adresi) olduğu gibi kalsın.
UPDATE settings
   SET setting_value = REPLACE(setting_value,
         '- Etik ilkeler ve gizliliğin korunması amacıyla, ses ve/veya görüntü kaydı
  almaya izin verilmemektedir.',
         '- Etik ilkeler ve gizliliğin korunması amacıyla, seansların ses ve/veya
  görüntü kaydının alınmasına izin verilmemektedir. Bu kural seansın kendisi
  içindir. Onamınızı çevrimiçi görüşmede sözlü olarak beyan etmeniz istenirse,
  alınan kısa kayıt yalnızca onamınızın belgesi olarak, seans içeriğinden ayrı
  biçimde ve aynı gizlilik kuralıyla saklanır.'),
       updated_at = NOW()
 WHERE setting_key = 'consent_text'
   AND COALESCE(setting_value, '') <> ''
   AND setting_value NOT LIKE '%onamınızın belgesi%';

-- Metni gerçekten değiştirdiysek sürüm de yükselmeli. Kanıt, yeni cümlenin
-- kayıtta bulunması. 2.0 dışında bir sürüm numarası kullanan kurumda
-- (kendi metnini yazmış olabilir) numaraya dokunulmaz — o metinde aranan
-- madde de zaten yoktu, REPLACE bir şey değiştirmedi.
UPDATE settings
   SET setting_value = '2.1',
       updated_at    = NOW()
 WHERE setting_key = 'consent_version'
   AND setting_value = '2.0'
   AND EXISTS (
         SELECT 1
           FROM (SELECT setting_key, setting_value FROM settings) AS metin
          WHERE metin.setting_key = 'consent_text'
            AND metin.setting_value LIKE '%onamınızın belgesi%'
       );

-- Yeni sürümü de arşive al. Yukarıdakiyle aynı ifade: bu kez 2.1 ve yeni
-- metin giriyor, 2.0 satırına INSERT IGNORE sayesinde dokunulmuyor.
INSERT IGNORE INTO consent_versions (version, text, created_at)
SELECT
  (SELECT s2.setting_value FROM (SELECT setting_key, setting_value FROM settings) AS s2
    WHERE s2.setting_key = 'consent_version'),
  s.setting_value,
  NOW()
  FROM (SELECT setting_key, setting_value FROM settings) AS s
 WHERE s.setting_key = 'consent_text'
   AND COALESCE(s.setting_value, '') <> ''
   AND (SELECT s3.setting_value FROM (SELECT setting_key, setting_value FROM settings) AS s3
         WHERE s3.setting_key = 'consent_version') IS NOT NULL;
