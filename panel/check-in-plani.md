# Seanslar arası check-in — inşa planı

Danışanın seanslar arasında haftada bir doldurduğu kısa bir check-in; terapist
danışan sayfasında zaman içindeki eğriyi görür. Amaç: portalı ölü bir login
ekranı olmaktan çıkarıp tedaviyi görünür kılan bir araca çevirmek.

## Kritik mimari karar

**Check-in formu login GEREKTİRMEZ.** Hatırlatmada tek kullanımlık token'lı bir
link gider (`/check-in/{token}`); danışan tıklar, telefonda ~30 saniyede
doldurur. Riskiest assumption doldurma oranıdır; login duvarı o oranı yarıya
böler. Login'li portal zaten var, ama bu döngü onun dışında, tokenli çalışır.

## İlk sürüm — sorular

- Ruh hali (1–10 kaydırıcı)
- Uyku (1–10)
- Kaygı (1–10)
- İsteğe bağlı tek cümle → KVKK'da hassas veri; `Crypto.php` ile şifreli saklanır
  (seans dosyasındaki desenle aynı).

PHQ-9 / GAD-7 gibi standart ölçekler ilk sürümde YOK — pilot tutarsa gelir (Faz 6).

---

## Fazlar

### Faz 0 — Karar & şema kağıt üstünde · ~0.5 gün
- [x] Soruları kesinleştir (yukarıdaki liste)
- [x] Not alanının şifreli saklanacağını doğrula (`Crypto.php`) — `encrypt()` iki
      değer döndürüyor (`ciphertext` + `nonce`), tek `note_enc` sütunu yetmez;
      şema `session_notes`/`case_files` desenini izliyor.
- [x] Hatırlatma kanalını seç (e-posta ilk sürüm). Bağlantı e-posta gitse bile
      panelde kopyalanabilir duruyor — ikinci kanalı denemek kod gerektirmesin.

