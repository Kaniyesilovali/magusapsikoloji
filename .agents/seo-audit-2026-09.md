# Teknik SEO Denetimi — magusapsikoloji.com

**Tarih:** 2026-09-02 · **Kapsam:** canlı 92 URL (sitemap'in tamamı), Googlebot UA ile tarandı
**Program fazı:** Arama Görünürlüğü Programı — Faz 1
**Yöntem:** özel tarayıcı (`cheerio`) + canlı HTTP kontrolleri + `npm run check`

> **Şema tespiti notu:** `seo-audit` becerisi curl/web_fetch ile JSON-LD tespitinin güvenilmez
> olduğunu belirtir — bu, şemayı istemci tarafı JS ile enjekte eden CMS'ler için geçerlidir.
> Bu sitede JSON-LD Eleventy tarafından **sunucuda** basılıyor (`rawSchemas` frontmatter → head),
> bu yüzden HTML'i ayrıştırarak tespit güvenilirdir ve öyle yapıldı: 92 sayfanın tamamındaki
> `<script type="application/ld+json">` blokları JSON olarak parse edildi.

---

## Yönetici Özeti

**Genel sağlık: teknik olarak çok sağlam, otorite sinyalleri bakımından boş.**

Sitenin teknik temeli bu ölçekte nadir görülecek kadar temiz. 92 URL'nin tamamı 200 dönüyor,
canonical'ların hepsi kendine işaret ediyor, **hreflang kurulumunda tek bir hata yok** (self,
karşılıklılık, x-default, sitemap-HTML tutarlılığı, kod geçerliliği — beşi de tam), yetim sayfa
ve kırık iç bağlantı yok, TTFB 60-130 ms. Geçmiş denetimlerin açık maddeleri (uzun title'lar,
eksik description, x-default tutarsızlığı, www kopyası) kapanmış.

Sorun teknikte değil. **Site hiçbir şeye kaynak göstermiyor, hiçbir metnin yazarı yok ve ana
dönüşüm kanalı çalışmıyor.**

**En öncelikli 5 bulgu:**

1. **WhatsApp bağlantısı 92/92 sayfada kırık** — sitenin tek dönüşüm kanalı ölü
2. **88 içerik sayfasında sıfır dış kaynak atfı** — üstelik kaynaksız istatistik iddiaları var
3. **İsimli yazar yok** — YMYL sağlık içeriğinde en ağır E-E-A-T eksiği
4. **Görünür tarih yok** — tarihler yalnızca JSON-LD'de, sayfada değil
5. **4 marka görseli 404** — 92 sayfanın og:image'i ve 46 yazının Article.image'i kırık

Hızlı kazanımlar: 5 ve 6 numaralı maddeler dosya yüklemesi + tek satır şablon değişikliği.
2, 3, 4 birlikte Faz 8'in (GEO) tüm dayanağını oluşturuyor.

---

## Kritik Bulgular

### K1 · WhatsApp bağlantıları 92 sayfanın tamamında kırık
**Etki:** Kritik (iş) · **Kanıt:** canlı tarama, 92/92 sayfa

İki ayrı kırık kaynağı var:

| Kaynak | Değer | Yayılım |
|---|---|---|
| `_data/contact.json` → footer + yüzen buton | `wa.me/9055555` | **92/92 sayfa** |
| İçerik dosyalarında sabit kodlu derin bağlantılar | `wa.me/905XXXXXXXXX?text=...` | ~110 bağlantı, 55 dosya |

