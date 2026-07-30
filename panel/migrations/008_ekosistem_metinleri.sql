-- "Haftanın hâli" halkasının metinleri — görüşmeci başına uyarlanabilir.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- Alan sözlüğü koddan geliyor (Ecosystem::DOMAINS) ve öyle kalıyor: on sekiz
-- alanın adı, kısa adı ve ipucu her dosyada aynı başlıyor. Eksik olan, o
-- ortak sözlüğün TEK BİR ÇOCUĞA göre eğilebilmesiydi.
--
-- Üç gerçek durum bunu zorunlu kıldı:
--
--  1. "Büyükler" bir evde nine ve dede, başka bir evde yalnız "Babaanne".
--     Ebeveyn kendi hayatındaki adı görmezse alanı boş geçiyor.
--  2. Sözlükte karşılığı olmayan şeyler: "Dans kursu", "Babanın nöbetleri",
--     "Yeni bebek". Bunlar için kodda alan açmak, her aileye bir sürüm demek.
--  3. Ergen ve yetişkin dosyalarında sorunun kendisi yanlış duruyordu:
--     "çocuğunun sırtını" diye başlayan bir cümle, formu kendisi dolduran
--     on yedi yaşındaki birine yazılmamıştı.
--
-- Boş bırakılan her alan varsayılana döner: satır yalnız SAPMAYI tutuyor
-- (ecosystem_domains'in kendi kuralı). Böylece koddaki metin ileride
-- düzeltilirse, uyarlanmamış dosyalar yeni metne kendiliğinden geçer.
ALTER TABLE ecosystem_domains
  ADD COLUMN label VARCHAR(60)  NULL AFTER domain_key,
  ADD COLUMN hint  VARCHAR(120) NULL AFTER label;

-- Halkanın üstündeki tek soru. NULL = koddaki varsayılan (Ecosystem::PROMPT).
-- Görüşmecinin kendi satırında duruyor çünkü sorulan kişi o: ergen dosyasında
-- "senin sırtını", ebeveyn dosyasında "çocuğunun sırtını".
ALTER TABLE clients
  ADD COLUMN checkin_prompt VARCHAR(200) NULL AFTER checkin_auto;
