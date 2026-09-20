# Teknik SEO Denetimi — magusapsikoloji.com

**Son tarama:** 2026-09-16 · **Kapsam:** canlı 106 URL (sitemap'in tamamı), Googlebot UA
**Önceki tarama:** 2026-09-02 (92 URL) — bulguları aşağıda kapandı/açık olarak taşındı
**Program fazı:** Arama Görünürlüğü Programı — Faz 1 (yeniden denetim)
**Yöntem:** özel tarayıcı (`cheerio`) + çok-UA canlı HTTP kontrolleri + `npm run check` + `npm run check:live`
**GSC:** Performans raporu (Web, son 3 ay: 2026-07-06 → 2026-09-14) 2026-09-16'da alındı ve işlendi.
Kapsam (Coverage) raporu **alınmadı** — indeksleme durumu hâlâ ölçülemedi.

> **Şema tespiti notu:** Bu sitede JSON-LD Eleventy tarafından **sunucuda** basılıyor
> (`rawSchemas` frontmatter → head), bu yüzden HTML ayrıştırarak tespit güvenilirdir.
> 106 sayfanın tamamındaki `<script type="application/ld+json">` blokları JSON olarak
> parse edildi: 0 parse hatası, 291 şema düğümü.

---

## Yönetici Özeti

**Genel sağlık: on-page ve yapısal katman kusursuza yakın; bu taramanın iki yeni bulgusu
tarama katmanında ve ikisi de dışarıdan gelen ayar değişikliği.**

2026-09-02'den bu yana site 92'den 106 URL'e çıktı ve önceki denetimin açık maddelerinin
çoğu kapandı: WhatsApp kanalı çalışıyor, şemasız sayfa kalmadı, görünür tarihler geldi,
URL yazım hataları düzeldi. On-page tarafında **hiçbir** hata bulunamadı — 106/106 sayfada
self-canonical, tek H1, başlık atlaması yok, title/description tamamen sınırlar içinde,
hreflang beş kontrolün beşinde de sıfır hata, yetim sayfa ve kırık iç bağlantı yok.

Sorun bu sefer sitenin kendisinde değil, **siteye kimin ulaşabildiğinde.**

**En öncelikli 5 bulgu:**

1. **GPTBot ve ClaudeBot 403 alıyor** — Cloudflare engelliyor. 2026-09-02'de ikisi de 200'dü.
   Bu bir gerileme ve tam da içinde bulunduğumuz Faz 8'in (AEO + GEO) dayanağını kesiyor.
2. **Bingbot en önemli 6 sayfada 406 alıyor** — her iki ana sayfa, her iki blog dizini,
   her iki hizmet dizini. Origin (cPanel ModSecurity) kaynaklı, Cloudflare değil.
3. **56/56 yazıda yazar hâlâ `Organization`** — psikologlar artık sitede isimli ve `Person`
   şeması var, ama hiçbir yazı onlara bağlanmıyor. Y2 yarı kapandı.
4. **92 sayfanın tarihi bugüne çekildi** — telefon numarası toplu değişimi 84 içerik dosyasına
   dokundu, `gitDates` bunu "güncellendi" saydı. İçerik değişmedi, tarih değişti.
5. **Şehir sayfaları ana sayfayla aynı kelimeyi hedefliyor** — `/gazimagusa-psikolog.html`
   ile `/` birebir aynı title'a sahip; her iki şehir sayfasının da yalnız 1 iç bağlantısı var.

**Hızlı kazanımlar:** 1 ve 2 numara kod değişikliği değil, panel ayarı — biri Cloudflare
kontrol panelinde, diğeri cPanel/hosting tarafında. İkisi birlikte bir saatlik iş.

**GSC verisi ne ekledi (son 3 ay: 46 tıklama, 1.449 gösterim):**

- **Y7 doğrulandı** — `/gazimagusa-psikolog.html` 3 ayda **sıfır gösterim** almış; aynı
  kelimeyi ana sayfa 8,66. sırada karşılıyor.
- **Ölçülmüş en büyük talep bir hizmet boşluğunda (G1)** — İngilizce travma sorguları
  165 gösterim, 0 tıklama, 40,9. sıra. Travma hizmet listesinde yok. **Kapsam kararı gerekiyor.**
- **Site gösterim alıyor, tıklama alamıyor** — 46 tıklamanın %76'sı tek sayfadan (ana sayfa).
  365 gösterimlik üç büyük küme 40-100. sıra bandında sıkışmış (G2).
- Ada içi hedefleme **çalışıyor**: Kıbrıs'ta ortalama 8,77. sıra, 33 tıklama.

---

## Kritik Bulgular

### K2 · Cloudflare, GPTBot ve ClaudeBot'u engelliyor — **YENİ, GERİLEME**
**Etki:** Kritik (AEO/GEO) · **Kanıt:** çok-UA canlı test, 2026-09-16

| Bot | 2026-09-02 | 2026-09-16 |
|---|---|---|
| GPTBot | 200 | **403** |
| ClaudeBot | 200 | **403** |
| CCBot | 200 | **403** |
| OAI-SearchBot | 200 | 200 |
| PerplexityBot | 200 | 200 |
| Google-Extended | 200 | 200 |
| Googlebot | 200 | 200 |

Yanıt gövdesi `Your request was blocked.` (25 bayt), başlıkta `server: cloudflare`,
`cf-ray` var ve `cf-cache-status` yok — engel Cloudflare kenarında, origin'e hiç gitmiyor.
Bu, Cloudflare'ın **AI Crawl Control / "Block AI bots"** özelliğinin imzası.

Neden önemli: programın şu anki fazı (Faz 8) AI motorlarında alıntılanmak üzerine kurulu.
GPTBot ve ClaudeBot eğitim/erişim tarayıcıları; ikisi de kapalıyken `llms.txt`, FAQPage
şeması ve çıkarılabilirlik çalışmasının bu iki motorda karşılığı olmaz. Not: OAI-SearchBot
(ChatGPT'nin arama tarayıcısı) ve PerplexityBot açık — yani zarar kısmi, ama Faz 2'de
kilitlenen 20 sorgunun ölçümü bu ayar değişmeden anlamlı karşılaştırma vermez.

**Düzeltme:** Cloudflare paneli → ilgili alan adı → **AI Crawl Control** (eski adıyla "Bot
Fight Mode" altındaki AI bots ayarı). GPTBot, ClaudeBot ve CCBot için izin ver. Hangi
tarayıcıya izin verileceği bir **içerik politikası kararı** — engel kasıtlıysa Faz 8'in
kapsamı buna göre daraltılmalı, çünkü o durumda iki büyük motor kalıcı olarak dışarıda.
**Öncelik:** 1

---

### K3 · Bingbot, en önemli 6 sayfada 406 alıyor — **YENİ**
**Etki:** Kritik (Bing + Copilot indeksleme) · **Kanıt:** 106 URL × bingbot UA taraması

Tam olarak **dizin biçimindeki** URL'ler etkileniyor; `.html` ile biten 100 URL sorunsuz:

| URL | bingbot | Googlebot |
|---|---|---|
| `/` | **406** | 200 |
| `/en/` | **406** | 200 |
| `/blog/` | **406** | 200 |
| `/en/blog/` | **406** | 200 |
| `/hizmetler/` | **406** | 200 |
| `/en/services/` | **406** | 200 |
| diğer 100 URL | 200 | 200 |

Aynı 406'yı **BingPreview** ve **YandexBot** da alıyor; DuckDuckBot, Googlebot, Facebook,
Twitter, WhatsApp, LinkedIn, Slack normal 200 alıyor.

Engel Cloudflare değil: yanıt `cf-cache-status: DYNAMIC` taşıyor, yani istek origin'e
ulaşmış ve 406'yı **origin üretmiş**. 406 (Not Acceptable) + belirli UA + yalnız
DirectoryIndex isteklerinde tetiklenmesi, cPanel'deki **ModSecurity** kural setinin
klasik davranışı.

Neden önemli: engellenen 6 URL sitenin en önemli 6 URL'i. Bing bu sayfaları hiç göremiyor.
Bing indeksi Copilot'u da besliyor, yani bu bulgu Faz 8'i de ilgilendiriyor.

**Düzeltme:** Hosting sağlayıcısına ModSecurity denetim kaydı (audit log) sordurulup
tetikleyen kural kimliği bulunacak ve alan adı için istisnaya alınacak. Sağlayıcı
erişimi yoksa cPanel → ModSecurity → alan adı için kapatma da çalışır ama geniş bir
çözümdür. **Öncelik:** 1

---

## Yüksek Öncelikli Bulgular

### Y2 · Yazar hâlâ kurum — **YARI KAPANDI**
**Etki:** Yüksek (E-E-A-T, GEO) · **Kanıt:** 56 yazının 56'sında `Article.author.@type = Organization`

Kapanan kısım: psikologlar artık sitede isimli. `/hakkimizda.html` ve `/en/about.html`
sayfalarında iki `Person` düğümü var, `@id` ile kimliklendirilmiş
(`#gokce-ince`, `#yaprak-parlan-yesilovali`), `LocalBusiness.employee` onlara bağlanıyor,
`alumniOf` ve `knowsAbout` dolu. `llms.txt` de isimleri ve eğitimleri taşıyor.

Kapanmayan kısım: **hiçbir yazı bu iki kişiye bağlanmıyor.** 56 yazının tamamında yazar
hâlâ kurumun kendisi. Faz 3'te bu maddenin alıntılanmayı en çok belirleyen madde olduğu
ölçülmüştü; varlık artık mevcut, yalnız yazılara bağlanmamış durumda.

**Düzeltme:** İki adım, ikisi de mekanik:
1. Her yazının frontmatter'ına yazar alanı; şemada `author` → `{"@id": "…/#gokce-ince"}`
2. Yazı künyesinde görünür imza (tarih zaten künyeye taşınıyor — çalışma ağacındaki
   değişiklik bunu yapıyor, aynı satıra yazar da eklenebilir)

Hangi yazının hangi psikologa ait olduğu **merkezden gelmeli** — Claude bunu atayamaz.
**Öncelik:** 2

---

### Y4 · Beş marka görseli 404 — ✅ **ÇÖZÜLDÜ (2026-09-20, `5f6967b` + devamı)**
Kullanıcı marka logosunu verdi; beş dosya da ondan üretildi. `logo.png` (800×295) yazı
işaretinin kırpılmış hâli, `og-image.jpg` (1200×630) logo marka kreminde ortalanmış,
favicon üçlüsü logodaki çiçek işaretinden. `/favicon.ico` köke passthrough ile taşınıyor.
Şablon tarafına dokunulmadı — yollar zaten doğruydu, yalnız dosyalar yoktu.

<details><summary>Özgün bulgu</summary>

**Etki:** Yüksek · **Kanıt:** doğrudan HTTP kontrolü

`/assets/images/og-image.jpg` · `/assets/images/logo.png` · `/assets/images/favicon.png` ·
`/assets/images/apple-touch-icon.png` · `/favicon.ico` — beşi de 404.

Sonuçları: 106 sayfanın tamamında `og:image` kırık bir dosyayı gösteriyor (her paylaşımın
önizlemesi boş), `LocalBusiness.image` ve `LocalBusiness.logo` 404'e işaret ediyor (Google
için varlık doğrulama sinyali zayıflıyor), sekmede ikon yok.

Şablon tarafı doğru kurulmuş — yalnız dosyalar eksik. **Kullanıcıdan dosya bekliyor.**
**Öncelik:** 2
</details>

---

### Y6 · 92 sayfanın güncelleme tarihi gerçeği yansıtmıyor — ✅ **ÇÖZÜLDÜ (2026-09-16, `fb21b47`)**
**Etki:** Yüksek (tazelik sinyali bütünlüğü) · **Kanıt:** sitemap + şema + görünür tarih sayımı

| Ölçüm | Değer |
|---|---|
| `lastmod` = 2026-09-16 olan sitemap URL'i | 92 / 106 |
| `Article.dateModified` = 2026-09-16 olan yazı | 52 / 56 |
| Görünür "16 Eylül 2026" taşıyan sayfa | 88 / 106 |

Nedeni: `b58739e` numaralı commit telefon numarasını 84 içerik dosyasında toplu değiştirdi.
`scripts/git-dates.js` `dateModified` değerini git commit tarihinden türetiyor, dolayısıyla
tek satırlık bir numara değişimi bütün siteyi "bugün güncellendi" yaptı. Mayıs'ta yazılmış
ve o günden beri içeriği değişmemiş yazılar da bugünün tarihini taşıyor.

Bu bir Google cezası konusu değil, ama iki somut zararı var: (a) tazelik sinyali gerçeği
yansıtmadığı için güvenilirliğini kaybediyor, (b) **gerçekten güncellenen yazı artık
diğerlerinden ayırt edilemiyor** — bundan sonraki içerik çalışmasının etkisi ölçülemez hale
geliyor. `datePublished` doğru kalmış (Mayıs–Eylül dağılımı korunuyor), zarar yalnız
`dateModified` tarafında.

**Düzeltme seçenekleri:**
- `git-dates.js` içinde belirli commit'leri (ör. mesajında bir işaret taşıyanları) yok sayma
- Anlamsal olarak değişmemiş içerikte `dateModified`'ı frontmatter'dan sabitleme
- Bundan sonra toplu teknik değişiklikleri içerik commit'lerinden ayrı tutma

**✅ Çözüldü (`fb21b47`):** `git-dates.js` artık commit mesajında `[lastmod skip]` taşıyan
commit'leri yok sayıyor ve dosyanın bir önceki gerçek değişikliğine düşüyor. Telefon
süpürmesi bu kural konmadan önce atıldığı için SHA'sı dosyada listelendi. Ayrıca iki git
çağrısı `execFileSync`'e çevrildi (`--grep` deseni boşluk içerdiği için kabuk onu hatalı
revizyon argümanına bölüyordu).

**Doğrulanan sonuç:**

| | Önce | Sonra |
|---|---|---|
| Görünür tarihin dağıldığı gün sayısı | 4 | **7** |
| Bugünün tarihini taşıyan sayfa | 88 | **4** |
| En kalabalık tek gün | 88 sayfa | 37 sayfa (2026-09-08) |

Bugünü gösteren 4 sayfa doğru: ana sayfa ve hakkımızda (biyografi gerçekten bugün yazıldı)
artı iki yeni travma sayfası.

**Öncelik:** — (kapandı)

---

### Y7 · Şehir sayfaları ana sayfayla aynı kelimeyi hedefliyor — **YENİ**
**Etki:** Yüksek (yamyamlık) · **Kanıt:** title karşılaştırması + iç bağlantı sayımı

2026-09-06 kararıyla açılan şehir sayfaları ana sayfalarla çakışıyor:

| | title | iç bağlantı |
|---|---|---|
| `/` | `Gazimağusa Psikolog \| Mağusa Psikoloji Merkezi` | çok |
| `/gazimagusa-psikolog.html` | `Gazimağusa Psikolog \| Mağusa Psikoloji Merkezi` | **1** |
| `/en/` | `Psychologist in Famagusta \| Psychology Centre — North Cyprus` | çok |
| `/en/psychologist-in-famagusta.html` | `Psychologist in Famagusta \| Famagusta Psychology Centre` | **1** |

Türkçe çiftin title'ı **birebir aynı** — sitedeki tek yinelenen title bu. İngilizce çift de
aynı ana kelimeyi (`psychologist in famagusta`) hedefliyor.

İki sorun üst üste biniyor: aynı sorgu için iki sayfa yarışıyor **ve** yarışan iki sayfanın
her birinin yalnız bir iç bağlantısı var. Faz 3'te "şehir sayfaları GBP'siz organik sonuç
alabiliyor" tespiti doğruydu, ama bunun için şehir sayfasının ana sayfadan **farklı** bir
niyeti hedeflemesi ve iç bağlantıyla desteklenmesi gerekiyor.

**GSC ile doğrulandı (2026-09-16):** `/gazimagusa-psikolog.html` son 3 ayda **hiç gösterim
almamış** — sayfa listesinde yok. Aynı dönemde `gazimağusa psikolog` sorgusu 11 gösterim,
ortalama 8,73. sıra almış; bu gösterimleri **ana sayfa** karşılıyor (`/` → 508 gösterim,
35 tıklama, 8,66. sıra). İngilizce şehir sayfası da 3 ayda 1 gösterim almış.

> **Uyarı:** şehir sayfaları 2026-09-06 civarında açıldı, yani veri penceresinin yalnız son
> ~1 haftasını kapsıyorlar. "Sıfır gösterim" kesin bir başarısızlık kanıtı değil; ama ana
> sayfanın aynı kelimede zaten 8,66. sırada oturduğu kesin. Şehir sayfası ana sayfayla aynı
> title'ı taşıdığı sürece Google'ın ikisinden birini seçmesi için sebep yok.

**Düzeltme:** Şehir sayfalarına ayrı niyet ver (ana sayfa = merkez tanıtımı, şehir sayfası =
"Gazimağusa'da psikolog nasıl bulunur, nereye gidilir, ne beklenir" gibi yerel arayışa
cevap), title'ları ayrıştır, hizmet ve blog sayfalarından iç bağlantı ver.
**Öncelik:** 2

---

## Orta Öncelikli Bulgular

### Y1 · Dış kaynak atfı — **KISMEN KAPANDI**
44/56 yazıda hâlâ sıfır dış atıf var; 12 yazı artık kaynak gösteriyor (nhs.uk ×6,
doi.org ×10, pubmed ×4). 2026-09-02'de bu sayı 0/56'ydı. Kaynak gösteren 12 yazının
tamamı ebeveyn ve online terapi kümelerinde — yani en son çalışılan kümelerde.
Kalan liste `.agents/kaynak-inceleme-listesi.md` içinde. **Öncelik:** 3

### O8 · `Person` şeması zayıf — **YENİ**
İki psikoloğun `Person` düğümünde `sameAs` yok (0 bağlantı) ve `hasCredential` yok.
`alumniOf` ve `knowsAbout` dolu, bu iyi. Varlık birleştirme için en güçlü sinyal
`sameAs` — psikologların mesleki profilleri (varsa LinkedIn, dernek üyelik sayfası,
üniversite sayfası) buraya bağlanmalı. Lisans bilgisi `hasCredential` ile işaretlenebilir.
**Bilgi merkezden bekleniyor.** **Öncelik:** 3

### O9 · `LocalBusiness` şemasında eksik alanlar — **KAPANDI (2026-09-20)**
`telephone`, `address`, `email`, `sameAs`, `availableLanguage`, `employee`, `areaServed`
dolu ve doğru. Google İşletme Profili açıldı; `geo`, `openingHoursSpecification` ve
`hasMap` profil verisiyle dolduruldu ve 42 sayfada basılıyor. Koordinat profilin iğne
konumundan alındı (35.1314183, 33.9310237), bağlantı CID biçiminde
(`maps.google.com/?cid=8380127482439713846`).

Kapanmayan tek alan **posta kodu**: profilde yok, KKTC'de tutarlı kullanılmıyor,
kullanıcı kararıyla boş bırakıldı. `priceRange` 2026-09-08 kararı gereği bilerek yok.
**Öncelik:** — (kapandı)

### O1 · İçerik derinliği — **KISMEN İYİLEŞTİ**
6 sayfa <300 kelime (önceki ölçümde 8), 12 sayfa 300-500 arası. Medyan 718 kelime.

| Kelime | Sayfa |
|---|---|
| 66 | `/iletisim.html` |
| 81 | `/en/contact.html` |
| 192 | `/blog/beyin-beden.html` |
| 234 | `/terapi-sureci.html` |
| 243 | `/felsefemiz.html` |
| 250 | `/en/blog/brain-body-connection.html` |

İletişim sayfalarının kısalığı beklenen ve sorun değil. Asıl zayıf grup hâlâ **hizmet
sayfaları**: 444-492 kelime bandında sıkışmış durumdalar ve Faz 2'de çıkarılabilirlik
ölçümünde de en düşük skoru onlar almıştı (sorgu-H2 %15, ideal pasaj %38). **Öncelik:** 3

### O4 · İçerik içi iç bağlantı seyrek
22 sayfada ana içerik içinde 2 veya daha az iç bağlantı var. En dikkat çekeni: **`/sss.html`
ve `/en/faq.html` sayfalarında sıfır.** SSS sayfaları en çok soru karşılayan sayfalar;
oradan hizmet sayfalarına bağlantı hem kullanıcı hem tarama açısından en verimli bağlantı
olurdu. **Öncelik:** 3

### O10 · Aynı SSS sorusu birden çok sayfada — **YENİ**
446 FAQ girdisinin 400'ü benzersiz; 40 soru birden fazla sayfada tekrar ediyor.
En çok tekrarlananlar ana sayfa, `/sss.html` ve `/blog/` arasında paylaşılıyor
(ör. "Terapi kaç seans sürer?" 4 sayfada). FAQPage şemasının aynı soruyu birden çok
URL'de iddia etmesi Google'ın hangi sayfayı seçeceğini belirsizleştirir.
**Düzeltme:** Kanonik soru sahibi sayfayı belirle (SSS sayfası), diğerlerinde görünür
metni bırak ama şemadan çıkar. **Öncelik:** 4

### O11 · `schema-markup.json` hâlâ canlıda — **AÇIK (değişmedi)**
`https://magusapsikoloji.com/schema-markup.json` 200 dönüyor ve içinde placeholder veri
servis ediliyor: `"streetAddress": "Adres Buraya"`, `"telephone": "+90-392-000-0000"`,
`"postalCode": "00000"`, ayrıca 404 olan `/logo.png` ve `/images/klinik.jpg`.

İyi haber: **hiçbir sayfa bu dosyayı referans göstermiyor** (depoda ve build çıktısında
tek referans yok), dolayısıyla etkisi sınırlı. Yine de herkese açık ve yanlış veri taşıyor.
cPanel'den elle silinmeli — depodan zaten kaldırılmış. **Öncelik:** 4

### O12 · 5 description 110 karakterin altında
`/en/services/family-therapy.html` (105) · `/en/services/online-therapy.html` (104) ·
`/hizmetler/aile-terapisi.html` (102) · `/hizmetler/ogrenci-danismanlik.html` (104) ·
`/hizmetler/yetiskin-psikolojisi.html` (109). Hepsi sınır içinde ama alan boş kalıyor.
**Öncelik:** 4

---

## Düşük Öncelikli

- **HSTS başlığı yok** — `strict-transport-security` hiçbir yanıtta yok. http→https 301
  çalışıyor, yani işlevsel sorun yok; HSTS ek güvenlik katmanı.
- **HTML'de `cache-control` yok** — origin başlık göndermiyor, Cloudflare `DYNAMIC` ile
  geçiyor. Statik bir sitede HTML için kısa süreli önbellek verilebilir.
- **Font preload yok** — Inter ve Lora `@font-face` ile kendi sunucumuzdan geliyor (Google
  Fonts'a bağımlılık yok, bu doğru kurulum), ama preload edilmiyor.
- **`/favicon.ico` yok** — Y4'ün parçası.

---

## Kapanan Bulgular (2026-09-02 → 2026-09-16)

| Kod | Bulgu | Durum |
|---|---|---|
| K1 | WhatsApp bağlantıları 92/92 sayfada kırık | ✅ 220 bağlantı gerçek numarada |
| Y3 | Görünür tarih yok | ✅ 102/106 sayfada görünür `<time>` |
| O2 | 6 sayfada şema yok | ✅ 106/106 sayfada şema, 0 parse hatası |
| O3 | "ruh" kelimesi 5 sayfada | ✅ URL taşındı + 301 |
| O6 | 2 URL'de yazım hatası | ✅ düzeltildi + 301 |
| O7 | Çift terapisi hizmet sayfası yok | ✅ `/hizmetler/cift-terapisi.html` yayında |
| O5 | Yamyamlık (online terapi ×3, aile terapisi ×2) | ✅ niyet ayrışmış — bkz. not |
| — | Uzun title'lar, eksik description | ✅ 106/106 sınırlar içinde |

**O5 notu:** Online terapi üçlüsü artık niyet olarak ayrışmış durumda — `/hizmetler/online-terapi.html`
(hizmet, 511 kelime), `/blog/online-terapi-etkili-mi.html` (kanıt/araştırma, 923),
`/blog/kuzey-kibris-online-terapi.html` (yerel nasıl işler, 771). Kaygı ikilisi de
(`anksiyete-nedir` / `kaygi-bozuklugu-nedir`) H2 düzeyinde ayrışmış: biri belirti tanıma,
diğeri türler ve tedavi. Bu küme artık sorun değil. Yamyamlık yalnız Y7'de (şehir sayfaları).

---

## Değişmeyen Sağlam Temel

106 URL üzerinde sıfır hata bulunan kontroller:

| Kontrol | Sonuç |
|---|---|
| HTTP durumu | 106/106 → 200 |
| Canonical | 106/106 self-canonical, eksik yok |
| Hreflang | 0 hata (self-referans, karşılıklılık, x-default, kod geçerliliği, canonical uyumu) |
| `noindex` | 0 sayfa |
| H1 | 106/106 tek H1, başlık atlaması 0 |
| Title | 0 eksik, 0 adet >60 karakter |
| Description | 0 eksik, 0 adet >160 karakter |
| Yetim sayfa | 0 |
| Kırık iç bağlantı | 0 (`npm run check`: 0 hata, 0 uyarı) |
| Görsel `alt` | 56/56 dolu |
| Görsel `width`/`height` | 56/56 var (CLS riski yok) |
| Görsel `loading` | 56/56 var |
| `viewport` / `html lang` | 106/106 var |
| Yönlendirmeler | http→https, www→apex, `/index.html`→`/` hepsi 301 |
| `robots.txt` / `sitemap.xml` | doğru, tutarlı, sitemap 106 URL |
| `llms.txt` | güncel, psikolog isimleri ve eğitimleri dahil |

---

## Performans

PSI kotası yine dolu (önceki denetimde de öyleydi) — **laboratuvar CWV skorları alınamadı.**
Bileşenler doğrudan ölçüldü ve hepsi iyi durumda:

| Ölçüm | Değer |
|---|---|
| TTFB (min/medyan/p90/maks) | 47 / 60 / 79 / 196 ms |
| CSS (brotli) | 8 KB |
| JS | 634 bayt, `</body>` öncesi, render engellemiyor |
| Sıkıştırma | brotli (`content-encoding: br`) |
| HTML boyutu (medyan/maks) | 43 KB / 68 KB |
| Font | kendi sunucumuzda `@font-face`, dış istek yok |
| Görsellerde boyut niteliği | 56/56 (düzen kayması riski yok) |

Gerçek CWV skoru için PSI API anahtarı alınması ya da GSC'nin Core Web Vitals raporu gerekir.

---

## Ölçülemeyenler

- **Laboratuvar CWV** — PSI günlük kota aşımı. API anahtarı kotayı açar.
- **CrUX alan verisi** — PSI üzerinden alınamadı.
- **İndeksleme kapsamı** — kaç URL indekslenmiş, hangileri hariç tutulmuş. **GSC Kapsam
  (Coverage) raporu hâlâ alınmadı** — Performans raporu geldi, Kapsam gelmedi.
  Bu, 106 URL'in kaçının fiilen indekste olduğunu bilmediğimiz anlamına geliyor.
- **Bing indeks durumu** — K3'ün fiili zararı Bing Webmaster Tools ister.
- **Sorgu × sayfa eşleşmesi** — alınan dışa aktarımlar sorgu ve sayfa boyutlarını **ayrı**
  tablolar halinde veriyor. "Hangi sorgu hangi sayfaya gitti" ancak GSC arayüzünde sorgu
  filtresi + sayfa kırılımı ile görülür. Aşağıdaki küme–sayfa eşleştirmeleri bu yüzden
  **çıkarım**, doğrudan ölçüm değil.

---

## Search Console Verisi (2026-07-06 → 2026-09-14, Web)

### Genel tablo

| Ölçüm | Değer |
|---|---|
| Tıklama (3 ay) | **46** |
| Gösterim (3 ay) | **1.449** |
| Ortalama TO | %3,2 |
| Ortalama sıra | ~28 |

| Dönem | Tıklama | Gösterim | TO | Ort. sıra |
|---|---|---|---|---|
| 2026-07-06 → 07-31 | 11 | 358 | %3,07 | 23,3 |
| 2026-08 | 21 | 666 | %3,15 | 34,7 |
| 2026-09-01 → 09-14 | 14 | 425 | %3,29 | 28,2 |

Eğilim yukarı: Eylül'ün ilk yarısı tek başına Temmuz'un tamamını geçmiş. Hacim hâlâ çok
düşük, yani her yorum geniş hata payıyla okunmalı.

### Tek sayfa her şeyi taşıyor

`/` → **35 tıklama / 508 gösterim / 8,66. sıra** — sitenin 46 tıklamasının **%76'sı**.
Ardından gelenler: `/en/blog/find-psychologist-north-cyprus.html` (4 tıklama, 10,2. sıra),
`/en/` (2 tıklama, 7,38. sıra), `/hizmetler/cocuk-psikolojisi.html` (2 tıklama, 9,81. sıra).

Geri kalan ~68 sayfa toplam 3 tıklama almış. Yani site **gösterim alıyor ama tıklama
alamıyor** — 1.449 gösterimin 941'i ilk 4 sayfa dışındaki sayfalara ait ve neredeyse
tamamı 40-100. sıra bandında.

### Talep kümeleri — ölçülen gerçek arama

| Küme | Gösterim | Tıklama | Ort. sıra | Sayfamız var mı? |
|---|---|---|---|---|
| **Travma / Kıbrıs (EN)** | **165** | 0 | 40,9 | ❌ **hizmet sayfası yok** |
| **Panik atak** | **104** | 0 | 71,6 | ✅ var, 71,9. sırada |
| **Psikolog mu psikiyatrist mi** | **96** | 0 | 74,8 | ✅ iki dilde var, 67-82. sırada |
| Yerel marka/hizmet (mağusa/kktc psikolog) | 82 | **5** | **13,5** | ✅ ana sayfa |
| Çocuk | 71 | 0 | 36,5 | ✅ hizmet + blog |
| Terapiye ne zaman | 20 | 0 | 50,6 | ✅ iki dilde var |
| Kaygı | 18 | 0 | 84,1 | ✅ iki yazı |
| Depresyon | 17 | 0 | 75,8 | ✅ iki dilde var |
| EMDR (**verilmiyor**) | 10 | 0 | 70,6 | ❌ bilerek yok |
| Nöropsikolojik | 5 | 0 | 93,8 | ✅ hizmet sayfası var |

**Okuma:** Tıklamayı getiren tek küme, hacmi en küçük olan yerel kümesi (82 gösterim →
5 tıklama, 13,5. sıra). Büyük hacimli üç küme (travma, panik atak, psikolog-psikiyatrist —
toplam **365 gösterim**) sıfır tıklama üretiyor çünkü hepsi 40-100. sıra bandında.

### G1 · En büyük ölçülmüş talep bir hizmet boşluğunda — **YENİ, KARAR GEREKTİRİR**
**Etki:** Yüksek (içerik stratejisi) · **Kanıt:** GSC sorgu kümesi, 165 gösterim

Tek başına en büyük küme İngilizce travma sorguları:

| Sorgu | Gösterim | Ort. sıra |
|---|---|---|
| childhood trauma treatment cyprus | 61 | 37,8 |
| developmental trauma treatment cyprus | 34 | 38,6 |
| mental health treatment cyprus | 16 | 44,6 |
| emotional trauma treatment cyprus | 12 | 36,8 |
| trauma and mental health treatment cyprus | 12 | 41,3 |
| ptsd treatment cyprus | 12 | 54,0 |
| psychological trauma treatment cyprus | 11 | 48,6 |
| trauma treatment cyprus | 3 | 46,0 |

Bu gösterimleri büyük olasılıkla `/en/blog/psychological-support-north-cyprus.html`
karşılıyor (151 gösterim, 27. sıra, 1 tıklama) — yani konuya **doğrudan** cevap veren bir
sayfa değil, genel bir destek yazısı.

Sitede "travma" 35 sayfada geçiyor, ama hepsinde **başka bir konunun içinde** (aile
terapisinde kuşaklararası örüntüler, çocuk psikolojisinde duygu düzenleme, nöropsikolojik
değerlendirmede kafa travması). Ayrı bir travma hizmet sayfası yok ve **travma
`product-marketing.md`'deki hizmet listesinde de yok.**

### ✅ KAPSAM CEVAPLANDI (2026-09-16)

**Travma odaklı çalışma veriliyor.** Yaş grupları: **çocuk, ergen, beliren yetişkinlik,
yetişkin** (kullanıcı teyidi). Kural 1 engeli kalktı; `product-marketing.md` hizmet
listesi güncellendi.

Bu, sitenin **en yüksek kanıtlı içerik fırsatı**: talep ölçülmüş (165 gösterim), hizmet
gerçekten veriliyor, ve konuya doğrudan cevap veren tek bir sayfa yok. Şu an bu gösterimleri
genel bir destek yazısı 40,9. sıradan karşılıyor.

**✅ UYGULANDI (2026-09-16).** TR + EN travma hizmet sayfası yazıldı:

| | TR | EN |
|---|---|---|
| URL | `/hizmetler/travma-terapisi.html` | `/en/services/trauma-therapy.html` |
| Kelime | 863 | 1.218 |
| Title | 55 kr | 53 kr |
| Description | 160 kr | 149 kr |
| H2 | 9 | 9 |
| Şema | Breadcrumb + FAQPage + LocalBusiness | aynı |
| Görünür SSS | 9 soru | 9 soru |
| İç bağlantı (giden) | 11 | 11 |
| İç bağlantı (gelen) | 3 konusal | 3 konusal |

Teyit edilen kapsam: **yaklaşımlar** travma odaklı BDT, psikodinamik, ACT, çocuklarda oyun
ve sanat temelli çalışma; **tablolar** çocukluk çağı, gelişimsel, TSSB, tek olaya bağlı
travma, yas ve kayıp; **yaş grupları** çocuk, ergen, beliren yetişkinlik, yetişkin.

**EMDR'ye ayrı bir H2 ayrıldı** ("EMDR uyguluyor musunuz?" / "Do you offer EMDR?") ve
verilmediği açıkça yazıldı. Gerekçe: travma denince en sık sorulan yöntem bu ve GSC'de
ölçülmüş EMDR talebi var — belirsiz bırakmak yanlış beklenti üretirdi.

H2'ler ölçülen sorgu kalıplarına göre kuruldu (`childhood trauma`, `developmental trauma`,
`ptsd`, `trauma treatment cyprus`). Hiçbir istatistik iddiası kullanılmadı, dolayısıyla
Y1 kapsamına yeni borç eklemiyor. Tanı koyma iddiası yok; tanı kararının hekime ait olduğu
sayfada belirtildi.

**Kalan:** psikolog onayı (klinik metin). Yayına çıktıktan sonra `childhood trauma treatment
cyprus` kümesinin sırası izlenecek — mevcut taban 40,9.

### G2 · İki güçlü sayfa var ama dipte duruyor — **YENİ**
**Etki:** Orta-Yüksek · **Kanıt:** GSC sayfa + sorgu verisi

| Sayfa | Gösterim | Sıra | Küme hacmi |
|---|---|---|---|
| `/blog/panik-atak-belirtileri.html` | 109 | **71,9** | 104 gösterim |
| `/blog/psikolog-mu-psikiyatrist-mi.html` | 72 | **82,0** | 96 gösterim |
| `/en/blog/psychologist-or-psychiatrist.html` | 61 | **67,0** | (aynı küme) |
| `/en/services/child-psychology.html` | 108 | 37,5 | 71 gösterim |

Bu sayfalar **zaten var ve talep de var** — sorun sıralama. 70-82. sıra, "sayfa 7-9"
demek; hiçbir tıklama gelmez. Bunlar sıfırdan içerik üretmeyi değil, **mevcut sayfayı
güçlendirmeyi** gerektiren dört sayfa:

- `psikolog ilaç yazabilir mi` ailesi tek başına ~49 gösterim taşıyor (5 ayrı sorgu).
  Bu, psikolog-psikiyatrist yazısının en net alt-niyeti ve yazıda ayrı bir H2 hak ediyor.
- Panik atak yazısı 10 ayrı belirti sorgusu alıyor; hepsi "belirtileri nelerdir" kalıbında.
- İkisi de Faz 5'in çıkarılabilirlik çalışmasından (sorgu-H2 eşleşmesi) doğrudan fayda görür.
- İkisi de Y1 kapsamında — kaynak atfı yok.

**Öncelik:** 3 — Faz 5/8 işiyle birebir örtüşüyor, ayrı iş değil.

### G3 · Masaüstü ile mobil arasında 36 sıralık fark — **YENİ, İZLENECEK**

| Cihaz | Tıklama | Gösterim | TO | Ort. sıra |
|---|---|---|---|---|
| Mobil | 32 | 641 | %4,99 | **10,8** |
| Masaüstü | 12 | 776 | %1,55 | **46,8** |
| Tablet | 2 | 32 | %6,25 | 7,3 |

Masaüstü daha çok gösterim alıyor ama 36 sıra daha geride ve TO'su üçte bir.

En olası açıklama **sorgu karması**: yerel niyetli aramalar (mobil ağırlıklı, "mağusa
psikolog") ana sayfayı 8-13. sırada getiriyor; bilgi amaçlı uzun kuyruk (masaüstü ağırlıklı,
travma/panik atak/psikiyatrist) 40-100. sırada. Yani bu, masaüstünde **teknik** bir sorun
olduğu anlamına gelmiyor — teknik taramada mobil/masaüstü ayrımı yaratacak hiçbir bulgu yok
(tek HTML, responsive, aynı içerik).

Yine de G1 ve G2 çözülürse bu farkın kapanması beklenir. **Kapanmazsa** ayrıca bakılmalı.
**Öncelik:** 4 — şimdilik yalnız izlenecek.

### G4 · Zengin sonuç görünümü sıfır — **YENİ**
Arama Görünümü (Search Appearance) dışa aktarımı **tamamen boş**. 87 sayfada FAQPage
şeması olmasına rağmen kayıtlı tek bir zengin sonuç görünümü yok.

Bu beklenen bir sonuç: Google, FAQ zengin sonuçlarını 2023 Ağustos'ta çok küçük bir
devlet/sağlık sitesi grubu dışında **kaldırdı**. Yani FAQPage şeması artık SERP'te görsel
kazanım getirmiyor. **Şema yine de değerli** — AI motorları ve pasaj çıkarımı için okunuyor,
Faz 6'nın gerekçesi buydu. Ama "zengin sonuç geleceği" beklentisi varsa düzeltilmeli.
**Aksiyon yok**, yalnız beklenti düzeltmesi.

### Doğrulanan temizlikler

GSC'de görünen bazı eski adresler kontrol edildi, hepsi doğru yanıt veriyor:

| GSC'de görünen | Canlı durum |
|---|---|
| `/blog/emdr-terapi-nedir.html` (22 gösterim) | ✅ 404 — kaldırılmış, doğru |
| `/en/blog/what-is-emdr-therapy.html` (5 gösterim) | ✅ 404 — kaldırılmış, doğru |
| `/blog/aile-terapisi-magussa.html` (1 tıklama, 4,67. sıra) | ✅ 301 → `aile-terapisi-magusa.html` |
| `/blog/magusa-uluslararasi-ogrenciler-ruh-sagligi.html` | ✅ 301 → `...-psikolojik-destek.html` |
| `http://magusapsikoloji.com/en/our-approach.html` (3 gösterim) | ✅ 301 → https |
| `https://www.magusapsikoloji.com/` (1 gösterim) | ✅ 301 → apex |

EMDR sorguları (10 gösterim) hâlâ geliyor ama sayfalar kaldırıldığı için tıklama üretmiyor —
**bu doğru davranış**, 2026-09-06 kararıyla uyumlu. Eski adreslerin GSC'de görünmesi
normaldir; Google kaldırılan adresleri bir süre raporlamaya devam eder.

### Coğrafya

| Ülke | Tıklama | Gösterim | Ort. sıra |
|---|---|---|---|
| Kıbrıs | **33** | 580 | 8,77 |
| Türkiye | 6 | 350 | 50,47 |
| İngiltere | 0 | 212 | 43,03 |
| ABD | 0 | 62 | 27,0 |

> **Not:** GSC'nin ayrı bir KKTC ülke kodu yok; ada içi aramalar "Kıbrıs" altında
> raporlanır. Yani 33 tıklamanın ezici çoğunluğu **hedef kitlemiz**. Yerel hedefleme
> çalışıyor: Kıbrıs'ta ortalama 8,77. sıra, Türkiye'de 50,47.

Türkiye'nin 350 gösterimi büyük olasılıkla jenerik bilgi sorguları (panik atak, depresyon
nedir) — dönüşmesi beklenmez ve hedef de değil. İngiltere'nin 212 gösterimi ise İngilizce
travma/psikiyatrist kümesi; **G1 kararı buna da dokunuyor.**

