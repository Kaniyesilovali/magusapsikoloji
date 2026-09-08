# AI Görünürlük Temel Ölçümü — magusapsikoloji.com

**Tarih:** 2026-09-06 · **Program fazı:** Faz 2 (`ai-seo` Adım 1 — yalnız denetim, Pillar çalışması yok)
**Amaç:** Program başlamadan önceki durumu kaydetmek. Bu kayıt olmadan sonradan
"program işe yaradı mı" sorusuna cevap verilemez.

---

## Neyi ölçebildim, neyi ölçemedim

`ai-seo` Adım 1'in dört alt adımı var. Üçünü tam yaptım, birini yapamam:

| Alt adım | Durum |
|---|---|
| 1. AI yanıtlarını kontrol et (ChatGPT/Perplexity/Google AI/Claude) | ⚠️ **Elle yapılmalı** — bu motorlara oturum açıp sorgu çalıştıramam |
| 2. Alıntı örüntülerini analiz et | ◐ Kısmi — rakip manzarası çıkarıldı, gerçek alıntı verisi 1. adıma bağlı |
| 3. İçerik çıkarılabilirlik denetimi | ✅ **Tam** — 92 sayfa, 10 maddelik kontrol |
| 4. AI bot erişim kontrolü | ✅ **Tam** — 14 bot doğrulandı |

**Neden 1. adımı yapamıyorum:** ChatGPT, Perplexity ve Google AI Mode'a sorgu gönderip
kimin alıntılandığını okuyamam. Elimdeki web araması bunun yerine geçmez — üstelik ABD
merkezli sonuç döndürüyor, Gazimağusa'dan bakan birinin göreceği sonuç değil.
Aşağıdaki sorgu seti ve çizelge tam da bunun için: **20 dakikalık elle bir çalışma**
ve temel ölçüm kapanır.

---

## 1. Bot erişimi — ✅ AÇIK (yeniden doğrulandı 2026-09-06)

14 botun tamamı `/blog/depresyon-nedir.html` üzerinde **200** döndü:

| Grup | Botlar | Durum |
|---|---|---|
| OpenAI | GPTBot, ChatGPT-User, OAI-SearchBot | 200 |
| Anthropic | ClaudeBot, anthropic-ai, Claude-User | 200 |
| Perplexity | PerplexityBot, Perplexity-User | 200 |
| Google | Googlebot, Google-Extended | 200 |
| Diğer | bingbot, CCBot, Applebot-Extended, meta-externalagent | 200 |

`robots.txt`'te tek `Disallow` satırı `/panel/` — doğru.

**Ayrıca doğrulandı: kopyalama (cloaking) yok.** Beş farklı UA ile aynı sayfa istendi,
beşine de bayt bayt aynı içerik döndü (43.785 bayt, 3 JSON-LD bloğu). Yani AI botları
kullanıcının gördüğü sayfanın aynısını görüyor.

**`llms.txt` yayında ve iyi durumda.** Her yayında otomatik üretiliyor, TR/EN yapıyı
açıklıyor ve dürüst — *"Merkezde EMDR uygulanmamaktadır"* satırı açıkça yazılmış.
Bu, dil modellerinin yanlış hizmet ataması yapmasını önleyen değerli bir satır.

---

## 2. İçerik çıkarılabilirlik denetimi — 92 sayfa

`ai-seo` Adım 3'ün 10 maddelik kontrolü, canlı sayfalar ayrıştırılarak uygulandı.

| # | Kontrol | Geçen | Oran |
|---|---|---|---|
| 1 | İlk paragraf doğrudan yanıt (25–90 kelime) | 51/92 | 55% |
| 2 | Bağımsız pasaj: 40–60 kelimelik paragraf var | 55/92 | 60% |
| 3 | **İstatistik + kaynak birlikte** | **0/92** | **0%** |
| 4 | Karşılaştırma tablosu | 6/92 | 7% |
| 5 | Numaralı/adımlı liste | 2/92 | 2% |
| 6 | SSS bölümü (FAQPage) | 73/92 | 79% |
| 7 | Şema işaretlemesi var | 86/92 | 93% |
| 8 | **İsimli yazar (Person)** | **0/92** | **0%** |
| 9 | **Görünür güncelleme tarihi** | **4/92** | **4%** |
| 10 | H2 başlıkları sorgu biçiminde (2+) | 45/92 | 49% |

