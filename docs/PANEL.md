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

| Yetki | Süper Admin | Admin | Terapist | Görüşmeci |
|---|:--:|:--:|:--:|:--:|
| Kullanıcı oluştur/düzenle/sil | ✔ | ✔ (yalnız terapist + görüşmeci) | — | — |
| Görüşmecinin panel hesabını aç/kapat | ✔ | ✔ | — | — |
| Süper admin / admin hesaplarını yönet | ✔ | — | — | — |
| Tüm görüşmeci kayıtları | ✔ | ✔ | kendi görüşmecileri | — |
| Görüşmeci kaydı oluştur / terapist ata | ✔ | ✔ | ✔ | — |
| Görüşmeci iletişim bilgisini düzelt | ✔ | ✔ | kendi görüşmecileri | — |
| Görüşmeci kaydını kalıcı sil | ✔ | — | — | — |
| Tüm randevular | ✔ | ✔ | kendi randevuları | kendi randevuları (salt okunur) |
| Ödeme/borç durumu | ✔ | ✔ | kendi seanslarının ücreti | kendi seansları (salt okunur) |
| Randevu oluştur/düzenle/iptal | ✔ | ✔ | kendi takvimine | — |
| Çalışma saati ve izin tanımla | ✔ | ✔ | kendi müsaitliği | — |
| Onam formunu düzenle | ✔ | ✔ | — | — |
| **Seans notu okuma/yazma** | **—** | **—** | **yalnız kendi yazdığı** | — |
| **Check-in eğrisi / bağlantı gönderme** | **—** | **—** | **kendi görüşmecileri** | — |
| Check-in soruları, gönderim listesi, sorulan alanlar | ✔ | ✔ | kendi görüşmecileri | — |
| Sistem kayıtları (audit log) | ✔ | — | — | — |
| Kendi profili | ✔ | ✔ | ✔ | ✔ |

Terapistin "kendi görüşmecisi" iki yoldan tanımlıdır: birincil terapisti olduğu kişiler
ve fiilen randevusunu gördüğü kişiler. Kural tek yerde:
[`panel/src/ClientScope.php`](../panel/src/ClientScope.php).

Terapist kendi görüşmeci kaydını açar ve birincil terapist olarak kendini atar
(`client.create` + `client.assign_therapist`). Merkez tek kişilik çalıştığı için bilinçli
bir karar: kaydı açanla seansı yapan aynı insan, kayıt açmayı yönetime bırakmak her yeni
görüşmeci için hesap değiştirmek demekti. Sınır **görünürlükte** duruyor — terapist ancak
`ClientScope`'un gösterdiği bir kaydın atamasını değiştirebilir; görmediği bir kaydı
devralamaz. **İkinci bir terapist çalışmaya başlarsa bu karar yeniden düşünülmeli:** o
noktada devir, yönetimin onayından geçmeden yapılabiliyor olacak.

Panel hesabı açma/kapatma yine yönetimde: kimin panele girebileceğine karar vermek giriş
yetkisi dağıtmaktır. Terapistin açtığı kayıtta e-posta varsa hesap kayıtla birlikte
kendiliğinden açılır (aşağıya bakın), ama sonradan erişimi kapatmak/daveti yenilemek
yöneticinin işidir.

Kurallar tek yerde: [`panel/src/Rbac.php`](../panel/src/Rbac.php).

> Süper admin bilinçli olarak seans notlarının ve check-in eğrisinin dışında bırakıldı.
> İkisi de özel nitelikli sağlık verisidir; "her şeyi görebilen hesap" olmaması hem KVKK
> hem meslek etiği açısından doğru varsayılandır.

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
hem görüşmeci için yapılır — bir görüşmecinin aynı saatte iki farklı terapiste yazılması da
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
vardır (varsayılan açık). Bildirim görüşmeciye (e-posta adresi kayıtlıysa) ve randevunun
terapistine gider; işlemi yapan kişiye kendi eylemi bildirilmez.

