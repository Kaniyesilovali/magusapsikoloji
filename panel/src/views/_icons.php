<?php
/**
 * Daraltılmış menünün işaretleri.
 *
 * Hepsi 16×16 ızgaraya, panelin kendi çizgi diliyle çizildi: 1.3 kalınlık,
 * yuvarlak uçlar, dolgu yok. Dolgu yalnız bir şeyin *durumunu* söylediği yerde
 * var (bugünün noktası, müsaitlik bandı, kayıt satırlarının madde imleri) —
 * panelin geri kalanında da dolgu aynı işi yapıyor.
 *
 * İki takvim bilerek birbirine benziyor: "Bugün" tek bir günü işaretler
 * (nokta), "Müsaitlik" bir kuralı (bant) — ekrandaki .rail-band'in ta kendisi.
 *
 * Ayarlar için dişli yerine kaydırıcı: dişli her panelde aynı, kaydırıcı ise
 * bu panelde gerçekten var olan bir şey (check-in formundaki .ci-range).
 *
 * Boyutlar CSS'te (.nav-icon); buradan yalnız yol veriliyor. Dolgu isteyen
 * parçalar .ic-fill taşır — CSP inline style'a izin vermiyor.
 *
 * @return array<string,string>
 */
return [
    // Takvim yalnız burada: "bugün" işaretlenmiş tek gün. Müsaitlik de takvim
    // olsaydı ikisi 18 pikselde birbirine karışırdı, o yüzden orası bantlara
    // bırakıldı.
    'bugun' => '
        <rect x="2" y="3.2" width="12" height="10.8" rx="2.2"/>
        <path d="M2 6.6h12"/>
        <path d="M5.4 1.9v2.2M10.6 1.9v2.2"/>
        <circle class="ic-fill" cx="8" cy="10.4" r="1.35"/>',

    'randevular' => '
        <circle cx="8" cy="8" r="5.9"/>
        <path d="M8 4.6V8.2l2.5 1.5"/>',

    'gorusmeciler' => '
        <circle cx="8" cy="5.8" r="2.6"/>
        <path d="M3.3 13.7a4.7 4.7 0 0 1 9.4 0"/>',

    // Çalışma bantları: ekranda müsaitlik zaten böyle çiziliyor (.rail-band).
    // Bantların boyu farklı, çünkü söyledikleri şey "her gün aynı değil".
    'musaitlik' => '
        <rect x="2" y="2.6" width="12" height="10.8" rx="2.2"/>
        <rect class="ic-fill" x="4.3" y="5.3" width="7.4" height="2.1" rx="1.05"/>
        <rect class="ic-fill" x="4.3" y="9.1" width="4.5" height="2.1" rx="1.05"/>',

    'odemeler' => '
        <rect x="1.6" y="4.2" width="12.8" height="7.6" rx="1.9"/>
        <circle cx="8" cy="8" r="1.9"/>',

    // Rapor: üç yükselen çubuk. Pasta dilimi denendi ama 16 pikselde üç dilim
    // birbirine giriyor; çubuklar hem daha okunur hem "aylık" fikrini taşıyor.
    'raporlar' => '
        <line x1="3.6" y1="12.4" x2="3.6" y2="9.2"/>
        <line x1="8" y1="12.4" x2="8" y2="5.6"/>
        <line x1="12.4" y1="12.4" x2="12.4" y2="7.6"/>',

    // Check-in: ekranın kendisinde çizilen şey — noktalı bir eğri (.ci-line +
    // .ci-dot). "Raporlar"ın dik çubuklarıyla karışmıyor, çünkü biri toplamı
    // biri gidişatı anlatıyor.
    'checkin' => '
        <path d="M2.7 11.4L6.2 7.9l3.3 2.1 3.8-5.3"/>
        <circle class="ic-fill" cx="6.2" cy="7.9" r="1.1"/>
        <circle class="ic-fill" cx="9.5" cy="10" r="1.1"/>',

    // Sitenin sayfası. Çerçeve çizilseydi "Müsaitlik" kutusuyla aynı gövdeyi
    // paylaşırdı; kıvrık köşe onu tek başına ayırıyor.
    'icerik' => '
        <path d="M9.1 1.9H4.6a1.8 1.8 0 0 0-1.8 1.8v8.6a1.8 1.8 0 0 0 1.8 1.8h6.8a1.8 1.8 0 0 0 1.8-1.8V5.7z"/>
        <path d="M9.1 1.9v3.8h4.1"/>
        <path d="M5.8 9.1h4.4M5.8 11.3h2.8"/>',

    'onam' => '
        <path d="M8 1.9l4.9 1.9v4.1c0 3-2.1 4.9-4.9 5.8-2.8-.9-4.9-2.8-4.9-5.8V3.8z"/>',

    'kullanicilar' => '
        <circle cx="6.2" cy="5.9" r="2.4"/>
        <path d="M2.2 13.4a4.1 4.1 0 0 1 8 0"/>
        <path d="M11.1 4a2.4 2.4 0 0 1 0 3.8"/>
        <path d="M12.1 9.6a4.1 4.1 0 0 1 1.7 3.3"/>',

    'kayitlar' => '
        <circle class="ic-fill" cx="3.3" cy="4.6" r="1.05"/>
        <circle class="ic-fill" cx="3.3" cy="8" r="1.05"/>
        <circle class="ic-fill" cx="3.3" cy="11.4" r="1.05"/>
        <path d="M6.3 4.6h7.4M6.3 8h7.4M6.3 11.4h4.9"/>',

    'sistem' => '
        <path d="M2.4 5.3h2.1M8 5.3h5.6"/>
        <circle cx="6.25" cy="5.3" r="1.7"/>
        <path d="M2.4 10.7h5.3M11.2 10.7h2.4"/>
        <circle cx="9.45" cy="10.7" r="1.7"/>',

    // Hesabın kendisi. "Görüşmeciler"in kişisi çerçevesiz duruyor; bu daire
    // içinde — biri listedeki bir insan, öbürü giriş yapmış olan sen.
    'profil' => '
        <circle cx="8" cy="8" r="6.1"/>
        <circle cx="8" cy="6.3" r="1.9"/>
        <path d="M4.5 12.8a3.7 3.7 0 0 1 7 0"/>',

    'cikis' => '
        <path d="M9.4 2.7H4.3a1.7 1.7 0 0 0-1.7 1.7v7.2a1.7 1.7 0 0 0 1.7 1.7h5.1"/>
        <path d="M11 5.5L13.5 8 11 10.5M13.5 8H6.5"/>',

    // Tanıtım turu: soru işareti ya da ampul değil, turun ekranda çizdiği
    // şeyin ta kendisi — karartılmış sayfada aydınlık kalan halka
    // (bkz. .tour-hole). Işınlar dört yönde; sekiz ışın 18 pikselde lekeye
    // dönüşüyordu.
    'tur' => '
        <circle cx="8" cy="8" r="3.1"/>
        <path d="M8 1.5v1.7M8 12.8v1.7M1.5 8h1.7M12.8 8h1.7"/>',
];
