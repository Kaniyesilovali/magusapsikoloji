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
    // Günün kendisi: tek bir sütun ve üzerinden geçen "şimdi" çizgisi —
    // ekrandaki .rail-now'ın ta kendisi. Takvim çizilseydi "Müsaitlik" ile
    // 18 pikselde birbirine karışırdı; ikisi de takvim olamaz.
    'bugun' => '
        <rect x="3.7" y="2" width="8.6" height="12" rx="2.2"/>
        <path d="M3.7 9.1h8.6"/>
        <circle class="ic-fill" cx="6.1" cy="9.1" r="1.2"/>',

    'randevular' => '
        <circle cx="8" cy="8" r="5.9"/>
        <path d="M8 4.6V8.2l2.5 1.5"/>',

    'gorusmeciler' => '
        <circle cx="8" cy="5.8" r="2.6"/>
        <path d="M3.3 13.7a4.7 4.7 0 0 1 9.4 0"/>',

    'musaitlik' => '
        <rect x="2" y="3.2" width="12" height="10.8" rx="2.2"/>
        <path d="M2 6.6h12"/>
        <path d="M5.4 1.9v2.2M10.6 1.9v2.2"/>
        <rect class="ic-fill" x="4.4" y="9" width="7.2" height="2.5" rx="1.25"/>',

    'odemeler' => '
        <rect x="1.6" y="4.2" width="12.8" height="7.6" rx="1.9"/>
        <circle cx="8" cy="8" r="1.9"/>',

    'icerik' => '
        <rect x="2" y="3" width="12" height="10" rx="2.2"/>
        <path d="M2 6.2h12"/>
        <path d="M4.7 9.4h6.6"/>',

    'kvkk' => '
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

    'cikis' => '
        <path d="M9.4 2.7H4.3a1.7 1.7 0 0 0-1.7 1.7v7.2a1.7 1.7 0 0 0 1.7 1.7h5.1"/>
        <path d="M11 5.5L13.5 8 11 10.5M13.5 8H6.5"/>',
];