> Veri doğrulaması: şema sayıları Faz 1 denetimiyle birebir tutuyor
> (BreadcrumbList 84, FAQPage 73, Article 46, şemasız 6). İlk taramada Cloudflare hız
> sınırı yanıtları veri sanılmıştı; tarama durum kontrolü ve throttle ile tekrarlandı.

### Asıl bulgu: blog hazır, hizmet sayfaları değil

| Sayfa tipi | n | SSS | Sorgu biçimli H2 | İdeal pasaj | Ort. kelime |
|---|---|---|---|---|---|
| **Blog** | 50 | 90% | **76%** | **80%** | 857 |
| **Hizmet** | 26 | 92% | **15%** | **38%** | 351 |
| Kurumsal | 16 | 25% | 19% | 31% | 436 |

Blog yazıları AEO açısından zaten büyük ölçüde doğru kurulmuş: soru biçimli başlıklar,
çıkarılabilir uzunlukta pasajlar, SSS bölümleri. **Hizmet sayfaları ise değil** — ticari
niyeti en yüksek 26 sayfanın yalnızca %15'i sorgu biçimli başlık kullanıyor, %38'inde
çıkarıma uygun uzunlukta pasaj var. Bu, Faz 1'deki O1 bulgusunun (hizmet sayfaları
267–350 kelime) AEO tarafındaki karşılığı.

### Pasaj uzunluk dağılımı (1.852 paragraf)

| Aralık | Sayı | Oran |
|---|---|---|
| <25 kelime — tek başına anlamsız | 1.032 | 56% |
| **25–60 kelime — çıkarım için ideal** | **791** | **43%** |
| 61–90 kelime — uzun | 28 | 2% |
| >90 kelime — çıkarılamaz | 1 | 0% |

Uzun paragraf sorunu yok; sorun ters yönde. Paragrafların yarısından fazlası 25 kelimenin
altında — dil rehberinin "kısa cümle, kısa paragraf" ilkesinin doğal sonucu. Bu insan
okuru için doğru, ama çok kısa parçalar bir AI yanıtına tek başına alınabilecek
bütünlükte olmuyor. **Rehberi bozmadan çözümü:** her bölümün ilk paragrafı tam bir yanıt
olsun, gerisi kısa kalsın.

> ### ⚠ ÖLÇÜM DÜZELTMESİ (2026-09-06, Faz 5'te bulundu)
>
> **`ai-seo`'nun 40–60 kelime bandı İngilizce için kalibre edilmiştir.** Türkçe sondan
> eklemeli olduğu için aynı bilgiyi belirgin biçimde daha az kelimeyle taşır.
>
> Yeni nöropsikolojik sayfasının birebir çevrilmiş paragraf çiftleriyle ölçüldü:
>
> | Aynı içerik | TR | EN |
> |---|---|---|
> | Açılış paragrafı | 40 kelime / 332 karakter | 53 kelime / 343 karakter |
> | "Kimlere yapılır" | 41 kelime / 329 karakter | 58 kelime / 349 karakter |
> | "Nasıl ilerliyor" | 35 kelime / 276 karakter | 44 kelime / 292 karakter |
> | "Ne veriliyor" | 36 kelime / 250 karakter | 49 kelime / 271 karakter |
>
> Karakter sayıları neredeyse aynı, kelime sayıları %25–30 farklı.
>
> **Türkçe için doğru hedef: 30–45 kelime (≈250–350 karakter).**
> İngilizce için 40–60 kelime bandı geçerliliğini koruyor.
>
> **Sonuç:** yukarıdaki "Bağımsız pasaj %60" ölçümü TR sayfaları İngilizce banda göre
> değerlendirdiği için **Türkçe çıkarılabilirliği olduğundan düşük gösteriyor.** Faz 8'de
> tekrar ölçülürken dile göre band kullanılmalı. Türkçe metni İngilizce metriğe uydurmak
> için şişirmek yapılmayacak — dil rehberine aykırı ve okura zarar verir.

