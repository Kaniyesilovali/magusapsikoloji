-- Rıza formu → onam formu: iki kâğıt tek kâğıt oldu.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- Kurumda iki metin dolaşıyordu. Biri psikoloğun süreç anlaşmasıydı: seans kaç
-- dakika, iptal kaç saat önce, kayıt alınmaz, istediğin zaman bırakabilirsin.
-- Diğeri panelin KVKK aydınlatma metniydi: hangi veri, hangi amaç, hangi hak.
-- Danışan aynı masada iki kez imza atıyor ve ikisinde de aynı cümleyi okuyordu:
-- "burada konuşulan burada kalır". Artık tek form var; KVKK bölümü, gizlilik
-- başlığının altına aynı dille yazılmış bir alt bölüm olarak girdi.
--
-- Buradaki tek veri işi sürüm numarası. Sürüm, `clients.consent_version` ile
-- birlikte "bu danışan HANGİ metne onay verdi" sorusunun tek cevabı; metin
-- değişip sürüm aynı kalırsa iki farklı kâğıt aynı numarayla imzalanmış görünür
-- ve kayıt ispat değerini kaybeder.
--
-- Bu yüzden koşullu: sürüm yalnızca metni HİÇ kaydetmemiş kurumlarda 2.0'a
-- çıkar. Onlarda görülen metin koddaki taslaktı ve o taslak şimdi değişti.
-- Metnini elle yazıp kaydetmiş bir kurumun sürümüne dokunulmaz — o kâğıt
-- değişmedi, numarası da değişmemeli. Değiştirmek isterse panelden kendisi
-- yükseltir (metin değişip sürüm sabit kalırsa form zaten kaydetmiyor).
-- Alt sorgu türetilmiş tablo üzerinden: MySQL, güncellenen tabloyu doğrudan
-- okuyan bir alt sorguya izin vermiyor.
UPDATE settings
   SET setting_value = '2.0',
       updated_at    = NOW()
 WHERE setting_key = 'consent_version'
   AND setting_value = '1.0'
   AND NOT EXISTS (
         SELECT 1
           FROM (SELECT setting_key, setting_value FROM settings) AS kayitli
          WHERE kayitli.setting_key = 'consent_text'
            AND COALESCE(kayitli.setting_value, '') <> ''
       );

-- Not: eskiden 1.0 ile onam işaretlenmiş danışanlar 1.0'da kalır. Sürüm geçmişe
-- dönük çalışmaz; o kayıtlar imzalandıkları metne bağlı kalmalı. Yeni metin
-- esaslı bir değişiklikse, mevcut danışanlardan yeniden onam alınıp alınmayacağı
-- kurumun kararıdır — panel bunu kendiliğinden yapmaz.