---

## Öncelikli Eylem Planı

**1 — Tarama engelleri (kod değil, panel ayarı; ~1 saat)**
1. Cloudflare → AI Crawl Control → GPTBot, ClaudeBot, CCBot izinli hale getir **(K2)**
2. Hosting → ModSecurity kuralını 6 dizin URL'i için istisnaya al **(K3)**

**2 — Travma hizmet sayfası (G1) — kapsam ✅ onaylandı 2026-09-16**
3. **Travma yaklaşımı + kapsanan tablolar** teyit edilecek → ardından TR+EN hizmet sayfası
   yazılabilir. Ölçülmüş 165 gösterimlik talep, sitenin en yüksek kanıtlı içerik fırsatı.
   Yaş grupları netleşti: çocuk, ergen, beliren yetişkinlik, yetişkin.

**3 — Psikologlardan cevap bekleyen**
4. Yazı–psikolog eşleştirmesi → `Article.author` + görünür imza **(Y2)**
5. Psikologların `sameAs` bağlantıları ve lisans bilgisi **(O8)**

**4 — Kullanıcıdan dosya/erişim bekleyen**
6. ~~Marka görselleri (og-image, logo, favicon, apple-touch-icon)~~ — **bitti 2026-09-20 (Y4)**
7. GSC **Kapsam (Coverage)** dışa aktarımı — indeksleme durumu hâlâ ölçülemedi
8. cPanel'den `schema-markup.json` sil **(O11)**