### Faz 1 — Veri modeli + migration · ~0.5–1 gün
- [x] `panel/migrations/005_checkins.sql`
  - [x] `checkins` — `id, client_id, request_id, mood, sleep_quality, anxiety,
        note_ciphertext, note_nonce, created_at`
        (`sleep` → `sleep_quality`: `SLEEP()` MySQL'in kendi işlevi)
  - [x] `checkin_requests` — `id, client_id, token_hash, expires_at, sent_at,
        completed_at, created_at` (düz jeton saklanmıyor, `sha256` özeti)
- [x] `Schema::checkinsReady()` ile tablo varlığı doğrulanıyor
- [ ] `/sistem/guncelle`'den migration'ı çalıştır ← **sunucuda yapılacak**

### Faz 2 — Danışan formu (login'siz, tokenli) · ~1–2 gün
- [x] `index.php`'ye rotalar: `GET|POST /check-in/{token}` + `/check-in/tesekkurler`
      (`Router` artık `{token}`'ı string olarak geçiriyor; `{id}` yine int)
- [x] `CheckinController` (yeni)
- [x] `src/views/checkins/` + kendi düzeni (`checkin_layout.php`) — panel chrome'u yok
- [x] Token doğrulama: geçersiz / kullanılmış / süresi dolmuş / arşivlenmiş kayıt
- [ ] Linki kendi telefonuna atıp elle test et (cron'u BEKLEME) ← **sunucuda yapılacak**

### Faz 3 — Terapist eğri görünümü · ~1–2 gün
- [x] Görüşmeci sayfasına check-in bölümü (`clients/_checkins.php`), idari
      alanların önünde
- [x] Inline SVG (harici kütüphane yok) — üç ölçü **üç ayrı satırda**: kaygıda
      yukarı kötü, diğer ikisinde iyi; tek eksene çakıştırmak yönü okunmaz yapardı.
      Altında tablo görünümü + her noktada `<title>` ipucu.
- [x] `Rbac.php` matrisine `checkin.view.own` (yalnız terapist)
- [x] Görünürlük `ClientScope` üzerinden (`ClientController::find`)

### Faz 4 — Hatırlatma cron'u · ~1 gün
- [x] `panel/cron/checkins.php` — randevu hatırlatma deseninin aynısı
- [x] Haftalık job: jeton üretip `Notifications::checkinRequest` ile yolluyor
- [x] Aynı hafta doldurmuşsa / son 6 günde bağlantı almışsa tekrar gitmiyor
- [x] **Kime gider:** yalnız terapistin bir kez bağlantı gönderdiği görüşmecilere.
      "Aktif tüm danışanlar" olsaydı cron kurulduğu an bütün listeye e-posta çıkardı;
      pilot 3–4 kişiyle yürüyor.
- [x] Israr etmiyor: son dolan check-in'den beri 3 bağlantı cevapsızsa cron susuyor
- [ ] Cron'u cPanel'e ekle (`0 9 * * 1`) ← **sunucuda yapılacak**

### Faz 4b — Soruları ve gönderimi panelden yönetmek · ~0.5 gün

Pilot açılınca iki soru sahadan geldi: cümleyi kim yazıyor, ve "bu dönem bize
e-posta gelmesin" diyen aileye ne yapılıyor. İkincisinin o ana kadarki tek cevabı
kaydı arşivlemekti — takibi bitiren, geçmişi kapatan fazla ağır bir işlem.

- [x] `Rbac.php` matrisine `checkin.manage` — soru metni ve gönderim listesi;
      eğriyi okuma yetkisinden (`checkin.view.own`) **ayrı** ve yöneticide de var.
      Ayrımın yeri: cevap sağlık verisidir, soru değil. Ekranda hiçbir puan ya da
      cümle görünmüyor, yalnız ad, adres ve gönderim durumu.
- [x] Menüde kendi satırı (`/check-in-sorulari`) — yönetici bir görüşmeci
      sayfasındaki check-in bölümünü hiç görmüyor; oradan girilen bir bağlantı
      olarak kalsaydı ekran ona kapalıydı.
- [x] `panel/migrations/007_checkin_dagitim.sql` — `clients.checkin_auto`
      (varsayılan 1: göç uygulandığında bugün e-posta alan herkes almaya devam eder)
- [x] Gönderim listesi: aktif görüşmeciler, tek işaret ve durumun açıklaması
      (*sırada · bu hafta doldu · bağlantı bekliyor · susuldu · başlamadı ·
      e-posta yok · kapalı*). Liste `ClientScope` ile sınırlı.
- [x] Aynı anahtarın tekil hâli görüşmeci sayfasında — karar çoğunlukla eğriye
      bakarken veriliyor.
- [x] "Sırada" kararı tek yerde (`Checkins::due()`): cron da liste de aynı
      sorguyu okuyor. İki kopya olsaydı ekran "sırada" derken cron susabilir
      ve kimse fark etmezdi.
- [x] Anahtarın kapattığı tek şey cron: elle gönderilen bağlantı kapalıyken de
      çalışıyor, geçmiş olduğu gibi duruyor. Açma/kapama denetim kaydında
      (`checkin.auto_on` / `checkin.auto_off`).
- [ ] `/sistem`'den 007'yi uygula ← **sunucuda yapılacak**

### Faz 4c — Metinler ve soru listesi panelde · ~1 gün

İki adım, iki commit. Birincisi: formdaki **her cümle** panelden düzenlenebilir
(giriş, üç soru, ölçek uçları, cümle alanı, halka, ✦ satırı, teşekkür sayfası) —
yarısı düzenlenebilir bir form, okuyana hangi cümlenin merkeze ait olduğunu
göstermiyor, yalnız iki dil arasındaki dikişi gösteriyor.

İkincisi: soruların **sayısı** da veri oldu.

- [x] `panel/migrations/010_checkin_olcekleri.sql` — `checkin_scales` (tanım) +
      `checkin_scores` (cevap). Koddaki üç ölçek, settings'e yazılmış
      düzenlemeleriyle birlikte bir kez kopyalanıyor; geçmiş cevaplar da taşınıyor.
- [x] En fazla 6 açık soru (`Scales::MAX`). Form her kaydırıcıda uzuyor ve
      ölçtüğümüz tek şey doldurma oranı.
- [x] **Yön veri:** +1 yüksek iyi, −1 yüksek kötü. Koddan gelen üçünde kilitli —
      on iki haftalık bir eğrinin yönü değişirse geçmiş de baş aşağı okunur.
      Bu üçü silinemez, yalnız kapatılır.
- [x] Kapatılan/kaldırılan ölçeğin cevapları KALIR (`scale_key` yabancı anahtar
      değil); eğri o satırı veri bitene kadar çizer. Yeni ölçeğin eğrisi ilk
      cevaptan başlar, geçmiş haftalar sıfır sayılmaz.
- [x] Örüntülerin karşılaştırdığı ölçek `Scales::primaryKey()` — ruh hali yoksa
      yüksek değeri iyi olan ilk ölçek; kaygıyla kurulan aynı cümle ters okunurdu.
- [x] Göç uygulanmadan da çalışır: liste koddaki üç ölçekten kurulur, cevaplar
      eski sütunlara yazılır, ekran neyin eksik olduğunu söyler.
- [ ] `/sistem`'den 010'u uygula ← **sunucuda yapılacak**

### Faz 5 — Pilot · ~2–3 hafta (gerçek zaman, az kod)
- [ ] 3–4 gerçek danışanda çalıştır (görüşmeci sayfasındaki düğme döngüyü başlatır)
- [ ] Tek ölçülen sayı: doldurma oranı — **Sistem** ekranında gönderilen/doldurulan
      olarak duruyor
- [ ] İzlenecek ikinci şey: kaydırıcılar 5'te başlıyor. Gelen kayıtların çoğu
      "5-5-5" ise sayı değil varsayılan konum kaydediliyor demektir; o durumda
      kaydırıcı yerine 1–10 arası radyo düğmeleri (dokunulmadan değer üretmez).
- [ ] İyiyse → Faz 6. Kötüyse → kod değil, hatırlatma kanalını değiştir
      (ör. metne yapıştırılacak hazır WhatsApp linki)

### Faz 6 (koşullu) — PHQ-9 / GAD-7 katmanı · ~2–3 gün
- [ ] Yalnız pilot tutarsa
- [ ] Ölçek soruları + puanlama + bantlama, aynı `checkins` altyapısını genişleterek
- [ ] Terapist bir danışana açar/kapatır

---

**Toplam çekirdek geliş (Faz 0–4): ~5–7 iş günü.** Ondan sonrası kod değil, gözlem.

**Sıra notu:** Faz 1 → 2 → 3 sırayla. Cron (Faz 4) en kırılgan parça — en sona,
tek başına oturt. Formu (Faz 2) bitirir bitirmez linki elle test et.
