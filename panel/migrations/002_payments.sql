-- Seans ücreti ve tahsilat takibi.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"
-- veya SSH varsa:  php panel/migrations/migrate.php

-- Ücret randevunun kendi alanıdır: her randevuda elle girilir, varsayılan yoktur.
-- NULL = "ücret henüz belirlenmedi" (0.00'dan farklı: 0.00 ücretsiz seans demektir).
ALTER TABLE appointments
  ADD COLUMN fee DECIMAL(10,2) NULL AFTER duration_min;

-- Tahsilatlar ayrı satırlar hâlinde tutulur; tek bir "ödendi" bayrağı değil.
-- Böylece kısmi ödeme, taksit ve "ne zaman, hangi yöntemle, kimden alındı"
-- bilgisi kaybolmadan durur. Randevunun kalanı = fee - SUM(amount) ile bulunur,
-- ayrı bir durum sütunu tutulmaz — tutulsaydı iki kayıt zamanla birbirinden kayardı.
CREATE TABLE IF NOT EXISTS payments (
  id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  appointment_id INT UNSIGNED  NOT NULL,
  amount         DECIMAL(10,2) NOT NULL,
  method         ENUM('cash','card','transfer','other') NOT NULL DEFAULT 'cash',
  paid_at        DATETIME      NOT NULL,
  note           VARCHAR(255)  NULL,
  created_by     INT UNSIGNED  NULL,
  created_at     DATETIME      NOT NULL,
  PRIMARY KEY (id),
  KEY idx_payments_appointment (appointment_id),
  KEY idx_payments_paid_at (paid_at),
  CONSTRAINT fk_payments_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_creator     FOREIGN KEY (created_by)     REFERENCES users(id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
