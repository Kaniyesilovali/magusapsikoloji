# Çocuğun Ekosistemi — tasarım ve inşa planı

Bronfenbrenner'dan ilham alan, seanslar arası ekolojik izleme katmanı.
Ebeveyn haftada **90 saniyeden az** vakit harcar, hiçbir şey yazmaz.
Terapist ek iş yükü almadan, semptom değil **bağlam** verisi kazanır.

> Felsefe: **Az girdi, çok klinik içgörü.**
> Bunun tasarımdaki karşılığı tek bir kural: *boş bırakılan hafta da geçerli bir
> cevaptır.* Varsayılan durum "sakin"dir; hiçbir şeye dokunmayan ebeveyn eksik
> veri değil, "bu hafta öne çıkan bir şey olmadı" verisi üretir.

---

## 0. Mimari karar: bu yeni bir ürün değil, mevcut formun ikinci sayfası

Panelde zaten şunlar var ve hepsi doğru yerde duruyor:

- `checkin_requests` — tek kullanımlık, **girişsiz** tokenli bağlantı (`sha256` özeti)
- `checkins` — mood / sleep_quality / anxiety + libsodium ile şifreli isteğe bağlı not
- `panel/cron/checkins.php` — haftalık hatırlatma, ısrar etmeyen mantık
- `clients/_checkins.php` — harici kütüphanesiz, inline SVG eğri
- `Rbac` üzerinde `checkin.view.own`, görünürlük `ClientScope`'tan

Ekosistem bunların **hiçbirini çoğaltmaz**. Yeni cron yok, yeni token yok,
yeni e-posta yok, yeni giriş yok. Ebeveyn aynı bağlantıya tıklar; üç kaydırıcıdan
sonra **tek bir ekran daha** gelir. Toplam süre 30 sn → ~75 sn.

Bu, tasarımın en önemli maddesi. Ayrı bir "ekosistem uygulaması" yapmak,
doldurma oranını (bu döngünün tek gerçek riski) baştan yarıya böler.

---

## 1. Arayüz konsepti — "Haftanın hâli"

Ebeveyne gösterilen isim: **Haftanın hâli**. (Panel içi teknik ad: *ekosistem*.)
"Ekolojik sistemler", "Bronfenbrenner", "mikrosistem" kelimelerinin hiçbiri
ebeveyn yüzünde geçmez.

### Metafor: rüzgâr

Çocuk ortada küçük, sıcak bir daire. Etrafında yaşam alanları duruyor.
Ebeveyne sorulan tek soru:

> **"Bu hafta çocuğunun sırtını hangileri itti, hangileri karşıdan esti?"**

Her alanın üç hâli var, dokunuşla döner:

| Hâl | İşaret | Anlamı | Klinik karşılığı |
|---|---|---|---|
| Sakin | `·` (varsayılan) | Bu hafta öne çıkmadı | veri yok ≠ sorun yok |
| Sırt rüzgârı | `↑` yumuşak yeşil | Destekledi, iyi geldi | koruyucu etken / kaynak |
| Karşı rüzgâr | `↓` yumuşak kehribar | Zorladı | stresör |

**Rüzgâr metaforu bilinçli bir seçim.** Hava kimsenin suçu değildir. "Karşı
rüzgâr" demek ebeveyni de çocuğu da işaret etmez; ortamı tarif eder. Aynı
şeyi "sorun alanları" diye sorsaydık, form her hafta bir kusur listesine
dönerdi — ve ebeveyn üç hafta sonra doldurmayı bırakırdı.

Kırmızı yok. Zorlayan alan kehribar; kırmızı panik ve suçluluk rengidir.

### Ekran (telefon, tek bakış)

```
┌──────────────────────────────┐
│  Bu hafta ne esti?           │
│  Dokun: ↑ iyi geldi  ↓ zorladı│
│                              │
│      ○ Uyku      ○ Okul      │
│                              │
│  ○ Ev        ( Elif )   ○ Arkadaş│
│                              │
│      ○ Büyükler  ○ Hareket   │
│                              │
│      ○ Ekran     ○ Beden     │
│                              │
│  [ + Bu hafta bir şey oldu ] │
│                              │
│      ( Bitti — 8 sn )        │
└──────────────────────────────┘
```

- Alanlar çocuğun adının etrafında serbest bir halka; **iç içe çemberler yok**
  (bkz. §2).
- Dokunma tek parmakla, kaydırma yok, yazma yok, açılır liste yok.
- "Bitti" düğmesi hiçbir şeye dokunulmasa da aktiftir.
- Sayfanın altında hiçbir sayaç, yüzde, seri (streak) veya ilerleme çubuğu yok.

### Mikro-etkileşim

Dokununca alan hafifçe merkeze **yaklaşır** (sırt rüzgârı) veya **uzaklaşır**
(karşı rüzgâr) — 200 ms, tek bir `transform`. Bu, ebeveynin "yakınlık/mesafe"
sezgisini bedensel olarak kurar ve klinik olarak da doğrudur: ekolojik etki
mesafeyle ilgilidir. CSS ile, kütüphanesiz.

---

## 2. Görselleştirme — neden iç içe çemberler değil

Bronfenbrenner'ın klasik halkaları bir **poster** için mükemmel, bir **telefon
formu** için felakettir: iç içe daireler dokunma hedefi olarak kötüdür, ekranda
küçüktür, ve en önemlisi **zaman boyutu yoktur**. Bir çocuğun ekosistemindeki
asıl klinik bilgi kesitte değil, harekettedir.

Üç ayrı görselleştirme, üç ayrı izleyici için:

### (a) Ebeveyn — "rüzgâr halkası" (haftalık, 8 sn)
Yukarıdaki ekran. Tek hafta, tek bakış, geçmiş yok. Ebeveyn kendi geçmiş
verisini burada **görmez** (gerekçe §11).

### (b) Terapist — **ekolojik şerit** (asıl ürün)
Satırlar alanlar, sütunlar haftalar, hücreler rüzgâr yönü. Üstüne mevcut
`Checkins::curve()` ile çizilen ruh hali eğrisi bindirilir:

```
            H1  H2  H3  H4  H5  H6  H7  H8  H9  H10
Ruh hali     6   7   6   4   3   5   7   8   6   4    ╮ mevcut SVG eğrisi
Okul         ·   ·   ↓   ↓   ↓   ·   ·   ·   ·   ↓
Arkadaş      ↑   ↑   ·   ·   ↓   ·   ↑   ↑   ↑   ·
Ev           ·   ·   ·   ↓   ↓   ·   ·   ·   ·   ·
Büyükler     ↑   ·   ·   ·   ↓   ↓   ·   ·   ·   ·
Uyku         ·   ·   ↓   ↓   ↓   ↓   ·   ·   ·   ↓
Hareket      ↑   ↑   ↑   ↑   ·   ·   ↑   ↑   ↑   ·
Ekran        ·   ·   ·   ↓   ·   ·   ·   ·   ·   ·
Beden        ·   ·   ·   ·   ·   ↓   ·   ·   ·   ·
Olay              ✦ nine hastalandı
```

