# İçerik Stratejisi — Boşluk Analizi

**Tarih:** 2026-09-06 · **Program fazı:** Faz 4 (`content-strategy`, retrofit izi)
**Kapsam:** Sıfırdan plan değil — mevcut 92 sayfanın sınıflandırılması + boşlukların doldurulması

**Girdiler:** `.agents/product-marketing.md` · `.agents/seo-audit-2026-09.md` ·
`.agents/ai-gorunurluk-temel-2026-09.md` · `competitor-profiles/_summary.md` · `competitor-profiles/dau-pdram.md`

---

## Kullanıcı kararları (2026-09-06)

| Karar | Sonuç |
|---|---|
| **EMDR kesinlikle açılmayacak** | Yazılar zaten kaldırılmış (doğrulandı: repoda yok, canlıda 404). `llms.txt`'teki bayat satır düzeltildi. **Kapandı.** |
| **GBP açılacak + şehir sayfası da yapılacak** | İkisi farklı SERP slotu. 7 yerel kelime **kapsama geri girdi.** |
| **Kurumsal danışmanlık veriliyor** | Hem çalışan destek programı hem eğitim/atölye. **İki ayrı hizmet** olarak kurgulanacak. |
| **PDRAM ayrımı** | Yalnız DAÜ aktif öğrencileri için, **yalnız 2 yerde** (hizmet sayfası + 2 yazıda birer cümle). Site geneline yayılmayacak. |

---

## 1. Mevcut 92 sayfanın sınıflandırması

| Sınıf | Sayfa | Ne yapılacak |
|---|---|---|
| **KORU** | 59 | Dokunulmayacak. Faz 5'te yalnız kaynak + yazar + tarih eklenecek. |
| **YENİLE — öncelik** | 22 | Hizmet sayfaları. Faz 5'in ana işi. |
| **YENİLE** | 7 | İnce blog + iki güçlü hizmet sayfası |
| **YENİLE — şema** | 4 | Yasal sayfalar, yalnız şema eksiği |
| **BİRLEŞTİR** | 2 çift | Yamyamlık (aşağıda) |
| **EMEKLİYE AYIR** | **0** | Emekliye ayrılacak içerik yok. EMDR zaten gitmiş. |

> Ölçüm notu: buradaki kelime sayıları `<main>` kapsamıyla alındı, Faz 1'deki gövde-eksi-menü
> ölçümünden ~%25 düşük çıkar. İki ölçüm kendi içinde tutarlı; karşılaştırmada aynı yöntem kullanılmalı.

### YENİLE — öncelik: 22 hizmet sayfası

Hepsinde aynı yapısal sorun: **tam 3 H2, bunların 0–1'i sorgu biçiminde, çıkarıma uygun
uzunlukta pasaj sıfır.** Şablondan üretilmişler ve şablon AEO'ya uygun değil.

| Sayfa | Kelime | Sorgu-H2 |
|---|---|---|
| `/hizmetler/` (dizin) | 190 | 0/13 |
| `/hizmetler/online-terapi.html` | 200 | 1/3 |
| `/hizmetler/bdt-terapisi.html` | 201 | 0/3 |
| `/hizmetler/bireysel-terapi.html` | 203 | 1/3 |
| `/hizmetler/cocuk-psikolojisi.html` | 224 | 1/3 |
| `/en/services/` (dizin) | 245 | 0/13 |
| `/hizmetler/motivasyonel-gorusme.html` | 258 | 1/3 |
| `/hizmetler/act-terapisi.html` | 259 | 0/3 |
| `/hizmetler/psikodinamik-terapi.html` | 261 | 1/3 |
| `/hizmetler/yetiskin-psikolojisi.html` | 261 | 1/3 |
| `/hizmetler/genc-yetiskinlik.html` | 281 | 1/3 |
| `/hizmetler/ergen-psikolojisi.html` | 282 | 1/3 |
| + 10 EN eşi | 245–361 | 0–1/3 |

