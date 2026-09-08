# Arama Görünürlüğü Programı — Mağusa Psikoloji Merkezi

**İz:** retrofit (yayında, sıralaması olan site)
**Başlangıç:** 2026-09-02
**Son güncelleme:** 2026-09-08
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
| AI bot erişimi | **AÇIK** — GPTBot, ClaudeBot, PerplexityBot, Google-Extended, CCBot, OAI-SearchBot, Googlebot hepsi 200 (2026-09-02 doğrulandı) |

## Faz Durumu

| # | Faz | Beceri | Durum | Çıktılar |
|---|---|---|---|---|
| 0 | Bağlam temeli | product-marketing | ✅ | `.agents/product-marketing.md` (v1) |
| 1 | Teknik teşhis | seo-audit | ✅ | `.agents/seo-audit-2026-09.md` · [rapor sayfası](https://claude.ai/code/artifact/5db9f4b7-52d6-4b21-bade-a0bc3197c5bf) |
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
- K1: 98 sayfada kırık `wa.me` — telefon bekliyor

**Faz 6 çıktısı (2026-09-08, yayında ve doğrulandı)**
- Şemasız sayfa 6 → 0 (yasal sayfalar breadcrumb, beyin-beden Article)
- `sameAs` 0 → 38 (Instagram + Facebook; varlık birleştirme)
- `availableLanguage` 2 → 38 (iki dillilik artık makine-okunur)
- SSS'lerde markdown render ediliyor; görünür bölüm ile FAQPage şeması aynı filtreden geçiyor
- Hâlâ eksik: `telephone`, `geo`, `openingHours`, posta kodu. `priceRange` bilerek yok.

## Taşınanlar

Fazlar arası girdiler.

**Dağıtım hedefleri** (Faz 4 → Faz 9)
- (henüz yok)

**AI ölçüm sorgu seti** (Faz 0 → Faz 2 → Faz 8) — ✅ **KİLİTLİ**
- 20 sorgu seçildi (12 TR + 8 EN), `.agents/ai-gorunurluk-temel-2026-09.md` bölüm 3.
- Set değiştirilmez; iki haftada bir aynı sorgular ölçülür.
- 7 sorgu GBP kararına bağlı (⚠ işaretli) — GBP açılırsa etkiyi ayırt etmek için sette tutuldu.

**Faz 5'te bulunan iki yeni yerel rakip** (Faz 12 döngüsünde profillenecek)
- Psikolog Onur Aydın — psikologonuraydin.com, Gazimağusa hizmet sayfası var
- Sesin Psikolojik Danışmanlık ve Terapi Merkezi — 1 Mustafa Kemal Bulvarı, Gazimağusa

**Rakip profilleri** (Faz 3 → Faz 4/5/9) — ✅ **TAMAMLANDI**, `competitor-profiles/_summary.md`
- **Y1 (kaynak atfı): 5 rakibin de sıfırı var → açık değil, FARK YARATMA fırsatı**
- **Y2 (isimli yazar): 4 rakipte de var → gerçek dezavantaj, en yüksek öncelik**
- Y3 (görünür tarih): yalnız Hoşkan'da var → küçük dezavantaj, ucuz düzeltme
- Bizde olup hiçbirinde olmayan: iki dillilik, hatasız hreflang, FAQPage şeması (73 sayfa), 92 sayfa kapsam
- **Şehir sayfaları GBP'siz çalışıyor** — Noesis 7 bölge (Gazimağusa dahil), Mayıs 15 şehir×hizmet.
  2026-09-02 kapsam kararı kısmen düzeltildi: harita kutusu GBP ister, **altındaki organik
  sonuçlar şehir sayfasıyla alınabiliyor.** Bizde tek şehir sayfası yok.
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

**Faz 1 denetim özeti (2026-09-02, 92 URL canlı tarandı)**
- Teknik temel sağlam: 92/92 200, self-canonical, **hreflang sıfır hata**, yetim/kırık link yok,
  TTFB 60-130ms, title/description tamamen kural içinde, tek H1, başlık atlaması yok.
- Kritik: **K1** wa.me 92/92 sayfada kırık.
- Yüksek: **Y1** 88 içerik sayfasında sıfır dış atıf + kaynaksız istatistik iddiaları ·
  **Y2** isimli yazar yok (46/46 Article author=Organization) · **Y3** görünür tarih yok ·
  **Y4** 4 marka görseli 404 · **Y5** Article.image 46/46 yazıda 404'ü gösteriyor.
- Orta: **O1** 8 sayfa <300 kelime (hizmet sayfaları en zayıf) · **O2** 6 sayfada şema yok ·
  **O3** "ruh" kelimesi 5 sayfada 16 kez (biri title+URL) · **O4** yeni içerik 2 iç bağlantı ·
  **O5** yamyamlık (online terapi ×3, aile terapisi ×2) · **O6** 2 URL yazım hatası ·
  **O7** çift terapisi hizmet sayfası yok.
- Düşük: HSTS yok, font preload yok, 5 description <110 kr.
- Ölçülemedi: PSI kotası dolu → laboratuvar CWV skorları alınamadı (bileşenler tek tek iyi).

**Bilinen açık teknik borç** (hafızadan devralındı, Faz 1'de doğrulandı)
- `wa.me` numaraları 86 sayfada placeholder (`9055555` / `905XXXXXXXXX`) — ana dönüşüm kanalı kırık
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

- [ ] Gerçek WhatsApp/telefon numarası — kullanıcı
- [ ] Marka görselleri (logo, og-image, favicon, apple-touch-icon) — kullanıcı
- [ ] İsimli psikolog bilgileri (ad, unvan, lisans, uzmanlık) — kullanıcı/psikologlar
- [ ] `.agents/product-marketing.md` "Açık Sorular" bölümündeki 10 madde — psikologlar
