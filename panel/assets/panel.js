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
