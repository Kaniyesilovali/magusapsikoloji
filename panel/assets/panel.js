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