E-posta içeriği bilinçli olarak yalındır: tarih, saat, terapist, görüşme yeri. İdari
not ve görüşmecinin diğer bilgileri gönderilmez — e-posta şifresiz bir kanaldır.
Gönderim başarısız olursa **kayıt geri alınmaz**, yalnız uyarı gösterilir.

### Hatırlatmalar (cron)

Görüşmecilere, randevudan varsayılan olarak 24 saat önce hatırlatma e-postası gider.
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

Hatırlatma yalnız görüşmeciye gider; terapist zaten kendi takvimine bakıyor ve her seans için
ikinci bir e-posta gürültü olurdu.

Ayarlar `settings` tablosunda: `reminder_hours_before` (varsayılan 24) ve
`reminders_enabled` (varsayılan 1). Cron'un çalışıp çalışmadığı, son sonucu ve kuyrukta
kaç randevu olduğu **Sistem** ekranında görünür — kurulmamış bir cron'un tek belirtisi
"hiç çalışmadı" satırıdır.

> Betik `cron/` dizinindedir ve `.htaccess` bu dizine web'den erişimi kapatır.

---

## Görüşmecinin panel hesabı

[`ClientAccount`](../panel/src/ClientAccount.php) — `clients` kaydı ile `users` kaydı
arasındaki bağı kuran/koparan tek yer.

**Hesap kaydın parçasıdır, ayrı bir karar değil.** Görüşmeci kaydı oluşturulurken e-posta
girilmişse hesap kendiliğinden açılır (rol: Görüşmeci, durum: davetli) ve şifre belirleme
bağlantısı o adrese gider. Ön büro "hesap açayım mı" diye karar vermez, iki ekran
arasında gidip gelmez.

Önceki tasarımda formda bir **"Panel hesabı (isteğe bağlı)"** açılır listesi vardı ve
seçenekleri dolduran tek yol, önce Kullanıcılar ekranından elle Görüşmeci rolünde bir hesap
açmaktı. Ekleme anında hiçbir seçenek görünmüyordu; alan "boş bırakılacak bir şey" gibi
okunuyordu. Bu yüzden kaldırıldı: Kullanıcılar ekranından artık **Görüşmeci rolü
açılamaz** — orada açılan hesap hiçbir kayda bağlı olmadığı için giriş yapar ama hiçbir
şey göremezdi.

E-postası olmayan kayıt yine açılır (telefonla gelen görüşmeci kaybolmasın); nedeni
söylenir ve hesap sonradan görüşmeci sayfasındaki **Panel erişimi** bölümünden tek
düğmeyle açılır. Aynı bölümde daveti yenileme ve erişimi kapatma/açma vardır.

| Durum | Anlamı |
|---|---|
| Hesap yok | `clients.user_id IS NULL` |
| Davet gönderildi | hesap açık, şifre henüz belirlenmedi (`invited`) |
| Açık | `active` — görüşmeci randevu ve ödeme durumunu görüyor |
| Kapalı | `suspended` — hesap duruyor, giriş kapalı |

Bağlı kayıt, görüşmeci kaydını takip eder:

- ad/e-posta düzeltilirse hesap da güncellenir — yoksa davet eski adrese giderdi;
- kayıt **arşivlenirse** erişim kapanır, arşivden çıkarılırsa geri açılır;
- kayıt **kalıcı silinirse** hesap da silinir — "kaydı sildik ama hâlâ giriş yapabiliyor"
  bir KVKK silme talebini karşılamış olmaz.

Erişim kapatmak hesabı silmez; görüşmeci geri geldiğinde aynı hesap yeniden açılır.
Hesap açma/kapatma yöneticide (`user.create`); terapist görüşmecinin iletişim bilgisini
düzeltebilir ama kimin panele girebileceğine karar veremez.

