-- Haftalık check-in e-postası kime gidiyor — birey başına açma/kapama.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- Bugüne kadar "kime gider" sorusunun cevabı dolaylıydı: döngüsü bir kez
-- başlatılmış (en az bir bağlantı almış) herkese gidiyordu. Bunu geri almanın
-- tek yolu kaydı arşivlemekti — yani takibi sürdürmek isteyip yalnız haftalık
-- e-postayı durdurmanın yolu yoktu. Ailenin "bu dönem doldurmayalım" demesi ya
-- da bağlantıyı WhatsApp'tan iletmeyi tercih etmek bunun en sık iki sebebi.
--
-- Varsayılan 1: göç uygulandığında bugün e-posta alan herkes almaya devam eder.
-- Sütunun yokluğunda da (deploy ile göç arasındaki boşluk) kod aynı şekilde
-- "açık" varsayıyor; bkz. Checkins::autoEnabled.
--
-- Bu bir GÖNDERİM ayarıdır, bir rıza kaydı değil: kapalıyken bağlantı elle hâlâ
-- üretilebilir (birey sayfasındaki düğme). Durdurduğu tek şey cron.
ALTER TABLE clients
  ADD COLUMN checkin_auto TINYINT(1) NOT NULL DEFAULT 1 AFTER status;
