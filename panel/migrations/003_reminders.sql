-- Randevu hatırlatma e-postaları.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- Hatırlatmanın gönderildiği an. NULL = henüz gönderilmedi.
-- Ayrı bir "gönderildi mi" bayrağı yerine zaman damgası tutulur: cron'un
-- gerçekten çalışıp çalışmadığı ancak böyle görülebilir. Tekrar gönderimi de
-- bu alan engeller — cron saatte bir çalışsa da her randevu bir kez uyarılır.
ALTER TABLE appointments
  ADD COLUMN reminder_sent_at DATETIME NULL AFTER status;

-- Cron her koşusunda "yaklaşan ve henüz uyarılmamış" randevuları arar.
CREATE INDEX idx_appt_reminder ON appointments (reminder_sent_at, starts_at);
