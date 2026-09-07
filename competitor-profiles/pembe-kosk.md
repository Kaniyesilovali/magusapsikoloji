# Pembe Köşk Online — Rakip Profili (ŞU AN YAYINDA DEĞİL)

**URL:** https://www.pembekoskonline.com
**Kontrol edildi:** 2026-09-06

## Durum: site kapalı

İki ayrı sorun doğrulandı:

| Kontrol | Sonuç |
|---|---|
| TLS sertifikası | **Süresi dolmuş** — Let's Encrypt R12, geçerlilik 24 May – **22 Ağu 2026** |
| `curl` doğrulamalı | `ssl_verify_result=10` (sertifika süresi doldu) |
| `http://` üzerinden | 302 → `/cgi-sys/suspendedpage.cgi` |
| Sayfa içeriği | **"Account Suspended"** |

Hosting hesabı askıya alınmış. Tarayıcıda açan herkes sertifika uyarısı görüyor;
arkasında da askıya alma sayfası var.

## Sonuç

**Aktif rakip listesinden çıkarıldı.** Web araması hâlâ eski dizin kaydını gösteriyor
(kadrosu Gazimağusa'da olan online psikoloji + psikiyatri merkezi), ama site çalışmıyor.

Bir dil modeli bu markayı eğitim verisinden hâlâ anabilir; ancak canlı tarama yapan
motorlar (Perplexity, ChatGPT arama) erişemez.

**Yeniden kontrol:** Faz 12 döngüsünde 3 ay sonra bakılsın. Geri gelirse profil çıkarılır.
