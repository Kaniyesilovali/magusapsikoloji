# Ebeveyn Segmenti — Arama Kelimesi Haritası

**Tarih:** 2026-09-11
**Kapsam:** Çocuğu için psikolog arayan ailenin kullandığı sorgular; mevcut kapsamla eşleştirme ve boşluklar
**Bağlam:** `.agents/product-marketing.md` · `.agents/icerik-stratejisi-2026-09.md` (Sütun 2)
**Veri notu:** Arama hacmi aracı yok. Sıralamalar SERP gözlemi, rakip başlıkları ve sorgu biçimi
analizine dayanıyor — sayısal hacim değil, **göreli tahmin**.

---

## 1. Temel bulgu: ebeveyn "psikolog" diye aramıyor

Ebeveyn aramasının en büyük kısmı **hizmet adıyla değil, davranışla** başlıyor.
Aile "çocuk psikoloğu" yazdığı anda kararın büyük bölümünü çoktan vermiş oluyor.
Ondan önceki haftalarda ya da aylarda aranan şey şu biçimde:

> "çocuğum okula gitmek istemiyor" · "çocuğum çok sinirli" · "çocuğum benden ayrılamıyor"
> · "çocuğum okumayı sökemedi" · "çocuğum kardeşini kıskanıyor"

Bu, sitenin en büyük fırsatı ve en büyük açığı. Mevcut çocuk kümesi **Aşama 2–4'te güçlü,
Aşama 1'de neredeyse boş.** Ailenin bizi ilk kez görebileceği yer Aşama 1.

---

## 2. Beş aşamalı ebeveyn arama yolculuğu

### Aşama 1 — Davranışı tarif etme (en yüksek hacim, en erken temas)
*Ebeveyn henüz "psikolojik" kelimesini kullanmıyor. Gördüğü şeyi yazıyor.*

| Sorgu ailesi | Örnek sorgular | Durum |
|---|---|---|
| Okul reddi | çocuğum okula gitmek istemiyor · sabah karın ağrısı okul | ✅ `okul-fobisi-cocuk` |
| **Öfke / davranış** | çocuğum çok sinirli · çocuklarda öfke nöbeti · çocuğum vuruyor · inatçı çocuk ne yapmalı · çocuğum söz dinlemiyor | ❌ **BOŞLUK** |
| **Kaygı / ayrılma** | çocuğum benden ayrılamıyor · anneye aşırı bağlı çocuk · ayrılık kaygısı · çocuğum sürekli endişeli · çocuğum yalnız uyuyamıyor | ❌ **BOŞLUK** |
| **Okuma-yazma** | çocuğum okumayı sökemedi · harfleri karıştırıyor · b ve d karıştırma · çocuğum ders çalışmıyor | ❌ **BOŞLUK** |
| Dikkat | çocuğum derse odaklanamıyor · çok hareketli çocuk | ◐ `dehb-belirtileri-cocuk` kısmen |
| Gelişim | çocuğum geç konuşuyor · göz teması kurmuyor | ✅ `otizm-...-erken-belirtiler` |
| Kardeş | çocuğum kardeşini kıskanıyor · kardeş gelince gerileme | ❌ boşluk |
| Uyku | çocuğum uyuyamıyor · gece korkuları · kâbus | ❌ boşluk |
| Tuvalet | çocuğum altını ıslatıyor · alt ıslatma kaç yaşına kadar normal | ❌ boşluk |
| Sosyal | çocuğum arkadaş edinemiyor · çocuğum çok utangaç | ❌ boşluk |
| Ekran | çocuğum telefondan ayrılmıyor · ekran bağımlılığı çocuk | ❌ boşluk |
| Beden dili | çocuğum tırnak yiyor · çocuğum tik yapıyor | ❌ boşluk |
| Ergen | ergenim benimle konuşmuyor · ergenim odasından çıkmıyor | ❌ boşluk |

### Aşama 2 — Adlandırma: "bu normal mi?"
*Ebeveyn bir isim arıyor. Tanım sorguları AI Overview'u en çok tetikleyen tip.*