Davet e-postası yönetici davetinden ayrı bir metindir ([`Invites::sendToClient`](../panel/src/Invites.php)):
görüşmeci panele çalışmaya değil, kendi randevu ve ödeme durumuna bakmaya geliyor.
Bağlantı 48 saat geçerli ve tek kullanımlıktır; e-posta gitse de gitmese de görüşmeci
sayfasında gösterilir, çünkü `mail()` başarısı teslimi garanti etmez.

---

## Görüşmeci dosyası

`/danisanlar/{id}/dosya` — [`CaseFileController`](../panel/src/Controllers/CaseFileController.php).

Seans notundan ayrıdır çünkü ayrı sorulara cevap verirler: seans notu "o gün ne oldu",
dosya "bu kişiyle ne üzerinde çalışıyoruz" (başvuru nedeni, öykü, formülasyon, plan).
Terapist ikincisini her seans öncesi okumak ister ve bunun için on iki seans notunu tek
tek açmak zorunda kalmamalıdır.

Saklama kuralı seans notuyla aynı: yalnız şifreli, yalnız yazarına açık, içeriği audit
kaydına asla yazılmaz. Kayıt **(görüşmeci, terapist) çifti başına tektir** — devir hâlinde
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

## Seanslar arası check-in

`/check-in/{jeton}` — [`CheckinController`](../panel/src/Controllers/CheckinController.php),
kuralların tamamı [`Checkins`](../panel/src/Checkins.php) içinde.

Görüşmeci haftada bir üç soru dolduruyor (ruh hali, uyku, kaygı — 1–10) ve isteğe bağlı
tek bir cümle yazabiliyor. Terapist görüşmeci sayfasında zaman içindeki eğriyi görüyor.
Amaç iki seans arasındaki boşluğu ölçülebilir kılmak: o bilgi bugüne kadar yalnız
görüşmecinin hatırladığı kadarıyla, seansın ilk on dakikasında alınabiliyordu.

### Giriş gerektirmez — bilinçli

Form panelin **giriş istemeyen tek ekranıdır**. Yetki bağlantının kendisinde: tek
kullanımlık, 10 gün geçerli, tek bir görüşmeciye ait 256 bitlik bir jeton. Düz jeton
veritabanında tutulmaz, yalnız `sha256` özeti (davet jetonlarındaki kural).

Gerekçe: bu döngünün ölçtüğü tek şey **doldurma oranı**. Giriş duvarı o oranı yarıya
böler — şifre hatırlamak zorunda kalan biri formu telefonda yarım dakikada doldurmaz.
Görüşmecinin giriş yapabildiği portal ayrıca duruyor; bu döngü onun dışında çalışır.

Geçersiz, kullanılmış, süresi dolmuş ve arşivlenmiş kayda ait bağlantıların hepsi kibar
ve kısa bir sayfa gösterir. Sayfa kimin bağlantısı olduğunu ele vermez; görüşmecinin adı
yalnız jeton geçerliyken görünür.

### Saklama

Sayılar açık, **cümle yalnız şifreli** (seans notuyla aynı kural: `ciphertext` + `nonce`,
içerik audit kaydına asla yazılmaz). Şifreleme kullanılamıyorsa cümle alanı formda hiç
gösterilmez — saklanamayacak bir şeyi yazdırmak yazanın güvenine ihanet ederdi. Sayılar
şifrelenmez çünkü eğriyi çizmek için sıralanmaları gerekiyor ve tek başına "7" bir şey
söylemez.

### Eğri

Görüşmeci sayfasında, idari alanların **önünde**: terapist o sayfayı "iki seans arasında
ne oldu?" sorusuyla açıyor. Yetki `checkin.view.own` ile yalnız terapistte — `note.*` ile
aynı gerekçe; yöneticide bilinçli olarak yok. Hangi görüşmecinin görülebildiği zaten
`ClientScope` ile sınırlı. (Sorunun metni, haftalık gönderim listesi ve sorulan alanlar
ayrı bir yetkidir ve yöneticide de vardır — aşağıya bakın.)