**5 — Claude'un tek başına yapabileceği (onay sonrası)**
9. ~~`dateModified` tazelik sinyalini onar **(Y6)**~~ ✅ yapıldı (`fb21b47`)
10. Şehir sayfası title'larını ayrıştır + iç bağlantı ver **(Y7)**
11. SSS sayfalarına iç bağlantı ekle **(O4)**
12. Yinelenen FAQPage sorularını tek sahibe indir **(O10)**
13. 5 kısa description'ı doldur **(O12)**

**6 — İçerik işi (Faz 5/8 ile birleşiyor, ayrı iş değil)**
14. **Panik atak + psikolog-psikiyatrist sayfalarını güçlendir (G2)** — talep ölçüldü
    (200 gösterim), sayfalar var, sorun 67-82. sıra. `psikolog ilaç yazabilir mi`
    alt-niyeti (~49 gösterim) ayrı H2 hak ediyor
15. Hizmet sayfalarının derinliği ve çıkarılabilirliği **(O1)**
16. Kalan 44 yazıya kaynak atfı — `.agents/kaynak-inceleme-listesi.md` **(Y1)**
17. ~~`geo` + `openingHoursSpecification`~~ — **bitti 2026-09-20 (O9)**

**İzlenecek, şimdilik aksiyon yok**
- Masaüstü–mobil 36 sıralık fark **(G3)** — G1/G2 sonrası kapanmazsa ayrıca bakılacak
- Zengin sonuç görünümü sıfır **(G4)** — beklenen; FAQPage şeması AEO için tutuluyor
