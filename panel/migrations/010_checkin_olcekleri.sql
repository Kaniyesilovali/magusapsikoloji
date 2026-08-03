-- Check-in soruları: üç sabit sütun yerine düzenlenebilir bir ölçek listesi.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- Üç soru (ruh hali, uyku, kaygı) veritabanında üç ayrı sütundu. Metinleri
-- düzenlenebilir oldu ama sayısı kodda kilitliydi: dördüncüsünü eklemek ya da
-- birini kapatmak göç istiyordu. Merkezin "bu dönem iştahı da soralım" demesi
-- bir yazılım sürümüne bağlı kalmamalı.
--
-- Sözlük değil TANIM tablosu: metinler settings'te değil burada, çünkü artık
-- kaç tane oldukları da veri. Koddaki üç varsayılan buraya bir kez kopyalanıyor
-- (settings'e yazılmış düzenlemeler varsa onlarla birlikte) ve bundan sonra tek
-- kaynak bu tablo.
CREATE TABLE IF NOT EXISTS checkin_scales (
  id         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  scale_key  VARCHAR(32)      NOT NULL,
  label      VARCHAR(60)      NOT NULL,          -- panelde ve eğride görünen ad
  question   VARCHAR(200)     NOT NULL,          -- bireye sorulan cümle
  low_label  VARCHAR(60)      NOT NULL,          -- 1 ucunun adı
  high_label VARCHAR(60)      NOT NULL,          -- 10 ucunun adı
  -- Yön: +1 yüksek değer iyi (ruh hali, uyku), -1 yüksek değer kötü (kaygı).
  -- Eğri bunu okuyor; ucun ADI değişebilir ama yön veri modelinin kendisi.
  direction  TINYINT          NOT NULL DEFAULT 1,
  sort       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  enabled    TINYINT(1)       NOT NULL DEFAULT 1,
  created_at DATETIME         NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_scale_key (scale_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cevaplar: check-in başına ölçek başına tek satır.
--
-- scale_key yabancı anahtar DEĞİL ve bu bilinçli: kapatılan ya da silinen bir
-- ölçeğin geçmiş cevapları durmalı. Sekiz hafta "iştah" sorulduysa o sekiz sayı
-- ölçek listeden kalktığında silinmez — ölçmeyi bırakmak, ölçtüğünü unutmak
-- değildir. Aynı gerekçe ekolojik işaretlerde de var (bkz. 006).
CREATE TABLE IF NOT EXISTS checkin_scores (
  id         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  checkin_id INT UNSIGNED     NOT NULL,
  scale_key  VARCHAR(32)      NOT NULL,
  value      TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_score (checkin_id, scale_key),
  CONSTRAINT fk_score_checkin FOREIGN KEY (checkin_id) REFERENCES checkins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Üç varsayılanı taşı ───────────────────────────────────────────────────
-- Metinler settings'te düzenlenmiş olabilir; düzenlenmişse o, değilse koddaki
-- cümle. NULLIF: ayar satırı var ama boşsa da varsayılana düşsün.
INSERT IGNORE INTO checkin_scales (scale_key, label, question, low_label, high_label, direction, sort, enabled, created_at)
SELECT 'mood',
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'checkin_measure_mood_label'), ''), 'Ruh hali'),
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'checkin_question_mood'), ''), 'Bu hafta genel olarak ruh hâlin nasıldı?'),
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'checkin_measure_mood_low'), ''), 'çok kötü'),
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'checkin_measure_mood_high'), ''), 'çok iyi'),
       1, 0, 1, NOW()
  FROM DUAL;

INSERT IGNORE INTO checkin_scales (scale_key, label, question, low_label, high_label, direction, sort, enabled, created_at)
SELECT 'sleep_quality',
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'checkin_measure_sleep_quality_label'), ''), 'Uyku'),
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'checkin_question_sleep_quality'), ''), 'Uykun nasıldı?'),
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'checkin_measure_sleep_quality_low'), ''), 'çok kötü'),
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'checkin_measure_sleep_quality_high'), ''), 'çok iyi'),
       1, 1, 1, NOW()
  FROM DUAL;

INSERT IGNORE INTO checkin_scales (scale_key, label, question, low_label, high_label, direction, sort, enabled, created_at)
SELECT 'anxiety',
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'checkin_measure_anxiety_label'), ''), 'Kaygı'),
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'checkin_question_anxiety'), ''), 'Kaygı düzeyin ne kadardı?'),
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'checkin_measure_anxiety_low'), ''), 'hiç yok'),
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'checkin_measure_anxiety_high'), ''), 'çok yoğun'),
       -1, 2, 1, NOW()
  FROM DUAL;

-- ── Geçmiş cevapları taşı ─────────────────────────────────────────────────
-- Eğri bundan sonra checkin_scores'tan çiziliyor; taşınmayan bir geçmiş, boş
-- bir eğri demekti. IGNORE: göç iki kez çalışırsa satırlar tekrarlanmasın.
INSERT IGNORE INTO checkin_scores (checkin_id, scale_key, value)
  SELECT id, 'mood', mood FROM checkins WHERE mood IS NOT NULL;
INSERT IGNORE INTO checkin_scores (checkin_id, scale_key, value)
  SELECT id, 'sleep_quality', sleep_quality FROM checkins WHERE sleep_quality IS NOT NULL;
INSERT IGNORE INTO checkin_scores (checkin_id, scale_key, value)
  SELECT id, 'anxiety', anxiety FROM checkins WHERE anxiety IS NOT NULL;

-- Eski sütunlar DURUYOR ve üç varsayılan ölçek için yazılmaya devam ediyor:
-- yedekten dönen ya da doğrudan SQL okuyan biri için `checkins` tablosu tek
-- başına hâlâ anlamlı. Ama artık NULL olabilirler — ruh hali ölçeği kapatılmış
-- bir merkezde o sütuna yazılacak bir sayı yok ve NOT NULL, olmayan bir cevabı
-- sıfırla doldurmaya zorlardı.
ALTER TABLE checkins
  MODIFY mood          TINYINT UNSIGNED NULL,
  MODIFY sleep_quality TINYINT UNSIGNED NULL,
  MODIFY anxiety       TINYINT UNSIGNED NULL;