### Sıfırdaki üç madde

3, 8 ve 9 numaralı kontroller sıfır ya da sıfıra yakın. Bunlar Faz 1'deki **Y1, Y2, Y3**
bulgularının aynısı ve Princeton GEO çalışmasının en yüksek etkili üç kaldıracına denk
geliyor: kaynak gösterme +%40, alıntı/yazar +%30, tazelik sinyali. **Program bu üçünü
kapatmadan AI alıntılanmasında anlamlı bir hareket beklememeli.**

---

## 3. Sorgu seti — 20 sorgu (kilitli)

Bu set `.agents/product-marketing.md` → Müşteri Dili bölümündeki birebir sorulardan
seçildi. **Set kilitli:** iki haftada bir aynı 20 sorgu ölçülür, yoksa karşılaştırma
anlamsız olur.

### Türkçe (12)

| # | Sorgu | Tip | Not |
|---|---|---|---|
| 1 | Gazimağusa'da psikolog nasıl bulunur? | yerel | ⚠ GBP'siz ulaşılamaz |
| 2 | Kuzey Kıbrıs'ta İngilizce konuşan psikolog var mı? | yerel + fark | sitenin en güçlü farkı |
| 3 | KKTC'de terapi ücretleri ne kadar? | ticari | ⚠ site ücret yayımlamıyor |
| 4 | Psikolog mu psikiyatrist mi, hangisine gitmeliyim? | karar | yazı var |
| 5 | Depresyon ile üzüntü arasındaki fark nedir? | tanım | yazı var |
| 6 | Panik atak ile kalp krizi nasıl ayırt edilir? | tanım | yazı var |
| 7 | Çocuğum okula gitmek istemiyor, ne yapmalıyım? | süreç | yazı var |
| 8 | Nöropsikolojik değerlendirme nedir, ne zaman istenir? | tanım | **rekabeti en düşük** |
| 9 | Online terapi yüz yüze terapi kadar etkili mi? | değerlendirme | yazı var, kaynaksız |
| 10 | İlk terapi seansında ne olur? | süreç | SSS var |
| 11 | Tükenmişlik mi depresyon mu yaşıyorum? | ayırt etme | yazı var |
| 12 | Sınav kaygısıyla nasıl baş edilir? | süreç | yazı var, kaynaksız |

### İngilizce (8)

| # | Sorgu | Tip | Not |
|---|---|---|---|
| 13 | English speaking psychologist in North Cyprus | yerel + fark | ⚠ GBP'siz zor |
| 14 | Therapy for international students in Famagusta | segment | ⚠ EMU-PDRAM ile yarışıyor |
| 15 | How to find a psychologist in North Cyprus | yerel | ⚠ GBP'siz zor |
| 16 | Is online therapy effective? | değerlendirme | küresel rekabet |
| 17 | Difference between a psychologist and a psychiatrist | tanım | küresel rekabet |
| 18 | Child psychologist Famagusta | yerel | ⚠ GBP'siz ulaşılamaz |
| 19 | Couples therapy North Cyprus | yerel | ⚠ hizmet sayfası yok (O7) |
| 20 | What happens in the first therapy session? | süreç | küresel rekabet |

**⚠ işaretli 7 sorgu**, kullanıcının 2026-09-02'de ertelediği Google Business Profile
kararına bağlı. Yine de sete dahil edildiler — GBP açılırsa etkinin nereden geldiği
ancak böyle görülebilir.

---

## 4. Ölçüm çizelgesi — doldurulacak

Her sorguyu dört motorda çalıştırın, iki soruyu yanıtlayın: **magusapsikoloji.com
alıntılandı mı?** ve **onun yerine kim alıntılandı?**

