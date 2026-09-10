# `.agents/` — arama görünürlüğü programının belgeleri

Bu klasör SEO + AEO + GEO programının çalışma belgelerini tutuyor. Kod değil, karar ve ölçüm
kaydı. Klasör adı nokta ile başladığı için Finder'da ve çoğu editörde **gizli** —
VS Code'da `Cmd+P` ile dosya adını yazmak en hızlı yol.

## Önce şunu aç

**`search-visibility-program.md`** — kontrol paneli. Hangi fazdayız, ne bitti, ne bekliyor,
hangi kararlar verildi. Her oturumun başında okunacak tek dosya budur; gerisi onun çıktıları.

## Dosyalar

| Dosya | Ne işe yarar | Ne zaman açılır |
|---|---|---|
| `search-visibility-program.md` | **Kontrol paneli.** Faz durumu, kararlar, engeller | Her zaman, ilk |
| `product-marketing.md` | Ürün, kitle, konumlandırma, **Müşteri Dili** (26 birebir soru), marka sesi | Yeni metin yazılırken |
| `seo-audit-2026-09.md` | Faz 1 teknik denetimi — 92 sayfa, bulgular önem sırasına göre | Teknik bir sorun araştırılırken |
| `ai-gorunurluk-temel-2026-09.md` | AI görünürlük **temel ölçümü** + kilitli 20 sorgu + doldurulacak çizelge | Aşağıya bakın ⬇ |
| `icerik-stratejisi-2026-09.md` | İçerik boşluk analizi, 5 sütun, öncelik listesi | Ne yazılacağına karar verilirken |
| `kaynak-inceleme-listesi.md` | Sitedeki her istatistik iddiası + kaynağı; **psikolog onayı bekleyen liste** | Psikolog toplantısında |

Ek olarak depo kökünde **`competitor-profiles/`** var: 5 rakip profili, `_summary.md`
(yan yana karşılaştırma) ve `dau-pdram.md` (ayrı kategori — ücretsiz üniversite hizmeti).

## Sizin doldurmanız gereken tek şey

**`ai-gorunurluk-temel-2026-09.md` → bölüm 4.**

20 sorguyu ChatGPT, Perplexity, Google AI ve Claude'da tek tek çalıştırıp iki şeyi not
etmek: *magusapsikoloji.com alıntılandı mı*, ve *onun yerine kim alıntılandı*.
Kod: `E` = alıntılandı · `A` = adı geçti, bağlantı yok · `H` = hiç geçmedi. Yaklaşık 20 dakika.

**Neden gerekli:** Bu, programın "önce" fotoğrafı. 2-3 ay sonra "işe yaradı mı?" sorusuna
ancak bu kayıtla cevap verilebilir. Claude bu ölçümü yapamıyor — o motorlara oturum açamıyor.

## Psikologlara gidecek olan

**`kaynak-inceleme-listesi.md`** — tek oturuşta okunacak biçimde hazırlandı. İçinde:

- **B11** — tek klinik karar: DEHB yazısındaki "ebeveyn eğitimi çocuk terapisiyle benzer
  etkinlikte" iddiası fazla geniş, daraltılmış cümle önerisi hazır
- **Dört karar sorusu** — panik atak %11 mi %13,2 mi · anksiyete yaşam boyu mu nokta
  yaygınlık mı · DEHB yazısında kaygı verisine atıf yerinde mi · "altı yıl" rakamı geri
  gelsin mi
- **6 araştırılmamış iddia** — kaynağı henüz aranmadı

Ayrıca `product-marketing.md` sonundaki **"Açık Sorular"** bölümünde 10 madde var
(test bataryası adları, rapor teslim süresi, adli rapor düzenleniyor mu vb.).

## Değişmez kurallar

Bu programda üretilen her metin şunlara uyar:

1. **Verilmeyen hizmet için içerik yazılmaz.** EMDR bu yüzden kalıcı olarak kapsam dışı.
   Kurumsal danışmanlık ancak kapsam teyit edildikten sonra yazılacak.
2. **Klinik iddia uydurulmaz.** Kaynağı bulunamayan iddia ya kaynaklanır ya yumuşatılır.
3. **Dil ve ton rehberi geçerli** — "ruh" kelimesi kullanılmaz, "danışan" değil **birey**,
   aciliyet ve garanti dili yok. Ayrıntısı `product-marketing.md` → Marka Sesi.
4. **Ücretlerden bahsedilmez** (2026-09-08 kararı). Bunun sonucu kabul edildi: "terapi
   ücretleri" sorgularında site erişilebilir olmayacak.

## Program şu an nerede

Faz 0-4 ✅ · Faz 5 ◐ (Y1 kısmi, Y2 engelli) · Faz 6 ✅ · Faz 7 n/a (atlandı) ·
Faz 8-12 bekliyor.

**Programı tıkayan dört bilgi — hepsi merkezden gelmeli:**

| Bilgi | Neyi açar |
|---|---|
| Gerçek telefon numarası | 98 sayfada kırık WhatsApp bağlantısı (K1) — sitenin tek dönüşüm kanalı |
| Psikolog ad, unvan, lisans | Y2 → Faz 8'in tamamı. Bu grupta isimsiz tek site biziz |
| Marka görselleri (logo, og-image, favicon, apple-touch-icon) | Hâlâ 404; her paylaşımın önizlemesi boş |
| Kurumsal hizmet kapsamı | Faz 5'in son iki maddesi |

Ayrıca: `schema-markup.json` cPanel'den elle silinmeli — depodan kaldırıldı ama sunucuda
duruyor ve canlıda sahte adres/telefon servis ediyor.