Eğriyi göremeyen ama `checkin.manage` taşıyan kullanıcı görüşmeci sayfasında bunun
yerine tek kartlık idari bir özet görür: haftalık gönderim anahtarı ve "sorulan alanlar"
bağlantısı. Puan, cümle, şerit ve örüntü o kartta yok — kart ölçümü değil kapıyı
gösteriyor.

Üç ölçü **üç ayrı satıra** çiziliyor, tek eksene çakıştırılmıyor: kaygıda yukarı kötüdür,
diğer ikisinde iyidir; üst üste çizilen üç eğride yükselen çizginin ne anlama geldiği
okunmaz olurdu. Satırlar aynı zaman eksenini ve aynı 1–10 ölçeğini paylaşıyor, bu yüzden
"uyku düştü, kaygı çıktı" hâlâ tek bakışta görülüyor. Çizim harici kütüphane olmadan
inline SVG; sabit ölçekli, dar ekranda kutu kendi içinde kayıyor (takvimdeki çözüm).
Altındaki tabloda bütün sayılar ve cümleler duruyor — eğrinin okunamadığı her durum için.

### Sorular ve gönderim listesi (panel → Check-in)

`/check-in-sorulari` — tek ekranda iki soru: bağlantıda **ne** soruluyor ve haftalık
e-posta **kime** gidiyor.

Yetki `checkin.manage` ve bu, eğriyi okuma yetkisinden (`checkin.view.own`) ayrıdır:
**cevap sağlık verisidir, soru değil.** Sorunun metni ile e-postanın kime çıktığı merkezin
işleyişine dair kararlar, bu yüzden yöneticide de var. Ekranda hiçbir puan, hiçbir cümle
görünmez — yalnız ad, adres ve gönderim durumu. Liste yine `ClientScope` ile sınırlı:
terapist kendi görüşmecilerini, yönetici hepsini görür.

