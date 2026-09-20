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
