# Mağusa Psikoloji Merkezi — içerik kuralları

Bu dosya, depoda üretilen **her metin** için bağlayıcıdır: blog yazıları, hizmet sayfaları,
SSS cevapları, meta açıklamalar, panel/CMS üzerinden girilen içerik ve bunlar için yapılan
her araştırma.

Programın geri kalanı `.agents/` altında: durum panosu `search-visibility-program.md`,
marka sesi `product-marketing.md`, mevcut iddiaların kaynak kaydı `kaynak-inceleme-listesi.md`.

---

## 1 · Kaynak kuralı

**Yalnız aşağıdaki 26 kurum kaynak olarak kullanılır.** Liste dışında hiçbir kaynağa
dayanarak iddia yazılmaz — blog, haber sitesi, içerik çiftliği, kişisel site, yapay zekâ
çıktısı, "genel bilgi" ve **liste dışı dergi makalesi** dahil.

Bir iddianın arkasında bu 26 kurumdan birinin yayını yoksa:

1. Önce kurumların kendi sayfalarında aranır (NICE kılavuzları, WHO bilgi notları,
   Cochrane özetleri ve APA/AACAP materyalleri çoğu konuyu kapsıyor).
2. Bulunamazsa **iddia metne girmez.** Sayı, oran, "araştırmalar gösteriyor" kalıbı ve
   etkililik ifadesi olmadan da yazılabiliyorsa cümle kaynaksız hâliyle yumuşatılarak yazılır;
   yazılamıyorsa o bölüm çıkarılır.
3. Liste dışı bir kaynağın gerçekten gerektiği düşünülüyorsa **sorulur** — sessizce eklenmez.

Adresler 2026-09-20'de tek tek doğrulandı.

### Meslek örgütleri ve standartlar
| # | Kurum | Adres |
|---|---|---|
| 1 | American Psychological Association (APA) | apa.org |
| 2 | European Federation of Psychologists' Associations (EFPA) | efpa.eu |
| 3 | European Association of Clinical Psychology and Psychological Treatment (EACLIPT) | eaclipt.org |
| 4 | European Association for Psychotherapy (EAP) | europsyche.org |
| 5 | Türk Psikologlar Derneği (TPD) | psikolog.org.tr |
| 6 | International Test Commission (ITC) | intestcom.org |

### Kanıt, kılavuz ve araştırma
| # | Kurum | Adres |
|---|---|---|
| 7 | World Health Organization (WHO) | who.int |
| 8 | National Institute for Health and Care Excellence (NICE) | nice.org.uk |
| 9 | Cochrane | cochrane.org |
| 10 | Society for Psychotherapy Research (SPR) | psychotherapyresearch.org |

### Terapi yaklaşımları
| # | Kurum | Adres |
|---|---|---|
| 11 | Beck Institute for Cognitive Behavior Therapy | beckinstitute.org |
| 12 | European Association for Behavioural and Cognitive Therapies (EABCT) | eabct.eu |
| 13 | Schema Therapy Society (ISST) | schematherapysociety.org |
| 14 | Association for Contextual Behavioral Science (ACBS) | contextualscience.org |
| 15 | European Family Therapy Association (EFTA) | europeanfamilytherapy.eu |

### Psikodinamik
| # | Kurum | Adres |
|---|---|---|
| 16 | European Federation for Psychoanalytic Psychotherapy (EFPP) | efpp.org |
| 17 | International Psychoanalytical Association (IPA) | ipa.world |
| 18 | Anna Freud | annafreud.org |
| 19 | Tavistock and Portman NHS Foundation Trust | tavistockandportman.nhs.uk |

### Travma
| # | Kurum | Adres |
|---|---|---|
| 20 | International Society for Traumatic Stress Studies (ISTSS) | istss.org |
| 21 | European Society for Traumatic Stress Studies (ESTSS) | estss.org |

### Çocuk, ergen ve gelişim
| # | Kurum | Adres |
|---|---|---|
| 22 | European Society for Child and Adolescent Psychiatry (ESCAP) | escap.eu |
| 23 | American Academy of Child & Adolescent Psychiatry (AACAP) | aacap.org |
| 24 | Society for Research in Child Development (SRCD) | srcd.org |
| 25 | World Association for Infant Mental Health (WAIMH) | waimh.org |
| 26 | Center on the Developing Child (Harvard) | developingchild.harvard.edu |

---

## 2 · Atıf biçimi

- Atıf **metnin içinde**, iddiayı taşıyan cümlenin yanında verilir — sayfa sonunda toplu
  kaynakça değil.
- Biçim: kurum adı + varsa yıl + **doğrudan sayfaya bağlantı**. Ana sayfaya değil,
  iddianın geçtiği belgeye.