Bu şerit tek başına seansın ilk on dakikasını kazandırır. "Sınav haftaları
Okul satırında koyu, ruh hali bir hafta sonra düşüyor" cümlesi bu tabloda
**hesaplanmadan**, gözle okunur. Mevcut `_checkins.php` deseniyle, harici
kütüphanesiz, inline SVG olarak çizilir.

### (c) Terapist — **kaynak/yük terazisi** (vaka formülasyonu için)
Tüm dönem boyunca her alanın ↑ ve ↓ toplamı, iki yönlü yatay bar. Bir bakışta:
"bu ailenin ayakta kalmasını sağlayan şey Hareket ve Arkadaş; yükü Okul ve Uyku
taşıyor." Formülasyon yazarken doğrudan kullanılır (§7).

---

## 3. Ebeveyn iki dakikanın altında nasıl kullanır

Toplam hedef **< 90 saniye**, gerçekçi dağılım:

| Adım | Süre |
|---|---|
| E-posta/WhatsApp'taki bağlantıya dokunma | 3 sn |
| Üç kaydırıcı (mevcut form) | 25 sn |
| Rüzgâr halkası | 20 sn |
| İsteğe bağlı tek cümle (çoğu hafta atlanır) | 0–25 sn |

Kuralları:

- **Giriş yok.** Mevcut tokenli mimari korunur.
- **Yazmak zorunlu değil.** Hiçbir hafta zorunlu metin alanı yoktur.
- **Bildirim tek.** Haftada bir, sabit gün/saat (`0 9 * * 1`). Hatırlatma
  tekrarı yok — mevcut cron zaten üç cevapsız bağlantıdan sonra susuyor; bu
  davranış aynen korunur.
- **Geç doldurma serbest.** Bağlantı 7 gün geçerli; "geciktin" uyarısı yok.
- **Atlanan hafta boş kalır**, kırmızı olmaz, telafi istenmez.

---

## 4. Hangi sistemler görünür

18 alanın hepsini göstermek, formu 90 saniyeden 4 dakikaya çıkarır ve doldurma
oranını öldürür. Katmanlı yapı:

### Çekirdek 8 (herkeste açık)
1. **Okul** — akademik yük, öğretmen ilişkisi, sınav dönemi
2. **Arkadaşlar** — akran ilişkileri
3. **Ev** — evin iklimi *(ebeveynlik değil — bkz. §10)*
4. **Büyükler** — büyükanne/dede ve geniş aile *(Kıbrıs'ta çekirdek, bkz. §17)*
5. **Uyku** — düzen ve rutin (sayısal uyku puanından ayrı: rutinin kendisi)
6. **Beden** — hastalık, ağrı, ilaç, iştah
7. **Hareket & oyun** — spor, dışarıda geçen zaman
8. **Ekran & dijital dünya**

### Koşullu (terapist birey sayfasından açar; en fazla +3)
- **Kardeş** — yalnız kardeş varsa
- **Bakım düzeni** — iki ev / boşanma / nöbetleşe bakım
- **İnanç & gelenek** — varsayılan **kapalı**, aile isterse
- **Dil & uyum** — göçmen/çokdilli aileler (§17)
- **Ev ekonomisi & belirsizlik** — yetişkin/ergen dosyalarında (§17)
- **Kültür & topluluk** — mahalle, dernek, cemaat

### Her zaman açık, ayrı bir çip
- **✦ Bu hafta bir şey oldu** — tek dokunuş + isteğe bağlı 5 kelime. Taşınma,
  ölüm, hastalık, ayrılık, kaza, sınav, doğum. Şeritte dikey işaret olarak
  çizilir; ekolojik okumanın en değerli tek verisi budur.

**Kural:** hiçbir bireyde aynı anda 11 alandan fazlası açık olamaz. Kod
seviyesinde sınırlanır, terapistin insafına bırakılmaz.

---

## 4.1 Alan sözlüğü — ebeveynin gördüğü tam metin

Bu bölüm §1 ve §4'ün uygulanabilir hâli: kod tarafındaki karşılığı
[`panel/src/Ecosystem.php`](src/Ecosystem.php) ve **tek kaynak orası**. Metin
değişirse orada değişir; bu tablo okunur kopyadır.

> **Psikolog onayı bekliyor.** Aşağıdaki cümleler ilk taslaktır. Değişmesi
> gereken tek şey `label` ve `hint` sütunları — anahtarlar (`domain_key`)
> sabittir, çünkü sekiz haftalık veri onlara bağlanacak. Anahtar değişirse
> geçmiş kopar.

### Ekranın üstündeki soru

> **“Bu hafta çocuğunun sırtını hangileri itti, hangileri karşıdan esti?”**
>
> Dokun: ↑ iyi geldi · ↓ zorladı · dokunmazsan “öne çıkmadı”

### Çekirdek 8 — herkeste açık

| Anahtar | Ebeveynin gördüğü ad | Altındaki açıklama |
|---|---|---|
| `okul` | Okul | ders yükü, öğretmen ilişkisi, sınav dönemi |
| `arkadas` | Arkadaşlar | akran ilişkileri, dışlanma, yeni arkadaşlık |
| `ev` | Ev | evin genel havası, gerginlik ya da huzur |
| `buyukler` | Büyükler | büyükanne, dede, geniş aile |
| `uyku` | Uyku düzeni | yatma saati, rutin, gece bölünmeleri |
| `beden` | Beden | hastalık, ağrı, iştah, ilaç |
| `hareket` | Hareket ve oyun | spor, dışarıda geçen zaman, serbest oyun |
| `ekran` | Ekran ve dijital dünya | telefon, oyun, sosyal medya — süre değil, etkisi |

Üç metin kararı, gerekçesiyle:

- **“Ev”, “Ebeveynlik” değil.** Sorulan evin iklimi. “Bu hafta nasıl tepki
  verdin?” sorusu formu haftalık bir öz-değerlendirmeye çevirir (§10).
- **“Uyku düzeni”, “Uyku” değil.** Formun birinci sayfasında zaten 1–10 arası
  bir uyku puanı var; bu alan onunla aynı şeyi sormuyor. O “nasıl uyudu”, bu
  “düzen tuttu mu”. Ayrım etikette görünmezse ebeveyn aynı soruyu iki kez
  sorulmuş sanır.
- **Ekranda süre sorulmuyor.** Dakika doğru hatırlanmaz ve sorulduğu anda
  suçluluk üretir. Sorulan tek şey o haftaki etkisi.

### Koşullu — terapist açar, ilk sürümde hepsi kapalı

| Anahtar | Ad | Açıklama | Ne zaman açılır |
|---|---|---|---|
| `kardes` | Kardeş | kardeş ilişkisi, rekabet, yakınlık | kardeş varsa |
| `bakim_duzeni` | Bakım düzeni | iki ev, nöbetleşe bakım, geçişler | ayrı ebeveynlik |
| `dil` | Dil ve uyum | yeni dil, okulda anlaşılma, yerleşme | göçmen/çokdilli aile |
| `topluluk` | Mahalle ve topluluk | komşuluk, dernek, kulüp | topluluk bağı belirginse |
| `inanc` | İnanç ve gelenek | — | **yalnız aile isterse** |
| `ekonomi` | Ev ekonomisi | geçim kaygısı, belirsizlik | ergen/yetişkin dosyası |

### Okul öncesi (3–6 yaş) — ayrı set değil, iki fark

§8 bu yaş için ayrı bir alan listesi öneriyordu. Uygulamada iki küçük fark
yetiyor ve §18'in “tek kod yolu, farklı liste” kuralına da bu uyuyor:

1. `okul` alanının **adı** “Kreş / anaokulu” olur. Ayrı bir `kres` anahtarı
   açılmaz — açılsaydı çocuk okula başladığı hafta şeritteki geçmişi ikiye
   bölünürdü.
2. `arkadas` **kapalı başlar**. Bu yaşta akran verisi zayıf.

Kalan altı çekirdek alan aynen durur. Yaş bilinmiyorsa çekirdek 8 verilir:
tahmin etmektense varsayılana dönmek, yanlış setle sekiz hafta veri
toplamaktan iyidir.

### Ergen (13+)

İlk sürümde **yok**. §8'deki alan seti (romantik ilişki, para/iş, gelecek
belirsizliği) ve paylaşım kontrolü ayrı bir fazdır; mimarisi de tersine
döndüğü için (kaydeden ergenin kendisi) aynı ekranın varyantı değil.

