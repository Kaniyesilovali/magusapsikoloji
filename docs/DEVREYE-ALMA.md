# Devreye alma kontrol listesi

Panelin canlıda kullanıma hazır hâle gelmesi için yapılacaklar. **Sırayla ilerleyin** —
üstteki adımlar alttakilerin ön koşulu.

Panelin nasıl çalıştığı ve neden böyle kurulduğu [PANEL.md](PANEL.md) dosyasında.
Bu belge yalnız "ne yapmalıyım" sorusunun cevabı.

Menü adları paneldeki hâliyle yazılmıştır: **Merkez** (Bugün, Randevular, Görüşmeciler,
Müsaitlik, Ödemeler), **Site** (Site içeriği, KVKK metni), **Yönetim** (Kullanıcılar,
Sistem kayıtları, Sistem).

> ⚠ Paneldeki hiçbir ekran bugüne kadar canlıda çalıştırılarak denenmedi. Aşağıdaki
> testler ilk gerçek denemedir; beklenenden farklı davranan her şeyi not edin.

---

## 1 · Yedek ve veritabanı güncellemesi

> ⚠ Veritabanı güncellemesi **geri alınamaz**. Yedek almadan 2. adıma geçmeyin.

- [ ] **1 · Veritabanı yedeği al** — *cPanel*

  cPanel → **Yedekler (Backup)** → *MySQL Veritabanı Yedeğini İndir* → `magu6081_panel`
  veritabanını seçip bilgisayarınıza indirin.

  *Tamam sayılır:* Bilgisayarınızda `.sql.gz` uzantılı bir dosya var.

- [ ] **2 · Bekleyen güncellemeleri uygula** — *Panel*

  Panele süper admin olarak girin → **Sistem** → **“Bekleyen güncellemeleri uygula”**.

  Listede **üç** dosya görünmeli:
  - `002_payments.sql` — seans ücreti ve tahsilat
  - `003_reminders.sql` — randevu hatırlatmaları
  - `004_case_files.sql` — görüşmeci dosyası

  *Tamam sayılır:* “3 güncelleme uygulandı” mesajı çıktı ve sayfada artık
  “Veritabanı güncel — bekleyen güncelleme yok” yazıyor.

  *Patlarsa:* Hata mesajının tamamını paylaşın. Hangi dosyanın hangi SQL ifadesinde
  durduğu mesajda yazıyor.

---

## 2 · Sunucu ve yapılandırma

- [ ] **3 · Sunucu durumunu oku** — *Panel*

  **Sistem** ekranındaki “Sunucu durumu” listesine bakın: PHP sürümü, PHP eklentileri,
  seans notu şifrelemesi, içerik yönetimi, veritabanı.

  *Tamam sayılır:* Hangi satırların “dikkat” dediğini biliyorsunuz. 4. ve 6. adımlar
  bunları çözecek.

- [ ] **4 · `sodium` eklentisini aç** — *cPanel*

  *Yalnız 3. adımda “Seans notu şifrelemesi → dikkat” çıktıysa gerekli.*

  cPanel → **Select PHP Version** → eklenti listesinde `sodium` kutusunu işaretleyip
  kaydedin. Aynı ekranda `pdo_mysql`, `mbstring`, `openssl` de işaretli olmalı.

  *Tamam sayılır:* Sistem ekranını yenileyince “Seans notu şifrelemesi → çalışıyor”.

  *Açılmazsa:* Panel çalışmaya devam eder, yalnız seans notu ve görüşmeci dosyası
  yazılamaz. Ekran bunu açıkça söyler, sessizce şifresiz kaydetmez.

- [ ] **5 · `note_key` yedeğini al** — *Parola yöneticisi*

  cPanel → Dosya Yöneticisi → `/home/<kullanıcı>/magusa-panel-config/config.php` →
  `security.note_key` değerini kopyalayın. Parola yöneticinize,
  **veritabanı yedeğinden ayrı bir yere** kaydedin.

  *Neden:* Bu anahtar kaybolursa yazılmış tüm seans notları ve görüşmeci dosyaları
  **kalıcı olarak** kurtarılamaz. Veritabanı yedeği tek başına yetmez.