**Metinler.** Görüşmecinin gördüğü **her cümle** buradan düzenlenir: giriş paragrafı, üç
sorunun metni, her ölçeğin panelde görünen adı ve iki ucunun adı ("1 · çok kötü →
10 · çok iyi"), cümle alanının başlığı/örneği/açıklaması, halkanın sorusu ve altındaki
açıklama, ✦ satırı, gönder düğmesi ve teşekkür sayfası. Kütük tek yerde
(`Checkins::TEXTS` + `Checkins::MEASURES`), değerler `settings` tablosunda
(`checkin_question_*`, `checkin_measure_*`, `checkin_text_*`).

Koddaki varsayılan yerinde durur ve boşaltılan alan ona geri döner — varsayılan ileride
değişirse o cümle eski kopyasında donup kalmaz. Metnin yarısı kodda bırakılmadı: yarısı
düzenlenebilir bir form, okuyana hangi cümlenin merkeze hangisinin yazılıma ait olduğunu
göstermez, yalnız ikisi arasındaki dil farkını gösterir.

Değişen yalnız **adlar**: alan adları ve dolayısıyla **ölçeğin yönü sabit**. `anxiety`
ters ölçeklidir (yüksek değer kötü) ve eğri bunu böyle okur; ucun adını ters yazan bir
düzenleme geçmiş kayıtları da baş aşağı okuturdu, bu yüzden ekran her sorunun altında
hangi ucun iyi olduğunu ayrıca yazar. Değişiklik yalnız bundan sonra üretilen
bağlantılarda görünür, geçmiş cevaplar aynı ölçekte kalır.

Halkanın sorusu buradaki değer **varsayılandır**; tek bir dosyada değiştirmek için
görüşmeci sayfası → "Sorulan alanlar".

**Soruların kendisi de liste** (`checkin_scales`, bkz. `Scales`): kaç tane sorulacağı,
sırası, hangi cümleyle ve hangi yönde. En fazla **6** açık soru — form her kaydırıcıda
uzuyor ve ölçülen tek şey doldurma oranı. Ad verilmeyen yuva yok sayılır; ekleme işlemi
budur, ayrı bir düğme yok.

Yön (`direction`) süs değil **veridir**: +1 yüksek değer iyi, −1 yüksek değer kötü
(kaygı). Koddan gelen üç ölçekte kilitlidir — on iki haftalık bir eğrinin yönünü
değiştirmek geçmiş haftaları da baş aşağı okuturdu. Bu üçü silinemez, yalnız kapatılır.

Cevaplar `checkin_scores` tablosunda (`checkin_id` + `scale_key`), üç varsayılan ölçek
için ayrıca `checkins` tablosunun kendi sütunlarında. `scale_key` yabancı anahtar
**değil**: kapatılan ya da listeden kaldırılan bir ölçeğin geçmiş cevapları durur ve eğri
o satırı veri bitene kadar çizmeye devam eder — ölçmeyi bırakmak, ölçtüğünü unutmak
değildir. Yeni eklenen ölçeğin eğrisi ilk cevabın geldiği haftadan başlar; geçmiş
haftalar boş kalır, **sıfır sayılmaz**.

Örüntü cümlelerinin ikinci yarısı ("… ruh hali düşüyor") tek bir ölçekten gelir: ruh hali
açıksa o, değilse yüksek değeri iyi olan ilk ölçek (`Scales::primaryKey`).

**Gönderim listesi.** Her aktif görüşmeci için tek bir işaret (`clients.checkin_auto`,
varsayılan açık) ve durumunun açıklaması: *sırada, bu hafta doldu, bağlantı bekliyor,
susuldu, başlamadı, e-posta yok, kapalı*. "Sırada" kararını ekran ikinci kez
hesaplamıyor — cron ne gönderecekse onu gösteriyor (`Checkins::due()`), geri kalan
durumlar bunun açıklaması.

İşaretin kapattığı tek şey **cron**: takibi kapatmaz, kaydı arşivlemez, geçmişi gizlemez
ve terapistin elle gönderdiği bağlantı kapalıyken de çalışır. Öncesinde gönderimi
durdurmanın tek yolu kaydı arşivlemekti; "bu dönem doldurmayalım" diyen bir aile için
fazla ağır bir işlemdi. Aynı anahtar görüşmeci sayfasındaki check-in bölümünde de var —
karar çoğunlukla eğriye bakarken veriliyor. Açma/kapama denetim kaydına yazılır
(`checkin.auto_on` / `checkin.auto_off`).

**Sorulan alanlar ve metinler** (`/danisanlar/{id}/alanlar`, listedeki "Alanlar"
bağlantısı). Formun ikinci sayfasındaki halka dosyaya göre uyarlanır: hangi alanlar açık,
her alanın ebeveynin gördüğü adı ve altındaki açıklama, halkanın üstündeki tek soru, ve
sözlükte karşılığı olmayan şeyler için dosya başına dört boş yuva. Sözlük kodda kalıyor
(`Ecosystem::DOMAINS`), tablo yalnız sapmayı tutuyor: varsayılanla birebir aynı yazılan
metin kaydedilmez, böylece ileride düzeltilen bir ifade uyarlanmamış dosyalara da iner.
Ayrıntı ve gerekçeler: [`panel/ekosistem-plani.md`](../panel/ekosistem-plani.md) §Faz 3b.

Bu ekran da `checkin.manage` ile yöneticiye açık ve bedeli açıkça şu: hangi alanların
açık olduğu ("Bakım düzeni", "Kardeş") o aile hakkında bir şey söyler. Ölçümler
söylemiyor — puan, cümle ve eğri `checkin.view.own` ile terapistte kalıyor. Uyarlanan
metin denetim kaydına **yazılmaz**; kimin ne zaman değiştirdiği yeter.

### Hatırlatmalar (cron)

```
cPanel → Cron Jobs → Pazartesi 09:00 (0 9 * * 1)
/usr/local/bin/php /home/<kullanıcı>/public_html/panel/cron/checkins.php
```

Kime gider: **döngüsü başlatılmış ve gönderimi açık** görüşmecilere. Kayıt olması yetmez,
terapistin görüşmeci sayfasından bir kez "Check-in bağlantısı gönder" demesi gerekir.
"Aktif tüm görüşmeciler" denseydi cron kurulduğu an merkezin bütün listesine e-posta
çıkardı; pilot üç-dört kişiyle yürüyor ve kime gittiğine terapist karar veriyor. İlk
bağlantı döngüyü **başlatır**, `clients.checkin_auto` ise onu durdurup yeniden açar —
ikisi ayrı sorular: "bu kişiyle check-in yapıyor muyuz" ve "bu dönem haftalık e-posta
çıksın mı".

Kimin sırada olduğunu cron kendi hesaplamıyor: kural `Checkins::due()` içinde ve panelin
gönderim listesi de aynı yerden okuyor. İki kopya olsaydı ekran "sırada" derken cron
susabilir ve kimse fark etmezdi. Koşu özetinde kapalı görüşmeci sayısı da yazıyor —
gönderimin azalması arıza mı, verilmiş bir karar mı, tek bakışta ayrılsın diye.

Günde bir çalıştırılsa da güvenli: aynı hafta doldurmuş ya da son altı gün içinde
bağlantı almış kimseye ikinci ileti gitmez. **Israr etmez:** son dolan check-in'den beri
üç bağlantı cevapsız kaldıysa o kişiye cron gönderimi durur — dördüncü hatırlatma oranı
yükseltmiyor, kanalı değiştirmek gerekiyor. Terapistin elle gönderdiği bağlantı her zaman
çalışır.

Bağlantı, e-posta gönderilmiş olsa bile ekranda gösterilir ve kopyalanabilir (davet
bağlantılarındaki aynı gerekçe): oran düşükse ilk şüpheli kanaldır ve ikinci kanalı
(WhatsApp, mesaj) denemenin en ucuz yolu budur.

Doldurulmamış bir bağlantı yenilenirken **silinmez, süresi doldurulur**. Satırın kalması
şart: doldurma oranının paydası "gönderilen bağlantı" sayısıdır, cevapsız kalanları silen
bir sürüm cevapsız kaldığını da silerdi. Oran, gönderilen/doldurulan olarak **Sistem**
ekranında görünür.

Ayar `settings` tablosunda: `checkins_enabled` (varsayılan 1).

> Standart ölçekler (PHQ-9, GAD-7) bilinçli olarak yok. Pilot tutarsa aynı altyapı
> genişletilerek gelir — bkz. [`panel/check-in-plani.md`](../panel/check-in-plani.md).

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

## Onam formu

`/onam-formu` — danışana imzalatılan **bilgilendirilmiş onam formu** `settings`
tablosunda sürümlü tutulur. Tek metinde dört bölüm var: psikoterapi süreci (seans
süresi, iptal, kayıt yasağı), gizlilik ve güvenilirlik (ve gizliliğin ihlal
edilebileceği iki koşul), kişisel bilgilerin kaydı (eski KVKK aydınlatma metninin
karşılığı) ve gönüllülük.

Görüşmeci kaydındaki `consent_version`, onamın hangi metne verildiğini gösterir; bu
yüzden **metin değişince sürüm de değişmek zorundadır** ve panel sürümü yükseltmeden
kaydetmeye izin vermez.

Görüşmeci sayfasındaki **"Onam formunu yazdır"** bağlantısı, görüşmecinin adıyla
birlikte güncel metni açar; çıktının sonuna metnin söz verdiği **aile yakını bilgisi**
kutusu ile danışan ve psikolog imza satırları eklenir.

> Önceki sürümlerde bu ekran "KVKK metni" adıyla `/kvkk` altındaydı ve süreç
> anlaşması ayrı bir kâğıt olarak imzalanıyordu. İkisi 2.0 sürümünde tek forma indi:
> [`009_onam_formu.sql`](../panel/migrations/009_onam_formu.sql) sürümü,
> [`010_onam_metni.sql`](../panel/migrations/010_onam_metni.sql) ise kayıtlı metni
> günceller — çünkü **kayıtlı metin koddaki taslağı ezer**. 010, değiştirdiği metnin
> eskisini `consent_text_onam_oncesi` anahtarına kopyalar; kurumun elle girdiği
> unvan/süre/adres bilgileri kaybolmaz.

> Paneldeki başlangıç metni bir **taslaktır**, hukuki tavsiye değildir. Köşeli
> parantezli alanlar kurumun bilgileriyle doldurulmalı ve metin bir hukukçuya
> onaylatılmalıdır.

---

## Tanıtım turu

Panele ilk kez giren kişi, "Bugün" ekranında kendiliğinden açılan bir turla karşılanır:
ekranın geri kalanı kararır, anlatılan yer aydınlık kalır, kart adım adım ilerler. O ilk
tur hem paneli hem o ekranı anlatır. Sonrası için menünün dibindeki **Tanıtım turu**
düğmesi var: her ekranda durur ve **yalnız bulunduğunuz ekranı** anlatır.

Motor [`panel.js`](../panel/assets/panel.js) içindedir, biçim
[`src/panel.css`](../src/panel.css) sonundaki `.tour-*` bloğunda.

**Adımlar ayrı bir dosyada tutulmaz; anlattıkları öğenin üzerinde dururlar:**

```html
<div class="rail" data-tour="21"
     data-tour-title="Gün cetveli"
     data-tour-text="Seanslar süreleri kadar yer kaplar…">
```

Kurallar:

- **Sayının iki işi var: sıra ve kapsam.** 20'nin altı **paneli** anlatır (menü, kişi
  arama, daraltma, hesap — [`layout.php`](../panel/src/views/layout.php), 10–19), 20'den
  başlayanlar **o ekranı**. Yeni bir ekrana tur eklemek için o ekranın görünümüne birkaç
  öznitelik yazmak yeter; kaydedilecek ya da bir yere eklenecek başka bir liste yok.
