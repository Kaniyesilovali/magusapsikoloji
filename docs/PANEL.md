# Yönetim Paneli — Mimari ve Kurulum

> Panel canlıda ama henüz devreye alınmadı. Yapılacaklar adım adım
> [DEVREYE-ALMA.md](DEVREYE-ALMA.md) dosyasında.

`/panel` altında çalışan PHP + MySQL yönetim paneli. Public site (Eleventy) ile aynı
depoda durur ve aynı FTP hattından yayınlanır; ayrı bir yükleme adımı yoktur.

```
panel/ (repo)  ──npm run build──▶  _site/panel/  ──GitHub Actions FTPS──▶  public_html/panel/
                                                                              │
                                        /home/<kullanici>/magusa-panel-config/config.php   ← sırlar (elle, bir kez)
                                                                              │
                                                                          MySQL
```

**Neden bu mimari:** cPanel'de composer/SSH garantisi yok, dağıtım FTP. Bu yüzden
framework kullanılmadı; sade PHP 8 + PDO + tek giriş noktalı yönlendirici var.
Veritabanı şifresi ve şifreleme anahtarı `panel/` dizininin **dışında** tutulur —
aksi hâlde `npm run build` onları `_site/panel/` içine kopyalar ve yayına çıkarırdı.

---

## Roller

| Yetki | Süper Admin | Admin | Terapist | Danışan |
|---|:--:|:--:|:--:|:--:|
| Kullanıcı oluştur/düzenle/sil | ✔ | ✔ (yalnız terapist + danışan) | — | — |
| Süper admin / admin hesaplarını yönet | ✔ | — | — | — |
| Tüm danışan kayıtları | ✔ | ✔ | kendi danışanları | — |
| Danışan kaydı oluştur / terapist ata | ✔ | ✔ | — | — |
| Danışan iletişim bilgisini düzelt | ✔ | ✔ | kendi danışanları | — |
| Danışan kaydını kalıcı sil | ✔ | — | — | — |
| Tüm randevular | ✔ | ✔ | kendi randevuları | kendi randevuları (salt okunur) |
| Randevu oluştur/düzenle/iptal | ✔ | ✔ | kendi takvimine | — |
| Çalışma saati ve izin tanımla | ✔ | ✔ | kendi müsaitliği | — |
| KVKK metnini düzenle | ✔ | ✔ | — | — |
| **Seans notu okuma/yazma** | **—** | **—** | **yalnız kendi yazdığı** | — |
| Sistem kayıtları (audit log) | ✔ | — | — | — |
| Kendi profili | ✔ | ✔ | ✔ | ✔ |

Terapistin "kendi danışanı" iki yoldan tanımlıdır: birincil terapisti olduğu kişiler
ve fiilen randevusunu gördüğü kişiler. Kural tek yerde:
[`panel/src/ClientScope.php`](../panel/src/ClientScope.php). Terapist bir danışanı
kendine atayamaz — birincil terapist ataması ve panel hesabı bağlama yöneticide kalır.

Kurallar tek yerde: [`panel/src/Rbac.php`](../panel/src/Rbac.php).

> Süper admin bilinçli olarak seans notlarının dışında bırakıldı. Notlar özel nitelikli
> sağlık verisidir; "her şeyi görebilen hesap" olmaması hem KVKK hem meslek etiği açısından
> doğru varsayılandır.

Kimse kendi rolünü veya durumunu değiştiremez, kendi hesabını silemez; sistemdeki
son aktif süper admin silinemez ve rolü düşürülemez.

---

## Kurulum (tek seferlik)

### 1. Hosting gereksinimleri

cPanel → **Select PHP Version** ekranında:

- PHP **8.3** (önerilen). Teknik alt sınır 8.1'dir — kodun kullandığı en yeni özellik
  `never` dönüş tipidir. 8.3'ü seçin: aktif güvenlik desteği var ve 8.2 bakım
  penceresinin sonuna yaklaşıyor.
- Eklentiler: `pdo_mysql`, `mbstring`, `openssl`, **`sodium`** (seans notu şifrelemesi için)

`sodium` yoksa panel çalışır ama şifreli seans notu özelliği devre dışı kalır.

### 2. Veritabanı

cPanel → **MySQL Databases**: bir veritabanı + kullanıcı oluşturun, kullanıcıya
veritabanı üzerinde **ALL PRIVILEGES** verin.

### 3. Yapılandırma dosyası