- [ ] **6 · GitHub token oluştur ve gir** — *GitHub + cPanel*

  1. GitHub → Settings → Developer settings → Personal access tokens →
     **Fine-grained tokens** → *Generate new token*
  2. **Repository access:** yalnız `magusapsikoloji` deposu. “All repositories” seçmeyin.
  3. **Permissions → Repository permissions → Contents:** `Read and write`.
     Başka izne gerek yok.
  4. Token'ı `config.php` dosyasına ekleyin:

  ```php
  'github' => [
      'token'  => 'github_pat_...',
      'repo'   => 'Kaniyesilovali/magusapsikoloji',
      'branch' => 'main',
  ],
  ```

  *Tamam sayılır:* Sistem ekranında “İçerik yönetimi (GitHub) → yapılandırılmış” ve
  **Site içeriği** menüsü kurulum yönergesi yerine gerçek formları açıyor.

- [ ] **7 · Cron'ları kur** — *cPanel*

  **İki** cron gerekiyor. cPanel → **Cron Jobs** → her biri için ayrı kayıt:

  | Ne | Zamanlama | Betik |
  |---|---|---|
  | Randevu hatırlatması | “Saatte bir” (`0 * * * *`) | `panel/cron/reminders.php` |
  | Seanslar arası check-in | Pazartesi 09:00 (`0 9 * * 1`) | `panel/cron/checkins.php` |

  Komutların tam hâli **Sistem** ekranında yazıyor — hatırlatmalar ve check-in
  bölümlerinin altındaki gri kutular. **Oradan kopyalayın**, elle yazmayın:

  ```
  /opt/cpanel/ea-php83/root/usr/bin/php /home/<kullanıcı>/public_html/panel/cron/reminders.php
  ```

  *Neden bu uzun yol:* `/usr/local/bin/php` sunucunun varsayılan PHP'sidir ve alan
  adı için seçtiğiniz sürümle aynı olmayabilir. Farklıysa cron çalışır, veritabanına
  yazar, ama `openssl` bulunmadığı için SMTP bağlantısı kurulamaz ve e-posta hiç
  gönderilmeden “başarısız” sayılır. Web tarafı doğru sürümle çalıştığı için
  **test e-postası geçer, cron geçmez** — teşhisi en zor arıza biçimi. Sistem
  ekranındaki komut panelin kendi sürümünden üretilir, bu yüzden doğrudur.

  *Tamam sayılır:* Bir saat içinde Sistem ekranında “Son çalışma → çalıştı” ve bir
  sonuç satırı (“0 gönderildi, 0 başarısız…”) görünüyor.

  *Not:* Saatte bir çalışması güvenlidir — her randevu bir kez uyarılır, ikinci
  e-posta gitmez. Check-in cron'u ilk pazartesiye kadar sessiz kalır; yalnız
  terapistin döngüyü başlattığı görüşmecilere gider, kurulur kurulmaz kimseye
  toplu ileti çıkmaz.

  *Gönderim başarısız çıkarsa:* cPanel → **Gönderimi İzle** ekranında o saate ait
  satır yoksa ileti sunucudan hiç çıkmamıştır — sorun SMTP bağlantısında, alıcıda
  değil. Cron çıktısı, Cron Jobs ekranının üstündeki “Cron E-posta” adresine gider
  ve hata metnini içerir.

