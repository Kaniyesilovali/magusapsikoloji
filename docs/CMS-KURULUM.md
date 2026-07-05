# CMS Mimarisi ve Kurulum Kılavuzu

## Mimari

```
Sveltia CMS (/admin) ──commit──▶ GitHub (main) ──▶ GitHub Actions
                                                    │  npm run build   (Tailwind + Eleventy → _site/)
                                                    │  npm run check   (link/hreflang/sitemap kontrolleri)
                                                    ▼
                                              FTPS ile cPanel (public_html)
```

- **İçerik**: `content/tr/…` ve `content/en/…` (front matter + HTML gövde). URL'ler birebir korunur (`permalink` alanı).
- **Şablonlar**: `_layouts/` + `_includes/chrome/` (nav, footer, GDPR banner, WhatsApp butonu tek yerden).
- **Global veriler**: `_data/contact.json` (telefon/WhatsApp/e-posta/© yılı — tek yerden), `_data/faqdata.json` (tüm SSS içeriği), `_data/site.json`.
- **TR↔EN eşleştirme**: her sayfada `translationKey`; hreflang etiketleri, dil düğmesi ve sitemap bundan otomatik üretilir. Yeni sayfalarda elle hreflang yazmak gerekmez (`hreflangOverride` sadece migre edilen sayfalarda vardır).
- **Doğrulama**: `scripts/diff-check.js` (migrasyon birebirlik kontrolü, eski site `pre-cms-migration` git tag'inde), `scripts/check-site.js` (CI'da her deploy öncesi çalışır, hata varsa yayın durur).

## Tek seferlik kurulum (geliştirici)

### 1. GitHub Actions FTP secrets
Repo → Settings → Secrets and variables → Actions:

| Secret | Değer |
|---|---|
| `FTP_SERVER` | cPanel FTP sunucusu (örn. `ftp.magusapsikoloji.com`) |
| `FTP_USERNAME` | Sadece `public_html`e yetkili ayrı bir FTP hesabı önerilir |
| `FTP_PASSWORD` | FTP şifresi |
| `FTP_SERVER_DIR` | örn. `/public_html/` |

İlk deploy tüm siteyi yükler (yavaş); sonrakiler artımlıdır. Elle tetiklemek için: Actions → Build & Deploy → Run workflow.

### 2. CMS girişi (Sveltia OAuth)
1. https://github.com/sveltia/sveltia-cms-auth adımlarını izleyerek ücretsiz Cloudflare Worker'ı deploy edin.
2. GitHub → Settings → Developer settings → OAuth Apps → New: callback URL = Worker adresi.
3. Client ID/Secret'ı Worker'ın env değişkenlerine ekleyin.
4. `admin/config.yml` içindeki `base_url` satırını açıp Worker adresini yazın.
5. Müşterinin GitHub hesabını repoya **collaborator** olarak davet edin.

### 3. Yerel geliştirme
```bash
npm install
npm run dev        # Eleventy dev sunucusu (localhost:8080)
npm run dev:css    # Tailwind watch (ayrı terminalde)
npm run build      # üretim build'i (_site/)
npm run check      # bütünlük kontrolleri
```
`localhost:8080/admin/` yerel çalışmada OAuth istemez (Sveltia "Work with Local Repository").

## Müşteri (içerik editörü) ne yapabilir?

- **Blog Yazıları (TR/EN)**: başlık, meta açıklama, kart bilgileri, SSS şeması, ilgili yazılar, CTA ve içerik. Yeni yazı = mevcut bir yazıyı Sveltia'da çoğaltıp düzenlemek en kolayı.
- **Hizmetler ve Sabit Sayfalar**: metin düzenleme (içerik HTML olarak durur; yapıyı bozmadan metinleri değiştirin).
- **Site Ayarları → İletişim Bilgileri**: WhatsApp numarası, telefon, e-posta, © yılı — **tüm sitede tek seferde** değişir.
- **Site Ayarları → SSS Sayfası**: sss.html ve en/faq.html'deki tüm soru/cevaplar.
- **Medya**: görseller `assets/images`e yüklenir (`/assets/images/...` olarak kullanılır).

Her kayıt bir git commit'idir → yanlışlıkla bozulan bir şey `git revert` ile geri alınır. Yayın, kayıttan ~2 dakika sonra otomatiktir.

## Bilinen eksikler / yapılacaklar

1. **Gerçek iletişim bilgileri**: `_data/contact.json` hâlâ placeholder (`9055555`, `+90 5XX…`). Gerçek numaralar girilince tüm site güncellenir.
2. **Görseller**: `assets/images` boş; `og-image.jpg`, `logo.png`, `favicon.png`, `apple-touch-icon.png` sitede referanslı ama dosyalar yok (eski siteden beri 404).
3. **Çevirisi olmayan sayfalar** (`npm run check` uyarı listeler): `blog/beyin-beden.html` (EN yok) ve 3 EN-only yazı (TR yok).
4. **Markdown ile yeni içerik**: v1'de yeni yazılar HTML gövdeli. İleride `@tailwindcss/typography` + shortcode seti eklenerek saf Markdown yazarlığı açılabilir.
5. **SSS konu havuzları** (`faqdata.json → topics`, blog altı akordiyonlar) CMS arayüzünde v1'de yok; düzenlemek için dosyayı doğrudan değiştirin.
