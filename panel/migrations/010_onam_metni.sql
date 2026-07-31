-- Birleştirilmiş onam metnini veritabanına da yazar.
-- Çalıştırma:  panel → Sistem → "Bekleyen güncellemeleri uygula"

-- 009 numaralı göç yalnız sürüm numarasını düzeltti. Eksik kalan şuydu: panel,
-- metni `Settings::get('consent_text', starterText())` ile okur. Yani koddaki
-- metin bir VARSAYILANDIR; ekranda bir kez kaydedilmiş metin varsa, kod ne
-- derse desin veritabanındaki kazanır. Kurumda kayıtlı bir metin olduğu için
-- birleştirilmiş form panelde görünmedi.
--
-- Burası o metni değiştirir, ama körlemesine değil:
--
--  * Yalnızca "45-50" geçmeyen kayıtlara dokunur. Bu ifade yeni metinde var,
--    eski KVKK metninde yok — yani hedef, birleştirme öncesi kalmış metin.
--    Kurum kendi metnini yazdıysa ve içinde 45-50 geçiyorsa, kayıt korunur.
--  * Değiştirmeden ÖNCE eskisini `consent_text_onam_oncesi` anahtarına kopyalar.
--    Kurumun elle girdiği bilgiler (unvan, saklama süresi, iletişim adresi)
--    kaybolmaz; veritabanında durur ve yeni metne elle taşınabilir.
--  * Sürümü yalnız gerçekten değiştirdiği kayıtlarda 2.0'a çeker.
--
-- Daha önce 1.0 ile onam işaretlenmiş danışanlar 1.0'da kalır: sürüm geçmişe
-- dönük çalışmaz, o kayıtlar imzaladıkları metne bağlı kalmalı.

-- 1) Eskisini sakla. Alt sorgu türetilmiş tablo üzerinden: MySQL güncellenen/
--    eklenen tabloyu doğrudan okuyan alt sorguya izin vermiyor.
INSERT IGNORE INTO settings (setting_key, setting_value, updated_at)
SELECT 'consent_text_onam_oncesi', s.setting_value, NOW()
  FROM (SELECT setting_key, setting_value FROM settings) AS s
 WHERE s.setting_key = 'consent_text'
   AND COALESCE(s.setting_value, '') <> ''
   AND s.setting_value NOT LIKE '%45-50%';

-- 2) Birleştirilmiş metni yaz. Metin, koddaki taslakla (ConsentController::
--    starterText) birebir aynı; ikisi birlikte değişmeli.
UPDATE settings
   SET setting_value = 'Bilgilendirilmiş Onam Formu, sahip olduğunuz haklarla ve sorumluluklarla
ilgili sizi bilgilendirmek amacıyla oluşturulmuştur. Psikoloğunuzla
karşılıklı olarak onayladığınız bir anlaşma niteliği taşıyacaktır.

PSİKOTERAPİ SÜRECİNE YÖNELİK BİLGİLENDİRME

- Seans süresi ortalama 45-50 dakikadır.
- Seanslara düzenli ve zamanında katılım, sağlıklı bir psikoterapötik
  ilişki kurabilmek için önemli ve gereklidir.
- Seansa danışan tarafından geç kalınması durumunda, yalnızca geriye kalan
  süre kadar görüşme yapılabilecektir. Eğer gecikme psikolog kaynaklıysa,
  psikolog süreyi tamamlamakla yükümlüdür.
- Seans ücreti, seans öncesinde ödenmektedir.
- Seansı erteleme veya iptal etmeniz gereken durumlarda, seans saatinden
  en az 24 saat öncesinde bilgi vermeniz beklenmektedir. Son 24 saat içinde
  iptal olan seanslar için seans ücreti talep edilmektedir.
- Yeni randevular, psikoterapi sürecinin seyrine göre haftalık veya iki
  haftada bir olacak şekilde planlanır.
- Online görüşmeler sırasında ise rahatsız edilme ihtimali olmayan, sessiz
  ve sakin, dikkat dağıtıcı unsurların olmadığı ortamlar tercih edilmelidir.
- Seans süresince telefonlar sessizde olmalıdır.
- Etik ilkeler ve gizliliğin korunması amacıyla, ses ve/veya görüntü kaydı
  almaya izin verilmemektedir.

GİZLİLİK VE GÜVENİLİRLİK İLKESİ

- Psikolog ve danışan arasında konuşulan her şey gizlidir ve üçüncü
  kişilerle paylaşılmaz.
