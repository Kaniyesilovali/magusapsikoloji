# Teknik SEO Denetimi — magusapsikoloji.com (2026-10)

**Tarama:** 2026-10-24 · **Kapsam:** canlı 116 URL (sitemap'in tamamı), Googlebot UA + 11 bot UA
**Önceki tarama:** 2026-09-16 (106 URL) → `.agents/seo-audit-2026-09.md`
**Yöntem:** `cheerio` tarayıcı (scratchpad) + `npm run check` (121 sayfa, 0 hata) + `npm run check:live` + çok-UA `curl`
**GSC:** bu turda yeni veri alınmadı. Kapsam (Coverage) raporu hâlâ hiç alınmadı.

---

## Yönetici özeti

Site 106'dan 116 URL'e çıktı. Yapısal temel yine **sıfır hatalı**. Asıl sorun hâlâ
aynı: **bazı arama motorları siteye giremiyor.** Eylül'de bulunan iki kritik engel beş
haftadır yerinde duruyor.

**Öncelikli 5 madde:**

1. **K3 · Bingbot ve YandexBot en önemli 5 sayfada 406 alıyor.** Açık, değişmedi.
2. **K2 · GPTBot, ClaudeBot ve CCBot her sayfada 403 alıyor.** Açık, değişmedi.
3. **Y2 · 62 yazının 62'sinde yazar hâlâ `Organization`.** Açık, değişmedi.
4. **Y7 · `/gazimagusa-psikolog.html` ana sayfayla birebir aynı title'ı taşıyor**, ve iki
   şehir sayfasının her birine yalnız 1 iç bağlantı geliyor. Açık, değişmedi.
5. **YENİ · Son yazılarda meta uzunlukları sınırı aşıyor.** 5 description 166–188, 7 title
   61–68 karakter. Nedeni: `check-site.js` uzunluğu denetlemiyor, Sveltia'daki sınır da
   yalnız CMS'ten girilen içerikte işliyor. Dosyaya doğrudan yazılan yazılar kontrolsüz geçiyor.

**Hızlı kazanımlar:** 1 ve 2 kod değil, panel ayarı. 5 numara (meta kısaltma + CI uyarısı)
yarım saatlik iş.

---

## Kritik

### K3 · Bingbot / YandexBot 406 — **AÇIK (2026-09-16'dan beri)**
| UA | `/` | `/en/` | `/blog/` | `/hizmetler/` | `/en/services/` | `*.html` |
|---|---|---|---|---|---|---|
| bingbot | **406** | **406** | **406** | **406** | **406** | 200 |
| YandexBot | **406** | **406** | **406** | **406** | **406** | 200 |
| Googlebot, DuckDuckBot, Applebot | 200 | 200 | 200 | 200 | 200 | 200 |

`cf-cache-status: DYNAMIC`, yani yanıtı origin üretiyor. Yalnız dizin biçimli URL'lerde
tetikleniyor, bu da cPanel ModSecurity davranışına uyuyor. Bing'in indeksi Copilot'u ve
ChatGPT aramasının bir kısmını besliyor, bu yüzden Faz 8'i de doğrudan etkiliyor.
**Düzeltme:** hosting'den ModSecurity audit log istenip tetikleyen kural kimliği alan adı
için istisnaya alınır. Ardından Bing Webmaster Tools → URL Denetimi ile doğrulanır.
**Öncelik:** 1

### K2 · Cloudflare GPTBot / ClaudeBot / CCBot'u engelliyor — **AÇIK**
Üçü de 6/6 test adresinde 403 alıyor. OAI-SearchBot, PerplexityBot, Google-Extended,
Applebot ise 200. Cloudflare kenarında kesiliyor, origin'e hiç ulaşmıyor.
**Düzeltme:** Cloudflare → AI Crawl Control → üç bota izin ver. Engel kasıtlıysa bu bir
politika kararıdır ve Faz 8'in kapsamı buna göre daraltılmalıdır. **Öncelik:** 1

---

## Yüksek

### Y2 · Yazar kurum — **AÇIK**
62/62 yazıda `Article.author` = `Organization`. `Person` düğümleri (`#gokce-ince`,
`#yaprak-parlan-yesilovali`) `hakkimizda`'da hazır duruyor, ama hiçbir yazı onlara bağlanmıyor.
Hangi yazının kime ait olduğu bilgisi merkezden gelmeli. Bilgi gelince iş mekanik:
frontmatter'a `author` eklenir, şemada `{"@id": …}` kullanılır, künyede görünür imza basılır.
**Öncelik:** 2

### Y7 · Şehir sayfası yamyamlığı — **AÇIK**
| URL | title | gelen iç bağlantı |
|---|---|---|
| `/` | Gazimağusa Psikolog \| Mağusa Psikoloji Merkezi | çok |
| `/gazimagusa-psikolog.html` | **aynı** | **1** |
| `/en/psychologist-in-famagusta.html` | Psychologist in Famagusta \| Famagusta Psychology Centre | **1** |

Sitede yinelenen tek title bu. **Düzeltme:** şehir sayfasına ayrı bir niyet verilmeli
("Gazimağusa'da psikoloğa nasıl ulaşılır, ne beklenir"), title ayrıştırılmalı ve hizmet/blog
sayfalarından iç bağlantı verilmeli. Alternatif yol: sayfa ana sayfaya 301'lenir.
**Öncelik:** 2

### Y8 · Meta uzunlukları CI'da denetlenmiyor — **YENİ**
| Description > 160 | kr |
|---|---|
| `/en/blog/what-is-psychodynamic-psychotherapy.html` | 188 |
| `/en/blog/social-anxiety-vs-shyness.html` | 185 |
| `/en/blog/family-transitions-parent-counselling.html` | 182 |
| `/blog/ailede-zor-donemler-ebeveyn-danismanligi.html` | 176 |
| `/en/blog/post-traumatic-stress-disorder.html` | 166 |

| Title > 60 | kr |
|---|---|
| `/blog/kuzey-kibris-psikolog-bulma.html` | 68 |
| `/en/blog/family-transitions-parent-counselling.html` | 66 |
| `/blog/ailede-zor-donemler-ebeveyn-danismanligi.html` | 65 |
| `/en/services/parent-counselling.html` | 64 |
| `/hizmetler/noropsikolojik-degerlendirme.html` | 63 |
| `/hizmetler/ebeveyn-danismanligi.html` | 62 |
| `/kaynaklar.html` | 61 |

Eylül'de 106/106 sayfa sınır içindeydi. Aşanların hepsi Ekim'de eklenen ya da düzenlenen
sayfalar. **Düzeltme:** (a) 12 meta kısaltılır, (b) `scripts/check-site.js`'e title >60 ve
description 110–160 için **uyarı** eklenir (deploy'u durdurmaz). **Öncelik:** 2

---

## Orta / düşük

- **O4 · Gövde içinde iç bağlantı yok:** `/sss.html`, `/en/faq.html`, `/iletisim.html`,
  `/en/contact.html` sayfalarında 0. 22 sayfada daha 1–2. En verimli ekleme SSS → hizmet
  sayfaları olur. **Öncelik:** 3
- **O1 · İnce sayfalar (<300 kelime):** 6 sayfa, Eylül'le aynı grup (iletişim ×2,
  `terapi-sureci` 220, `en/therapy-process` 289, `felsefemiz` 250, `en/blog/brain-body-connection`
  199). Medyan 775 kelime (Eylül'de 718). **Öncelik:** 3
- **Yeni yazıların iç bağlantısı az:** psikodinamik (TR+EN), otizm (TR+EN), terapi dili
  (TR+EN), grup çalışmaları (TR+EN) sayfalarına 2'şer iç bağlantı geliyor. Psikodinamik
  bugün yayımlandı, bu yüzden beklenen bir durum. `/hizmetler/psikodinamik-terapi.html`
  sayfasından yazıya bağlantı verilebilir. **Öncelik:** 3
- **O10 · Tekrarlanan SSS soruları:** 492 FAQ girdisi, 446 benzersiz, 38 soru birden çok
  sayfada geçiyor (en çok: "Terapi kaç seans sürer?" ×4). Cevaplar karar 4'e uygun, sayı
  vermiyor. **Öncelik:** 4
- **O12 · Kısa description (<110):** 3 sayfa. `/en/services/online-therapy.html` 104,
  `/hizmetler/ogrenci-danismanlik.html` 104, `/hizmetler/yetiskin-psikolojisi.html` 109.
  **Öncelik:** 4
- **O11 · `/schema-markup.json` hâlâ canlıda:** placeholder veri taşıyor ("Adres Buraya",
  +90-392-000-0000). Hiçbir sayfa referans vermiyor. cPanel'den elle silinmeli. **Öncelik:** 4
- **Site geneli ajans bağlantısı:** `raptordigital.net` 116 sayfanın altbilgisinde
  `rel="noopener"` ile, nofollow olmadan duruyor. Google site geneli "tasarım:" bağlantılarında
  `nofollow` öneriyor. Risk düşük, ama değişiklik tek satır. **Öncelik:** 4
- **HSTS yok, HTML'de `cache-control` yok:** değişmedi. **Öncelik:** 5

---

## Sağlam temel (116 URL, 0 hata)

| Kontrol | Sonuç |
|---|---|
| HTTP | 116/116 → 200 · bilinmeyen adres 404 |
| Yönlendirmeler | http→https, www→apex, `/blog/index.html`→`/blog/` hepsi 301 |
| Canonical | 116/116 self |
| noindex | 0 |
| H1 / başlık atlaması | 116/116 tek H1 · 0 atlama |
| Şema | 116/116 sayfada var · 0 parse hatası |
| Hreflang | x-default 58 kümenin hepsinde, `check-site` 0 hata |
| Yinelenen description | 0 |
| Görseller | 308 `<img>`, alt eksik 0 · 77 varlık 200 |
| WhatsApp | 242 bağlantı gerçek numarada |
| Kırık iç bağlantı | 0 |
| `robots.txt` / `sitemap.xml` / `llms.txt` | doğru, sitemap 116 URL |
| Dış atıflar | yalnız liste içi kurumlar (APA 44, WHO 18, Cochrane 9, AACAP 8, ISTSS 8 …). Liste dışı tek alan adı yok; KVKK/EDPB yasal sayfalarda |

`dateModified` dağılımı: 39 yazı 2026-10-17'de. Bu, kaynak dengesi turunda atıfların gerçekten
değiştiği güne denk geliyor. Sahte bir tazelik sinyali değil.

---

## Kapanan / değişen (2026-09-16 → 2026-10-24)

| Kod | Durum |
|---|---|
| O12 | 5 → 3 sayfa (aile terapisi sayfaları kaldırıldı) |
| Dış atıf | 12/56 yazı atıflıydı → **43/62**. NHS/DOI/PubMed kaldırıldı. Atıfsız 19 yazının çoğu yerel/pratik yazı ("psikolog bulma", "terapi dili", öğrenci desteği), klinik iddia taşımıyor. Kaynak eklenebilecek ilk aday: sınav kaygısı (TR+EN) |
| Y4, Y6 | Eylül'de kapanmıştı, kapalı kaldı |

## Ölçülemeyenler

- GSC Kapsam raporu (kaç URL indekste) · Bing Webmaster Tools (K3'ün fiili zararı) ·
  laboratuvar/alan CWV (PSI kotası). Eylül'de ölçülen TTFB/CSS/JS bileşenleri iyiydi,
  bu turda tekrar ölçülmedi.
