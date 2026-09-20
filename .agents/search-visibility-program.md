# Arama Görünürlüğü Programı — Mağusa Psikoloji Merkezi

**İz:** retrofit (yayında, sıralaması olan site)
**Başlangıç:** 2026-09-02
**Son güncelleme:** 2026-09-20
**Mevcut faz:** 8 — AEO + GEO çekirdeği (kısmi; Y2 engelli)
**Öncelik:** dengeli

## Kurulum

| Alan | Değer |
|---|---|
| Site | https://magusapsikoloji.com |
| Site tipi | yerel hizmet + içerik (psikoloji merkezi, Gazimağusa/KKTC) |
| Çok dilli | evet (tr, en — `translationKey` + hreflang) |
| Yığın | Eleventy + Sveltia CMS, GitHub Actions → FTPS (cPanel), Cloudflare |
| Kapasite | ~4-8 sa/hafta inceleme; metinleri Claude hazırlar, psikologlar onaylar; kod+deploy Claude |
| AI bot erişimi | **KISMEN KAPALI** — 2026-09-16 yeniden denetimde GPTBot, ClaudeBot, CCBot **403** (Cloudflare engeli, gerileme). OAI-SearchBot, PerplexityBot, Google-Extended, Googlebot 200. Ayrıca bingbot 6 dizin URL'inde 406 (origin/ModSecurity) |

## Faz Durumu