- Psikolog, seans notları da dahil olmak üzere, psikoterapi süreci boyunca
  tutacağı raporları ve danışanın kişisel bilgilerini, kimsenin
  ulaşamayacağı şekilde saklamak ve muhafaza etmekle yükümlüdür. Bu kural,
  psikoterapötik süreç sona erdikten sonra da geçerlidir.
- Gizlilik ilkesinin, psikolog tarafından ihlal edilebileceği sadece 2 koşul
  mevcuttur:
  - Bu koşulların en önemlisi; danışanın kendine ve/veya bir başkasına zarar
    verme riski görülmesidir. Bu gibi durumlarda, psikolog gerekli mercilere
    bilgi vermek ve/veya danışanın bir aile yakınıyla iletişime geçmek
    durumundadır.
  - Diğer bir koşul ise herhangi bir hukuki süreçte, mahkeme kararıyla
    psikologdan bilgi istenilmesi durumudur. Bu durumda da psikolog yasal
    olarak, mahkeme kararını uygulamaya geçirmek zorundadır.
- Psikoterapötik süreç içinde gerçekleşebilecek herhangi bir olağandışı
  durumda danışanı koruyabilmek ve erken müdahaleyi sağlayabilmek için bir
  aile yakını bilgisi almak gereklidir.

KİŞİSEL BİLGİLERİNİZİN KAYDI

- Kaydınızda ad-soyadınız, iletişim bilgileriniz ve doğum tarihiniz,
  randevularınız ve seans sürecine ilişkin notlar tutulur. Seans notları,
  yasa önünde sağlık verisi sayılır; en korunaklı bilgi türüdür.
- Bu bilgiler yalnızca randevunuzun planlanması, sürecin yürütülmesi ve
  takibi ile yasal saklama yükümlülükleri için kullanılır. Başka hiçbir
  amaçla kullanılmaz.
- Seans notları şifrelenerek saklanır ve yalnızca notu tutan psikolog
  tarafından görüntülenebilir. Kayıtlar [saklama süresi] süresince saklanır,
  sürenin sonunda imha edilir.
- Kaydınız, yukarıdaki gizlilik başlığında belirtilen iki koşul dışında
  hiç kimseyle paylaşılmaz.
- Sağlık verisi niteliğindeki seans notlarınız, KVKK m.6 uyarınca yalnızca
  açık rızanızla işlenebilir; bu formu imzalamanız bu rızayı da kapsar.
  Diğer bilgileriniz sözleşmenin kurulması ve ifası hukuki sebebine dayanır.
- KVKK m.11 kapsamında bilgilerinize erişme, düzeltilmesini veya silinmesini
  isteme ve işlenmesine itiraz etme haklarına sahipsiniz. Başvurularınızı
  [iletişim adresi] üzerinden iletebilirsiniz.

GÖNÜLLÜLÜK İLKESİ

- Psikoterapi sürecine başlamak ve sürecin devamlılığını sağlamak için
  danışanın gönüllülüğü çok önemlidir. Danışanın kendi isteğiyle ve hiçbir
  zorlama altında olmadan psikolojik destek istediğinden emin olunmalıdır.
- Danışan, istediği zaman görüşmeleri sonlandırma hakkına sahiptir.
- Psikoterapötik sürecinize son vermek istemeniz, yeniden başlamaya ihtiyaç
  duyduğunuzda bir engel oluşturmaz. İstediğiniz zaman yeniden randevu
  alabilir ve sürecinize geri dönebilirsiniz.

"Bu formu imzalayarak, yukarıda belirtilen hak ve sorumlulukları kabul
ediyorum. Psikolojik destek süreci için gönüllü ve psikoloğum ile iş birliği
içinde çalışmaya istekli olduğumu beyan ederim. Seans notlarım dahil kişisel
bilgilerimin yukarıda anlatılan biçimde işlenmesine açık rızam ile onay
veriyorum."',
       updated_at = NOW()
 WHERE setting_key = 'consent_text'
   AND COALESCE(setting_value, '') <> ''
   AND setting_value NOT LIKE '%45-50%';

-- 3) Metni gerçekten değiştirdiysek sürüm de 2.0 olsun. Kanıt, 1. adımda
--    bırakılan yedek satırın varlığı.
UPDATE settings
   SET setting_value = '2.0',
       updated_at    = NOW()
 WHERE setting_key = 'consent_version'
   AND setting_value <> '2.0'
   AND EXISTS (
         SELECT 1
           FROM (SELECT setting_key FROM settings) AS yedek
          WHERE yedek.setting_key = 'consent_text_onam_oncesi'
       );