### Psikologlara sorulacaklar

- [ ] “Büyükler” doğru sözcük mü, yoksa “Geniş aile” mi? Kıbrıs'ta gündelik
      dilde hangisi daha az resmî duruyor?
- [ ] “Beden” alanı fazla soyut mu? Alternatif: “Sağlık ve beden”.
- [ ] `hareket` ile `ekran` aynı haftada sistematik olarak ters yönde
      işaretlenirse bu gerçek bir örüntü mü, yoksa formun kendi ürettiği bir
      yankı mı? (Pilotun bakacağı ilk şey.)
- [ ] Okul öncesinde `arkadas` gerçekten kapalı mı kalmalı — kreşteki akran
      ilişkisi bu yaşta klinik olarak okunabilir mi?
- [ ] `inanc` alanının varlığı bile — kapalı dahi olsa — terapistin ekranında
      görünmeli mi, yoksa aile açıkça isteyene kadar hiç listelenmemeli mi?

---

## 5. Ebeveyn her hafta ne kaydeder

Sadece dört şey, üçü zaten var:

1. Ruh hali, uyku, kaygı (mevcut, 1–10)
2. Alanların rüzgâr yönü (yeni, dokunmalı, varsayılan sakin)
3. İsteğe bağlı "bu hafta bir şey oldu" işareti
4. İsteğe bağlı tek cümle (mevcut, şifreli)

**Kaydetmediği şeyler** — ve bunlar bilerek dışarıda:
- Davranış sıklığı sayımı (ABC kaydı, öfke nöbeti sayısı) → ev ödevi hissi verir
- Günlük giriş → haftalık bile zor, günlük hiç tutulmaz
- Semptom ölçekleri → seansta, terapistin elinde durmalı
- Ekran süresi dakikası → doğru ölçülemez, suçluluk üretir
- Ebeveynin kendi davranışı → "nasıl tepki verdin?" sorusu formu mahkemeye çevirir

---

## 6. Örüntüler nasıl bulunur — ve nasıl bulunmuş gibi yapılmaz

**Dürüst sınır: 8–12 haftalık, n=1, haftalık ölçümle istatistik yapılamaz.**
Korelasyon katsayısı, p değeri, "makine öğrenmesi" — hepsi bu veri hacminde
gürültüyü bilgi diye sunar. Bu yüzden sistem *istatistik yapmaz*; **şeffaf,
önceden yazılmış, sayılabilir kurallar** çalıştırır.

Her kural aynı cümleyle biter: **"Bu bir bulgu değil, bakılacak bir yer."**

### Kural 1 — Eşzamanlılık
Bir alan ≥3 haftada ↓ işaretliyse, o haftaların ruh hali ortalaması ile diğer
haftaların ortalaması karşılaştırılır. Fark ≥1.5 puansa gösterilir.
> "Okul'un zorladığı 4 haftada ruh hali ortalaması 4.2; diğer 6 haftada 6.8."

### Kural 2 — Gecikmeli etki (lag-1)
Aynı hesap, **bir sonraki** haftanın ruh haliyle.
> "Uyku'nun zorladığı haftaları izleyen haftalarda ruh hali ortalama 1.9 puan düşük."

### Kural 3 — Koruyucu etken
↑ yönünde aynı hesap. **Bu kural en az stresör kuralı kadar öne çıkarılır** —
aksi hâlde sistem yalnız kusur biriktiren bir cihaza dönüşür.
> "Hareket'in desteklediği haftalarda uyku puanı ortalama 2.1 daha yüksek."

### Kural 4 — Dönemsellik
Takvimle kesişim: sınav haftaları, tatiller, yarıyıl, Ramazan/bayram, okul
dönemi başlangıcı. Yalnız ≥2 kez tekrar ederse gösterilir.

### Kural 5 — Olay çapası
✦ işaretinden sonraki 3 haftanın eğrisi, öncesindeki 3 haftayla yan yana çizilir.
Yorum yok, sadece görüntü.

### Görüntüleme kuralları (mimari olarak zorunlu)
- Örüntüler **önce terapiste** görünür. Ebeveyne otomatik hiçbir örüntü
  gösterilmez — bu, "uygulama bana çocuğumun sorunu şu dedi" felaketini
  yapısal olarak imkânsız kılar.
- Terapist bir örüntüyü **seansta konuşup** ebeveynle paylaşmayı seçebilir.
- Nedensellik dili yasak: "yol açıyor", "sebebi", "yüzünden" kelimeleri metin
  şablonlarında bulunmaz. Yalnız "birlikte gidiyor", "aynı haftalara denk geliyor".
- Kural eşiği karşılanmıyorsa **hiçbir şey gösterilmez.** Boş kutu, zayıf
  içgörüden iyidir.

Teknik olarak: 52 satırlık bir dizi üzerinde saf PHP. `Patterns.php`, sayfa
yüklenirken hesaplanır, saklanmaz.

---

## 7. Terapist bunu değerlendirme ve formülasyona nasıl bağlar

Tek entegrasyon kuralı: **terapistin zaten açtığı sayfada, zaten açtığı anda.**
Ayrı bir "ekosistem paneli" yapılırsa hiç açılmaz (§18).

### Seans öncesi — birey sayfasının en üstü
Şerit + en fazla 3 örüntü satırı, idari alanların üstünde. Terapist seansa
girmeden önceki 40 saniyede bakar. Tek eylem: bir örüntüyü **"seansta sor"**
olarak işaretlemek — bu, seans notu formuna hazır bir satır düşürür.