- [ ] **8 · E-posta gönderimini test et** — *Panel*

  **Kullanıcılar** → *Yeni kullanıcı* → kendi ikinci e-posta adresinize bir hesap açın
  (rol: Görüşmeci; sonra silersiniz).

  *Tamam sayılır:* Davet e-postası geldi ve içindeki bağlantı şifre belirleme ekranını
  açıyor.

  *Gelmezse:* `config.php` içinde `mail.driver` değerini `'smtp'` yerine `'mail'`
  yapın — cPanel'de sunucunun kendi posta servisi genelde daha güvenilirdir. Kullanıcı
  listesinden “Daveti gönder” ile tekrar deneyin.

---

## 3 · Kurulum verisi

- [ ] **9 · Terapist hesaplarını aç** — *Panel*

  **Kullanıcılar** → *Yeni kullanıcı* → rol **Terapist**. Her terapist için tekrarlayın.

  Hesap “Davetli” olarak açılır; şifresini terapist kendi e-postasındaki bağlantıyla
  belirler. Panelde kimsenin şifresi görünmez.

- [ ] **10 · Çalışma saatlerini gir** — *Panel*

  **Müsaitlik** → üstten terapisti seçin → alttaki formdan gün + başlangıç + bitiş
  ekleyin. Öğle arası varsa günü ikiye bölün (09:00–13:00 ve 14:00–18:00).

  *Tamam sayılır:* Haftalık tabloda dolu günler saat aralıklarıyla görünüyor.

  *Not:* Şablon boş bırakılırsa “her saat uygun” sayılır ve mesai dışı uyarısı hiç
  çıkmaz. 15. adımı test edebilmek için en az bir gün girmelisiniz.

---

## 4 · Test · Görüşmeci ve randevu

- [ ] **11 · Deneme görüşmecisi ekle** — *Panel*

  **Görüşmeciler** → *Yeni görüşmeci*. Ad, telefon, birincil terapist. **Açık rıza kutusunu
  işaretleyin.** E-posta adresi de girin — 28. adımdaki hatırlatma testi buna bağlı.

  *Tamam sayılır:* Görüşmeci sayfasında “Onam formu imzalandı” ve tarih görünüyor.

- [ ] **12 · Onam formunu yazdır** — *Panel*

  Görüşmeci sayfası → Onam kutusundaki **“Onam formunu yazdır”**. Yeni sekmede açılır,
  üstte *Yazdır* düğmesi var.

  *Tamam sayılır:* Çıktı önizlemesinde görüşmecinin adı, metin, **aile yakını bilgisi
  kutusu** ve iki imza alanı (danışan, psikolog) var; panel menüsü çıktıda görünmüyor.

- [ ] **13 · Randevu oluştur** — *Panel*

  **Randevular** → *Yeni randevu*. Çalışma saatleri **içinde** bir saat seçin.
  Ücreti şimdilik boş bırakabilirsiniz.

  *Tamam sayılır:* Haftalık takvimde doğru günün altında görünüyor. **Bugün** ekranındaki
  gün cetvelinde de yerini alıyor (randevu bugüne verildiyse).

- [ ] **14 · Çakışma engelini dene** — *Panel*

  Aynı terapiste, **aynı saate** ikinci bir randevu oluşturmayı deneyin.

  *Beklenen:* Kayıt **geçmemeli**. Saat alanının altında “Bu saat dolu: …” diye çakışan
  randevuyu gösteren bir hata çıkmalı.

  *Geçerse:* Bu bir hata — haber verin.

- [ ] **15 · Mesai dışı uyarısını dene** — *Panel*

  Çalışma saatleri **dışında** bir saate randevu oluşturmayı deneyin.

  *Beklenen:* Sarı uyarı çıkar ve formda bir onay kutusu belirir. Kutuyu işaretleyip
  tekrar gönderince kayıt **geçer** — bu engel değil, uyarıdır.

- [ ] **16 · Durum değiştir ve iptal et** — *Panel*

  **Liste** görünümünde randevunun altındaki *Onayla* / *Tamamlandı* / *Gelmedi*
  düğmelerinden birini deneyin. Sonra *İptal et* → gerekçe yazın → *Randevuyu iptal et*.

  *Tamam sayılır:* Randevu üstü çizili görünüyor, altında iptal gerekçesi yazıyor.
  İptal edilen saate artık yeni randevu **verilebiliyor**.