- **Panel bir kez tanıtılır.** Kenar çubuğunun adımları yalnız turu hiç görmemiş kişiye
  açılır; sonraki her "Tanıtım turu" doğrudan o ekranın kendi adımlarıyla başlar. İkinci
  kez basan kişi menünün ne olduğunu değil, o an baktığı ekranı soruyor — menüyü yeniden
  anlatmak ilk anlatışı da değersizleştirirdi.
- **Kendi adımı olmayan ekranda panel turu geri gelir.** Düğmeye basan biri bir şey
  bekliyor ve "bu ekran için söyleyecek bir şeyim yok" demenin en kötü yolu hiçbir şey
  olmamasıdır. Menüyü hatırlamanın da tek yolu bu.
- **Metin anlattığı şeyle birlikte yaşar.** Bölüm silinince adımı da onunla gider; ayrı
  bir adım listesi, kaldırılmış bir bölümü anlatmaya devam ederdi.
- **Görünmeyen adım atlanır.** Yetkisi olmayan kişinin menüsünde o satır hiç basılmıyor,
  dar ekranda kenar çubuğu kapalı `<details>` içinde. Tur, sayfayı açan kişinin gerçekten
  gördüğü ekranı anlatır; sayımı (`Adım 2 / 5`) da ona göre yapar.