**Hedef:** her hizmet sayfası 3 H2 → 6–8 H2, en az 4'ü sorgu biçiminde, ilk paragraf
40–60 kelimelik doğrudan yanıt, en az bir SSS bloğu.

### BİRLEŞTİR — yamyamlık

| Terim | Yarışanlar | Karar |
|---|---|---|
| **online terapi** | `/hizmetler/online-terapi.html` (ticari) · `/blog/kuzey-kibris-online-terapi.html` (yerel) · `/blog/online-terapi-etkili-mi.html` (kanıt) | **Birleştirme değil, niyet ayrıştırma.** Üçü farklı niyete hizmet ediyor. Hizmet sayfası "nasıl alınır", biri "Kıbrıs'ta nasıl işler", biri "etkili mi". H2'ler ve iç linkler bu ayrımı netleştirecek şekilde yeniden yazılsın. |
| **aile terapisi** | `/hizmetler/aile-terapisi.html` (682 kl) · `/blog/aile-terapisi-magussa.html` | **Aynı ayrıştırma.** Hizmet sayfası "biz nasıl çalışıyoruz", blog "ne zaman başvurulur". Blog'un URL yazım hatası (O6) ayrı karar. |

**Not:** Hiçbirini silmiyoruz. `çocuk psikoloğu` çakışması 2026-08-28'de URL taşımayla çözülmüştü;
burada taşımaya gerek yok, niyet ayrımı yeterli.

---

## 2. İçerik sütunları (pillar)

Beş sütun. İlk dördü mevcut içeriği topluyor, beşincisi yeni.

### Sütun 1 — Terapiye başlarken
*Karar aşamasındaki bireyin soruları. En yüksek AEO değeri: tanım sorguları AI Overview'u en çok tetikleyen tip.*

Mevcut: terapiye ne zaman gidilmeli · terapiye başlamadan önce · psikolog mu psikiyatrist mi ·
terapi dili TR/EN · kuzey kıbrıs'ta psikolog bulma · terapi süreci · SSS

### Sütun 2 — Çocuk, ergen ve ebeveynlik
*Ebeveyn segmenti. Rakiplerin en çok yatırım yaptığı alan da bu.*

Mevcut: çocuk psikoloğu · okul fobisi · DEHB belirtileri · ebeveyn danışmanlığı ·
nöropsikolojik değerlendirme · çocuk/ergen hizmet sayfaları

### Sütun 3 — Kaygı, depresyon ve tükenmişlik
*Yetişkin klinik konular. Hacmi en yüksek, rekabeti en küresel.*

Mevcut: anksiyete · kaygı bozukluğu · depresyon · panik atak · tükenmişlik · sınav kaygısı · beyin-beden

### Sütun 4 — Kıbrıs'ta psikolojik destek
*Sitenin gerçek farkı burada. Yerel + iki dilli + öğrenci. Rakiplerde İngilizce içerik YOK.*

Mevcut: kuzey kıbrıs psikolojik destek · online terapi KKTC · üniversite öğrencileri ·
uluslararası öğrenciler · öğrenci danışmanlığı
**Yeni:** Gazimağusa şehir sayfası · PDRAM ayrımı

### Sütun 5 — Kurumlarda psikolojik sağlık ⭐ YENİ
*Sıfırdan. Yerelde yalnız Mayıs Psikoloji'nin dokunduğu, Gazimağusa'da kimsenin dokunmadığı alan.*

**Yeni:** çalışan destek programı · eğitim ve atölyeler · kurumsal tükenmişlik · yönetici desteği

Bu sütun aynı zamanda **farklı bir alıcıya** hitap ediyor: birey değil, kurum
(İK yöneticisi, işveren). Dil rehberi burada da geçerli ama muhatap değişiyor.

---

## 3. Kapatılacak yapısal boşluklar

Faz 1 ve Faz 3'ten gelen, hizmet verilip sayfası olmayan alanlar:

