-- Check-in ölçeğindeki "ruh hali" sözcüğünü "duygudurum" ile değiştirir.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- Dil rehberi "ruh" sözcüğünü hiçbir Türkçe metinde kullandırmıyor; sitenin
-- her yeri buna göre yazılmıştı ama panel atlanmıştı. Bireyin haftada bir
-- gördüğü soru hâlâ "ruh hâlin nasıldı?" diye soruyordu.
--
-- 010'daki varsayılanlar da düzeltildi, ama o göç uygulanmış kurulumlarda bir
-- daha çalışmaz: metin çoktan checkin_scales'e yazıldı. Değişikliğin oraya
-- ulaşmasının tek yolu yeni bir göç.
--
-- Soru yeniden yazıldı, kelime kelime çevrilmedi: "duygudurumun nasıldı"
-- bireye sorulacak bir cümle değil, klinik bir terim. Sorunun kendisi
-- gündelik kalıyor, ölçeğin adı terimi taşıyor.
--
-- 012'deki desen: yalnız eski varsayılanla birebir eşleşen satırlar
-- değişiyor. Merkez metni panelden kendisi düzenlediyse o düzenleme kalır —
-- göç, kurumun kendi cümlesinin üstüne yazmaz.
UPDATE checkin_scales
   SET label = 'Duygudurum'
 WHERE scale_key = 'mood'
   AND label = 'Ruh hali';

UPDATE checkin_scales
   SET question = 'Bu hafta kendini genel olarak nasıl hissettin?'
 WHERE scale_key = 'mood'
   AND question = 'Bu hafta genel olarak ruh hâlin nasıldı?';

-- 010 öncesinden kalan ayar satırları: artık okunmuyorlar (tek kaynak
-- checkin_scales) ama yasaklı sözcüğü veritabanında tutmalarının bir sebebi
-- yok. Kurulumda bu satırlar hiç yoksa bu iki cümle bir şey bulmaz.
UPDATE settings
   SET setting_value = 'Duygudurum'
 WHERE setting_key = 'checkin_measure_mood_label'
   AND setting_value = 'Ruh hali';

UPDATE settings
   SET setting_value = 'Bu hafta kendini genel olarak nasıl hissettin?'
 WHERE setting_key = 'checkin_question_mood'
   AND setting_value = 'Bu hafta genel olarak ruh hâlin nasıldı?';