İkinci grup, konuya göre ön yazılmış mesajlar içeriyor ("Merhaba, nöropsikolojik değerlendirme
hakkında bilgi almak istiyorum") — iyi tasarlanmış ama hepsi geçersiz numaraya gidiyor.

**Düzeltme:** Gerçek numara verildiğinde `contact.json` panelden düzeltilir; 55 dosyadaki
`905XXXXXXXXX` tek seferde toplu değiştirilir. **Kullanıcıdan numara bekleniyor.**
**Öncelik:** 1 — trafik artışının hiçbir anlamı yok, çünkü gelen kişi iletişime geçemiyor.

---

## Yüksek Öncelikli Bulgular

### Y1 · Sıfır dış kaynak atfı + kaynaksız istatistik iddiaları
**Etki:** Yüksek (E-E-A-T + GEO) · **Kanıt:** 92 sayfada toplam 4 dış bağlantı; dördü de yasal sayfalarda (kvkk.gov.tr, edpb.europa.eu)

88 içerik sayfasının **hiçbirinde** tek bir kaynak gösterimi yok. Buna rağmen site kanıt
iddiasında bulunuyor:

- `/blog/online-terapi-etkili-mi.html` — başlık: "Araştırmalar Ne Diyor?"; metin: "Meta-analizler, görüntülü terapinin depresyon, kaygı ve travmada yüz yüze seansla…" → **hangi meta-analiz, belirtilmiyor**
- `/blog/sinav-kaygisiyla-bas-etme.html` — "5 Kanıta Dayalı Yol", "Araştırmalarla desteklenen 5 yöntem" → **atıf yok**
- `/blog/depresyon-nedir.html` — "nüfusun %10-15'ini etkiler" → **kaynak yok**

Bu iki yönden sorunlu:

**E-E-A-T / YMYL:** Sağlık içeriğinde kaynaksız istatistik, Google'ın en sıkı değerlendirdiği
kategoride güvenilirlik sinyalini düşürür.

**GEO:** Princeton GEO çalışmasına göre (KDD 2024, Perplexity.ai üzerinde) üretken arama
motorlarında görünürlüğü en çok artıran müdahaleler: **kaynak gösterme +%40, istatistik ekleme
+%37, alıntı +%30, otoriter ton +%25, açıklık +%20.** Düşük otoriteli alan adları atıf
eklemekten **+%115'e kadar** kazanıyor. Bu site tam olarak o profilde ve kaldıracın tamamını
boşta bırakıyor.

**Düzeltme:** Mevcut iddiaların arkasına gerçek kaynak koy (WHO, APA, Cochrane, NICE, TÜİK,
ilgili meta-analizler). Yeni iddia eklemeden önce kaynağı bul. Psikologların onayı gerekir —
klinik iddia uydurulmaz. **Faz 5 (`copy-editing`) ve Faz 8'in (`ai-seo`) ana işi budur.**
**Öncelik:** 2

### Y2 · İsimli yazar yok
**Etki:** Yüksek (E-E-A-T + GEO) · **Kanıt:** 46 Article şemasının 46'sında `author` = Organization

Hiçbir yazının insan yazarı yok. `Hakkımızda` sayfasındaki iki ekip kartı da placeholder:
başlık "Klinik Psikolog", alt başlık "Klinik Psikolog" — isim, unvan, lisans numarası,
eğitim, uzmanlık yok.

YMYL sağlık içeriğinde Google açıkça yazar yetkinliği arıyor; LLM'ler de isimli-yetkinlikli
kaynağı daha çok alıntılıyor. **Bu programdaki en yüksek etkili tek madde.**

**Düzeltme:** Psikologların ad/unvan/lisans/eğitim bilgileri → `Person` şeması, yazar
sayfaları, her yazıda görünür imza, `Article.author` → Person.
**Bu bilgi kullanıcıdan gelmeli — üretilemez.** **Öncelik:** 3

### Y3 · Görünür tarih yok
**Etki:** Yüksek (AEO) · **Kanıt:** `<time>` etiketi 0; "Güncelleme/Son güncelleme" ifadesi 0; tarihler yalnızca JSON-LD içinde

`datePublished` ve `dateModified` şemada var ama sayfanın hiçbir yerinde görünmüyor.
Google'ın "yapılandırılmış veri görünür içeriği yansıtmalı" ilkesiyle çelişiyor (aynı sorun
2026-08-04'te FAQPage için çözülmüştü, tarihler atlanmış). AI motorları tazelik sinyali
olarak görünür tarihe bakıyor.

Ek sorun: 46 yazının çoğunda `datePublished` = `dateModified`. Ağustos'ta güncellenen yazılarda
bile değişmemiş.

**Düzeltme:** `post.njk`'ye görünür "Son güncelleme: GG.AA.YYYY" satırı; `dateModified`
sitemap'teki gibi git son-commit tarihinden üretilsin. **Öncelik:** 4

### Y4 · Dört marka görseli 404 — 92 sayfanın og:image'i kırık
**Etki:** Yüksek · **Kanıt:** canlı HEAD kontrolü

| Dosya | Durum | Sonuç |
|---|---|---|
| `/assets/images/og-image.jpg` | **404** | 92 sayfanın `og:image`'i + 46 yazının `Article.image`'i kırık |
| `/assets/images/logo.png` | **404** | 32 sayfadaki LocalBusiness/MedicalOrganization şema `logo` alanı geçersiz |
| `/assets/images/favicon.png` | **404** | Tarayıcı sekmesinde simge yok |
| `/assets/images/apple-touch-icon.png` | **404** | iOS ana ekran simgesi yok |

Pratik sonucu: WhatsApp ve Facebook'ta paylaşılan her bağlantının önizlemesi boş çıkıyor —
ve WhatsApp bu sitenin birincil kanalı.

**İyi haber:** 22 blog kart görseli artık repoda ve canlıda çalışıyor (`assets/images/blog/`,
800×450 JPEG, ~1.3 MB toplam, Unsplash lisanslı, kaynak kaydı `KAYNAKLAR.md`'de). Eski
denetimdeki "klasör tamamen boş" tespiti artık geçerli değil — yalnız 4 marka dosyası eksik.

**Düzeltme:** Dört dosya kullanıcıdan gelmeli. **Öncelik:** 5

### Y5 · Article.image 46/46 yazıda 404 gösteriyor
**Etki:** Yüksek · **Kanıt:** 46 Article şemasının tamamında `image` = `og-image.jpg` (404)

Bu Y4'ten ayrı ve **kullanıcı beklemeden düzeltilebilir**: her yazının gerçek kart görseli
zaten var ve çalışıyor, ama şema onu değil, olmayan dosyayı gösteriyor.

**Düzeltme:** `Article.image` (ve tercihen `og:image`) sayfanın kendi `cardImage` alanına
bağlansın, yoksa genel og-image'e düşsün. Tek şablon değişikliği.
**Öncelik:** 6 — **en hızlı kazanım, bugün yapılabilir.**

---

## Orta Öncelikli Bulgular

### O1 · İnce içerik: 8 sayfa <300, 33 sayfa <500 kelime
**Etki:** Orta · GSC'deki "Keşfedildi, dizine eklenmedi" satırının asıl sebebi

En ince sayfalar (görünür gövde metni, nav/footer hariç):

| Sayfa | Kelime |
|---|---|
| `/iletisim.html` | 131 |
| `/en/contact.html` | 161 |
| `/hizmetler/` | 257 |
| `/blog/beyin-beden.html` | 259 |
| `/hizmetler/online-terapi.html` | 267 |
| `/hizmetler/bdt-terapisi.html` | 268 |
| `/hizmetler/bireysel-terapi.html` | 270 |
| `/hizmetler/cocuk-psikolojisi.html` | 291 |

Hizmet sayfaları 267–350 aralığında kümelenmiş; blog yazıları 700–1.640. Yani **ticari niyeti
en yüksek sayfalar sitenin en zayıf sayfaları.** İletişim sayfasının ince olması normaldir.

**Düzeltme:** Faz 5'te hizmet sayfaları öncelikli genişletme. Klinik içerik psikolog onayı ister.
**Öncelik:** 7

### O2 · 6 sayfada hiç JSON-LD yok
**Etki:** Orta · `/gizlilik.html`, `/kvkk.html`, `/en/privacy.html`, `/en/kvkk.html`,
`/blog/beyin-beden.html`, `/en/blog/brain-body-connection.html`

Yasal sayfalarda en azından `BreadcrumbList` olmalı. `beyin-beden` bir blog yazısı olarak
listeleniyor ama `Article` şeması almamış.
**Öncelik:** 8 — Faz 6'da (`schema`) kapatılır.

### O3 · Dil rehberi ihlali: "ruh" kelimesi 5 sayfada 16 kez
**Etki:** Orta (marka) · **Kanıt:** görünür metin taraması, TR sayfalar

Psikologların dil rehberi "ruh" kelimesini tamamen yasaklıyor (ruh sağlığı → psikolojik sağlık).

| Sayfa | Geçiş |
|---|---|
| `/blog/magusa-uluslararasi-ogrenciler-ruh-sagligi.html` | 6 — **title, H1 ve URL'de** |
| `/blog/` (dizin, kart başlığından) | 4 |
| `/blog/universite-ogrencileri-psikolojik-destek.html` | 3 |
| `/blog/sinav-kaygisiyla-bas-etme.html` | 2 |
| `/blog/depresyon-nedir.html` | 1 ("Ruh hali") |

URL'deki `ruh-sagligi` düzeltilirse 301 gerekir — Faz 5'te O6 ile birlikte değerlendirilmeli.
Rehberin geri kalanı temiz: "danışan" hiç geçmiyor, pazarlama/aciliyet kalıplarının
hiçbiri bulunmadı.
**Öncelik:** 9

### O4 · Yeni içerik zayıf bağlanmış
**Etki:** Orta · **Kanıt:** iç bağlantı grafiği

En az gelen bağlantı alan sayfalar (footer'da olmayanlar):

| Sayfa | Gelen bağlantı |
|---|---|
| `/hizmetler/motivasyonel-gorusme.html` | 2 |
| `/blog/noropsikolojik-degerlendirme-magusa.html` | 2 |
| `/blog/terapi-dili-turkce-ingilizce.html` | 2 |
| (+ EN eşleri) | 2 |

Karşılaştırma: footer'daki sayfalar 46, anasayfa 91 bağlantı alıyor. En yeni ve rekabeti en
düşük içerik en az desteklenen içerik durumunda.
**Öncelik:** 10

### O5 · Anahtar kelime yamyamlığı riski
**Etki:** Orta

| Terim | Yarışan sayfalar |
|---|---|
| online terapi | `/hizmetler/online-terapi.html` + `/blog/kuzey-kibris-online-terapi.html` + `/blog/online-terapi-etkili-mi.html` |
| aile terapisi | `/hizmetler/aile-terapisi.html` + `/blog/aile-terapisi-magussa.html` |

`çocuk psikoloğu` çakışması 2026-08-28'de çözülmüş (blog `/blog/cocuk-psikologu-magusa.html`'e
taşındı + 301). Aynı yöntem bu ikisine de uygulanabilir. Faz 4'te (`content-strategy`)
ele alınacak.
**Öncelik:** 11

### O6 · İki URL'de yazım hatası — ÇÖZÜLDÜ (2026-09-06)
**Etki:** Orta (düşük düzeltilebilirlik)

- ~~`/blog/aile-terapisi-magussa.html`~~ → `/blog/aile-terapisi-magusa.html`
- ~~`/blog/cift-terapisi-gazimagussa.html`~~ → `/blog/cift-terapisi-gazimagusa.html`

Görsel dosya adları da düzeltildi. Eski adresler `static/.htaccess` içinde 301'lendi.
Beklemeye alınmıştı (O3'teki `ruh-sagligi` ile toplu karar önerisi); site sahibi hatanın
hedeflenen kelimenin kendisinde olduğunu belirtince ayrı düzeltildi. `ruh-sagligi` URL'i
hâlâ açık — O3 kapsamında ele alınacak.

### O7 · Çift terapisi hizmet sayfası yok
**Etki:** Orta · Hizmet veriliyor, blog yazısı var, hizmet sayfası yok. Faz 5'te kapatılacak
yapısal boşluk. **Öncelik:** 13

---

## Düşük Öncelikli Bulgular

| # | Bulgu | Not |
|---|---|---|
| D1 | HSTS başlığı yok | Cloudflare'den tek tıkla açılır; güven sinyali |
| D2 | Font preload yok | `inter-latin.woff2` (47 KB) CSS ayrıştıktan sonra isteniyor; LCP'ye küçük katkı |
| D3 | 5 description <110 karakter | SERP alanı boşta |
| D4 | `/assets/images/blog/KAYNAKLAR.md` canlıda erişilebilir | Zararsız (görsel kaynak kaydı), istenirse passthrough'dan çıkarılır |
| D5 | `dateModified` = `datePublished` çoğu yazıda | Y3 ile birlikte çözülür |

---

## Sağlam Olan — Dokunulmayacak

Bu maddeler doğrulandı ve **regresyon riski taşıyor**; sonraki fazlarda korunmalı.

**Taranabilirlik ve indeksleme**
- 92/92 URL 200 (Googlebot UA)
- 92/92 self-referencing canonical; çapraz canonical yok
- `robots.txt` temiz; AI botlarının tamamı açık (GPTBot, ClaudeBot, PerplexityBot, Google-Extended, CCBot, OAI-SearchBot → 200)
- 404 doğru davranıyor; yetim sayfa yok; kırık iç bağlantı yok
- `llms.txt` canlı · `npm run check` → 0 hata 0 uyarı

**Çok dilli (hreflang) — sıfır hata**
- Self-referencing: 92/92 · Karşılıklılık: 92/92 · `x-default`: 92/92
- Sitemap `<xhtml:link>` ile HTML `<link>` tam uyumlu (92/92)
- Geçersiz dil kodu yok · hreflang hedeflerinin tamamı 200 ve canonical
- `<html lang>` dengeli: 46 tr / 46 en · TR-EN içerik paritesi iyi (46 çiftin 42'si %25 içinde)

**Yönlendirmeler**
`http→https` 301 · `www→apex` 301 · `/index.html→/` 301 · `/blog→/blog/` 301

**On-page**
- Title 33–60 karakter, **hiçbiri >60**, çift yok
- Description 92/92 dolu, **hiçbiri >160**, çift yok
- Sayfa başına tek H1 (92/92) · başlık hiyerarşisinde atlama yok (h2→h4 vb. sıfır)

**Hız ve Core Web Vitals bileşenleri**
- TTFB 60–130 ms (Cloudflare edge, `cf-cache-status: HIT` statik varlıklarda)
- HTML brotli sıkıştırmalı, 34–65 KB · CSS 8 KB gzip · JS 1 KB + GA4 async
- Yerel subset font, `font-display: swap` (6/6 `@font-face`)
- Blog kart görselleri `width`/`height`/`loading="lazy"`/`decoding="async"` ile — **CLS güvenli**
- 44 görselin 44'ünde betimleyici alt metni

> **Ölçülemedi:** PageSpeed Insights API günlük kotası dolu olduğu için laboratuvar CWV
> skorları (LCP/INP/CLS) alınamadı. Yukarıdaki bileşenler tek tek ölçüldü ve hepsi iyi
> aralıkta; yine de kesin skor için PSI'nin elle çalıştırılması önerilir.
> CrUX saha verisi muhtemelen yetersiz trafik nedeniyle mevcut değil.

---

## Öncelikli Eylem Planı

### Aşama 1 — Kullanıcı girdisi beklemeden yapılabilir (bu hafta)
1. **Y5** `Article.image` → sayfanın gerçek kart görseline bağla (tek şablon değişikliği)
2. **Y3** Görünür "Son güncelleme" tarihi + `dateModified`'ı git tarihinden üret
3. **O2** 6 sayfaya eksik şemaları ekle
4. **D1** HSTS · **D2** font preload · **D3** 5 description'ı genişlet

### Aşama 2 — Kullanıcıdan bilgi/dosya bekliyor (engelleyici)
5. **K1** Gerçek WhatsApp numarası → 92 sayfa + 55 dosya toplu düzeltme
6. **Y4** 4 marka görseli (og-image, logo, favicon, apple-touch-icon)
7. **Y2** İsimli psikolog bilgileri → Person şeması + yazar imzaları

### Aşama 3 — Faz 5'e devredilir (`copy-editing`)
8. **Y1** Mevcut iddialara kaynak ekleme — *en yüksek GEO getirisi*
9. **O1** Hizmet sayfalarını genişletme
10. **O3** "ruh" kelimesinin temizlenmesi
11. **O4** Yeni içeriğe iç bağlantı desteği

### Aşama 4 — Diğer fazlara devredilir
12. **O5** Yamyamlık → Faz 4 (`content-strategy`)
13. **O6** URL yazım hataları → Faz 5, tek 301 kararı olarak
14. **O7** Çift terapisi hizmet sayfası → Faz 5

---

## Faz 2'ye Taşınanlar

- **Y1, Y2, Y3 birlikte Faz 8'in (GEO) dayanağıdır.** Bunlar kapanmadan AI alıntılanma
  oranında anlamlı bir artış beklenmemeli.
- AI bot erişimi açık doğrulandı (2026-09-02) — Faz 8 Adım 1'de tekrar kontrol edilecek.
- Teknik temel sağlam olduğu için Faz 7 (`site-architecture`) muhtemelen **atlanabilir**;
  tek yapısal boşluk O7 (çift terapisi sayfası). Faz 4 sonunda karar verilecek.
