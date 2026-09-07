# Arama Görünürlüğü Programı — Mağusa Psikoloji Merkezi

**İz:** retrofit (yayında, sıralaması olan site)
**Başlangıç:** 2026-09-02
**Son güncelleme:** 2026-09-07
**Mevcut faz:** 5 — Mevcut Metni AEO Formatına Getirme
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
| 5 | Mevcut metni AEO formatına getir | copy-editing | ▶ | |
| 6 | Yapılandırılmış veri | schema | ☐ | |
| 7 | Site mimarisi (yalnız gerekirse) | site-architecture | ☐ / n/a | |
| 8 | AEO + GEO çekirdeği | ai-seo (tam) | ☐ | |
| 9 | Site dışı otorite | directory-submissions, public-relations, community-marketing, social | ☐ | |
| 10 | Ölçümleme | analytics | ☐ | |
| 11 | Dönüşüm | cro, popups, emails | ☐ | |
| 12 | Döngüler | marketing-loops | ☐ | |

Durum: ☐ bekliyor · ▶ sürüyor · ✅ bitti · ⏸ engellendi · n/a kapsam dışı

## Mevcut Faz

**Faz:** 5 — Mevcut Metni AEO Formatına Getirme (`copy-editing`) + eksik sayfaların açılması
**Çıkış ölçütü:** Öncelik listesinin 1–9'u tamam; her dokunulan sayfada hem yapısal (sorgu-H2,
40-60 kelimelik açılış, SSS bloğu) hem otorite (yazar, kaynak, tarih) katmanı uygulanmış.
**Engelleyen:** Y2 için psikolog ad/unvan/lisans bilgisi. Yapısal iş bunsuz yapılabilir,
otorite işi yapılamaz.
**Sonraki adım:** Push (kullanıcı onayı verdi: "22 hizmet sayfasından sonra push edelim"),
ardından öncelik listesi 4. madde (Gazimağusa şehir sayfası — adres bekleniyor).

**Faz 5 ilerlemesi:**
> **YAYINDA** — commit `eff671b`, deploy 2026-09-07 doğrulandı. Canlı ölçüm (28 hizmet
> sayfası): 2+ sorgu-H2 %100 · ideal pasaj %96 · tablo %36 · ort. 660 kelime · ort. 4,6 SSS.
> Sitemap 92 → 94 URL. Yeni sayfalarda canonical self + hreflang tr/en/x-default +
> BreadcrumbList/FAQPage/LocalBusiness şeması doğrulandı.
> URL yazım hatası düzeltmesi (O6) de yayında: eski adresler 301 veriyor.

- ✅ **1. Nöropsikolojik değerlendirme hizmet sayfası TR+EN** — YAYINDA.
  `content/tr/hizmetler/noropsikolojik-degerlendirme.njk` · `content/en/services/neuropsychological-assessment.njk`
  Sonuç: 8 H2 (5'i sorgu biçimli), 2 tablo, 1 numaralı liste, FAQPage şeması, 40 kelimelik
  açılış pasajı. TR 745 kelime / EN 1002 kelime — eski hizmet sayfaları 270/336'ydı.
  Site 92 → 94 URL. `npm run check` 0 hata 0 uyarı.
- ✅ **2. 22 hizmet sayfasının AEO yapısı** — YAYINDA. 10 TR + 10 EN hizmet sayfası + 2 dizin sayfası.
  Toplu sonuç (28 hizmet sayfası): 2+ sorgu-H2 **%15 → %100** · ideal pasaj **%38 → %96** ·
  tablo **~%7 → %36** · liste %93 · ortalama kelime **351 → 660**. SSS 3 → 5 (yeni sayfada 6).
- ⏸ Y2 (isimli yazar) engelli — psikologlar eğitim bilgilerini toparlıyor
- ✅ **4. Gazimağusa şehir sayfası TR+EN** — YAYINDA (commit `ab8803d`, deploy doğrulandı).
  `/gazimagusa-psikolog.html` (610 kelime) · `/en/psychologist-in-famagusta.html` (836 kelime).
  Her ikisi 5 H2 (4'ü soru), 1 tablo, 6 SSS. PDRAM ayrımı bu sayfada da var.
  **Şehir sayfası ÇOĞALTILMAYACAK** — yalnız Gazimağusa.
- ✅ **Adres tüm siteye uygulandı** (2026-09-07). Kullanıcı adresi metin olarak verdi:
  *Eşref Bitlis Caddesi, Sancak Plaza, Kat 5 Daire 2, Gazimağusa.*
  `_data/contact.json` tek kaynak; iletişim + KVKK + gizlilik sayfaları (TR/EN) oradan besleniyor.
  **36 sayfanın `LocalBusiness` şemasına `streetAddress` eklendi** (önceden yalnız locality vardı).
  İki dilde birebir aynı sokak dizesi — GBP ile NAP tutarlılığı için.
  İletişim sayfasındaki görünür `[Sokak / Bina bilgisi eklenecek]` placeholder'ı kapandı.
  **Posta kodu YOK** — verilmedi, uydurulmadı. GBP'de varsa şemaya eklenecek.
  **GBP notu:** `kgmid=/g/11nvs6qzw7` — zaten bir Google kaydı var, sıfırdan açılmayacak;
  mevcut kayıt sahiplenilip tamamlanacak. Google onay duvarı nedeniyle içeriği okunamıyor.
- ⏸ 5/7. Kurumsal hizmetler — sözleşme modeli, seans kotası, atölye başlıkları teyit bekliyor

**Faz 5 iş listesi (puana göre):**
1. Nöropsikolojik değerlendirme hizmet sayfası TR+EN ⭐YENİ — 9.0
2. 22 hizmet sayfasının AEO yapısı — 8.9
3. Çift terapisi hizmet sayfası TR+EN ⭐YENİ — 8.8
4. Gazimağusa şehir sayfası TR+EN ⭐YENİ — 8.6
5. Çalışan destek programı hizmet sayfası TR+EN ⭐YENİ — 8.4
6. Öğrenci danışmanlığına PDRAM ayrımı — 8.4
7. Eğitim ve atölyeler hizmet sayfası TR+EN ⭐YENİ — 7.8
8. Hizmet dizin sayfaları TR+EN — 7.7
9. Online terapi üçlüsünde niyet ayrıştırma — 7.5

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