### Değerlendirme (ilk 4–6 hafta)
Şerit, klasik alım görüşmesinin yapamadığı şeyi yapar: **taban çizgisini
prospektif** toplar. Retrospektif ebeveyn beyanı (son iki ayda nasıldı?) hatırlama
yanlılığıyla doludur; şerit hatırlamaya değil işarete dayanır.

### Vaka formülasyonu
Kaynak/yük terazisi doğrudan formülasyona girer:
- **Sürdürücü etkenler** ← tekrar eden ↓ alanları
- **Koruyucu etkenler / kaynaklar** ← tekrar eden ↑ alanları
- **Çökeltici olaylar** ← ✦ işaretleri
- **Sistemik döngüler** ← birlikte hareket eden iki satır (ör. Ev ↓ ile Kardeş ↓
  aynı haftalarda → sistemik terapi hipotezi)

BDT tarafında: ekolojik şerit, "durum" sütununu ebeveynin hafızasından değil
kayıttan doldurur. Formülasyondaki *tetikleyici* kutusu artık boş varsayım değil.

### Tedavi planlaması
En güçlü kullanım **koruyucu etken üzerinden müdahale**: "Futbol sezonunda duygu
düzenleme daha iyi" gözlemi, semptomu azaltmaya çalışmak yerine sezon dışında
hareketi korumayı hedef yapar. Bu, ekolojik yaklaşımın klasik BDT'ye kattığı
şeyin tam olarak kendisi ve şerit onu **görünür** kılıyor.

### İlerleme takibi
Şerit, hedef alanın rengi değiştiğinde bunu gösterir — semptom skoru
değişmeden önce ekoloji değişir. Terapinin işe yaradığının erken sinyali.

---

## 8. Yaş sürümleri

### 3–6 yaş — yalnız ebeveyn
- Çocuk sisteme **hiç** dokunmaz. Bu yaşta öz-bildirim geçerli değildir ve
  ekranı çocuğa vermek, aracı oyuncağa çevirir.
- Alanlar: Ev, Büyükler, Uyku, Beden, Hareket & oyun, Kreş/anaokulu, Kardeş,
  Ekran. (Okul → "Kreş", Arkadaşlar → kapalı, bu yaşta akran verisi zayıf.)
- Ek tek soru: **"Bu hafta düzen nasıldı?"** (yemek/uyku/geliş-gidiş ritmi) —
  bu yaşta rutin, semptomdan daha iyi bir yordayıcıdır.
- Süre hedefi: 45 sn.

### 7–12 yaş — ebeveyn + çocuğun kendi 20 saniyesi
- Ebeveyn rüzgâr halkasını doldurur.
- Çocuk **ayrı bir bağlantıda** iki şey yapar (ebeveyn cevaplarını görmeden):
  1. **Haftanın havası** — 5 hava ikonu (güneşli/bulutlu/rüzgârlı/yağmurlu/fırtına).
     Tek dokunuş.
  2. **Yakınlık halkası** — 4–6 kişi/şey adı, merkeze ne kadar yakın
     hissettiğini sürükler. Bu bir **mini sosyogram**tır; oyun gibi hissettirir,
     klinik olarak zengin veridir. Ayda bir, her hafta değil.
- **Çocuğun ekranı asla "ödev" dili kullanmaz.** Doğru/yanlış yok, puan yok.
- Ebeveyn çocuğun cevaplarını **görmez** (§9). Terapist görür.

### Ergen (13+) — kayıt ergenin
Burada mimari tersine döner ve bu klinik olarak zorunludur:
- **Birincil kaydeden ergendir.** Ebeveyn girdisi ikincil ve isteğe bağlıdır.
- Ergen kendi eğrisini **görür** (küçük çocuklarda ebeveyn görmez, ergende
  kişi kendi verisini görür — özerklik ve öz-farkındalık kazanımı).
- **Paylaşım kontrolü ergende:** hangi alanların ebeveyne açık olduğunu üç
  seçenekle belirler (kapalı / yalnız genel eğri / hepsi). Varsayılan **kapalı**.
- Alan seti değişir: Okul/sınav, Arkadaşlar, Romantik ilişki, Aile, Uyku,
  Beden, Ekran & sosyal medya, Para/iş, Gelecek belirsizliği.
- Üniversite öğrencisi alt sürümü (Mağusa bağlamı, §17): Barınma, Ev
  arkadaşı, Memleket özlemi, Vize/oturum, Para, Ders yükü.

---

## 9. Kanıt-bilgili ve etik olarak sorumlu tasarım

### Kanıt tabanı (iddia edilen ve edilmeyen)
- **Dayandığı yer:** ekolojik sistem kuramı; ekolojik anlık değerlendirme (EMA)
  literatürü; rutin sonuç izleme (ROM) ve ölçüm-temelli bakımın terapi
  sonuçlarını iyileştirdiğine dair veri; koruyucu etken / dayanıklılık yazını.
- **İddia edilmeyen:** bu araç bir ölçek değildir, geçerlilik-güvenilirlik
  çalışması yapılmamıştır, hiçbir tanısal karar için kullanılmaz. Panelde ve
  onam metninde bu **açıkça** yazar.
- Standart ölçekler (SDQ, PHQ-9, GAD-7) gelecekte eklenirse **ayrı** durur ve
  ekolojik işaretlerle karıştırılmaz. (Mevcut plandaki Faz 6 ile aynı ilke.)

### Veri ve hukuk
- Alan işaretleri, çocuk kaydına bağlı olduğu an **özel nitelikli sağlık
  verisidir** (KVKK m.6 / GDPR Art. 9). Mevcut şifreleme deseni (libsodium
  secretbox, `ciphertext` + `nonce`) serbest metinler için aynen uygulanır.
- **Ayrı onam:** ekosistem modülü için, terapi onamından bağımsız, geri
  alınabilir bir onam. Geri alındığında modül kapanır ve **veri silinir** —
  arşivlenmez.
- Saklama süresi tanımlı; dosya kapandıktan sonra otomatik anonimleştirme.
- `Rbac` üzerinden yalnız birincil terapist ve süpervizör görür. `Audit`
  kaydı zaten var; ekosistem görüntülemeleri de oraya düşer.

### Güvenlik sınırı — en önemli etik madde
Bu sistem **izlenmiyor**. Ebeveynin gece 3'te işaretlediği dört karşı rüzgâr,
kimseye alarm çalmaz. Bunu formda açıkça söylemek zorundayız:

> "Buraya yazdıkların terapistine bir sonraki seanstan önce ulaşır, anında
> değil. Acil bir durumda: [kriz hattı / doğrudan telefon]"

Örtük bir "biri bakıyor" izlenimi yaratmak, bu ürünün yapabileceği en ciddi
etik hatadır. Panel tarafında terapistin panosunda sessiz bir işaret (ör. 4
ardışık düşük hafta) durabilir — ama bu **ebeveyne verilmiş bir söz değildir**
ve öyle sunulmaz.