---

## 5 · Test · Seans notu ve görüşmeci dosyası

- [ ] **17 · Terapist olarak giriş yap** — *Panel*

  Çıkış yapın, 9. adımda açtığınız terapist hesabıyla girin.

  *Tamam sayılır:* Menüde **Kullanıcılar**, **Sistem** ve **KVKK metni** *görünmüyor*.

- [ ] **18 · Seans notu yaz** — *Panel*

  **Randevular** → üstteki `Hafta | Ay | Liste` anahtarını **Liste**'ye alın → kendi
  randevunuzun altındaki **“Seans notu”** → birkaç cümle yazıp kaydedin.

  *Liste görünümü şart:* Düzenle, Seans notu, durum ve iptal düğmelerinin hepsi
  yalnız orada. Hafta ve Ay görünümleri okumak içindir, kutulara tıklayınca not
  bağlantısı çıkmaz.

  Bağlantının görünmesi iki şeye bağlı: randevu **iptal edilmemiş** olmalı ve
  terapisti **siz** olmalısınız. Yöneticide hiç çıkmaz (bkz. 21. adım).

  *Tamam sayılır:* “Seans notu şifrelenerek kaydedildi” mesajı çıktı ve listede
  bağlantı artık **“Seans notu ✓”** diyor.

  *“Şifreleme kullanılamıyor” çıkarsa:* 4. adıma dönün, `sodium` kapalı.

- [ ] **19 · Notu tekrar aç** — *Panel*

  Aynı bağlantıyı yeniden açın.

  *Beklenen:* Yazdığınız metin aynen geliyor. Bu, şifre çözmenin çalıştığı anlamına
  gelir — **en kritik testlerden biri.**

  *Boş gelirse ya da hata verirse:* Hemen haber verin, başka not yazmayın.

- [ ] **20 · Görüşmeci dosyası yaz ve tekrar aç** — *Panel*

  **Görüşmeciler** → deneme görüşmecisini açın → **Dosya** düğmesi → birkaç satır yazıp
  kaydedin. Sonra tekrar açın.

  *Beklenen:* Metin aynen geliyor ve düğme artık **“Dosya ✓”** diyor.

  *Fark:* Seans notu tek görüşmeyi, dosya sürecin tamamını (başvuru nedeni, öykü,
  formülasyon, plan) taşır.

- [ ] **21 · Yöneticinin göremediğini doğrula** — *Panel*

  Süper admin hesabına dönün. Aynı randevuya ve aynı görüşmeciye bakın.

  *Beklenen:* Ne **“Seans notu”** bağlantısı ne de **“Dosya”** düğmesi görünüyor.
  Süper admin dahil hiçbir yönetici bu ikisini okuyamaz — bilinçli bir tasarım kararı.

- [ ] **22 · Sistem kayıtlarını denetle** — *Panel*

  **Sistem kayıtları** → filtreden *Seans notu yazıldı* ve *Görüşmeci dosyası yazıldı*
  kayıtlarına bakın.

  *Beklenen:* Kim, ne zaman yazdı görünüyor ama **içerik hiçbir yerde yok**.
  KVKK açısından doğru davranış budur.

---

## 6 · Test · Ödemeler

- [ ] **23 · Seans ücreti gir** — *Panel*

  **Ödemeler** → bir seansı *Aç* → “Seans ücreti” kutusuna tutar yazın
  (`1500` ya da `1.500,00`) → kaydedin.

  *Tamam sayılır:* Üstte “Seans ücreti 1.500,00 ₺”, durum **Ödenmedi**.

