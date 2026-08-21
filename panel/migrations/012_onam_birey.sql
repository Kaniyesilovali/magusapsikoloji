-- Onam metnindeki "danışan" sözcüğünü "birey" ile değiştirir.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- Kurumun kendi dili "birey": panel her yerde birey kaydı diyor, kâğıdın
-- imza satırları da artık kimseyi danışan diye adlandırmıyor. Metnin içinde
-- kalan sekiz "danışan", kâğıdı okuyan kişiye başka bir isimle sesleniyordu.
--
-- Anlam değişmiyor, sözcük değişiyor. Yine de sürüm yükseliyor: bu panelde
-- "metin değiştiyse sürüm de değişir" kuralı, imzalanmış her formun geri
-- getirilebilir bir metne bağlı kalmasının tek güvencesi.
--
-- 011'deki desen: bütün metni yeniden yazmak yerine yalnız geçen yerleri
-- değiştiriyoruz — kurumun elle doldurduğu alanlar (saklama süresi, iletişim
-- adresi) olduğu gibi kalsın. Parçalar satır sonu içermeyecek kadar kısa
-- seçildi; metnin satır düzeni kurumda farklı olsa da tutarlar.
--
-- Metin, koddaki taslakla (Consent::starterText) birebir aynı olmalı; ikisi
-- birlikte değişir.
UPDATE settings
   SET setting_value = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
         setting_value,
         'Seansa danışan tarafından geç kalınması',   'Seansa birey tarafından geç kalınması'),
         'Psikolog ve danışan arasında',              'Psikolog ve birey arasında'),
         'danışanın kişisel bilgilerini',             'bireyin kişisel bilgilerini'),
         'en önemlisi; danışanın kendine',            'en önemlisi; bireyin kendine'),
         'danışanın bir aile yakınıyla',              'bireyin bir aile yakınıyla'),
         'durumda danışanı koruyabilmek',             'durumda bireyi koruyabilmek'),
         'danışanın gönüllülüğü çok önemlidir. Danışanın kendi isteğiyle',
         'bireyin gönüllülüğü çok önemlidir. Bireyin kendi isteğiyle'),
         '- Danışan, istediği zaman',                 '- Birey, istediği zaman'),
       updated_at = NOW()
 WHERE setting_key = 'consent_text'
   AND COALESCE(setting_value, '') <> '';

-- Çevirinin de aynı kişiyi aynı adla anması gerekiyor: Türkçesi "birey"
-- diyorsa İngilizcesi "the individual" der. Çeviri hiç kaydedilmemişse
-- (kod taslağı kullanılıyorsa) bu satır bir şey bulmaz ve dokunmaz.
UPDATE settings
   SET setting_value = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
         setting_value,
         'between the psychologist and the client is', 'between the psychologist and the individual is'),
         'as the client''s personal information',      'as the individual''s personal information'),
         'a perceived risk of the client',             'a perceived risk of the individual'),
         'family member of the client.',               'family member of the individual.'),
         'In order to protect the client and enable',  'In order to protect the individual and enable'),
         '- The client''s willingness is essential',   '- The individual''s willingness is essential'),
         'client is seeking psychological support',    'individual is seeking psychological support'),
         '- The client has the right to end the meetings',
         '- The individual has the right to end the meetings'),
       updated_at = NOW()
 WHERE setting_key = 'consent_text_en'
   AND COALESCE(setting_value, '') <> '';

-- Metni gerçekten değiştirdiysek sürüm de yükselmeli. Kanıt, yeni sözcüğün
-- kayıtta bulunması. 2.1 dışında bir sürüm numarası kullanan kurumda (kendi
-- metnini yazmış olabilir) numaraya dokunulmaz — o metinde aranan ifadeler de
-- zaten yoktu, REPLACE bir şey değiştirmedi.
--
-- Alt sorgu türetilmiş tablo üzerinden (bkz. 010, 011): MySQL güncellenen
-- tabloyu doğrudan okuyan alt sorguya izin vermiyor.
UPDATE settings
   SET setting_value = '2.2',
       updated_at    = NOW()
 WHERE setting_key = 'consent_version'
   AND setting_value = '2.1'
   AND EXISTS (
         SELECT 1
           FROM (SELECT setting_key, setting_value FROM settings) AS metin
          WHERE metin.setting_key = 'consent_text'
            AND metin.setting_value LIKE '%Psikolog ve birey arasında%'
       );

-- Çevirinin sürüm künyesi Türkçeyle birlikte yürüyor: ikisini de bu göç
-- değiştirdiğine göre çeviri artık 2.2'nin çevirisi. Ayrı kalsaydı panel,
-- çıktının üstüne "çeviri eski sürüme ait" uyarısını haksız yere basardı.
UPDATE settings
   SET setting_value = '2.2',
       updated_at    = NOW()
 WHERE setting_key = 'consent_text_en_version'
   AND setting_value = '2.1'
   AND EXISTS (
         SELECT 1
           FROM (SELECT setting_key, setting_value FROM settings) AS ceviri
          WHERE ceviri.setting_key = 'consent_text_en'
            AND ceviri.setting_value LIKE '%and the individual is%'
       );

-- Yeni sürümü arşive al — 011'dekiyle aynı ifade, bu kez çeviriyle birlikte.
-- Çeviri kaydedilmemişse text_en NULL girer: çıktı o zaman eldeki taslağı
-- basar ve bunu kâğıdın üstünde söyler (bkz. ConsentController::renderPrint).
-- INSERT IGNORE sayesinde 2.1 satırına dokunulmaz; imzalanmış formlar okudukları
-- metne bağlı kalır.
INSERT IGNORE INTO consent_versions (version, text, text_en, created_at)
SELECT
  (SELECT s2.setting_value FROM (SELECT setting_key, setting_value FROM settings) AS s2
    WHERE s2.setting_key = 'consent_version'),
  s.setting_value,
  (SELECT s4.setting_value FROM (SELECT setting_key, setting_value FROM settings) AS s4
    WHERE s4.setting_key = 'consent_text_en'),
  NOW()
  FROM (SELECT setting_key, setting_value FROM settings) AS s
 WHERE s.setting_key = 'consent_text'
   AND COALESCE(s.setting_value, '') <> ''
   AND (SELECT s3.setting_value FROM (SELECT setting_key, setting_value FROM settings) AS s3
         WHERE s3.setting_key = 'consent_version') IS NOT NULL;