Config dosyası **`public_html`'in dışında**, ana dizinde durur. İçinde olsaydı
`magusapsikoloji.com/…/config.php` adresinden veritabanı şifresi okunabilirdi.

cPanel → **Dosya Yöneticisi** adımları:

1. Sol ağaçta en üstteki **🏠 ana dizine** tıklayın — `public_html`'in bir üstü.
   Adres çubuğunda `/home/xxxxxxx` görünür; `xxxxxxx` sizin cPanel kullanıcı adınızdır
   (örn. `magu6081`). Bu adı **kendiniz yazmazsınız**, zaten oradasınızdır.
2. **+ Klasör** → ad: `magusa-panel-config`
3. Klasörün içine girip **+ Dosya** → ad: `config.php`
4. Dosyayı seçip **Düzenle** ile açın; [`panel/config.example.php`](../panel/config.example.php)
   içeriğini yapıştırıp doldurun.

Doğru sonuç:

```
/home/xxxxxxx/magusa-panel-config/config.php   ← sırlar (web'den erişilemez)
/home/xxxxxxx/public_html/                     ← site
/home/xxxxxxx/public_html/panel/               ← panel (deploy ile gelir)
```

> ⚠ `magusa-panel-config` klasörünü **`public_html` içine açmayın.** Panel dosyayı
> yalnızca `public_html`'in bir üstünde arar; içeride olursa hem bulunamaz hem de
> tarayıcıdan okunabilir hâle gelir.

`note_key` değerini yerelde üretin (bu değer veritabanına asla yazılmaz).
macOS/Linux'ta PHP kurulu olmadan da çalışır:

```bash
openssl rand -base64 32
```

> ⚠ **`note_key` kaybolursa şifreli seans notları kalıcı olarak kurtarılamaz.**
> Bu değeri bir parola yöneticisinde ayrıca saklayın. Değeri sonradan değiştirmeyin.

### 4. Şema ve ilk hesap

Panel yayına çıktıktan sonra `https://magusapsikoloji.com/panel/` adresine gidin.
Hiç kullanıcı yokken **ilk kurulum ekranı** açılır:

1. "Tabloları oluştur" → şema kurulur
2. Süper admin bilgilerini girin

İlk hesap oluşur oluşmaz `/panel/kurulum` **kalıcı olarak 404 döner**; yeniden açılamaz.

SSH varsa aynı işi komut satırından da yapabilirsiniz:

```bash
php panel/migrations/migrate.php
php panel/migrations/migrate.php --create-admin
```

### 5. E-posta

Davet ve şifre sıfırlama bağlantıları e-posta ile gider. cPanel → **Email Accounts**
ile `panel@magusapsikoloji.com` hesabı açıp SMTP bilgilerini config'e yazın.
`driver: 'mail'` de çalışır ama iletiler çoğunlukla spam'e düşer.

---

## Yerel geliştirme

macOS'ta sistem PHP'si yoktur:

