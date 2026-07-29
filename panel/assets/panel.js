/**
 * Panelin tek istemci betiği. CSP inline script'e izin vermediği için
 * onay diyalogları da dahil her şey buradan bağlanır.
 */
(function () {
  'use strict';

  // data-copy="#hedef" taşıyan düğmeler hedef alanın içeriğini panoya kopyalar.
  // CSP inline betiğe izin vermediği için olay burada bağlanır.
  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-copy]');
    if (!button) return;

    var field = document.querySelector(button.getAttribute('data-copy'));
    if (!field) return;

    field.select();
    field.setSelectionRange(0, field.value.length);

    var done = function () {
      var original = button.textContent;
      button.textContent = 'Kopyalandı';
      window.setTimeout(function () { button.textContent = original; }, 1500);
    };

    // clipboard API yalnız güvenli bağlamda çalışır; yoksa metin zaten seçili kalır.
    if (navigator.clipboard) {
      navigator.clipboard.writeText(field.value).then(done, function () {});
    } else {
      done();
    }
  });

  // data-reveal="#alan" taşıyan düğme şifre alanını okunur yapar. JS kapalıyken
  // hiçbir işe yaramayacağı için düğme gizli geliyor, burada açılıyor.
  document.querySelectorAll('[data-reveal]').forEach(function (button) {
    button.hidden = false;
  });

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-reveal]');
    if (!button) return;

    var field = document.querySelector(button.getAttribute('data-reveal'));
    if (!field) return;

    var show = field.type === 'password';
    field.type = show ? 'text' : 'password';
    button.textContent = show ? 'Gizle' : 'Göster';
    button.setAttribute('aria-label', show ? 'Şifreyi gizle' : 'Şifreyi göster');
    button.setAttribute('aria-pressed', show ? 'true' : 'false');
  });

  // Yazdırma düğmesi. CSP inline script'e izin vermediği için buradan bağlanır.
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-print]');
    if (trigger) window.print();
  });

  // data-autosubmit taşıyan filtre formları seçim değişir değişmez yenilenir.
  // Küçük bir "Göster" yazısını bulup tıklamak, filtrenin çalışmadığı sanılan
  // bir bekleme yaratıyordu. Yalnız <select> dinlenir: tarih alanında her tuş
  // vuruşunda sayfayı yenilemek yazmayı imkânsız kılardı.
  document.querySelectorAll('form[data-autosubmit]').forEach(function (form) {
    form.addEventListener('change', function (event) {
      if (event.target.tagName !== 'SELECT') return;
      if (form.requestSubmit) form.requestSubmit(); else form.submit();
    });
  });

  // JS açıkken gereksizleşen öğeler — autosubmit formlarının "Göster" düğmesi
  // gibi. Silinmiyor, gizleniyor: JS kapalıyken formun tek gönderme yolu odur.
  document.querySelectorAll('[data-no-js]').forEach(function (el) {
    el.hidden = true;
  });

  // Kaydırıcının yanındaki sayı. type=range'in değeri yalnızca tutamacın
  // yerinden okunabiliyor; check-in formunda kişi hangi puanı verdiğini
  // görmeden gönderiyordu. CSP inline betiğe izin vermediği için buradan bağlanır.
  document.querySelectorAll('[data-range-output]').forEach(function (input) {
    var output = document.querySelector(input.getAttribute('data-range-output'));
    if (!output) return;

    var sync = function () { output.textContent = input.value; };
    input.addEventListener('input', sync);
    sync();
  });

  // Kenar çubuğunu daraltır. Durum çerezde tutuluyor çünkü panel çok sayfalı:
  // istemciden okunsaydı her sayfa geniş menüyle boyanıp betik çalışınca
  // daralırdı. Sunucu çerezi okuyup <body class="nav-tight"> basıyor, burası
  // yalnız çevirip yazıyor (bkz. views/layout.php).
  var sideToggle = document.querySelector('[data-side-toggle]');
  if (sideToggle) {
    sideToggle.hidden = false;

    var paintSide = function (tight) {
      document.body.classList.toggle('nav-tight', tight);
      sideToggle.setAttribute('aria-expanded', tight ? 'false' : 'true');

      // Ad iki yere birden gidiyor: ekran okuyucunun duyduğu gizli metne ve
      // ipucunun okuduğu data-side-label'a. title kullanılmıyor — tarayıcının
      // kendi ipucu saniyelerce bekletiyor ve bu süre ayarlanamıyor; kutu
      // CSS'ten çiziliyor (bkz. .side-toggle::after).
      var name = tight ? 'Menüyü genişlet' : 'Menüyü daralt';
      sideToggle.querySelector('.side-toggle-text').textContent = name;
      sideToggle.setAttribute('data-side-label', name);
    };

    paintSide(document.body.classList.contains('nav-tight'));

    sideToggle.addEventListener('click', function () {
      var tight = !document.body.classList.contains('nav-tight');
      paintSide(tight);

      document.cookie = 'panel_nav=' + (tight ? 'tight' : 'open')
        + '; path=' + sideToggle.getAttribute('data-side-path')
        + '; max-age=31536000; samesite=lax'
        + (window.location.protocol === 'https:' ? '; secure' : '');
    });
  }

  // ── Haftanın hâli: rüzgâr halkası ─────────────────────────────────────
  //
  // Halka bir ilerletme (progressive enhancement): sayfadaki gerçek form üç
  // radyo düğmesinden oluşan listedir ve JS kapalıyken aynen o çalışır. Burada
  // yaptığımız tek şey, o listenin üstüne dokunmalı bir yüz koymak ve dokunuşu
  // gizlenmiş radyoya iletmek. İkinci bir veri yolu yok — halka bozulursa
  // form yine gönderilir.
  //
  // Çip dokunuşta döner: sakin → sırt rüzgârı → karşı rüzgâr → sakin. Listedeki
  // üç ayrı düğme yerine burada döngü var, çünkü halkada üç düğmeyi yan yana
  // koyacak yer yok; sekiz alan × üç hedef bir telefon ekranına sığmıyor.
  (function () {
    var slot = document.querySelector('[data-ring]');
    var source = document.querySelector('[data-ring-source]');
    if (!slot || !source) return;

    var fields = Array.prototype.slice.call(source.querySelectorAll('.ci-dom'));
    if (!fields.length) return;

    var ORDER = [0, 1, -1];               // sakin → iyi geldi → zorladı
    var TONE  = { '1': 'is-up', '0': 'is-calm', '-1': 'is-down' };
    var WORD  = { '1': 'iyi geldi', '0': 'öne çıkmadı', '-1': 'zorladı' };

    var ring = document.createElement('div');
    ring.className = 'ci-ring';
    ring.style.setProperty('--n', String(fields.length));

    var center = document.createElement('span');
    center.className = 'ci-ring-center';
    center.textContent = slot.getAttribute('data-center') || '';
    ring.appendChild(center);

    fields.forEach(function (field, index) {
      var radios = Array.prototype.slice.call(field.querySelectorAll('.ci-wind-in'));
      if (!radios.length) return;

      var chip = document.createElement('button');
      chip.type = 'button';                // formu göndermesin
      chip.className = 'ci-chip';
      chip.style.setProperty('--i', String(index));

      var name = document.createElement('span');
      name.className = 'ci-chip-name';
      name.textContent = field.getAttribute('data-short') || '';
      chip.appendChild(name);

      var mark = document.createElement('span');
      mark.className = 'ci-chip-mark';
      mark.setAttribute('aria-hidden', 'true');
      chip.appendChild(mark);

      var current = function () {
        for (var i = 0; i < radios.length; i++) {
          if (radios[i].checked) return parseInt(radios[i].value, 10);
        }
        return 0;
      };

      var paint = function () {
        var value = String(current());
        chip.className = 'ci-chip ' + TONE[value];
        chip.style.setProperty('--i', String(index));
        mark.textContent = value === '1' ? '↑' : (value === '-1' ? '↓' : '·');
        // Ekran okuyucu için tam cümle; görsel ad kısa kalabilir.
        chip.setAttribute('aria-label', field.querySelector('legend').textContent + ' — ' + WORD[value]);
      };

      chip.addEventListener('click', function () {
        var next = ORDER[(ORDER.indexOf(current()) + 1) % ORDER.length];
        radios.forEach(function (radio) {
          radio.checked = parseInt(radio.value, 10) === next;
        });
        paint();
      });

      paint();
      ring.appendChild(chip);
    });

    slot.appendChild(ring);
    // Liste artık halkanın veri deposu: erişilebilirlik ağacında kalır ama
    // görünmez. `hidden` demiyoruz, çünkü o radyoların hâlâ gönderilmesi gerek.
    source.classList.add('ci-dom-list-off');
  })();

  // data-confirm taşıyan formlar gönderilmeden önce onay ister.
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    var message = form.getAttribute('data-confirm');
    if (message && !window.confirm(message)) {
      event.preventDefault();
      return;
    }

    // Çift gönderimi engelle (yavaş bağlantıda iki kullanıcı oluşmasın).
    var button = form.querySelector('button[type="submit"], button:not([type])');
    if (button) {
      window.setTimeout(function () {
        button.disabled = true;
      }, 0);
    }
  });
})();