| # | Sorgu | ChatGPT | Perplexity | Google AI | Claude | Yerine kim alıntılandı |
|---|---|---|---|---|---|---|
| 1 | Gazimağusa'da psikolog nasıl bulunur? | | | | | |
| 2 | KKTC İngilizce konuşan psikolog | | | | | |
| 3 | KKTC terapi ücretleri | | | | | |
| 4 | Psikolog mu psikiyatrist mi | | | | | |
| 5 | Depresyon ile üzüntü farkı | | | | | |
| 6 | Panik atak / kalp krizi ayrımı | | | | | |
| 7 | Çocuğum okula gitmek istemiyor | | | | | |
| 8 | Nöropsikolojik değerlendirme nedir | | | | | |
| 9 | Online terapi etkili mi | | | | | |
| 10 | İlk terapi seansında ne olur | | | | | |
| 11 | Tükenmişlik mi depresyon mu | | | | | |
| 12 | Sınav kaygısıyla baş etme | | | | | |
| 13 | English speaking psychologist N. Cyprus | | | | | |
| 14 | Therapy international students Famagusta | | | | | |
| 15 | How to find a psychologist N. Cyprus | | | | | |
| 16 | Is online therapy effective | | | | | |
| 17 | Psychologist vs psychiatrist | | | | | |
| 18 | Child psychologist Famagusta | | | | | |
| 19 | Couples therapy North Cyprus | | | | | |
| 20 | First therapy session | | | | | |

**Doldurma kuralı:** `E` = alıntılandı (bağlantı verildi), `A` = adı geçti ama bağlantı yok,
`H` = hiç geçmedi. Son sütuna alıntılanan ilk üç kaynağı yazın.

**Tekrar ölçüm:** iki haftada bir, aynı sorgular, aynı sıra. Faz 12'de bu bir döngüye bağlanacak.

---

## 4b · Erişilebilirlik ön ölçümü (2026-09-08) — alıntı ölçümü DEĞİL

**Ne ölçüldü:** 20 sorgunun her biri için sitede o soruya doğrudan yanıt veren bir sayfa
var mı; o sayfada sorgu biçimli H2 / eşleşen SSS / çıkarılabilir pasaj bulunuyor mu.
Alıntılanmanın **ön koşulu** budur, alıntının kendisi değil.

**Neden:** Bölüm 4'teki asıl çizelge dört AI motorunda elle çalıştırma gerektiriyor ve bu
ortamdan yapılamıyor. Arz tarafı ise ölçülebilir.

**Sonuç (98 sayfa, yerel build):** 11 güçlü · 6 zayıf · 3 sinyalsiz.

### ⚠ Yöntem uyarısı — bu rakamlar ALT SINIR

Skorlayıcı, anahtar kelimelerin tek bir H2/SSS/pasajda **birlikte** geçmesini arıyor;
kelimeler başlık ile gövdeye dağılınca kaçırıyor. Elle doğrulamada 3 sinyalsizden
**ikisi yanlış negatif** çıktı:

| Sorgu | Skorlayıcı | Gerçek |
|---|---|---|
| "İlk terapi seansında ne olur?" | sinyalsiz | `/blog/terapiye-baslamadan-once.html`'de **birebir aynı SSS var** |
| "Child psychologist Famagusta" | sinyalsiz | Sayfa **başlığı** tam olarak bu; gövdede Famagusta 14 kez |
| "KKTC'de terapi ücretleri ne kadar?" | sinyalsiz | ✅ **gerçek boşluk** |

Sıralama, görünürlük ya da alıntılanma göstergesi olarak kullanılmamalı.

### Tek gerçek içerik boşluğu: ücret

Site ücret yayımlamıyor; `/terapi-sureci.html` yalnızca *"ücret bilgisi için WhatsApp veya
e-posta ile iletişime geçin"* diyor. Bu, Müşteri Dili bölümündeki birebir sorulardan biri
("Kuzey Kıbrıs'ta terapi ücretleri nasıl?") ve `ai-seo` becerisinin özellikle uyardığı durum:
ücreti okunamayan siteyi AI aracıları karşılaştırmadan eleyip okunabilir rakibi öneriyor.