### Çocuğun mahremiyeti
- 7 yaşından itibaren **çocuk rızası** (assent) alınır, yaşına uygun dille.
- Çocuğun kendi girdisini ebeveyn ham hâliyle **göremez**. Terapist neyin
  paylaşılacağına seansta karar verir. Bu kural teknik olarak zorlanır, politika
  olarak değil.
- Ergen paylaşım kontrolü (§8) bunun devamıdır.

---

## 10. Ebeveyn nasıl suçlanmış hissetmez

Bu bölüm süs değil; sistemin doldurma oranı buna bağlı.

**Dil denetimi — yasaklı kelimeler.** Arayüzde şu kelimeler geçmez:
*uyum (compliance), atladın, kaçırdın, eksik, tamamlanmadı, başarısız, seri
bozuldu, ihmal, sorun davranış, problemli.*

**Soru daima dünyaya sorulur, ebeveyne değil.**
- ✗ "Bu hafta öfke nöbetlerini nasıl yönettiniz?"
- ✓ "Bu hafta ev nasıl geçti?"

**"Ev" alanı asla "Ebeveynlik" diye adlandırılmaz.** Evin iklimini işaretlemek
ile kendi ebeveynliğini notlamak arasında büyük fark var; ikincisi haftada bir
kendini yargılamaya davettir.

**İki yön eşit ağırlıkta.** ↑ ve ↓ aynı boyutta, aynı belirginlikte. Yalnız
stresör kaydedilebilen bir sistem, ailenin dosyasını kusur arşivine çevirir.
Terapist görünümünde ilk gösterilen panel **kaynaklar**dır.

**Boş hafta nötr.** Doldurulmamış hafta gri bir boşluktur; kırmızı değil, uyarı
değil, "kaçırılan" değil. Şeritte "3 hafta atlandı" yazmaz.

**Normatif karşılaştırma yok.** Yaş ortalaması yok, yüzdelik yok, "diğer
çocuklar" yok. Karşılaştırma yalnız çocuğun kendi geçmişiyledir.

**Kırmızı yok.** Renk paleti sıcak ve düşük doygunluklu; kehribar en koyu ton.

**Terapist tarafında da geçerli:** örüntü metinleri asla ebeveyn davranışını
özne yapmaz. "Ev'in zorladığı haftalar" der, "ebeveyn tutarsızlığı" demez.

---

## 11. Öz-yeterlik — mükemmeliyetçilik değil

Bandura'ya göre öz-yeterliğin en güçlü kaynağı *başarı deneyimi*, ikincisi
*doğru atıf*. Tasarımın ikisini de hedeflemesi gerekir, ama dikkatli:

**Ebeveyne kendi grafiği gösterilmez** — en azından ilk sürümde. Sebep: eğriyi
gören ebeveyn onu iyileştirmeye çalışır. "Bu hafta puanı yükseltmeliyim" düşüncesi
hem veriyi bozar hem de ebeveyni haftalık bir sınava sokar. Veri toplama ile
kişisel içgörü aracı aynı ekranda olamaz.

**Bunun yerine: fark etme, seansta geri verilir.** Terapistin söylediği tek
cümle, uygulamanın gösterebileceği her grafikten güçlüdür:

> "Ocak'tan beri işaretlediklerine bakınca, futbol olan haftalarda Elif'in
> sakinleşmesi belirgin şekilde kolaylaşıyor. **Bunu sen fark ettin.**"

Öz-yeterliği kuran cümle budur — *iyi bir ebeveynsin* değil, **gözlemin işe
yaradı**. Ebeveyn kendini terapinin nesnesi değil, veri ortağı olarak konumlar.

**Seri (streak) yok, yüzde yok, rozet yok.** Bunların hepsi mükemmeliyetçilik
motorudur ve kaygılı ebeveynde (klinik popülasyonun büyük kısmı) tam ters
çalışır.

**Ebeveyne gösterilen tek geri bildirim:** gönderdikten sonra tek satır,
değişmeyen, nötr — *"Kaydedildi. Bir sonraki seansta konuşulur."*

---

## 12. Yapay zekânın rolü — ve kesin sınırları

Kullanılırsa **tek bir iş** için kullanılır: terapist birey sayfasını açtığı
anda, şeridi 3 maddelik nesir özete çevirmek. Zaman kazandırır, karar vermez.

### Girdi (kısıtlı)
- Sayısal seriler + alan işaretleri + hafta etiketleri.
- **Şifreli serbest metinler varsayılan olarak gönderilmez.** Terapist tek tek
  açabilir; bu bir onay kutusu değil, bilinçli bir eylemdir.
- Ad, doğum tarihi, iletişim bilgisi hiç gönderilmez — sadece "çocuk", yaş bandı.

### Çıktı sözleşmesi (şablonla zorlanır)
- En fazla 3 madde, madde başına en fazla 25 kelime.
- Her madde **hafta numarası içermek zorunda**. İçermiyorsa gösterilmez.
- Yasaklı: tanı adı, tanı imâsı, "olabilir/muhtemelen [bozukluk]", risk
  sınıflaması, tedavi önerisi, ilaç, "yapmalısınız", ebeveyn davranışı hakkında
  değerlendirme.
- Zorunlu kapanış: *"Bu bir özet; yorum ve karar terapiste aittir."*
- Nedensellik dili filtrelenir (§6 ile aynı liste).

### Yapısal koruma
- Özet **her zaman şeridin altındadır**, üstünde değil. Terapist önce veriyi
  görür, sonra özeti. Sıra ters olsaydı özet, gözlemi çerçevelerdi.
- Özet **saklanmaz**, dosyaya yazılmaz, seans notuna otomatik geçmez. Terapist
  isterse kopyalar ve kendi cümlesiyle yazar.
- **Ebeveyn ve çocuk yapay zekâ çıktısını hiçbir koşulda görmez.**
- Modül kapatılabilir; kapalıyken sistemin tamamı çalışmaya devam eder. Yapay
  zekâ bu üründe bir bağımlılık değil, bir kolaylıktır.

---

## 13. Oyunlaştırma — ve neden çoğu burada zararlı

**Seri, rozet, puan, seviye, günlük hatırlatma: hiçbiri olmayacak.**

Gerekçe klinik: seri mekaniği, ilgilenme davranışını *yükümlülüğe* çevirir.
Kaygılı bir ebeveynde seri bozulması suçluluk üretir; daha kötüsü, **seriyi
korumak için doldurulan hafta yanlış veridir.** Yanlış veri, veri yokluğundan
tehlikelidir — çünkü formülasyona girer. Duolingo'nun mekaniği dil öğrenmede
işe yarar; klinik ölçümde ölçtüğü şeyi bozar.

Kalması gereken üç "hafif" öğe:

1. **Kapanış ritüeli.** Gönderince tek, sakin bir animasyon (rüzgâr halkasının
   yavaşça durulması, ~1 sn). Ödül değil, nokta. Bitmişlik hissi tek başına
   sürdürülebilir bir alışkanlık kurar.
2. **Çocuk tarafında oyun, ebeveyn tarafında değil.** 7–12 yaş yakınlık halkası
   sürüklemeli ve keyifli olabilir; puanı yok, "iyi iş!" demiyor.
3. **Anlam geri bildirimi — seansta.** Tek gerçek pekiştireç, terapistin
   "geçen hafta işaretlediğin şeye bakalım" demesidir. Ebeveyn işaretlerinin
   odada kullanıldığını gördüğü an doldurmayı sürdürür; görmediği an bırakır —
   ve hiçbir rozet bunu telafi etmez.

Ekran süresi hedefi: **haftada 90 saniye.** Bu üründe artan kullanım süresi bir
başarı göstergesi değil, bir tasarım hatasıdır.

---

## 14. Mevcut uygulamalardan farkı

| | Yaygın ebeveynlik/çocuk ruh sağlığı uygulamaları | Bu |
|---|---|---|
| Odak | Semptom sıklığı, davranış sayımı | **Bağlam** — semptomun etrafındaki dünya |
| Kime ait | Uygulamaya; terapi varsa yanında durur | **Terapiye ait**; terapi dışında anlamı yok |
| Girdi | Günlük, çok soru, ödev hissi | Haftada bir, dokunmalı, 90 sn |
| Geri bildirim | Ebeveyne otomatik öneri/içerik | Ebeveyne **hiçbir otomatik yorum yok** |
| Yapay zekâ | Sohbet botu, "tavsiye" | Yalnız terapiste özet, karar yok |
| Motivasyon | Seri, rozet, bildirim | Seansta kullanılmak |
| Kuramsal temel | Genellikle yok / davranışçı ipuçları | Ekolojik sistem kuramı + ROM |
| İş modeli | Kullanım süresi | Kullanım süresini **azaltmak** |

Tek cümlelik fark: **bu bir uygulama değil, seansın uzantısı.** Terapist yoksa
çalışmaz ve çalışmaması gerekir.

---

## 15. Araştırma fırsatları

Sıralama önemli — birinci çalışma etki değil, **yapılabilirlik** olmalı.

**Çalışma 1 — Fizibilite & kabul edilebilirlik (n≈25–40 aile, 12 hafta).**
En gerçekçi ilk yayın.
- Birincil: haftalık doldurma oranı, 12 hafta sonunda devam oranı.
- İkincil: algılanan yük (ör. kısa yük ölçeği), kullanılabilirlik (SUS),
  ebeveyn/terapist nitel görüşmeleri.
- Bu tek başına yayınlanabilir ve alanda gerçekten eksik.

**Çalışma 2 — Klinik yardımcılık (nitel + karma).**
Terapistlerde: şerit vaka formülasyonunu değiştiriyor mu? Formülasyon
metinlerinin öncesi/sonrası nitel analizi + terapist görüşmeleri. Küçük örneklemle
yapılabilir, güçlü bir çalışma.

**Çalışma 3 — İdiyografik desen (SCED / çoklu temel çizgi).**
Bu veri tipine en uygun yöntem, az denekle yayınlanabilir sonuç verir.
Ekolojik değişkenlerle duygudurum arasındaki bireysel ilişkiler.

**Çalışma 4 (uzun vade) — Terapötik ittifak ve sonuç.**
- Ölçümler: SDQ (Türkçe geçerlik var), WAI-SR/kısa ittifak ölçeği, ebeveyn
  öz-yeterlik ölçeği, seans devamlılığı/erken bırakma oranı.
- Hipotez: ittifak ve devam oranı, semptom skorundan önce etkilenir.

**Kıbrıs'a özgü katkı (en özgün yayın potansiyeli):** çokdilli, bölünmüş,
geniş aile yapısının baskın olduğu bir bağlamda ekolojik haritanın **Batı
örneklemlerinden farklı** çıkması. Büyükanne/dede satırının Batı verisindeki
"geniş aile" ağırlığından belirgin şekilde yüksek olması, kendi başına
yayınlanabilir bir bulgudur.

Etik kurul, ön kayıt (preregistration) ve "araştırma verisi ≠ klinik veri"
ayrımı baştan kurulmalı.

---

## 16. Merkezin ayırt edici özelliği (USP) olarak

USP teknoloji değil. Ailenin hissettiği şey şu:

> **"Terapist bizim haftalarımızı hatırlıyor."**

Bunun pazarlama karşılığı üç cümle:
1. Seanslar arasındaki üç hafta artık kayıp değil.
2. Çocuğun sorununu değil, çocuğun dünyasını izliyoruz.
3. Bunu haftada doksan saniyede yapıyoruz.

Somut ayrışmalar:
- **Klinik**: koruyucu etken üzerinden müdahale planı — çoğu merkezin yapısal
  olarak yapamadığı şey.
- **Akademik**: yayın üretebilen bir klinik. KKTC'de üniversite kenti (DAÜ)
  bağlamında bu, hem prestij hem işbirliği (staj, süpervizyon, araştırma) demek.
- **Kurumsal**: yeni terapist geldiğinde, dosyada 6 aylık ekolojik şerit hazır
  duruyor. Devir maliyeti düşer.
- **Dürüstlük**: "verinizi satmıyoruz, yapay zekâ tanı koymuyor, kriz izlemesi
  yapmıyoruz" — bu şeffaflık, ruh sağlığı uygulamalarına güvensiz bir pazarda
  başlı başına farklılaştırıcıdır.

Dikkat: "dünyanın ilk/tek" iddiası kurulmamalı. Doğrulanamaz, meslek etiği
açısından da riskli. Doğru iddia: *"bu merkezde çalışma biçimimizin parçası."*

---

## 17. Kıbrıs bağlamı — harita neden farklı

Ekolojik model Batı çekirdek ailesi varsayımıyla çizilmiştir. KKTC'de bu
varsayım geçerli değil ve bunu düzeltmek yalnızca yerelleştirme değil, **klinik
doğruluk** meselesidir.

**Büyükanne/dede mikrosistemdir, makrosistem değil.** Birçok ailede günlük
bakımın büyük kısmını üstlenirler; okula bırakan, akşam yemeğini veren, disiplin
kuralı koyan kişidirler. Bu yüzden "Büyükler" burada çekirdek 8'in içinde ve
"Ev" kadar ağırlıklıdır. Batı şablonunda "geniş aile" başlığı altında ezilirdi
ve tam da en yüksek varyansı taşıyan satır kaybolurdu. **Klasik üçgen çatışma
(anne–büyükanne–çocuk) burada en sık sistemik formülasyondur** ve şerit onu
görünür kılar.

**Bölünmüş ada.** Bazı ailelerde geniş aile karşı tarafta, bazılarında kapı
geçişleri günlük hayatın parçası. "Kültür & topluluk" alanı bu ailelerde
kimlik, aidiyet ve pratik erişim anlamına gelir — Batı literatüründeki
"community" ile aynı şey değildir.

