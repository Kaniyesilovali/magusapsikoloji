-- Birey dosyası (vaka formülasyonu) — özel nitelikli sağlık verisi.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- Seans notundan farkı: seans notu tek bir görüşmeyi anlatır, dosya ise sürecin
-- tamamını taşır (başvuru nedeni, öykü, formülasyon, plan). Terapist her seansta
-- bunu yeniden okumak ister; seans notlarını tek tek açmak istemez.
--
-- Saklama seans notuyla aynı kuralda: yalnız şifreli, yalnız yazarına açık.
-- Bu yüzden birey başına değil, (birey, terapist) çifti başına tek kayıt
-- tutulur — devir hâlinde devralan terapist öncekinin dosyasını açamaz, kendi
-- formülasyonunu yazar.
CREATE TABLE IF NOT EXISTS case_files (
  id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  client_id    INT UNSIGNED  NOT NULL,
  therapist_id INT UNSIGNED  NOT NULL,
  ciphertext   BLOB          NOT NULL,
  nonce        VARBINARY(24) NOT NULL,
  created_at   DATETIME      NOT NULL,
  updated_at   DATETIME      NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_case_client_therapist (client_id, therapist_id),
  KEY idx_case_therapist (therapist_id),
  CONSTRAINT fk_case_client    FOREIGN KEY (client_id)    REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_case_therapist FOREIGN KEY (therapist_id) REFERENCES users(id)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