- **Hiç `[data-tour]` olmayan ekranda düğme çıkmaz.** Düğme `hidden` geliyor, `panel.js`
  açıyor — JS kapalıyken de hiç görünmez.
- Klavye: `←` `→` adımlar, `Esc` kapatır; odak kart içinde tutulur.

Menüdeki her ekranın ve görüşmeci kaydının kendi adımları var. **Formlar bilinçli olarak
dışarıda** (randevu, görüşmeci, kullanıcı, seans notu, görüşmeci dosyası): orada tur
yarım kalmış bir işin üstünü örterdi ve alanların kendi etiketi ile ipucu zaten yanlarında
duruyor.

**"Gördü" bilgisi çerezde** (`panel_tur`), veritabanında değil — menü daraltma durumu da
öyle (`panel_nav`). Bir karşılama ekranı için `users` tablosuna sütun açmak, göç
gerektiren bir şema değişikliğini yalnızca "bir daha gösterme" için ödemek olurdu. Bedeli:
tur tarayıcı başına bir kez çıkar. Çerezin değeri `<kullanıcı kimliği>-<tur sürümü>`
biçiminde: merkezdeki ortak bilgisayarda ikinci kişi turu kendi ilk girişinde yine görür.
Turu **kapatmak da görmüş saymaya yeter**; kapatan biri onu bir daha istemiyor demektir.

