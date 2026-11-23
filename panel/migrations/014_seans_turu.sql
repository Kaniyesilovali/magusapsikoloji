-- Seans türü: çocuk / ergen / yetişkin / çift / aile / grup.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- İki yerde tutuluyor, anlamları farklı:
--   clients.session_type      bireyin kaydı açılırken seçilen tür; yeni
--                             randevu formuna ön değer olarak gelir.
--   appointments.session_type o randevunun gerçek türü. Bir yetişkin
--                             bireyin bir seansı aile görüşmesi olabilir;
--                             randevu kendi türünü taşır, bireyinkine bağlı
--                             değildir.
--
-- Eski kayıtlar NULL kalır ("—" görünür): geriye dönük bir tür uydurmak,
-- olmayan bir bilgiyi kayda geçirmek olurdu. Kayıt düzenlendiğinde form
-- seçim ister.
ALTER TABLE clients
  ADD COLUMN session_type ENUM('child','adolescent','adult','couple','family','group') NULL AFTER birth_date;

ALTER TABLE appointments
  ADD COLUMN session_type ENUM('child','adolescent','adult','couple','family','group') NULL AFTER location;