| Sorgu | Durum |
|---|---|
| DEHB belirtileri çocuklarda · dikkat eksikliği nedir | ✅ |
| otizm belirtileri · otizm kaç yaşında belli olur | ✅ |
| okul fobisi nedir | ✅ |
| **öğrenme güçlüğü nedir · özel öğrenme güçlüğü** | ✅ `ogrenme-guclugu-belirtileri` |
| disleksi / diskalkuli belirtileri | ⛔ **KAPSAM DIŞI** — tanı etiketi kullanılmıyor (2026-09-11 kararı) |
| **çocuklarda kaygı bozukluğu belirtileri** | ❌ **BOŞLUK** (yetişkin sayfaları var, çocuk yok) |
| çocuklarda depresyon belirtileri | ❌ boşluk |
| çocuklarda davranış bozukluğu | ❌ boşluk |
| yaşına göre normal mi (3/5/7 yaş davranışları) | ❌ boşluk |

### Aşama 3 — "Kime gideceğim?" (meslek ayrımı)
*Türkiye'de ve KKTC'de ebeveynin en çok karıştırdığı alan.*

| Sorgu | Durum |
|---|---|
| çocuk psikoloğu ne yapar · ne zaman gidilir | ✅ `cocuk-psikologu-magusa` |
| çocuk psikoloğu mu çocuk psikiyatristi mi | ✅ (aynı yazıda H2) |
| **pedagog nedir · pedagog mu psikolog mu** | ❌ **BOŞLUK** — Türkçe aramada çok yaygın, rakiplerde var |
| psikolog mu psikolojik danışman mı | ◐ `psikolog-mu-psikiyatrist-mi` kısmen |
| oyun terapisi nedir · oyun terapisi kaç yaş | ❌ **BOŞLUK** — hizmet sayfasında yalnız H3 başlığı var |

### Aşama 4 — Yerel + ticari niyet (en yüksek dönüşüm)

| Sorgu | Durum |
|---|---|
| Gazimağusa çocuk psikoloğu · Mağusa çocuk psikoloğu | ✅ hizmet sayfası title'ı |
| KKTC / Kıbrıs çocuk psikoloğu | ✅ |
| ergen psikoloğu Mağusa | ✅ |
| **İngilizce konuşan çocuk psikoloğu Kıbrıs** | ❌ **BOŞLUK** — en büyük ayrıştırıcımız, hiç hedeflenmiyor |
| online çocuk terapisi · online ergen terapisi | ❌ boşluk (online terapi sayfası yetişkin dilinde) |
| çocuk psikoloğu ücretleri | ⛔ **kapsam dışı** — ücret yayımlanmıyor (karar) |
| zeka testi Kıbrıs · WISC KKTC · dikkat testi Mağusa | ⛔ **ENGELLİ** — test bataryası adları teyitsiz |
| DEHB tanısı / teşhis sorguları | ⛔ **KAPSAM DIŞI** — değerlendirme yapılıyor, **tanı konulmuyor** |
| özel eğitim raporu KKTC · okul için rapor | ⛔ **ENGELLİ** — KKTC süreci teyitsiz (aşağıya bak) |

### Aşama 5 — Randevu öncesi tereddüt
*Hacmi düşük, dönüşüme etkisi en yüksek grup. Ucuz içerik.*

| Sorgu | Durum |
|---|---|
| ilk seansta ne olur (çocuk) · çocuk da gelecek mi | ✅ hizmet sayfası SSS |
| ebeveyn danışmanlığı nedir | ✅ |
| **çocuğuma psikoloğa gideceğimizi nasıl söylerim** | ❌ **BOŞLUK** |
| **çocuğum/ergenim terapiye gitmek istemiyor** | ◐ ergen sayfasında 1 SSS, yazı yok |
| çocuk terapisi kaç seans sürer | ❌ boşluk |
| eşim çocuğu psikoloğa götürmek istemiyor | ❌ boşluk |
| çocuğumun anlattıkları bana söylenir mi | ✅ ergen sayfası SSS |

---

## 3. İngilizce ebeveyn segmenti (expat + uluslararası aile)

Rakiplerin **hiçbirinde İngilizce içerik yok** (Faz 3). Ebeveyn tarafında bu açık daha da büyük.

