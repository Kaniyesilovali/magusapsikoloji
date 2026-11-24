-- Özel zaman: terapistin takvimde kendisi için kapattığı saatler.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- time_off'tan (izin) ayrı bir tablo, çünkü kuralı farklı: izin yalnız uyarır
-- ve onayla geçilir, özel zaman randevu kaydını ENGELLER. Aynı tabloda
-- dursalardı mevcut izin kayıtlarının hepsi sessizce engele dönüşürdü.
--
-- note yalnız terapistin kendisine gösterilir. Ekip ve yönetim o saatte
-- yalnız "Dolu" görür: neden dolu olduğu kişinin özel hayatıdır.
CREATE TABLE IF NOT EXISTS busy_times (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  therapist_id INT UNSIGNED NOT NULL,
  starts_at    DATETIME     NOT NULL,
  ends_at      DATETIME     NOT NULL,
  note         VARCHAR(150) NULL,
  created_by   INT UNSIGNED NULL,
  created_at   DATETIME     NOT NULL,
  updated_at   DATETIME     NULL,
  PRIMARY KEY (id),
  KEY idx_busy_therapist (therapist_id, starts_at),
  CONSTRAINT fk_busy_therapist FOREIGN KEY (therapist_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