- [ ] **24 · Kısmi tahsilat ekle** — *Panel*

  Alttaki formdan ücretin **bir kısmını** girin (1500'ün 500'ü) → yöntem seçin →
  *Tahsilat ekle*.

  *Beklenen:* Durum **Kısmi**, “Kalan 1.000,00 ₺”.

- [ ] **25 · Kalanı tahsil et** — *Panel*

  Tutar alanı kalanı otomatik öneriyor. *Tahsilat ekle*.

  *Beklenen:* Durum **Ödendi**, kalan 0,00 ₺, listede iki tahsilat satırı.

- [ ] **26 · Özeti kontrol et** — *Panel*

  **Ödemeler** listesine dönün (varsayılan aralık bu ay).

  *Beklenen:* Üstteki üç kutu tutarlı — toplam ücret, tahsil edilen, bekleyen.
  Durum filtresini *Ödenmedi* yapınca liste daralıyor.

- [ ] **27 · Terapistin tahsilat ekleyemediğini doğrula** — *Panel*

  Terapist hesabına geçin → **Ödemeler** → kendi seansınızı açın.

  *Beklenen:* Ücreti **girebiliyorsunuz**, ama tahsilat formu yerine “Tahsilat kaydı
  merkez yönetimi tarafından girilir” yazısı var. Listede yalnız kendi randevularınız
  görünüyor.

---

## 7 · Test · Randevu hatırlatması

- [ ] **28 · Yarına randevu ver** — *Panel*

  11. adımdaki görüşmeciye (e-posta adresi kayıtlı olan) **yarın** için bir randevu verin.

  *Tamam sayılır:* **Sistem** ekranında “Sıradaki gönderim → 1 randevu bekliyor”.

- [ ] **29 · Hatırlatmanın gittiğini doğrula** — *Panel + e-posta*

  Cron'un çalışmasını bekleyin (en fazla bir saat).

  *Beklenen:* Görüşmecinin adresine “Randevu hatırlatması” konulu e-posta geldi ve Sistem
  ekranında “1 gönderildi” yazıyor.

  *Gelmezse:* 7. adımdaki cron kurulmamış olabilir — Sistem ekranında “hiç çalışmadı”
  yazıyorsa sebep budur. E-posta ayarları için 8. adıma bakın.

---

## 8 · Test · Site içeriği

- [ ] **30 · İletişim bilgilerini düzelt** — *Panel*

  **Site içeriği** → *İletişim bilgileri*. Şu an geçersiz değerler var:
  `+90 5XX XXX XX XX` ve `9055555`. Gerçek numarayı yazıp *Kaydet ve yayınla*.

  *Tamam sayılır:* 2–3 dakika sonra sitenin alt bilgisindeki numara güncellenmiş oluyor.

  *Not:* Bu, sitedeki WhatsApp linklerinin **bir kısmını** düzeltir. Blog yazılarındaki
  66 link ayrı dosyalarda; numarayı bana ilettiğinizde onları ben düzelteceğim.

- [ ] **31 · Bir SSS cevabını düzenle** — *Panel*

  **Site içeriği** → *SSS içeriği* → bir konu → küçük bir değişiklik → *Kaydet ve yayınla*.

  *Beklenen:* “Site yeniden yayınlanıyor” uyarısı çıkıyor ve birkaç dakika sonra
  değişiklik sitede görünüyor.

  *Bu ekran açılmıyorsa:* 6. adımdaki token girilmemiş.

---

## 9 · KVKK

- [ ] **32 · Metnin boşluklarını doldur** — *Panel*

  **KVKK metni** ekranındaki taslakta köşeli parantezli alanlar var:
  `[Kurum unvanı]`, `[saklama süresi]`, `[iletişim adresi]`.

  *Tamam sayılır:* Metinde köşeli parantez kalmadı, kaydedildi.

- [ ] **33 · Hukukçuya onaylat** — *Kurum dışı*

  Metni bir hukukçuya inceletmeden gerçek görüşmecilerden rıza almayın.

  *Neden:* Paneldeki metin bir **taslaktır**, hukuki tavsiye değildir. Panel bunu her
  ekranda taslak olarak işaretler.