```bash
brew install php mysql
brew services start mysql
mysql -u root -e "CREATE DATABASE magusa_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Yapılandırma **repo kökünün dışında değil, `panel/` dizininin dışında** aranır:

```
<repo>/magusa-panel-config/config.php      # .gitignore'da
```

`app.env` → `local`, `app.debug` → `true`, `mail.driver` → `log` yapın.

```bash
npm run dev:panel        # panel → http://localhost:8000
npm run dev:css:panel    # panel Tailwind watch (ayrı terminal)
npm run dev              # public site → http://localhost:8080 (PHP çalıştırmaz)
```

Eleventy dev sunucusu PHP yorumlamaz; panel her zaman ayrı portta çalışır.

---

## Randevu kuralları

Zamanlama kararları tek yerde: [`panel/src/Scheduling.php`](../panel/src/Scheduling.php).

**Engel (kayıt geçmez).** Aynı anda başka randevusu olan taraf. Kontrol hem terapist
hem danışan için yapılır — bir danışanın aynı saatte iki farklı terapiste yazılması da
gerçek bir çakışmadır ve yalnız terapist takvimine bakmak bunu kaçırırdı. İptal edilen
ve "gelmedi" işaretli randevular saati serbest bırakır.

**Uyarı (onaylanırsa geçer).** Terapistin çalışma saatleri dışı, izin aralığı, geçmiş
tarih. Bunlar engellenmez; form uyarıyı gösterir ve kullanıcı onay kutusunu işaretleyip
tekrar gönderirse kayıt geçer. Acil görüşme ve telafi seansı meşru ihtiyaçlardır.

**Müsaitlik şablonu boşsa uyarı da çıkmaz.** Hiç çalışma saati girilmemiş bir terapist
için "her saat uygun" varsayılır; "hiçbir saat uygun değil" değil.

İzin eklemek, o aralıktaki planlı randevuları otomatik iptal etmez — kaç randevunun
etkilendiği söylenir, kararı kullanıcı verir.

> Randevu kartındaki **idari not** alanı klinik içerik için değildir; organizasyon
> notudur (ör. "faturayı kurum ödeyecek"). Klinik içerik şifreli seans notuna yazılır.

### Bildirimler

Randevu oluşturma, güncelleme ve iptal ekranlarında **"E-posta ile bildir"** kutusu
vardır (varsayılan açık). Bildirim danışana (e-posta adresi kayıtlıysa) ve randevunun
terapistine gider; işlemi yapan kişiye kendi eylemi bildirilmez.

E-posta içeriği bilinçli olarak yalındır: tarih, saat, terapist, görüşme yeri. İdari
not ve danışanın diğer bilgileri gönderilmez — e-posta şifresiz bir kanaldır.
Gönderim başarısız olursa **kayıt geri alınmaz**, yalnız uyarı gösterilir.

### Hatırlatmalar (cron)

Danışanlara, randevudan varsayılan olarak 24 saat önce hatırlatma e-postası gider.
Gönderimi panel kendi kendine tetiklemez — cPanel cron'u
[`panel/cron/reminders.php`](../panel/cron/reminders.php) betiğini çalıştırır:

```
cPanel → Cron Jobs → Saatte bir (0 * * * *)
/usr/local/bin/php /home/<kullanıcı>/public_html/panel/cron/reminders.php
```

Saatte bir çalışması güvenlidir: her randevu `reminder_sent_at` ile bir kez işaretlenir.
Gönderim başarısız olursa alan boş bırakılır ve sonraki koşuda yeniden denenir; deneme,
randevu pencereden çıkınca kendiliğinden durur. Cron günlerce durmuş olsa bile **geçmiş
randevular için uyarı gitmez** — sorgu yalnız gelecekteki randevuları alır.

Hatırlatma yalnız danışana gider; terapist zaten kendi takvimine bakıyor ve her seans için
ikinci bir e-posta gürültü olurdu.

Ayarlar `settings` tablosunda: `reminder_hours_before` (varsayılan 24) ve
`reminders_enabled` (varsayılan 1). Cron'un çalışıp çalışmadığı, son sonucu ve kuyrukta
kaç randevu olduğu **Sistem** ekranında görünür — kurulmamış bir cron'un tek belirtisi
"hiç çalışmadı" satırıdır.

> Betik `cron/` dizinindedir ve `.htaccess` bu dizine web'den erişimi kapatır.

---

## Danışan dosyası

`/danisanlar/{id}/dosya` — [`CaseFileController`](../panel/src/Controllers/CaseFileController.php).

Seans notundan ayrıdır çünkü ayrı sorulara cevap verirler: seans notu "o gün ne oldu",
dosya "bu kişiyle ne üzerinde çalışıyoruz" (başvuru nedeni, öykü, formülasyon, plan).
Terapist ikincisini her seans öncesi okumak ister ve bunun için on iki seans notunu tek
tek açmak zorunda kalmamalıdır.

Saklama kuralı seans notuyla aynı: yalnız şifreli, yalnız yazarına açık, içeriği audit
kaydına asla yazılmaz. Kayıt **(danışan, terapist) çifti başına tektir** — devir hâlinde
devralan terapist öncekinin dosyasını açamaz, kendi formülasyonunu yazar.

---

## Ödemeler

`/odemeler` — [`PaymentController`](../panel/src/Controllers/PaymentController.php).

Ücret **randevunun alanıdır** ve her randevuda elle girilir; varsayılan ücret yoktur.
`NULL` "ücret belirlenmedi" demektir ve `0,00` (ücretsiz seans) ile aynı şey değildir.

Tahsilatlar ayrı satırlar hâlinde tutulur, tek bir "ödendi" bayrağı yoktur. Böylece
kısmi ödeme, taksit ve "ne zaman, hangi yöntemle alındı" bilgisi durur. **Ödeme durumu
saklanmaz, hesaplanır:** `fee` ile tahsilat toplamı karşılaştırılır. Bayrak tutulsaydı
bir tahsilat silindiğinde geride kalır ve kayıtlar birbirini tutmazdı.

| Durum | Anlamı |
|---|---|
| Ücret girilmedi | `fee IS NULL` |
| Ödenmedi | tahsilat yok |
| Kısmi | 0 < tahsilat < ücret |
| Ödendi | tahsilat ≥ ücret |

Yetki ayrımı: terapist kendi randevularının ücretini **görür ve girer**, ama tahsilat
kaydedemez — para alma işi merkez yönetimindedir (`payment.manage`). İptal edilmiş
seanslar "bekleyen" toplamına girmez; tahsil edilmişse tahsilat toplamında kalır.

Tutarlar PHP tarafında float'a çevrilmez ([`Money`](../panel/src/Money.php)); toplamlar
kuruş cinsinden tam sayı olarak toplanır, aksi hâlde uzun listelerde kasa kuruş kaydırır.

---

## Veritabanı güncellemeleri

`/sistem` — [`SystemController`](../panel/src/Controllers/SystemController.php), yalnız süper admin.

İlk kurulum ekranı ilk hesap açılınca kalıcı olarak kapanıyor ve cPanel'de SSH garantisi
yok. Bu ikisi birleşince kurulmuş bir panelde yeni migration uygulamanın yolu kalmıyordu.
Sistem ekranı bekleyen `migrations/*.sql` dosyalarını gösterir ve tek düğmeyle uygular;
ayrıca PHP sürümü, eklentiler, şifreleme ve GitHub erişimi için durum listesi verir.

Deploy ile güncelleme arasında bir boşluk vardır: yeni kod sunucuya çıkar ama sütunlar
henüz yoktur. Bu aralıkta ilgisiz ekranların çökmemesi için yeni alanlar
[`Schema::hasColumn`](../panel/src/Schema.php) ile kontrol edilerek kullanılır —
randevu ekranı ücret alanı olmadan çalışmaya devam eder, Ödemeler ekranı ise SQL hatası
yerine "önce güncellemeyi uygulayın" der.

> Güncelleme geri alınamaz. Uygulamadan önce cPanel'den veritabanı yedeği alın.

---

## Seans notları

`/randevular/{id}/not` — [`NoteController`](../panel/src/Controllers/NoteController.php).

Üç kural:

1. **Yalnız şifreli saklanır.** libsodium secretbox (XSalsa20-Poly1305); veritabanı
   yedeği tek başına okunamaz, `note_key` olmadan içerik çözülemez.
2. **Yalnız yazan terapist okur.** Yetki matrisinde `note.*` sadece terapistte olduğu
   için yöneticiler ve süper admin ekrana hiç giremez. Sistem kayıtlarına "not yazıldı"
   bilgisi düşer, **içerik asla loglanmaz**.
3. **Devir notu taşımaz.** Randevu başka bir terapiste geçse bile eski not açılamaz ve
   üzerine yazılamaz; ekran bunu açıkça söyler.

`sodium` eklentisi kapalıysa ya da `note_key` geçersizse ekran not kabul etmez ve
sebebini gösterir — yarı şifreli, sessizce düz metne düşen bir davranış yoktur.

---

## Site içeriği

`/icerik` — [`ContentController`](../panel/src/Controllers/ContentController.php) ve
[`FaqController`](../panel/src/Controllers/FaqController.php).

Panel içerik dosyalarını **sunucuya değil depoya** yazar
([`Github`](../panel/src/Github.php), Contents API). Site Eleventy ile derlenip FTPS ile
atıldığı için sunucudaki `_site` çıktısına yazmak bir sonraki deploy'da silinirdi. Depo
tek gerçek kaynaktır; panel commit atar, GitHub Actions siteyi yeniden yayınlar. Yani
**kaydetmek = yayınlamak**, birkaç dakika sürer ve ekranlar bunu söyler.

Şu an düzenlenebilenler:

| Ekran | Dosya |
|---|---|
| İletişim bilgileri | `_data/contact.json` |
| SSS içeriği | `_data/faqdata.json` |

Kurallar:

- **Ham JSON düzenletilmez.** Alan alan form gösterilir; tek bir eksik virgül siteyi
  derlenemez hâle getirir ve hata ancak deploy başarısız olunca fark edilirdi.
- **Tanınmayan anahtarlar korunur.** Panel dosyayı yeniden okur, yalnız bildiği alanları
  değiştirir; sonradan eklenmiş bir alan silinmez.
- **Biçim korunur.** Çıktı 2 boşluk girintiyle ve Unicode kaçırılmadan yazılır
  ([`Json::pretty`](../panel/src/Json.php)) — aksi hâlde tek kelimelik bir düzeltme,
  86 KB'lık `faqdata.json` dosyasının tamamını yeniden biçimlendiren bir commit üretirdi.
- **Çakışma yazmayı durdurur.** Form açılırken dosyanın `sha` değeri alınır, kaydederken
  geri gönderilir. Arada başkası aynı dosyayı değiştirdiyse kayıt reddedilir ve sayfayı
  yenilemeniz istenir; sessiz üzerine yazma olmaz.
- **Konu anahtarları ve kategori kimlikleri panelden değiştirilemez.** Şablonlar bunlara
  ada göre başvuruyor; yeniden adlandırmak sitedeki bölümü sessizce boşaltırdı.

### GitHub anahtarı

Fine-grained personal access token, **yalnız bu depo**, tek izin: `Contents: Read and write`.
Değer `config.php`'deki `github` bloğuna yazılır (bkz.
[`config.example.php`](../panel/config.example.php)). Anahtar yoksa içerik ekranları
kurulum yönergesi gösterir, panelin geri kalanı etkilenmez.

---

## KVKK metni

`/kvkk` — aydınlatma metni ve açık rıza beyanı `settings` tablosunda sürümlü tutulur.
Danışan kaydındaki `consent_version`, rızanın hangi metne verildiğini gösterir; bu
yüzden **metin değişince sürüm de değişmek zorundadır** ve panel sürümü yükseltmeden
kaydetmeye izin vermez.

Danışan sayfasındaki **"Rıza formunu yazdır"** bağlantısı, danışanın adıyla birlikte
güncel metni imza alanlarıyla yazdırılabilir biçimde açar.

> Paneldeki başlangıç metni bir **taslaktır**, hukuki tavsiye değildir. Köşeli
> parantezli alanlar kurumun bilgileriyle doldurulmalı ve metin bir hukukçuya
> onaylatılmalıdır.

---

## Güvenlik

Uygulanan önlemler:

- Şifreler `password_hash()` ile saklanır; panelde hiçbir yerde şifre görünmez.
  Yeni kullanıcı **davetli** olarak açılır, şifresini e-postasındaki tek kullanımlık
  bağlantıyla kendisi belirler. Jetonun düz hâli veritabanında tutulmaz (SHA-256 özeti).
- Her POST tek noktada CSRF doğrulamasından geçer ([`panel/index.php`](../panel/index.php)).
- 5 hatalı girişte hesap 15 dk kilitlenir; hatalı e-posta ile hatalı şifre aynı mesajı
  döndürür (hesap numaralandırması önlenir).
- Oturum çerezi `/panel` yoluyla sınırlı, `HttpOnly` + `Secure` + `SameSite=Lax`;
  girişte ve şifre değişiminde oturum kimliği yenilenir; 30 dk hareketsizlikte düşer.
- `.htaccess`: `src/` ve `migrations/` dizinlerine doğrudan erişim yok, HTTPS zorlaması,
  CSP (inline script yok), `X-Frame-Options: DENY`, `noindex`.
- Tüm sorgular hazırlanmış ifadelerle; şablonlarda çıktı `e()` ile kaçırılır.
- Yetki kontrolü menü gizlemeye değil, her istekte `Auth::requirePermission()` çağrısına dayanır.

### Yedekleme

- **Veritabanı**: cPanel yedeklerinin yanında düzenli, şifreli bir dışa aktarım tutun.
  Seans notları şifreli olduğu için yedek tek başına yeterli değildir — `note_key` olmadan okunamaz.
- **`note_key` + `config.php`**: parola yöneticisinde, veritabanı yedeğinden ayrı yerde.

---

## KVKK

Panel özel nitelikli kişisel veri (sağlık verisi) işler. Teknik tarafta karşılananlar:
şifreli saklama, rol bazlı erişim, erişim kaydı (audit log), veri minimizasyonu
(TC kimlik no gibi alanlar toplanmaz).

Kurumun tamamlaması gerekenler:

1. Panele özel **aydınlatma metni** ve danışandan **açık rıza** (kayıtta `clients.consent_at` + `consent_version`).
2. **Saklama ve imha politikası**; danışan kaydını anonimleştirme akışı (silme hakkı).
3. Hosting sağlayıcısıyla **veri işleyen sözleşmesi**.
4. **VERBİS** kayıt yükümlülüğünün kontrolü.

> Bu belge teknik bir kılavuzdur, hukuki tavsiye değildir. Metinler bir hukukçuya onaylatılmalıdır.

---

## Yol haritası

| Faz | Kapsam | Durum |
|---|---|---|
| 1a | İskelet, kimlik doğrulama, roller, kullanıcı yönetimi, profil, audit log | ✅ tamam |
| 1b | Danışan kayıtları, terapist müsaitliği, randevu takvimi, çakışma kontrolü | ✅ tamam |
| 1c | Şifreli seans notları, e-posta bildirimleri, KVKK metinleri | ✅ tamam |
| 2a | Site verileri ve SSS içeriği (GitHub Contents API) | ✅ tamam |
| 2b | Blog yazılarının panele taşınması, Sveltia'nın kaldırılması | ✗ yapılmayacak |
| 3a | Seans ücreti ve tahsilat takibi | ✅ tamam |
| 3b | Randevu hatırlatmaları, danışan dosyası, bugün ekranı | ✅ tamam |
| 3c | Süper admin için TOTP 2FA, WhatsApp/SMS, raporlar | opsiyonel |

### Sveltia neden kalıyor

Yol haritası "içerik yönetimi panele taşınsın, Sveltia kalksın" diye yazılmıştı; blog
dosyalarına bakılınca bu hedefin kötü bir takas olduğu görüldü.

Blog yazılarının **gövdesi boş**. İçeriğin tamamı front-matter'daki `blocks:` listesinde
duruyor: `metin`, `kart-grid`, `kart-yigini`, `numarali-liste`, `bilgi-kutusu`, `adimlar`,
`onayli-liste`, `ozel` — sekiz blok tipi, üç seviyeye kadar iç içe geçen listelerle
(bloklar → kartlar → maddeler). Yani bu bir markdown yazı değil, yapılandırılmış bir
içerik modeli ve [`admin/config.yml`](../admin/config.yml) onu 684 satırla zaten modelliyor.

Paneli yerine koymak iki şey gerektirirdi: elle yazılmış bir YAML ayrıştırıcı/üretici
(`|-` blok metinleri, tırnak içinde tırnak, iç içe liste-map'ler) ve JavaScript'siz
formlarla üç seviyeli bir blok editörü. Round-trip'teki tek hata 43 yazının içeriğini
bozar ve bu ancak site yayınlandıktan sonra fark edilir. Karşılığında kazanılan tek şey
"tek yerden giriş" olurdu.

Bu yüzden iş bölümü şöyle bırakıldı:

| | Yönetir |
|---|---|
| **Panel** | Klinik işleyiş (danışan, randevu, seans notu) + operasyonel veri (iletişim, SSS, KVKK) |
| **Sveltia** | Blog yazıları ve yapısal sayfa içeriği |

Statik sayfalar (`hakkimizda`, `index`) 120–380 satırlık Nunjucks gövdesine sahip; onlar
içerik değil şablon kodudur ve hiçbir CMS'e taşınmamalıdır.

Faz 1b ve 1c şema değişikliği getirmedi — `clients`, `appointments`, `working_hours`,
`time_off` ve `session_notes` tabloları 001_init.sql'de zaten vardı; eksik olan
arayüzlerdi. KVKK metni de mevcut `settings` tablosunda saklanır.

Faz 1 kapsamı dışında bilerek bırakılanlar: randevu hatırlatma e-postaları (cron
gerektirir), seans ücreti/tahsilat takibi, danışan portalinden randevu talebi.

---

## Sorun giderme

| Belirti | Sebep |
|---|---|
| "Panel yapılandırılmamış" | `config.php` beklenen yolda değil. `/home/<kullanici>/magusa-panel-config/config.php` olmalı. |
| 500 hatası, boş sayfa | `app.debug` geçici olarak `true` yapıp cPanel → Errors günlüğüne bakın. |
| Davet e-postası gitmiyor | SMTP bilgileri yanlış veya port kapalı. Kullanıcı listesinden "Daveti gönder" ile tekrar deneyin. |
| Sonsuz HTTPS yönlendirme döngüsü | Cloudflare SSL modu "Flexible". `Full` yapın. |
| `/panel/kurulum` 404 | Normal — en az bir kullanıcı var demektir. Şifre için "Şifremi unuttum" kullanın. |
| Panel yayına çıkmıyor | GitHub Actions → Build & Deploy adımının yeşil olduğunu doğrulayın. |