| # | Faz | Beceri | Durum | Çıktılar |
|---|---|---|---|---|
| 0 | Bağlam temeli | product-marketing | ✅ | `.agents/product-marketing.md` (v1) |
| 1 | Teknik teşhis | seo-audit | ✅ (2026-09-16 yeniden tarandı) | `.agents/seo-audit-2026-09.md` (106 URL) · [eski rapor sayfası](https://claude.ai/code/artifact/5db9f4b7-52d6-4b21-bade-a0bc3197c5bf) |
| 2 | AI görünürlük temel ölçümü | ai-seo (yalnız Adım 1) | ✅ | `.agents/ai-gorunurluk-temel-2026-09.md` |
| 3 | Rakip profilleri | competitor-profiling | ✅ | `competitor-profiles/` (5 profil + `_summary.md` + `dau-pdram.md`) |
| 4 | İçerik boşluk analizi | content-strategy | ✅ | `.agents/icerik-stratejisi-2026-09.md` |
| 5 | Mevcut metni AEO formatına getir | copy-editing | ◐ | Y1 kısmi, Y2 engelli | |
| 6 | Yapılandırılmış veri | schema | ✅ | `851fce5` — şemasız sayfa 6→0, sameAs 0→38, availableLanguage 2→38 |
| 7 | Site mimarisi | site-architecture | **n/a** | Atlandı — Faz 1'deki tek yapısal boşluk O7 (çift terapisi) Faz 5'te kapandı |
| 8 | AEO + GEO çekirdeği | ai-seo (tam) | ☐ | |
| 9 | Site dışı otorite | directory-submissions, public-relations, community-marketing, social | ☐ | |
| 10 | Ölçümleme | analytics | ☐ | |
| 11 | Dönüşüm | cro, popups, emails | ☐ | |
| 12 | Döngüler | marketing-loops | ☐ | |

Durum: ☐ bekliyor · ▶ sürüyor · ✅ bitti · ⏸ engellendi · n/a kapsam dışı

## Mevcut Faz

**Faz:** 8 — AEO + GEO çekirdeği (`ai-seo` tam). **Kısmi çalışılabilir.**
**Engelleyen:** Y2 (isimli yazar). Faz 3'te ölçüldü: bu grupta isimsiz tek site biziz,
alıntılanmayı en çok belirleyen madde bu. Y2 kapanmadan Faz 8'de anlamlı hareket beklenmemeli.

**Faz 5 kalanları**
- Y1: 3 hatalı atıf düzeltildi + 5 kaynak bağlandı (`f56483f`). Kalan: B11 + 4 karar sorusu
  + 6 araştırılmamış iddia. Liste: `.agents/kaynak-inceleme-listesi.md`
- Kurumsal danışmanlık + eğitim/atölye — kapsam teyidi bekliyor
- ~~K1: 98 sayfada kırık `wa.me`~~ — **çözüldü (2026-09-16):** gerçek numara `+905391232547` girildi

**Faz 6 çıktısı (2026-09-08, yayında ve doğrulandı)**
- Şemasız sayfa 6 → 0 (yasal sayfalar breadcrumb, beyin-beden Article)
- `sameAs` 0 → 38 (Instagram + Facebook; varlık birleştirme)
- `availableLanguage` 2 → 38 (iki dillilik artık makine-okunur)
- SSS'lerde markdown render ediliyor; görünür bölüm ile FAQPage şeması aynı filtreden geçiyor
- ~~Hâlâ eksik: `telephone`, `geo`, `openingHours`, posta kodu.~~ **2026-09-20'de kapandı**
  (GBP açıldı) — posta kodu dışında hepsi yayında. `priceRange` bilerek yok.

## Taşınanlar

Fazlar arası girdiler.

**Dağıtım hedefleri** (Faz 4 → Faz 9)
- (henüz yok)

**AI ölçüm sorgu seti** (Faz 0 → Faz 2 → Faz 8) — ✅ **KİLİTLİ**
- 20 sorgu seçildi (12 TR + 8 EN), `.agents/ai-gorunurluk-temel-2026-09.md` bölüm 3.
- Set değiştirilmez; iki haftada bir aynı sorgular ölçülür.
- 7 sorgu GBP kararına bağlı (⚠ işaretli). **GBP 2026-09-20'de açıldı** — bir sonraki
  ölçümde bu 7'deki hareket GBP etkisi olarak okunacak.

**Ebeveyn segmenti kelime haritası** (2026-09-11) — `.agents/ebeveyn-kelime-haritasi-2026-09.md`
- Ebeveyn aramasının 5 aşaması çıkarıldı. Bulgu: **Aşama 2-4'te güçlüyüz, Aşama 1'de (davranışı
  tarif eden sorgular) neredeyse boştuk.** En büyük 3 boşluk kapatıldı: öfke nöbetleri,
  çocuklarda kaygı/ayrılık kaygısı, öğrenme güçlüğü (TR+EN, 6 sayfa).
- Kalan ilk 10 iş ve **teyit bekleyen bloklu kalemler** (test bataryası adları, KKTC özel eğitim
  raporu süreci, ücret) aynı dosyada.

**Faz 5'te bulunan iki yeni yerel rakip** (Faz 12 döngüsünde profillenecek)
- Psikolog Onur Aydın — psikologonuraydin.com, Gazimağusa hizmet sayfası var
- Sesin Psikolojik Danışmanlık ve Terapi Merkezi — 1 Mustafa Kemal Bulvarı, Gazimağusa
- **Kıbrıs Çocuk – Ergen – Aile Psikoterapi Merkezi** — kibrisruhsagligi.com, Gönyeli/Yenikent.
  Çocuk/ergen segmentini alan adıyla sahipleniyor; **iki isimli klinik psikolog yayında** (Y2
  dezavantajı doğrulandı); zekâ ve dikkat testlerini açıkça ilan ediyor. İngilizcesi yok.
  (2026-09-11'de bulundu)

**Rakip profilleri** (Faz 3 → Faz 4/5/9) — ✅ **TAMAMLANDI**, `competitor-profiles/_summary.md`
- **Y1 (kaynak atfı): 5 rakibin de sıfırı var → açık değil, FARK YARATMA fırsatı**
- **Y2 (isimli yazar): 4 rakipte de var → gerçek dezavantaj, en yüksek öncelik**
- Y3 (görünür tarih): yalnız Hoşkan'da var → küçük dezavantaj, ucuz düzeltme
- Bizde olup hiçbirinde olmayan: iki dillilik, hatasız hreflang, FAQPage şeması (73 sayfa), 92 sayfa kapsam
- **Şehir sayfaları GBP'siz çalışıyor** — Noesis 7 bölge (Gazimağusa dahil), Mayıs 15 şehir×hizmet.
  2026-09-02 kapsam kararı kısmen düzeltildi: harita kutusu GBP ister, **altındaki organik
  sonuçlar şehir sayfasıyla alınabiliyor.** ~~Bizde tek şehir sayfası yok.~~ **Güncel
  (2026-09-16):** iki şehir sayfası açıldı (`/gazimagusa-psikolog.html`,
  `/en/psychologist-in-famagusta.html`) ama ana sayfayla aynı kelimeyi hedefliyorlar ve
  ikisinin de yalnız 1 iç bağlantısı var — bkz. **Y7**.
- Pembe Köşk **yayında değil** (sertifika süresi dolmuş + hosting askıya alınmış) — 3 ay sonra bak
- EMDR artık rekabet riski: vermiyoruz, yazılar yayında, iki rakip veriyor

**Eski rakip adayları** (Faz 2'de çıkarıldı)
- Noesis Psikoloji (noesispsikoloji.com) — Lefkoşa, KKTC geneli
- Uzman Psikolog Volkan Hoşkan — Gazimağusa klinik, Google Sites + bio.link
- Mayıs Psikoloji Kıbrıs (mayispsikolojikibris.com) — Lefkoşa + 4 şehir
- Cyprus Central Hospital / Mağusa Tıp Merkezi — psikolojik danışmanlık polikliniği
- Pembe Köşk Online (pembekoskonline.com) — online psikoloji + psikiyatri
- **DAÜ PDRAM (emu.edu.tr)** — ikincil rakip: 6 psikolog + 1 psikiyatrist, **ücretsiz**,
  yüksek otoriteli .edu.tr; öğrenci segmentinde AI alıntılarını alması beklenir

**⚠ ÖLÇÜM DÜZELTMESİ** (Faz 5'te bulundu, 2026-09-06)
- `ai-seo`'nun **40-60 kelimelik pasaj bandı İngilizce için kalibre.** Türkçe aynı bilgiyi
  %25-30 daha az kelimeyle taşıyor (karakter sayıları eşit). **Türkçe hedef: 30-45 kelime.**
- Faz 2'deki "bağımsız pasaj %60" ölçümü bu yüzden TR sayfaları olduğundan düşük gösteriyor.
  Faz 8'de dile göre band kullanılacak. Türkçe metin İngilizce metriğe uydurmak için şişirilmeyecek.

**Çıkarılabilirlik taban çizgisi** (Faz 2 → Faz 8)
- 10 kontrol, 92 sayfa. Sıfırdakiler: istatistik+kaynak 0%, isimli yazar 0%, görünür tarih 4%.
- Blog AEO açısından hazır (sorgu-H2 %76, ideal pasaj %80); **hizmet sayfaları değil**
  (sorgu-H2 %15, ideal pasaj %38). Faz 5'in önceliği hizmet sayfaları.

**GSC Performans temel ölçümü (2026-07-06 → 2026-09-14, 2026-09-16'da alındı)** — ✅ **KAYIT ALTINDA**

Bu, programın "önce" fotoğrafının arama ayağı. Sonraki ölçümler buna karşı okunacak.

| Ölçüm | Değer |
|---|---|
| Tıklama (3 ay) | 46 |
| Gösterim (3 ay) | 1.449 |
| Ort. TO | %3,2 |
| Ort. sıra | ~28 |
| Temmuz / Ağustos / Eylül(1-14) | 11 / 21 / 14 tıklama |

- **Tıklamanın %76'sı tek sayfadan:** `/` → 35 tıklama, 508 gösterim, 8,66. sıra.
  Diğer ~68 sayfa toplam 3 tıklama.
- **Ada içi hedefleme çalışıyor:** Kıbrıs 33 tıklama / 8,77. sıra · Türkiye 6 / 50,47 ·
  İngiltere 0 / 43,03. (GSC'de ayrı KKTC kodu yok, ada içi "Kıbrıs" altında raporlanıyor.)
- **Mobil 10,8. sıra vs masaüstü 46,8. sıra** — büyük olasılıkla sorgu karması (yerel=mobil,
  bilgi amaçlı uzun kuyruk=masaüstü), teknik bulgu yok. G1/G2 sonrası kapanmazsa bakılacak.

**Ölçülen talep kümeleri** (büyükten küçüğe):

| Küme | Gös. | Tık. | Ort. sıra | Durum |
|---|---|---|---|---|
| Travma / Kıbrıs (EN) | **165** | 0 | 40,9 | ❌ **hizmet sayfası yok — G1** |
| Panik atak | 104 | 0 | 71,6 | ✅ sayfa var, dipte — G2 |
| Psikolog mu psikiyatrist mi | 96 | 0 | 74,8 | ✅ sayfa var, dipte — G2 |
| Yerel (mağusa/kktc psikolog) | 82 | **5** | **13,5** | ✅ tıklamayı getiren tek küme |
| Çocuk | 71 | 0 | 36,5 | ✅ hizmet + blog |
| EMDR (verilmiyor) | 10 | 0 | 70,6 | ✅ kapsam dışı, doğru davranış |

**G1 — ✅ CEVAPLANDI (2026-09-16): travma odaklı çalışma VERİLİYOR.** Yaş grupları:
**çocuk, ergen, beliren yetişkinlik, yetişkin** (kullanıcı teyidi). Böylece ölçülen en büyük
talep (165 gösterim, `childhood trauma treatment cyprus` ve türevleri) artık gerçek bir
hizmete karşılık geliyor ve kural 1 engeli kalktı. `product-marketing.md` hizmet listesi
güncellendi.

**✅ UYGULANDI (2026-09-16):** TR + EN travma hizmet sayfası yazıldı ve derlemede doğrulandı.
`/hizmetler/travma-terapisi.html` (863 kelime) + `/en/services/trauma-therapy.html` (1.218
kelime). Yaklaşımlar teyit edildi: travma odaklı BDT, psikodinamik, ACT, çocuklarda oyun ve
sanat temelli çalışma. Kapsanan tablolar: çocukluk çağı, gelişimsel, TSSB, tek olaya bağlı
travma, yas ve kayıp. **EMDR'nin verilmediği sayfada ayrı bir H2 ile açıkça yazıldı** —
travma denince en çok sorulan yöntem olduğu ve ölçülen EMDR talebi bulunduğu için.

Sayfalar hizmet indekslerine otomatik girdi (`cardOrder: 6.5` — 16 dosyayı yeniden
numaralamamak için ondalık kullanıldı, Y6'yı kötüleştirmemek adına). Her birine 3 konusal
iç bağlantı verildi (indeks + bireysel terapi + çocuk psikolojisi) — şehir sayfalarının
düştüğü tek-bağlantı durumuna (Y7) düşmemesi için. Sitemap 106 → 108 URL, `npm run check`
0 hata. **Psikolog onayı bekliyor** (klinik metin).

**G2 — en ucuz kazanım:** Panik atak ve psikolog-psikiyatrist sayfaları **zaten var**,
talep de var (200 gösterim), ama 67-82. sırada. Sıfırdan içerik değil, mevcut sayfayı
güçlendirme işi — Faz 5/8'in çıkarılabilirlik çalışmasıyla birebir örtüşüyor.
`psikolog ilaç yazabilir mi` alt-niyeti tek başına ~49 gösterim taşıyor, ayrı H2 hak ediyor.

**Doğrulandı:** EMDR sayfaları 404, yazım hatası URL'leri 301, http/www 301 — hepsi doğru.
Eski adreslerin GSC'de görünmesi normal.

**Faz 1 YENİDEN denetim (2026-09-16, 106 URL canlı tarandı)** — tam rapor `.agents/seo-audit-2026-09.md`

- **Kapandı:** K1 (wa.me), Y3 (görünür tarih, 102/106), O2 (şemasız sayfa 0), O3, O5 (niyet
  ayrışmış), O6, O7. Title/description 106/106 sınır içinde. Hreflang yine **0 hata**.
- **İki YENİ kritik — ikisi de panel ayarı, kod değil:**
  - **K2 · Cloudflare GPTBot + ClaudeBot + CCBot'u 403'lüyor.** 2026-09-02'de üçü de 200'dü →
    gerileme. Faz 8'in (şu anki faz) dayanağını kesiyor. OAI-SearchBot ve PerplexityBot açık.
  - **K3 · bingbot/BingPreview/YandexBot 6 dizin URL'inde 406** (`/`, `/en/`, `/blog/`,
    `/en/blog/`, `/hizmetler/`, `/en/services/`) — sitenin en önemli 6 sayfası. Origin
    kaynaklı (cPanel ModSecurity), Cloudflare değil. `.html` biten 100 URL sorunsuz.
- **İki YENİ yüksek:**
  - **Y6 · Tazelik sinyali bozuldu → ✅ ÇÖZÜLDÜ aynı gün (`fb21b47`).** `b58739e` telefon
    değişimi 52 dosyaya dokunmuş, `git-dates.js` bunu "güncellendi" saymıştı. Artık
    `[lastmod skip]` işaretli commit'ler yok sayılıyor. Sonuç: bugünün tarihini taşıyan
    sayfa 88 → **4**, tarihler 7 ayrı güne yayıldı.
  - **Y7 · Şehir sayfası yamyamlığı.** `/gazimagusa-psikolog.html` ile `/` **birebir aynı
    title**; EN çifti de aynı kelimeyi hedefliyor. Her iki şehir sayfasının da yalnız 1
    iç bağlantısı var. 2026-09-06 kararının uygulaması eksik kalmış.
- **Y2 yarı kapandı:** `Person` şeması iki psikolog için var (`#gokce-ince`,
  `#yaprak-parlan-yesilovali`, `alumniOf` + `knowsAbout` dolu, `employee` bağlı), ama
  **56/56 yazıda `author` hâlâ `Organization`.** Yazı–psikolog eşleştirmesi merkezden gelmeli.
  Ayrıca `Person.sameAs` = 0, `hasCredential` yok.
- **Y1 kısmen kapandı:** 12/56 yazı artık kaynak gösteriyor (nhs.uk, doi.org, pubmed);
  2026-09-02'de 0/56'ydı. Kalan 44 yazı.
- **Açık kalanlar:** Y4 (5 marka görseli 404, `/favicon.ico` dahil), O1 (6 sayfa <300 kelime,
  hizmet sayfaları 444-492 bandında sıkışık), O4 (SSS sayfalarında **0** iç bağlantı),
  O9 (`geo`, `openingHoursSpecification`, `hasMap` eksik), O11 (`schema-markup.json` hâlâ
  canlıda ve placeholder veri servis ediyor — referanssız), O10 (40 FAQ sorusu birden çok
  sayfada), O12 (5 description <110 karakter).
- **Performans sağlam:** TTFB 47/60/79/196 ms, CSS 8 KB br, JS 634 B, brotli açık, fontlar
  kendi sunucumuzda, 56/56 görselde `alt` + `width`/`height` + `loading`.
- **Ölçülemedi:** PSI kotası yine dolu (lab CWV yok) · **GSC Kapsam (Coverage) raporu hâlâ
  alınmadı** — indeksleme durumu bilinmiyor. Performans raporu geldi ve işlendi (aşağıda).

**Faz 1 denetim özeti (2026-09-02, 92 URL canlı tarandı)**
- Teknik temel sağlam: 92/92 200, self-canonical, **hreflang sıfır hata**, yetim/kırık link yok,
  TTFB 60-130ms, title/description tamamen kural içinde, tek H1, başlık atlaması yok.
- ~~Kritik: **K1** wa.me 92/92 sayfada kırık.~~ Çözüldü 2026-09-16.
- Yüksek: **Y1** 88 içerik sayfasında sıfır dış atıf + kaynaksız istatistik iddiaları ·
  **Y2** isimli yazar yok (46/46 Article author=Organization) · **Y3** görünür tarih yok ·
  **Y4** 4 marka görseli 404 · **Y5** Article.image 46/46 yazıda 404'ü gösteriyor.
- Orta: **O1** 8 sayfa <300 kelime (hizmet sayfaları en zayıf) · **O2** 6 sayfada şema yok ·
  **O3** ~~"ruh" kelimesi 5 sayfada 16 kez~~ (2026-09-15 kapatıldı, URL taşındı + 301) · **O4** yeni içerik 2 iç bağlantı ·
  **O5** yamyamlık (online terapi ×3, aile terapisi ×2) · **O6** 2 URL yazım hatası ·
  **O7** çift terapisi hizmet sayfası yok.
- Düşük: HSTS yok, font preload yok, 5 description <110 kr.
- Ölçülemedi: PSI kotası dolu → laboratuvar CWV skorları alınamadı (bileşenler tek tek iyi).

**Bilinen açık teknik borç** (hafızadan devralındı, Faz 1'de doğrulandı)
- ~~`wa.me` numaraları 86 sayfada placeholder~~ — 2026-09-16'da `905391232547` ile değiştirildi; 106/106 kamuya açık sayfada çalışıyor
- ~~`assets/images/` boş~~ **DÜZELDİ kısmen:** 22 blog kart görseli repoda ve canlıda 200.
  Yalnız 4 marka dosyası hâlâ 404 (og-image, logo, favicon, apple-touch-icon).
- ~~26 sayfa <300 kelime~~ **GÜNCEL ÖLÇÜM: 8 sayfa <300, 33 sayfa <500** (hizmet sayfaları 267-350)
- `/hizmetler/cift-terapisi.html` yok — yalnız blog yazısı var
- `schema-markup.json` placeholder adres/telefon/posta kodu
- ~~iki blog URL'inde yazım hatası ("magussa" çift s)~~ **DÜZELDİ (2026-09-06):**
  `/blog/aile-terapisi-magusa.html` ve `/blog/cift-terapisi-gazimagusa.html`;
  görsel adları da düzeltildi, eski adresler `.htaccess`'te 301.
- **YENİ:** Hakkımızda sayfasındaki iki ekip kartı placeholder ("Klinik Psikolog",
  isim/unvan/lisans yok) — E-E-A-T ve GEO için en yüksek etkili açık.

## GEO Temel Ölçümü (Faz 2)

Kaydedilmedi.

## Kararlar

- 2026-09-20 — **Google İşletme Profili AÇILDI ve siteye bağlandı.** Knowledge Graph'a
  girdi (MID `/g/11nvs6qzw7`, CID `8380127482439713846`). `_data/contact.json`'a iğne
  koordinatı, Haritalar bağlantısı ve çalışma saatleri (Pzt–Cum 09:00–18:00,
  Cmt 10:00–18:00, Pazar kapalı) girildi; `geo` + `hasMap` +
  `openingHoursSpecification` 42 sayfada basılıyor. Saatler iletişim sayfalarında
  görünür kartta da var — görünür metin ile şema tek kaynaktan besleniyor.
  Posta kodu (`99450`) aynı gün eklendi — yalnız şemada, sayfada görünmüyor. O9 kapandı;
  geriye kalıcı olarak yalnız `priceRange` boş (ücret kararı).

- 2026-09-08 — **Ücret içeriği kalıcı olarak kapsam dışı.** Kullanıcı: "ücretlerden
  bahsetmiyoruz". Rakam, aralık ya da açıklama yazılmayacak. Kabul edilen sonuç:
  "terapi ücretleri" sorgularında site erişilebilir olmayacak. Tekrar önerilmeyecek.

- 2026-09-06 — **GBP KARARI DEĞİŞTİ: Google İşletme açılacak.** 7 yerel kelime kapsama
  geri girdi. Ayrıca Gazimağusa şehir sayfası da yapılacak — GBP harita kutusunu, şehir
  sayfası altındaki organik sonucu hedefliyor; birbirinin yerine geçmiyorlar. Faz 3'te
  doğrulandı: rakipler şehir sayfasıyla organik sonuçları alıyor, bunun için GBP gerekmiyor.
  Şehir sayfası ÇOĞALTILMAYACAK — yalnız Gazimağusa.
- 2026-09-06 — **Kurumsal danışmanlık veriliyor** (kullanıcı teyidi): çalışan destek programı
  + eğitim/atölye, iki ayrı hizmet. Yeni içerik sütunu açıldı.
- 2026-09-06 — **PDRAM ayrımı yalnız 2 yerde** (öğrenci hizmet sayfası + 2 yazıda birer
  cümle), site geneline yayılmayacak. Yalnız DAÜ aktif öğrencileri için.
- 2026-09-06 — **EMDR kalıcı olarak kapsam dışı.** Yazıların zaten kaldırıldığı doğrulandı
  (repoda yok, canlıda 404, sitemap'te yok). Faz 3 özetindeki "yazılar hâlâ yayında" ifadesi
  yanlıştı, düzeltildi. `llms.txt`'teki bayat satır temizlendi + nöropsikolojik değerlendirme
  hizmet listesine eklendi (eksikti).
- 2026-09-02 — ~~**Yerel arama (GBP) kapsam dışı bırakıldı.**~~ **(2026-09-06'da iptal)** Kullanıcı erteledi. **Risk:** 7 hedef kelime (`Mağusa psikolog`, `Gazimağusa psikolog`, `KKTC psikolog`, `Kuzey Kıbrıs psikolog`, `Kuzey Kıbrıs Psikoloji merkezi`, `Kıbrıs terapi merkezi`, `Gazimağusa terapi`) Google harita kutusuna bağlı; blog/sayfa çalışmasıyla alınamaz. `schema-markup.json` placeholder kalıyor, LocalBusiness şeması eksik veri ile yayında. Yerel niyet taşıyan aramalarda program etki üretmeyecek.
- 2026-09-02 — Rakip listesini Claude araştırıp kullanıcıya onaylatacak (Faz 3).
- 2026-09-02 — Öncelik dengeli; retrofit sırası izleniyor (greenfield değil).

## Açık Maddeler

- [x] Gerçek WhatsApp/telefon numarası — `+905391232547` (2026-09-16)
- [ ] **Cloudflare AI bot engelini aç** (GPTBot, ClaudeBot, CCBot) — kullanıcı · **K2, Faz 8'i tıkıyor**
- [ ] **ModSecurity 406 istisnası** (6 dizin URL'i, bingbot) — kullanıcı/hosting · **K3**
- [x] GSC **Performans** dışa aktarımı — alındı ve işlendi (2026-09-16)
- [ ] GSC **Kapsam (Coverage)** dışa aktarımı — kullanıcı · indeksleme durumu hâlâ bilinmiyor
- [ ] **Travma kapsam sorusu** — psikologlar · **G1, ölçülmüş en büyük talep buna bağlı**
- [ ] Yazı–psikolog eşleştirmesi (hangi yazı kimin) — psikologlar · **Y2'nin kalan yarısı**
- [ ] Marka görselleri (logo, og-image, favicon, apple-touch-icon) — kullanıcı
- [ ] İsimli psikolog bilgileri (ad, unvan, lisans, uzmanlık) — kullanıcı/psikologlar
- [ ] `.agents/product-marketing.md` "Açık Sorular" bölümündeki 10 madde — psikologlar