| Sorgu | Durum |
|---|---|
| child psychologist Famagusta / North Cyprus | ✅ `child-psychology` |
| **English speaking child psychologist Cyprus** | ❌ **BOŞLUK** — en yüksek öncelikli EN sorgu |
| my child won't go to school | ✅ `school-refusal-in-children` |
| ADHD symptoms in children | ✅ |
| autism early signs | ✅ |
| **child anxiety / separation anxiety** | ❌ boşluk |
| **child tantrums / angry child** | ❌ boşluk |
| learning difficulty signs | ✅ `learning-difficulties-in-children` |
| dyslexia signs | ⛔ **KAPSAM DIŞI** — tanı etiketi kullanılmıyor |
| child adjusting to a new school abroad · expat child homesick | ❌ boşluk — **sıfır rekabet**, bize özgü |

---

## 4. Rekabet durumu

**Yeni bulunan doğrudan rakip (profillerde yok):**
**Kıbrıs Çocuk – Ergen – Aile Psikoterapi ve Aile Psikolojik Destek Merkezi** — kibrisruhsagligi.com
- Gönyeli/Yenikent (Lefkoşa bölgesi), Gazimağusa değil → yerel sorguda doğrudan çakışmıyor
- **Çocuk/ergen odaklı konumlanma** — alan adı ve unvanı bu segmenti sahipleniyor
- **İki isimli klinik psikolog yayında** (Uzm. Psk. İpek Akbirgün, Klinik Psikolog İzlem Gülmez Kuşi) → Y2 dezavantajımız bu rakiple de doğrulandı
- Zekâ ve dikkat testlerini açıkça ilan ediyor; bizde bu alan teyit bekliyor
- Sitesi İngilizcesiz, "Bilgi Bankası" bölümü var ama derinliği düşük
- **Not:** `competitor-profiles/` klasörüne profil açılmalı (Faz 12 döngüsü)

**Segmentteki genel tablo:** Aşama 1 sorgularının çoğunu Türkiye merkezli büyük psikoloji
siteleri (Acıbadem, Moodist, çeşitli merkez blogları) tutuyor. Bunlar **yerel değil** —
"Gazimağusa", "KKTC", "iki dilli okul", "adadaki süreç" hiçbirinde yok. Aşama 1 yazısını
yerel bağlamla yazmak, hacmin tamamını almasa da nitelikli kısmını alır.

---

## 5. Öncelik — ebeveyn segmenti ilk 10

Puanlama `.agents/icerik-stratejisi-2026-09.md` ile aynı:
Birey Etkisi %40 · Hizmet Uyumu %30 · Arama Potansiyeli %20 · Kaynak %10.

| # | İş | Tip | Etki | Uyum | Arama | Kaynak | **Puan** | Durum |
|---|---|---|---|---|---|---|---|---|
| 1 | **Çocuklarda öfke nöbetleri ve davranış** (TR+EN) | YENİ | 9 | 9 | 10 | 7 | **9.0** | ✅ yazıldı |
| 2 | **Çocuklarda kaygı ve ayrılık kaygısı** (TR+EN) | YENİ | 9 | 9 | 9 | 7 | **8.9** | ✅ yazıldı |
| 3 | **Öğrenme güçlüğü belirtileri** (TR+EN) | YENİ | 9 | 10 | 8 | 7 | **9.1** | ✅ yazıldı |
| 4 | Oyun terapisi nedir (TR+EN) | YENİ | 8 | 9 | 8 | 6 | **8.2** | ☐ |
| 5 | "Çocuğuma psikoloğa gideceğimizi nasıl söylerim" | YENİ | 9 | 8 | 6 | 8 | **8.1** | ☐ |
| 6 | İngilizce konuşan çocuk psikoloğu sayfası (EN öncelikli) | YENİ | 8 | 9 | 7 | 8 | **8.1** | ☐ |
| 7 | Pedagog / psikolog / psikolojik danışman ayrımı | YENİ | 7 | 8 | 8 | 8 | **7.6** | ☐ |
| 8 | `cocuk-psikolojisi` hizmet sayfası AEO yapısı (624 kl → 3 H2) | YENİLE | 8 | 10 | 7 | 6 | **8.2** | ☐ |
| 9 | Kardeş kıskançlığı | YENİ | 7 | 8 | 8 | 7 | **7.5** | ☐ |
| 10 | Ergenle iletişim / "ergenim konuşmuyor" | YENİ | 8 | 9 | 7 | 6 | **8.0** | ☐ |

