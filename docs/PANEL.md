# Yönetim Paneli — Mimari ve Kurulum

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
> notudur (ör. "faturayı kurum ödeyecek"). Klinik içerik Faz 1c'deki şifreli seans
> notlarına yazılacak.

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
| 1c | Şifreli seans notları, e-posta bildirimleri, KVKK metinleri | sırada |
| 2  | İçerik yönetimi (GitHub Contents API üzerinden), Sveltia CMS'in kaldırılması | planlı |
| 3  | Süper admin için TOTP 2FA, WhatsApp/SMS hatırlatma, raporlar | opsiyonel |

Faz 1b şema değişikliği getirmedi — `clients`, `appointments`, `working_hours` ve
`time_off` tabloları 001_init.sql'de zaten vardı, eksik olan arayüzlerdi. Faz 1c için
`session_notes` de hazır bekliyor.

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