**Karar gerekiyor (iş kararı, teknik değil):** ücret yayımlanacak mı, aralık mı verilecek,
yoksa "neden yayımlamıyoruz" açıkça yazılıp bu soru bir SSS olarak mı karşılanacak?
Üçüncüsü bile hiçbir şey yazmamaktan iyi — soruyu yanıtlar ve sayfa o sorgu için
erişilebilir hale gelir.

---

## 5. Rekabet manzarası (kısmi)

Web araması ABD merkezli sonuç döndürdüğü için bu **Gazimağusa'dan görülecek sonuç
değil.** Yine de dil modellerinin çekebileceği kaynak havuzu hakkında fikir veriyor.

### Adadaki doğrudan rakipler — Faz 3'e taşınacak

| Rakip | Not |
|---|---|
| Noesis Psikoloji | Lefkoşa merkezli, KKTC geneli, Gazimağusa'ya da hizmet |
| Uzman Psikolog Volkan Hoşkan | Gazimağusa'da klinik, ~20 yıl, yüz yüze + online; Google Sites + bio.link |
| Mayıs Psikoloji Kıbrıs | Lefkoşa merkezli; Girne, Gazimağusa, Güzelyurt, Lefke |
| Cyprus Central Hospital (Mağusa Tıp Merkezi) | 1995'ten beri özel hastane, psikolojik danışmanlık polikliniği |
| Pembe Köşk Online | Online psikoloji + psikiyatri, kadrosu Gazimağusa'da |

### En önemli bulgu: öğrenci segmentinde ücretsiz ve yüksek otoriteli bir rakip var

**DAÜ PDRAM** (Psikolojik Danışmanlık, Rehberlik ve Araştırma Merkezi) Gazimağusa'da,
**altı psikolog, bir psikiyatrist ve bir sosyal hizmet uzmanından** oluşuyor ve
**hizmet ücretsiz.**

Bu doğrudan sitenin en yatırım yapılmış içerik alanıyla çakışıyor: öğrenci danışmanlığı,
uluslararası öğrenci desteği, DAÜ'ye özel yazılar. Bir dil modeli
*"Famagusta'da öğrenciler için psikolojik destek"* sorusuna yanıt verirken `emu.edu.tr`
alan adını — üniversite, yüksek otorite, çok bağlantı alan — bu siteye tercih edecektir.

**Bu, öğrenci içeriğinden vazgeçmek anlamına gelmiyor.** Anlamı şu: bu segmentte
kazanılacak yer *"ücretsiz üniversite hizmetinin kapsamadığı"* alandır — süreklilik,
öğrenci olmayanlar, mezuniyet sonrası, kapasite dolduğunda, üniversite dışı gizlilik
tercihi. Bu ayrımı yapan içerik yok; Faz 4'te (`content-strategy`) ele alınacak.

---

## 6. Faz 8'e taşınanlar

- Sorgu seti kilitlendi (yukarıdaki 20).
- Çıkarılabilirlik taban çizgisi kaydedildi — Faz 8'de aynı 10 kontrol tekrar çalıştırılacak.
- **Yapılacak işin sırası netleşti:** hizmet sayfalarının AEO yapısı (sorgu-H2 %15 → hedef %70+),
  ardından Y1/Y2/Y3 (kaynak, yazar, tarih). Blog tarafında yapısal iş neredeyse bitmiş —
  oraya harcanacak süre hizmet sayfalarına gitmeli.
- Rakip listesi Faz 3'e devredildi (5 doğrudan + DAÜ PDRAM).

## Açık madde

- [ ] **20 sorguluk çizelge doldurulacak** — 4 motor × 20 sorgu, ~20 dakika. Bu yapılmadan
      programın etkisi sonradan kanıtlanamaz.