**Göç ve çokdillilik.** Türkiye'den, Afrika ve Asya ülkelerinden gelen ailelerde
**okul dili ≠ ev dili** başlı başına bir stresördür ve semptom gibi görünen
şeyin (dikkat, içe kapanma, akran sorunu) altında sıklıkla bu vardır. "Dil &
uyum" alanı bu aileler için açılır. Formun kendisi de en az **TR/EN** olmalı;
mevcut sitede TR/EN altyapısı zaten var.

**Üniversite kenti.** Mağusa'da danışan profilinin önemli kısmı öğrenci —
ailesinden ve kültüründen uzakta, kısa süreli kalan, farklı bir ekolojisi olan
bir grup. Onların haritasında Okul/Arkadaş değil **Barınma, Vize/oturum, Para,
Memleket özlemi, Ev arkadaşı** var (§8). Bu, aynı motorun ikinci pazarı.

**Kuşaklararası travma.** 1963–74 yerinden edilme deneyimi birçok ailede
sessizce taşınıyor. **Bu haftalık bir kutucuk olamaz** — travmayı haftalık
işaretlemeye indirgemek hem klinik olarak yanlış hem etik olarak kaba olur.
Yeri: vaka dosyasında, terapistin bir kez yazdığı aile öyküsü/soyağacı alanı.
Şeritte değil, formülasyonda durur.

**Hızlı sosyal ve ekonomik değişim.** Kur dalgalanması, elektrik kesintileri,
kira artışı — bunlar KKTC'de gerçek ve ailenin haftasını gerçekten belirleyen
şeylerdir. "Ev ekonomisi & belirsizlik" alanı yetişkin ve ergen dosyalarında
açılabilir; küçük çocuk dosyalarında **kapalı** kalır (çocuğun ekolojisine
ebeveynin finansal kaygısını yazmak, ebeveyni suçlama riskini yükseltir).

**Din/gelenek.** Varsayılan kapalı. Bazı aileler için cami/kilise ve bayramlar
haftanın ritmini kuran ana yapıdır, bazıları için hiç yoktur. Terapistin aileyle
konuşup açtığı bir alan olmalı, formda hazır bekleyen bir kutu değil.

---

## 18. Varsayımlara itiraz — dürüst bölüm

### En büyük itiraz: sıra yanlış

`check-in-plani.md`'de **Faz 5 pilotu henüz yapılmadı.** Üç kaydırıcının 3–4
gerçek aile tarafından doldurulup doldurulmadığını bilmiyorsun. Ekosistem
katmanını şimdi inşa etmek, temeli dökülmemiş binaya on ikinci katı çizmektir.

Somut olarak: eğer pilotta doldurma oranı %40 çıkarsa, sorun soru sayısı
değil kanal ya da ritimdir — ve ekosistemi eklemek o oranı **düşürür**, açıklamaz.
Eğer %80 çıkarsa, ekosistem katmanı için gerçek bir zemin var demektir.

**Öneri: pilot 3 hafta çalışmadan ekosistem için tek satır kod yazma.**
Bu plan hazır beklesin.

Ayrıca pilotta zaten izlemeyi planladığın "hepsi 5-5-5 mi geliyor" kontrolü,
ekosistemin de kaderini belirler: kaydırıcıya dokunmayan ebeveyn, sekiz alana
hiç dokunmaz.

### Klinik olarak zayıf olanlar

- **Ebeveynin ↑/↓ işareti bir ölçüm değildir.** Ruh hâline, o günkü yorgunluğuna,
  çocuğa dair anlatısına göre kayar. Buna "veri" muamelesi yapmak en büyük risk.
  Doğru çerçeve: *yapılandırılmış hatırlatıcı*, ölçüm değil. Şerit "gerçek"
  değil, "ebeveynin o hafta böyle gördüğü" demektir. Terapist görünümünde bu
  bir dipnot değil, başlığın altındaki cümle olmalı.
- **Vekil bildirim sorunu.** 3–6 yaşta çocuğun içsel durumunu tamamen ebeveyn
  raporundan okuyoruz; literatürde ebeveyn–çocuk uyumu içselleştirme
  belirtilerinde zayıftır. Yani sistem içe kapanık kaygıyı sistematik olarak
  kaçırabilir. Bunu bilerek kabul et ve iddiayı ona göre kur.
- **Yakınlık halkası (7–12) klinik olarak çekici ama en zayıf halka.** Sosyogram
  benzeri veriler yorum gerektirir, kendi başına anlam taşımaz. **İlk sürümden
  çıkar**, faz 3'e ertele.
- **Örüntü kuralları 8 haftada gürültü üretir.** Eşikleri yüksek tut, az
  göster. En iyi ilk sürüm örüntü motoru: **hiç örüntü göstermemek**, sadece
  şeridi çizmek. Göz zaten örüntüyü görüyor.

### Gereksiz iş yükü yaratanlar

- **18 alanın hepsi.** Formu dört dakikaya çıkarır, doldurma oranını yarılar.
  Çekirdek 8'e in.
- **Alan başına açıklama metni / psikoeğitim içeriği.** Yazması saatler alır,
  okunmaz.
- **Ebeveyn tarafında grafik.** Yapması iş, faydası tartışmalı, zararı gerçek (§11).
- **Yapay zekâ özeti — ilk sürümde.** Şerit varken özet yalnızca kolaylık.
  Sonraya bırak.
- **Çok kanallı hatırlatma (WhatsApp API vb.).** Mevcut "panelden bağlantı
  kopyala" düğmesi zaten yeterli; entegrasyon parası ve bakım maliyeti erken.

### Ebeveynlerin muhtemelen kullanmayacakları

- Uygulama indirme gerektiren her şey (bu yüzden tokenli web doğru karar).
- Giriş gerektiren her şey.
- Serbest metin — hafta 1'de yazılır, hafta 3'te bırakılır. İsteğe bağlı kalsın,
  asla zorunlu olmasın, doldurulmadı diye bir şey eksik sayılmasın.
- Günlük herhangi bir şey.
- Kendi geçmiş verisini incelemek. Ebeveyn ürünü açıp geçmişe bakmaz; terapistin
  göstermesiyle bakar.

### Terapistlerin gerçekçi olarak bakmayacakları

- **Ayrı bir "ekosistem" sekmesi/panosu.** Açılmaz. Şerit, birey sayfasının
  üstünde, tek tıkla açılan bir sayfada olmalı — zaten açılan sayfada.
- **Uzun otomatik raporlar.** 3 satırdan fazlası okunmaz.
- **Bildirim/uyarı sistemi.** Yanlış alarm üretir, iki hafta sonra görmezden
  gelinir, ve daha kötüsü sessiz bir "izleniyor" sözü verir (§9).
- **Terapistin haftalık veri yorumlaması istenen her akış.** Seans öncesi 40
  saniye — bütçe budur.

### Çıkarılacaklar (ilk sürümden)