| Boşluk | Durum | Kanıt |
|---|---|---|
| **Nöropsikolojik değerlendirme** | Hizmet veriliyor, **yalnız blog yazısı var, hizmet sayfası YOK** | Rekabeti en düşük alan (Faz 2), rakiplerde açık kapsam yok (Faz 3) |
| **Çift terapisi** | Hizmet veriliyor, **yalnız blog yazısı var, hizmet sayfası YOK** | Faz 1 · O7. CCH ve Mayıs bu alanda güçlü |
| **Çalışan destek programı** | Hizmet veriliyor, **hiç içerik yok** | Kullanıcı teyidi 2026-09-06 |
| **Eğitim ve atölyeler** | Hizmet veriliyor, **hiç içerik yok** | Kullanıcı teyidi 2026-09-06 |
| **Gazimağusa şehir sayfası** | Yok | Noesis'te var (358 kelime, şablonluk), Mayıs'ta yok |

**Beşinin ortak noktası:** hiçbiri yeni hizmet uydurmuyor. Hepsi zaten verilen ama
sitede görünmeyen hizmetler. EMDR hatasının tersi.

---

## 4. Öncelik listesi — ilk 20

Puanlama: Birey Etkisi %40 · İçerik-Hizmet Uyumu %30 · Arama Potansiyeli %20 · Kaynak %10.
(Arama hacmi verisi yok — Faz 3'te not edildi. Arama potansiyeli rekabet analizinden tahmin.)

| # | İş | Tip | Etki | Uyum | Arama | Kaynak | **Puan** |
|---|---|---|---|---|---|---|---|
| 1 | **Nöropsikolojik değerlendirme hizmet sayfası** (TR+EN) | YENİ | 9 | 10 | 8 | 7 | **9.0** |
| 2 | **22 hizmet sayfasının AEO yapısı** | YENİLE | 9 | 10 | 8 | 6 | **8.9** |
| 3 | **Çift terapisi hizmet sayfası** (TR+EN) | YENİ | 9 | 10 | 7 | 7 | **8.8** |
| 4 | **Gazimağusa psikolog şehir sayfası** (TR+EN) | YENİ | 8 | 9 | 10 | 7 | **8.6** |
| 5 | **Çalışan destek programı hizmet sayfası** (TR+EN) | YENİ | 8 | 10 | 7 | 6 | **8.4** |
| 6 | Öğrenci danışmanlığı sayfasına **PDRAM ayrımı** | YENİLE | 9 | 9 | 6 | 8 | **8.4** |
| 7 | **Eğitim ve atölyeler hizmet sayfası** (TR+EN) | YENİ | 7 | 10 | 6 | 6 | **7.8** |
| 8 | `/hizmetler/` ve `/en/services/` dizin sayfaları | YENİLE | 7 | 9 | 7 | 8 | **7.7** |
| 9 | Online terapi üçlüsünde **niyet ayrıştırma** | YENİLE | 7 | 8 | 8 | 8 | **7.5** |
| 10 | `beyin-beden` TR/EN (192/250 kelime + şema yok) | YENİLE | 6 | 7 | 7 | 8 | **6.7** |
| 11 | Aile terapisi hizmet/blog niyet ayrımı | YENİLE | 6 | 8 | 7 | 8 | **6.9** |
| 12 | Yasal sayfalara BreadcrumbList şeması | YENİLE | 4 | 5 | 3 | 10 | **4.6** |
| 13 | "Kurumsal tükenmişlik" yazısı | YENİ | 7 | 9 | 6 | 6 | **7.3** |
| 14 | "İşveren için: çalışan ne zaman desteğe ihtiyaç duyar" | YENİ | 7 | 9 | 6 | 6 | **7.3** |
| 15 | "Mezuniyet sonrası psikolojik destek" (PDRAM boşluğu) | YENİ | 7 | 8 | 5 | 7 | **6.9** |
| 16 | "Öğrenci değilim, Mağusa'da nereye başvururum" | YENİ | 7 | 8 | 5 | 7 | **6.9** |
| 17 | Nöropsikolojik: "rapor okula/hekime nasıl sunulur" | YENİ | 7 | 9 | 5 | 6 | **7.1** |
| 18 | "Çift terapisine partner gelmek istemiyorsa" | YENİ | 8 | 8 | 5 | 7 | **7.3** |
| 19 | İngilizce SSS kütüphanesinin genişletilmesi | YENİLE | 6 | 8 | 7 | 7 | **6.9** |
| 20 | `terapiye-ne-zaman-gidilmeli` (486 kl, 1/5 sorgu-H2) | YENİLE | 6 | 8 | 8 | 8 | **7.2** |

### Sıralamanın mantığı

**1, 3, 5, 7 neden en üstte:** verilen ama görünmeyen hizmetler. İçerik üretmenin en ucuz
ve en dürüst biçimi — yeni iddia gerektirmiyor, sadece var olanı yazıyor. Ve dördü de
ticari niyet taşıyor.

**2 neden bu kadar yüksek:** 22 sayfa tek bir yapısal düzeltmeyle iyileşiyor. Faz 2'de
ölçüldü: hizmet sayfalarının sorgu-H2 oranı %15, blogunki %76. Fark kapatılabilir ve
etkisi 22 sayfaya birden yayılıyor.

**4 neden 10 puan arama potansiyeli:** 7 yerel kelime GBP kararıyla kapsama geri girdi ve
rakipler bu sayfalara sahip. Ama **Noesis'in Gazimağusa sayfası 358 kelime ve şablonluk** —
gerçekten yazılmış tek bir sayfa yeter.

**12 neden en altta:** doğru ama etkisi küçük. Faz 6'da (`schema`) zaten kapanacak.

---

## 5. Küme haritası

```
Sütun 4 — KIBRIS'TA PSİKOLOJİK DESTEK  (merkez küme, sitenin farkı)
│
├── /gazimagusa-psikolog.html  ⭐YENİ  ◄── GBP ile birlikte
│     ├── /hizmetler/  (tüm hizmetlere dağıtır)
│     └── /blog/kuzey-kibris-psikolog-bulma
│
├── /hizmetler/ogrenci-danismanlik.html  ◄── PDRAM ayrımı buraya
│     ├── /blog/universite-ogrencileri-psikolojik-destek   (1 cümle PDRAM)
│     ├── /blog/magusa-uluslararasi-ogrenciler-...          (1 cümle PDRAM)
│     ├── "Mezuniyet sonrası destek"        ⭐YENİ
│     └── "Öğrenci değilim, nereye başvururum" ⭐YENİ
│
├── /hizmetler/online-terapi.html
│     ├── /blog/kuzey-kibris-online-terapi   (yerel niyet)
│     └── /blog/online-terapi-etkili-mi      (kanıt niyeti)
│
└── /blog/terapi-dili-turkce-ingilizce  ◄── EN kümesinin girişi


Sütun 5 — KURUMLARDA PSİKOLOJİK SAĞLIK  ⭐ TAMAMEN YENİ
│
├── /hizmetler/kurumsal-danismanlik.html  ⭐ (çalışan destek programı)
│     ├── "Kurumsal tükenmişlik"                    ⭐
│     └── "İşveren için: çalışan ne zaman desteğe ihtiyaç duyar" ⭐
│
└── /hizmetler/egitim-atolye.html  ⭐


Sütun 2 — ÇOCUK, ERGEN, EBEVEYNLİK
│
├── /hizmetler/noropsikolojik-degerlendirme.html  ⭐YENİ  ◄── en düşük rekabet
│     ├── /blog/noropsikolojik-degerlendirme-magusa  (mevcut, 1200 kl, KORU)
│     ├── /blog/dehb-belirtileri-cocuk
│     └── "Rapor okula/hekime nasıl sunulur"  ⭐
│
└── (mevcut çocuk/ergen kümesi — KORU)


Sütun 1 — TERAPİYE BAŞLARKEN   (çoğu KORU)
Sütun 3 — KAYGI, DEPRESYON, TÜKENMİŞLİK   (tamamı KORU)
```

---

## 6. Gazimağusa şehir sayfası — ince içerik tuzağına düşmeden

Rakip sayfası 358 kelime, H2'leri şehir adı değiştirilerek üretilmiş. Onu kopyalamak
`programmatic-seo` becerisinin açıkça uyardığı hataya düşmek olur. **Bir şehir, bir sayfa,
gerçek içerik.** İçermesi gerekenler:

- Merkezin gerçek adresi ve nasıl gidileceği (GBP ile aynı NAP)
- Gazimağusa'ya özgü gerçek sorular: *"Tanıdığım birine denk gelir miyim?"* — ada
  toplumunun asıl kaygısı, rakiplerin hiçbirinde yok
- DAÜ yakınlığı ve öğrenci erişimi
- Hangi hizmetlerin yüz yüze, hangilerinin online alınabildiği
- Türkçe ve İngilizce seans seçeneği
- Sorgu biçimli H2'ler: *"Gazimağusa'da psikolog nasıl seçilir?"*, *"İlk görüşme nasıl ayarlanır?"*

**Şehir sayfası çoğaltılmayacak.** İskele, Girne, Lefkoşa sayfaları açılmayacak — merkez
Gazimağusa'da; olmayan yerellik iddia etmek hem yanlış hem ince içerik üretir.

---

## 7. PDRAM ayrımı — nerede geçecek

Kullanıcı kararı: yalnız DAÜ **aktif** öğrencileri için, site geneline yayılmayacak.

| Yer | Ne kadar |
|---|---|
| `/hizmetler/ogrenci-danismanlik.html` | **Bir bölüm** — PDRAM adıyla anılır, ücretsiz olduğu söylenir, "önce oraya bakın" denir, sonra hangi durumlarda merkeze gelinebileceği ayrılır |
| `/blog/universite-ogrencileri-psikolojik-destek` | **Bir cümle** + hizmet sayfasına link |
| `/blog/magusa-uluslararasi-ogrenciler-...` | **Bir cümle** + hizmet sayfasına link |
| Diğer 89 sayfa | **Hiç** |

Ayrımın özü: PDRAM aktif DAÜ öğrencisi içindir. Mezunlar, diğer üniversiteler,
öğrenci olmayanlar, süreklilik isteyenler ve üniversiteden bağımsız gizlilik tercih edenler
için merkez.

---

## 8. Yapılmayacaklar

- **EMDR içeriği** — hizmet verilmiyor, kalıcı karar (2026-09-06)
- **Şehir sayfası çoğaltma** — yalnız Gazimağusa
- **Programatik sayfa üretimi** — `programmatic-seo` bu programda çalıştırılmayacak;
  ince içerik riski bu sitede getiriden büyük
- **Karşılaştırma / "alternatif" sayfaları** — sağlık hizmetinde yanlış, dil rehberine aykırı
- **Yeni klinik iddia** — psikolog teyidi olmadan hiçbir hizmet, test adı, süre veya
  sonuç iddiası yazılmayacak

---

## 9. Faz 5'e devir

**Sıra:** öncelik listesinin 1–9'u Faz 5'in iş listesidir.

**Faz 5 iki iş birden yapacak** — her sayfaya dokunulduğunda ikisi de uygulanır:
1. Yapısal (AEO): sorgu biçimli H2, 40–60 kelimelik açılış pasajı, SSS bloğu
2. Otorite: **isimli yazar (Y2 — en yüksek öncelik), kaynak (Y1 — fark yaratıcı), görünür tarih (Y3)**

**Hâlâ engelli:** Y2 için psikolog ad/unvan/lisans bilgisi bekleniyor. Bu gelmeden
yapısal iş yapılabilir ama otorite işi yapılamaz.

## Açık maddeler

- [ ] Psikolog ad/unvan/lisans bilgileri (Y2 engelleyici)
- [ ] Gerçek adres ve telefon (şehir sayfası + GBP + K1 engelleyici)
- [ ] Kurumsal hizmetler: sözleşme modeli, seans kotası, atölye konu başlıkları — yazmadan önce teyit
- [ ] 20 sorguluk AI ölçüm çizelgesi (Faz 2'den devreden)