- [ ] **34 · Onaylanmış metni sürümüyle kaydet** — *Panel*

  Son metni yapıştırın ve **sürüm numarasını yükseltin** (`1.0` → `1.1`).

  *Not:* Metni değiştirip sürümü aynı bırakırsanız panel kaydetmeyi reddeder. Her
  görüşmeci kaydı hangi sürüme rıza verdiğini ayrıca saklar; iki farklı metnin aynı
  sürümü taşıması kaydın ispat değerini bozardı.

---

## 10 · Temizlik

- [ ] **35 · Deneme kayıtlarını sil** — *Panel*

  Silinecekler:

  | Kayıt | Nerede | Not |
  |---|---|---|
  | **Deneme Görüşmeci** | Görüşmeciler | 11. adımda açıldı; randevuları ve seans notu da gider |
  | **Foxy Sarıkız** | Görüşmeciler | deneme kaydı, randevusu Yaprak'a bağlı |
  | **Yaprak P Yeşilovalı** | Kullanıcılar | deneme terapisti; daveti geçersiz adrese gitti, hiç aktifleşmedi |
  | 8. adımdaki deneme kullanıcısı | Kullanıcılar | e-posta testi için açıldıysa |

  **Sıra önemli: önce görüşmeciler, sonra terapist.** Görüşmeci sayfasının altındaki
  *Kalıcı olarak sil* randevuları ve seans notlarını da siler — deneme verisi için
  doğru seçenek budur. Terapist hesabı ise üzerine kayıtlı randevu kaldığı sürece
  silinemez; panel bu durumda hesabı silmek yerine **askıya alır** ve bunu söyler.
  Görüşmeciler gidince randevular da gittiği için terapist normal silinir.

  *Tamam sayılır:* Görüşmeciler ve Kullanıcılar listelerinde yalnız gerçek kayıtlar var.

---

## Bekleyenler — bilgi/dosya gelince yapılacak

Bunlar için adım atmanıza gerek yok; bilgiyi ilettiğinizde yapılacak.

| Bekleyen | Ne olacak |
|---|---|
| **WhatsApp numarası** | 55 içerik dosyasındaki `905XXXXXXXXX` placeholder'ı tek commit'te düzeltilecek. Şu an sitedeki bütün WhatsApp düğmeleri çalışmıyor. |
| **Logo dosyası** | `assets/images/` klasörü **tamamen boş**; `og-image.jpg`, `logo.png`, `favicon.png`, `apple-touch-icon.png` dördü de canlıda 404. Sonucu: siteyi WhatsApp'ta paylaşınca önizleme boş çıkıyor, schema'daki logo geçersiz. Logodan doğru boyutlar türetilecek. |

Düzeltmelerden sonra `npm run check:live` ile canlı site yeniden taranabilir.

---

## Sonraki tur (bu liste bitince)

Panel şu an Faz 1a–1c, 2a, 3a–3b kapsıyor. Sıradaki adaylar, getiri sırasına göre:

1. **Siteden randevu talebi** — site şu an yalnız WhatsApp'a yönlendiriyor; form →
   panelde “bekleyen talep” kuyruğu.
2. **Makbuz/PDF çıktısı** — ödeme takibi var, doğal devamı.
3. **Raporlar** — aylık gelir, no-show oranı, terapist doluluğu.
4. **Otomatik şifreli yedek** — `note_key` kaybolursa notlar kalıcı gider; yedek şu an
   tamamen elle.

Klinik tarafta değerli olabilecekler (bir psikoloğa doğrulatılmalı): görüşmeci kaydında
**risk işareti**, ölçek/test skorlarının zaman içinde takibi, seans notu şablonu
(SOAP/DAP), dosya eki (KVKK açısından en ağır madde).
