-- "Haftanın hâli" — check-in formunun ikinci sayfası (ekolojik işaretler).
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"
--
-- Bu göç yeni bir ürün kurmuyor: mevcut check-in kaydına üç sayının yanına
-- birkaç işaret ekliyor. Aynı bağlantı, aynı jeton, aynı gönderim.

-- ── Görüşmeci başına açık alanlar ─────────────────────────────────────────
-- Hangi alanların sorulacağı görüşmeciye göre değişir: kardeşi olmayan bir
-- çocuğa her hafta "Kardeş" sormak formu kendi gözünde gereksiz kılar.
--
-- Satır YOKSA varsayılan geçerlidir (Ecosystem::defaultsFor). Yani kayıt
-- açılırken sekiz satır yazmaya gerek yok; tablo yalnız terapistin
-- varsayılandan saptığı yerleri tutar. `enabled = 0` da bu yüzden anlamlı:
-- "çekirdek olduğu hâlde bu görüşmecide kapalı" demek.
--
-- Aynı görüşmecide en fazla 11 açık alan kuralı PHP tarafında (Ecosystem::MAX_OPEN);
-- veritabanı bir satırın diğerlerini sayamaz, sayabilse de hata mesajı okunmaz olurdu.
CREATE TABLE IF NOT EXISTS ecosystem_domains (
  id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  client_id  INT UNSIGNED    NOT NULL,
  domain_key VARCHAR(32)     NOT NULL,
  enabled    TINYINT(1)      NOT NULL DEFAULT 1,
  sort       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME        NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_eco_domain (client_id, domain_key),
  CONSTRAINT fk_eco_domain_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Haftalık işaretler ────────────────────────────────────────────────────
-- Bir check-in kaydına bağlı, alan başına tek satır. valence: -1 karşı rüzgâr,
-- 0 sakin, +1 sırt rüzgârı.
--
-- Sakin (0) olan alan YAZILMAZ. Bu, tasarımın en önemli veri kararı: dokunulmayan
-- alan eksik veri değil, "bu hafta öne çıkmadı" verisidir ve satır yokluğu bunu
-- zaten söyler. Sekiz alanın sekizini her hafta yazmak, tabloyu on haftada
-- sıfırlarla doldurup şeridi okunmaz kılardı.
--
-- domain_key yabancı anahtar DEĞİL: alan sözlüğü kodda duruyor (Ecosystem::DOMAINS),
-- veritabanında değil. Sözlüğü tabloya taşımak, metin düzeltmesini bile göçe
-- çevirirdi. Bedeli: kodda olmayan bir anahtar buraya yazılabilir — bu yüzden
-- yazma yolunda Ecosystem::known() ile doğrulanır.
CREATE TABLE IF NOT EXISTS ecosystem_marks (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  checkin_id INT UNSIGNED NOT NULL,
  domain_key VARCHAR(32)  NOT NULL,
  valence    TINYINT      NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_eco_mark (checkin_id, domain_key),
  CONSTRAINT fk_eco_mark_checkin FOREIGN KEY (checkin_id) REFERENCES checkins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── "Bu hafta bir şey oldu" ───────────────────────────────────────────────
-- Tek dokunuş + isteğe bağlı birkaç kelime. Şeritte dikey çapa olarak çizilir;
-- ekolojik okumanın en değerli tek verisi bu.
--
-- Etiket serbest metin ve bir ailenin en kırılgan haftasını anlatıyor olabilir
-- ("nine öldü", "babam taşındı"). Seans notu ve check-in cümlesiyle aynı kural:
-- yalnız şifreli saklanır. Şifreleme kapalıysa çip işaretlenebilir ama metin
-- alanı gösterilmez — işaretin kendisi (tarih) zaten klinik olarak yeterli.
--
-- İşaret metinsiz de anlamlı olduğu için ciphertext NULL olabilir: satırın
-- varlığı "o hafta bir şey oldu" demektir.
CREATE TABLE IF NOT EXISTS ecosystem_events (
  id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  checkin_id       INT UNSIGNED  NOT NULL,
  label_ciphertext BLOB          NULL,
  label_nonce      VARBINARY(24) NULL,
  created_at       DATETIME      NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_eco_event (checkin_id),
  CONSTRAINT fk_eco_event_checkin FOREIGN KEY (checkin_id) REFERENCES checkins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