1. Yakınlık halkası / sosyogram
2. Yapay zekâ özeti
3. Ebeveyn tarafı grafikler
4. Örüntü motoru (evet, en sevdiğin parça — sonraya)
5. Oyunlaştırmanın tamamı
6. Çekirdek 8 dışındaki alanlar
7. Ergen paylaşım kontrol paneli (ergen sürümü kendisi faz 2)

### Basitleştirilecekler

- Üç hâl (↑ · ↓) **yeterli**; beş kademeli şiddet ölçeği eklemeyin.
- Tek bir "✦ bir şey oldu" çipi, 12 farklı olay kategorisinden iyidir.
- Yaş sürümleri **alan setiyle** ayrışsın, ayrı arayüzlerle değil. Tek kod
  yolu, farklı `domains` listesi.
- Şerit tek görselleştirme olsun. Terazi (§2c) faz 2.

### Sonuç olarak ilk sürüm

**Mevcut check-in formunun ikinci sayfasında 8 alanlık rüzgâr halkası + birey
sayfasında ekolojik şerit. Hepsi bu.** Yaklaşık 3–4 gün iş. Geri kalan her şey
bu ikisinin gerçek ailelerde çalıştığı kanıtlandıktan sonra.

---

## Fazlar

### Faz 0 — Önkoşul (kod yok)
- [ ] `check-in-plani.md` Faz 5 pilotunu tamamla (3–4 aile, en az 3 hafta)
- [ ] Doldurma oranı ve "5-5-5" kontrolü. Oran düşükse **buraya geçme**, kanalı düzelt.

### Faz 1 — Veri modeli · ~0.5 gün
- [ ] `panel/migrations/006_ecosystem.sql`
  - `ecosystem_domains` — `client_id, domain_key, enabled, sort` (birey
    başına açık alan seti; kod tarafında en fazla 11 ile sınırlı)
  - `ecosystem_marks` — `checkin_id, domain_key, valence TINYINT` (−1 / 0 / +1)
  - `ecosystem_events` — `checkin_id, label_ciphertext, label_nonce` (✦ olayı;
    serbest metin olduğu için `Crypto` deseni)
- [ ] `Schema::ecosystemReady()` — mevcut `checkinsReady()` desenini izler
- [ ] Yaş bandına göre varsayılan alan seti (`Ecosystem::DEFAULTS`)

### Faz 2 — Formun ikinci sayfası · ~1–1.5 gün
- [ ] `checkins/form.php` içinde ikinci adım (aynı POST, aynı token, aynı CSRF)
- [ ] Dokunmalı üç hâlli alan bileşeni — JS kapalıysa radyo düğmelerine düşer
- [ ] Hiçbir alana dokunmadan gönderim geçerli
- [ ] Kendi telefonunda elle test

### Faz 3 — Ekolojik şerit · ~1–1.5 gün
- [ ] `clients/_ecosystem.php`, `_checkins.php` ile aynı inline SVG kuralı
- [ ] Mevcut ruh hali eğrisi şeridin üstüne bindirilir
- [ ] Başlık altında sabit cümle: *"Bunlar ölçüm değil, ebeveynin o haftaki
      işaretidir."*
- [ ] Terapistin alan setini açıp kapadığı küçük form
- [ ] `Rbac` — mevcut `checkin.view.own` yeniden kullanılır, yeni izin yok

### Faz 3b — Halkayı dosyaya uyarlamak · ~0.5 gün

Sözlük ortak dili tutuyor ama tek bir evin dilini tutmuyordu. Üç gerçek durum:
"Büyükler" bir evde nine ve dede, başka bir evde yalnız "Babaanne" — ebeveyn
kendi hayatındaki adı görmezse alanı boş geçiyor. Sözlükte karşılığı olmayan
şeyler ("Dans kursu", "Babanın nöbetleri") için kodda alan açmak her aileye bir
sürüm demek. Ve ergen dosyasında sorunun kendisi yanlış kişiye sesleniyordu:
"çocuğunun sırtını" cümlesi formu kendisi dolduran on yedi yaşındakine
yazılmamıştı.

- [x] `panel/migrations/008_ekosistem_metinleri.sql` — `ecosystem_domains.label`
      + `.hint` (dosyaya uyarlanmış metin), `clients.checkin_prompt` (o dosyanın
      sorusu). Hepsi NULL = varsayılan.
- [x] Sözlük **kodda kalıyor**: tablo yalnız sapmayı tutuyor, böylece ileride
      düzeltilen bir ipucu uyarlanmamış dosyalarda kendiliğinden görünüyor.
      Varsayılanla birebir aynı yazılan metin kaydedilmiyor (`saveDomains`).
- [x] Elle eklenen alanlar: dosya başına en fazla 4 (`Ecosystem::MAX_CUSTOM`,
      `ozel1…ozel4`). `MAX_OPEN` ortak — dört özel alan açan dosyada sözlükten
      yedi alan kalır. Adı silmek alanı kaldırır; geçmiş işaretleri şeritte
      "Elle eklenen alan" başlığıyla durmaya devam eder, veri silinmez.
- [x] Halkadaki çipin kısa adı uyarlanmış addan türetiliyor (`shorten`) —
      elle yazılmış kısa ad yalnız sözlük adı aynen duruyorsa kullanılıyor.
- [x] Şeridin satır başlıkları da dosyanın kendi adlarıyla (`strip` artık
      birey kimliğini alıyor): terapist ebeveynin gördüğü kelimeyi görmeli,
      yoksa iki ekran aynı haftayı iki dille anlatır.
- [x] Uyarlama ekranı: birey sayfası → "Sorulan alanlar". Yetki değişmedi
      (`checkin.view.own`) — bu klinik bir karar, idari ayar değil.
- [x] Uyarlanmış metin denetim kaydına YAZILMAZ: bir ailenin hayatından bir
      ayrıntı olabiliyor. Kimin ne zaman değiştirdiği yeter.
- [ ] `/sistem`'den 008'i uygula ← **sunucuda yapılacak**

### Faz 4 — Pilot · 6–8 hafta, kod yok
- [ ] Aynı 3–4 aile + 2 yeni
- [ ] Ölçülen: ikinci sayfa gönderim oranı (birinci sayfaya göre düşüş) ve
      terapistin şeridi seansta gerçekten kullanıp kullanmadığı
- [ ] Düşüş %15'ten fazlaysa alan sayısını 8'den 5'e indir

### Faz 5+ (koşullu, sırayla)
- [ ] Örüntü kuralları (`Patterns.php`) — yalnız 12+ haftalık veri biriktiğinde
- [ ] Kaynak/yük terazisi
- [ ] Ergen sürümü + paylaşım kontrolü
- [ ] Yapay zekâ özeti (§12 sözleşmesiyle)
- [ ] 7–12 yaş çocuk ekranı
- [ ] Üniversite öğrencisi alan seti

**Faz 1–3 toplamı: ~3–4 iş günü.** Ondan sonrası yine kod değil, gözlem.
