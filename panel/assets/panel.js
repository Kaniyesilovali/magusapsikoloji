/**
 * Panelin tek istemci betiği. CSP inline script'e izin vermediği için
 * onay diyalogları da dahil her şey buradan bağlanır.
 */
(function () {
  'use strict';

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