> Tur metinleri değişip herkesin yeniden görmesi istenirse `layout.php` içindeki
> `$tourVersion` bir artırılır — eski çerez değeri eşleşmez ve tur yeniden karşılar.

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

1. Kurumun kendi bilgileriyle doldurulmuş **onam formu** ve danışandan imza (kayıtta `clients.consent_at` + `consent_version`).
2. **Saklama ve imha politikası**; görüşmeci kaydını anonimleştirme akışı (silme hakkı).
3. Hosting sağlayıcısıyla **veri işleyen sözleşmesi**.
4. **VERBİS** kayıt yükümlülüğünün kontrolü.

> Bu belge teknik bir kılavuzdur, hukuki tavsiye değildir. Metinler bir hukukçuya onaylatılmalıdır.

---

## Yol haritası

| Faz | Kapsam | Durum |
|---|---|---|
| 1a | İskelet, kimlik doğrulama, roller, kullanıcı yönetimi, profil, audit log | ✅ tamam |
| 1b | Görüşmeci kayıtları, terapist müsaitliği, randevu takvimi, çakışma kontrolü | ✅ tamam |
| 1c | Şifreli seans notları, e-posta bildirimleri, KVKK metinleri | ✅ tamam |
| 2a | Site verileri ve SSS içeriği (GitHub Contents API) | ✅ tamam |
| 2b | Blog yazılarının panele taşınması, Sveltia'nın kaldırılması | ✗ yapılmayacak |
| 3a | Seans ücreti ve tahsilat takibi | ✅ tamam |
| 3b | Randevu hatırlatmaları, görüşmeci dosyası, bugün ekranı | ✅ tamam |
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
| **Panel** | Klinik işleyiş (görüşmeci, randevu, seans notu) + operasyonel veri (iletişim, SSS, KVKK) |
| **Sveltia** | Blog yazıları ve yapısal sayfa içeriği |

Statik sayfalar (`hakkimizda`, `index`) 120–380 satırlık Nunjucks gövdesine sahip; onlar
içerik değil şablon kodudur ve hiçbir CMS'e taşınmamalıdır.

Faz 1b ve 1c şema değişikliği getirmedi — `clients`, `appointments`, `working_hours`,
`time_off` ve `session_notes` tabloları 001_init.sql'de zaten vardı; eksik olan
arayüzlerdi. KVKK metni de mevcut `settings` tablosunda saklanır.

Faz 1 kapsamı dışında bilerek bırakılanlar: randevu hatırlatma e-postaları (cron
gerektirir), seans ücreti/tahsilat takibi, görüşmeci portalinden randevu talebi.

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