- Bağlantı **düz metin** olarak verilir. **Kurum logosu kullanılmaz** — çoğu kurum izinsiz
  kullanımı yasaklıyor, ayrıca logo dizisi onay izlenimi bırakıyor.
- Elle yazılan HTML gövdede dış bağlantılar `target="_blank" rel="noopener noreferrer"`
  ile açılır. Kart/`metin:` alanlarındaki markdown bağlantılara renderer bu öznitelikleri
  **eklemiyor** — site genelinde böyle (mevcut DOI bağlantıları da öyle). Tek tek elle
  düzeltilmez; istenirse markdown renderer'ında bir kez çözülür.
- SSS cevapları `{{ item.a }}` ile basılıyor, **markdown render edilmiyor**: oralarda kaynak
  düz metinle yazılır ("NICE'in 2022 kılavuzuna göre"), bağlantı verilemez. Bağlantı şart
  olan bir iddia SSS'ye değil, sayfa gövdesine yazılır.

## 3 · Üyelik ve onay izlenimi yasağı

Merkez bu kurumların çoğunun üyesi değil. Hiçbir metin aksini ima etmez.

- Başlık daima **"Yararlandığımız Bilimsel Kaynaklar"** kalıbındadır.
  "Referanslarımız", "İş Birliklerimiz", "Üyeliklerimiz", "Onaylı" **yasak**.
- "X kurumuna göre" yazılır; "X kurumu ile çalışıyoruz", "X standartlarına sahibiz" yazılmaz.
- Tek istisna: `hakkimizda` sayfasındaki **gerçek** eğitim ve onay bilgisi
  (Beck Institute ve TPD onaylı BDT eğitimleri — Yaprak Parlan Yeşilovalı). Bu bir kaynak
  atfı değil, kişisel özgeçmiş bilgisidir ve teyitlidir.

## 4 · Mevcut istisnalar — psikolog kararı bekliyor

Kural yürürlüğe girmeden önce yayımlanmış, doğrulanmış **dört dergi atfı** canlıda. Hepsi
`.agents/kaynak-inceleme-listesi.md`'de teyit edildi ama hiçbiri bu 26 kurumun yayını değil:

| Sayfa | Atıf | Durum |
|---|---|---|
| `/blog/online-terapi-etkili-mi` · `/blog/kuzey-kibris-online-terapi` · `/hizmetler/online-terapi` · `/blog/terapi-dili-turkce-ingilizce` | Carlbring ve ark. (2018), *Cognitive Behaviour Therapy* | Doğrulandı |
| `/blog/online-terapi-etkili-mi` · `/blog/kuzey-kibris-online-terapi` · `/hizmetler/online-terapi` · `/blog/terapi-dili-turkce-ingilizce` | Fernandez ve ark. (2021) | Doğrulandı |
| `/blog/cocuk-psikologu-magusa` | Polanczyk ve ark. (2015), *JCPP* | Doğrulandı |
| `/blog/sinav-kaygisiyla-bas-etme` | Brooks (2014), *J Exp Psychol Gen* | Doğrulandı |
| `/blog/sinav-kaygisiyla-bas-etme` | Latimier ve ark. (2021), *Educational Psychology Review* | **Hiç incelenmedi** |

**Bunlara dokunulmadı.** Karar psikologlarındır: ya kural bu beşini kapsayacak şekilde
gevşetilir, ya atıflar 26'lık listeden eşdeğer bir kaynakla değiştirilir, ya da iddialar
kaldırılır. Karar verilene kadar **yeni metinlerde bu tarz dergi atfı kullanılmaz.**

Latimier 2021 (aralıklı tekrarın tek gecede çalışmaya üstünlüğü) `kaynak-inceleme-listesi.md`'de
hiç geçmiyor — yani ne doğrulandı ne de reddedildi. İncelenmesi gereken ilk madde bu.

## 4b · Kurala göre düzeltilen içerikler — 2026-09-20

Kural konduğu gün mevcut metinler tarandı ve **15 yerde** (TR + EN eşleri) düzeltme yapıldı.
WHO rakamları `who.int` bilgi notlarından tek tek okunarak güncellendi:

| Sayfa | Neydi | Ne oldu |
|---|---|---|
| `anksiyete-nedir` / `what-is-anxiety` | "4 kişiden 1'i yaşam boyu anksiyete bozukluğu (WHO)" | WHO'nun **"anksiyete bozukluğu olanların 1/4'ü tedavi alıyor"** cümlesi yaygınlık sanılmış. 470 milyon kişi / %5,8 (2023) ile değiştirildi |
| `kaygi-bozuklugu-nedir` / `what-is-anxiety-disorder` | "284 milyon (WHO, 2019)" | 284 milyon WHO'nun değil, eski GBD rakamı. 470 milyon / %5,8 |
| `depresyon-nedir` / `what-is-depression` | "280 milyon (WHO, 2023)" | Güncel WHO bilgi notu: 322 milyon, yetişkinlerin %5,2'si |
| `panik-atak-belirtileri` / `panic-attack-symptoms` | "%11 panik atak · %2-3 panik bozukluğu · kadınlarda iki kat (APA, 2022)" | **"(APA, 2022)" doğrulanamadı.** Rakamlar kaldırıldı; WHO'nun verdiği anksiyete rakamı ve "kadınlar daha çok etkileniyor" ifadesiyle sınırlandı |
| `magusa-uluslararasi-ogrenciler` / `mental-health-international-students` | "2023 tarihli bir meta-analiz: her 3 öğrenciden 1'i" | Adsız meta-analiz ve oran kaldırıldı; merkezin kendi gözlemine dayanan nitel anlatım |
| `kuzey-kibris-online-terapi` · `online-terapi-etkili-mi` (+ EN) | "Çok sayıda araştırma ve meta-analiz doğruladı" | Sitenin fiilen atıf verdiği iki meta-analize (Carlbring, Fernandez) bağlandı — iddia kanıttan geniş duruyordu |
| `cocuk-psikologu-magusa` · `tukenmislik-sendromu` (+ EN) | WHO adı geçiyor, bağlantı yok | WHO bilgi notu ve ICD-11 duyurusuna bağlandı |

**Not:** "%11 panik atak" `kaynak-inceleme-listesi.md`'de zaten açık karar sorusuydu
("%11 mi %13,2 mi"). Rakam kaldırıldığı için o soru şimdilik konusuz kaldı; psikologlar
doğrulanmış bir kaynak verirse geri eklenebilir.

### İkinci geçiş — NICE ve Cochrane

İlk geçiş yalnız WHO kullanmıştı. İkinci geçişte listedeki diğer kaynaklar tarandı ve
**tedavi/etkililik** iddiaları da kaynaklandı:

| Sayfa | Neydi | Ne oldu |
|---|---|---|
| `anksiyete-nedir` · `kaygi-bozuklugu-nedir` (+ EN) | "Kaygı bozuklukları için **en güçlü kanıta sahip** yöntem BDT'dir" | NICE'ın fiilen dediğine indirildi: CG113'te yaygın kaygı bozukluğu ve panik bozukluğu için **önerilen yüksek yoğunluklu psikoterapi** BDT |
| `hizmetler/bdt-terapisi` / `services/cbt-therapy` | "**yüzlerce araştırmayla** desteklenmiş" · "supported by **hundreds of clinical trials**" | Sayı iddiası kaldırıldı; NICE CG113 + NG222 referansı kondu |
| `kuzey-kibris-online-terapi` · `online-terapi-etkili-mi` (+ EN) | Yalnız liste dışı Carlbring/Fernandez'e dayanıyordu | **Cochrane** (Flodgren ve ark., 2015 — yedi çalışmada görüntülü ve yüz yüze terapi arasında fark yok) eklendi; liste içi bir dayanak kazandı |

**Erişim notu:** APA ve NICE'ın CKS sayfaları bu makineden okunamıyor (bot duvarı, 403).
APA'ya dayandırılacak bir iddia çıkarsa tarayıcıdan elle doğrulanmalı.

## 4c · Hâlâ kaynaksız duran etkililik iddiaları

Taramada çıkan ama bu turda **düzeltilmeyen** maddeler. Her birinin karşısında 26'lık
listeden hangi kurumun karşılayabileceği yazılı:

| Sayfa | İddia | Muhtemel kaynak |
|---|---|---|
| `okul-fobisi-cocuk` / `school-refusal-in-children` | "Okul reddinde **en güçlü kanıta sahip** yaklaşımdır" | AACAP (Facts for Families) · ESCAP |
| `dehb-belirtileri-cocuk` / `what-is-adhd-symptoms-in-children` | Ebeveyn eğitimi "**first-line**, özellikle 6 yaş altı" | NICE NG87 · AACAP |
| `hizmetler/act-terapisi` | "ACT'nin **en etkili olduğu** durumlar" | ACBS |
| `universite-ogrencileri` · `magusa-uluslararasi-ogrenciler` (+ EN) | "Terapi **en erken** başlandığında en etkili sonucu verir" | Kaynak bulunamayabilir → yumuşatılmalı |
| `depresyon-nedir` | "Orta-ağır depresyonda ilaç + terapi kombinasyonu **genellikle en etkili**" | NICE NG222 |

§4c kapatıldı — aşağıdaki §4d'ye bakın.

Ayrıca `kaynak-inceleme-listesi.md`'deki **B11** (DEHB'de ebeveyn eğitimi iddiası) ve
**6 araştırılmamış madde** hâlâ açık.

## 4d · 26 kaynağın tamamının değerlendirmesi — 2026-09-20

Listedeki her kurum sitedeki mevcut içerikle tek tek karşılaştırıldı. Sonuç:

### Fiilen kullanılanlar (3)

| # | Kurum | Nerede |
|---|---|---|
| 1 | **WHO** | Anksiyete (470 mn / %5,8), depresyon (322 mn / %5,2), ergen yaygınlığı (7'de 1), ICD-11 tükenmişlik — 12 bağlantı |
| 8 | **NICE** | CG113 (kaygı/panikte BDT), NG222 (ağır depresyonda BDT+ilaç birleşimi ilk sırada), NG87 (5 yaş altı DEHB'de ilk basamak ebeveyn eğitimi) — 5 bağlantı + SSS'lerde düz metin |
| 9 | **Cochrane** | Flodgren ve ark. 2015 — görüntülü terapi ile yüz yüze terapi arasında fark yok; online terapi sayfalarının 4 yerinde |

### Erişilemediği için kullanılamayanlar (2)

| # | Kurum | Sorun |
|---|---|---|
| 1 | **APA** | Sayfalar bu makineden okunmuyor (200 dönüyor ama gövde boş — JS/bot duvarı). Panik atak rakamları bu yüzden geri getirilemedi |
| 23 | **AACAP** | Bağlantı kurulamıyor (DNS çözülüyor, 80 ve 443 zaman aşımı). Okul reddi ve DEHB'de ebeveyn materyali için ilk aday; tarayıcıdan elle kontrol gerekiyor |

### Şu an karşılayacak iddia bulunmayanlar (21)

Bunlara zorla atıf iliştirilmedi — kaynak göstermek değil süs olurdu:

- **EFPA, EACLIPT, EAP, TPD, ITC** (2-6) — meslek/etik/test standartları. Sitede bu
  standartlara *uyulduğunu* söyleyen bir cümle yok; eklemek **§3'e aykırı olurdu**
  (uyum/onay izlenimi). Nöropsikolojik değerlendirme sayfası testleri betimliyor ama
  merkezin ITC standartlarına uyduğu iddiası teyitsiz — bu yüzden eklenmedi.
- **SPR** (10) — psikoterapi süreç/sonuç araştırması. Karşılığı olacak cümle yok.
- **Beck Institute, EABCT** (11-12) — BDT'nin kurumsal kaynağı. `hakkimizda`'da zaten
  **gerçek** eğitim bilgisi olarak geçiyor (Yaprak Parlan Yeşilovalı); kaynak atfı olarak
  ayrıca kullanılmadı.
- **Schema Therapy Society, EFTA** (13, 15) — merkezde şema terapi ve sistemik aile
  terapisi ayrı hizmet olarak tanıtılmıyor.
- **ACBS** (14) — ACT hizmet sayfasındaki "en etkili olduğu durumlar" başlığı "sık
  kullanıldığı durumlar" olarak düzeltildi; artık etkililik iddiası yok, atıf gerekmiyor.
- **EFPP, IPA, Anna Freud, Tavistock** (16-19) — psikodinamik hizmet sayfası yaklaşımı
  betimliyor, etkililik iddiası taşımıyor.
- **ISTSS, ESTSS** (20-21) — travma sayfaları tarandı: **tek bir kanıt/etkililik iddiası
  yok.** Travma içeriği derinleştirilirse ilk kaynak bunlar olacak.
- **ESCAP, SRCD, WAIMH, Center on the Developing Child** (22, 24-26) — çocuk/gelişim
  içeriği şu an yaygınlık (WHO) ve tedavi (NICE) üzerinden kaynaklı. Bağlanma, erken
  çocukluk ve gelişim yazıları yazılırsa devreye girecekler.

**Sonuç:** 26 kaynağın 3'ü kullanıldı, 2'si teknik olarak erişilemedi, 21'i mevcut
içerikte karşılığı olmadığı için beklemede. Bu 21'i kullanmanın yolu yeni içerik yazmak —
var olan cümlelere iliştirmek değil.

## 5 · Yıllık bakım

Bağlantı listesi **yılda bir** kontrol edilir; kurum adları ve adresleri değişiyor
(ISST → Schema Therapy Society gibi). Kontrol edilecek yerler: bu dosya,
`/kaynaklar.html` ve `/en/sources.html`.

- Son doğrulama: **2026-09-20**
- Sıradaki: **2027-09-20**

## 6 · Bu kuraldan önce gelen kurallar

`.agents/README.md` → "Değişmez kurallar" bölümü aynen geçerli:

1. **Verilmeyen hizmet için içerik yazılmaz** (EMDR kalıcı olarak kapsam dışı).
2. **Klinik iddia uydurulmaz.**
3. **Dil ve ton rehberi** — "ruh" kelimesi kullanılmaz, "danışan" değil **birey**,
   aciliyet ve garanti dili yok.
4. **Ücretlerden bahsedilmez** (2026-09-08 kararı).