**1–3 neden seçildi:** üçü de Aşama 1'in en büyük boşlukları, üçü de **zaten verilen hizmete**
bağlanıyor (çocuk psikolojisi, ebeveyn danışmanlığı, nöropsikolojik değerlendirme) ve
hiçbiri yeni iddia gerektirmiyor. 3 numara ayrıca **nöropsikolojik değerlendirme sayfasını
besliyor** — Faz 2'de rekabeti en düşük ölçülen alan.

**8 neden listede:** üç yeni yazı da `/hizmetler/cocuk-psikolojisi.html` sayfasına link
veriyor. O sayfa 624 kelime ve 3 H2 ile kümenin en zayıf halkası; trafiğin indiği yer orası.

---

## 6. Bloklu kalemler — teyit bekliyor

Aşağıdaki sorgular **ticari niyeti en yüksek** olanlar ama içerik üretilemiyor.
`.agents/product-marketing.md` "Açık Sorular" bölümüne bağlı.

| Sorgu ailesi | Engel | Kim açar |
|---|---|---|
| zekâ testi / WISC / dikkat testi + Kıbrıs | Kullanılan test bataryası adları teyitsiz | Psikologlar |
| özel eğitim raporu / okul raporu KKTC | KKTC'de sürecin nasıl işlediği (RAM muadili var mı, rapor nereye sunulur) araştırılmadı; **resmî rapor düzenleniyor mu** teyitsiz | Psikologlar + KKTC MEB araştırması |
| çocuk psikoloğu ücreti | Ücret yayımlama kararı yok | Psikologlar |
| yakınımdaki çocuk psikoloğu / harita sonuçları | GBP ertelendi (2026-09-02) | Kullanıcı |

**Ayrıca:** ebeveyn segmentine gelen tüm trafiğin dönüşüm yolu `wa.me` — **98 sayfada kırık (K1).**
Bu kelime çalışmasının getirisi telefon numarası gelene kadar ölçülemez.

---

## 7. Kapsam kararları (2026-09-11, psikologlarla teyit)

Bu kelime çalışmasında doğrulanan hizmet sınırları. İçerik bu sınırların dışına çıkmaz.

| Alan | Karar | Etkilenen yüzeyler |
|---|---|---|
| **Öğrenme güçlüğü** | Kapsamda — nöropsikolojik değerlendirmenin başvuru nedenlerinden biri | — |
| **Disleksi / diskalkuli / disgrafi** | **Tanı etiketleri kullanılmaz.** Zorlanma alan olarak tarif edilir | `hizmetler/noropsikolojik-degerlendirme` (TR+EN), `blog/noropsikolojik-degerlendirme` (TR+EN), yeni öğrenme güçlüğü yazısı — hepsi temizlendi |
| **DEHB / dikkat** | Değerlendirme ve destek var, **tanı konulmuyor** (tanı çocuk psikiyatristinde) | `hizmetler/cocuk-psikolojisi` düzeltildi; nöro sayfasında "tanı koymaz" SSS'i zaten vardı |
| **Hiperaktivite** | Hizmet sayfalarında iddia yok, yalnız blog belirti anlatımı — sorun yok | — |
| **Dürtüsellik** | Hiçbir hizmet iddiası yok — sorun yok | — |
| **Otizm** | Tanı üstlenilmiyor; çocuk psikolojisi görüşmeleri + gelişimsel değerlendirme. Mevcut yazılar bunu doğru anlatıyor | — |

**Açık kalan tek istisna:** NHS kaynak bağlantısının görünen adı `NHS, Dyslexia in children`.
Bu bizim hizmet iddiamız değil, kaynağın gerçek sayfa başlığı. Atfı bozmamak için olduğu gibi
bırakıldı — psikologlar isterse bağlantı tamamen kaldırılabilir.

---

## 8. Değişiklik Kaydı

- 2026-09-11 — Kapsam kararları eklendi; disleksi/diskalkuli etiketleri site genelinden temizlendi,
  öğrenme güçlüğü yazısı `ogrenme-guclugu-belirtileri` olarak yeniden adlandırıldı.
- 2026-09-11 — İlk harita. Ebeveyn yolculuğu 5 aşamaya ayrıldı, 3 yazı üretildi (öfke, kaygı,
  öğrenme güçlüğü; TR+EN). Yeni rakip kibrisruhsagligi.com kaydedildi.
