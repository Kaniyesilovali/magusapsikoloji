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

  // Buradaki `data-dirty-guard` bekçisi kaldırıldı: check-in ekranı artık tek
  // form ve tek "Kaydet". Bekçi, iki formun birbirinin kaydedilmemiş
  // değişikliklerini silmesini haber veriyordu ama sorunu ortadan
  // kaldırmıyordu; formlar birleşince silinecek bir şey de kalmadı.

  // ── Tanıtım turu ───────────────────────────────────────────────────────
  //
  // Tur ayrı bir betik dosyası ya da adımların sayıldığı bir liste DEĞİL:
  // her adım, anlattığı öğenin kendi üzerinde durur.
  //
  //   <section data-tour="21" data-tour-title="Gün cetveli"
  //            data-tour-text="Seanslar süreleri kadar yer kaplar…">
  //
  // Sıra data-tour'daki sayıdan gelir; layout kenar çubuğu için 10–19'u
  // kullanır, sayfalar 20'den başlar. Bunun tek nedeni şu: adımın metni
  // anlattığı şeyden ayrı bir dosyada dursaydı, ekran değişince metin orada
  // kalırdı. Burada bölüm silinince adımı da onunla gidiyor.
  //
  // Görünmeyen adım atlanır — kenar çubuğu dar ekranda kapalı bir <details>
  // içinde ve yetkisi olmayan kişinin menüsünde o satır hiç basılmıyor. Yani
  // tur, sayfayı açan kişinin gerçekten gördüğü ekranı anlatıyor.
  (function () {
    var starters = Array.prototype.slice.call(document.querySelectorAll('[data-tour-start]'));
    if (!starters.length) return;

    var all = Array.prototype.slice.call(document.querySelectorAll('[data-tour]'));
    all.sort(function (a, b) {
      return parseInt(a.getAttribute('data-tour'), 10) - parseInt(b.getAttribute('data-tour'), 10);
    });
    // Anlatacak bir şeyi olmayan ekranda düğme de yok: JS kapalıyken hiçbir işe
    // yaramayacağı için zaten gizli geliyor (bkz. [data-reveal] ile aynı yöntem).
    if (!all.length) return;

    var motion = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var GAP = 12;   // kart ile hedef arasındaki hava
    var PAD = 6;    // ışık halkasının hedeften taşma payı

    var steps = [];
    var index = 0;
    var open = false;
    var lastFocus = null;
    var frame = 0;
    var veil, hole, card, elCount, elTitle, elText, btnBack, btnNext, btnSkip;

    var onScreen = function (el) {
      return el.getClientRects().length > 0;
    };

    var build = function () {
      // Perde saydam: karartmayı deliğin kendi gölgesi çiziyor (bkz. .tour-hole).
      // Buradaki tek iş tıklamayı yakalamak — karanlığa tıklamak turu kapatır.
      //
      // Aydınlık alan bunun dışında: perde onun da üstünde duruyor, yani oraya
      // basan kişi tur penceresine değil anlatılan şeye basmış oluyor. Turu
      // kapatmak olmaz; kapanma isteği "başka bir yere" tıklamakla anlaşılır.
      veil = document.createElement('div');
      veil.className = 'tour-veil';
      veil.addEventListener('click', function (event) {
        var box = hole.getBoundingClientRect();
        var inside = event.clientX >= box.left && event.clientX <= box.right
                  && event.clientY >= box.top  && event.clientY <= box.bottom;
        if (!inside) close();
      });

      hole = document.createElement('div');
      hole.className = 'tour-hole';

      card = document.createElement('div');
      card.className = 'tour-card';
      card.setAttribute('role', 'dialog');
      card.setAttribute('aria-modal', 'true');
      card.setAttribute('aria-labelledby', 'tur-baslik');
      card.tabIndex = -1;

      elCount = document.createElement('p');
      elCount.className = 'eyebrow tour-count';

      elTitle = document.createElement('h2');
      elTitle.className = 'tour-title';
      elTitle.id = 'tur-baslik';

      elText = document.createElement('p');
      elText.className = 'tour-text';

      btnSkip = document.createElement('button');
      btnSkip.type = 'button';
      btnSkip.className = 'btn-text btn-text-quiet';
      btnSkip.textContent = 'Kapat';
      btnSkip.addEventListener('click', close);

      btnBack = document.createElement('button');
      btnBack.type = 'button';
      btnBack.className = 'btn btn-quiet btn-sm';
      btnBack.textContent = 'Geri';
      btnBack.addEventListener('click', function () { go(index - 1); });

      btnNext = document.createElement('button');
      btnNext.type = 'button';
      btnNext.className = 'btn btn-primary btn-sm';
      btnNext.addEventListener('click', function () { go(index + 1); });

      var acts = document.createElement('span');
      acts.className = 'tour-acts';
      acts.appendChild(btnBack);
      acts.appendChild(btnNext);

      var foot = document.createElement('div');
      foot.className = 'tour-foot';
      foot.appendChild(btnSkip);
      foot.appendChild(acts);

      card.appendChild(elCount);
      card.appendChild(elTitle);
      card.appendChild(elText);
      card.appendChild(foot);
    };

    // Kart hedefin altına konur; sığmıyorsa üstüne. Uzun bir hedefin (kenar
    // çubuğu menüsü ekran boyunda) altı da üstü de yok — o zaman yanına geçer.
    var place = function () {
      var step = steps[index];
      if (!step) return;

      var box = step.getBoundingClientRect();
      var vw = document.documentElement.clientWidth;
      var vh = document.documentElement.clientHeight;

      hole.style.top    = (box.top - PAD) + 'px';
      hole.style.left   = (box.left - PAD) + 'px';
      hole.style.width  = (box.width + PAD * 2) + 'px';
      hole.style.height = (box.height + PAD * 2) + 'px';

      var cw = card.offsetWidth;
      var ch = card.offsetHeight;
      var top, left;

      if (box.height > vh * 0.6 && box.width < vw * 0.5) {
        left = box.right + GAP;
        if (left + cw > vw - GAP) left = box.left - GAP - cw;
        top = box.top;
      } else {
        top = box.bottom + GAP;
        if (top + ch > vh - GAP) {
          var above = box.top - GAP - ch;
          if (above >= GAP) top = above;
        }
        left = box.left + box.width / 2 - cw / 2;
      }

      card.style.top  = Math.max(GAP, Math.min(top, vh - ch - GAP)) + 'px';
      card.style.left = Math.max(GAP, Math.min(left, vw - cw - GAP)) + 'px';
    };

    var go = function (next) {
      if (next < 0) return;
      if (next >= steps.length) { close(); return; }

      index = next;
      var step = steps[index];

      elCount.textContent = 'Adım ' + (index + 1) + ' / ' + steps.length;
      elTitle.textContent = step.getAttribute('data-tour-title') || '';
      elText.textContent  = step.getAttribute('data-tour-text') || '';
      btnBack.disabled = index === 0;
      btnNext.textContent = index === steps.length - 1 ? 'Bitir' : 'İleri';

      // Ekranın dışında kalan hedefe önce gidilir. Ekranı zaten dolduran bir
      // hedef (kenar çubuğu ekran boyunda) kaydırılmıyor: ortalanacak bir şey
      // yok, sayfa boşuna oynardı. Yumuşak kaydırma sürerken kart yerinde
      // durmaz; kaydırmayı zaten dinlediğimiz için kendini düzeltiyor
      // (bkz. aşağıdaki scroll dinleyicisi).
      var box = step.getBoundingClientRect();
      var vh = document.documentElement.clientHeight;
      var away = box.bottom < GAP || box.top > vh - GAP;
      var cut  = box.height < vh * 0.8 && (box.top < GAP || box.bottom > vh - GAP);
      if (away || cut) {
        step.scrollIntoView({ block: 'center', behavior: motion ? 'smooth' : 'auto' });
      }

      place();
      card.focus();
    };

    function close() {
      if (!open) return;
      open = false;

      veil.remove();
      hole.remove();
      card.remove();
      remember();

      // Kapatan kişi turu başlattığı düğmeye dönsün; kendiliğinden açıldıysa
      // odak sayfanın başındaydı ve orada kalır.
      if (lastFocus && document.contains(lastFocus)) lastFocus.focus();
    }

    // "Gördü" bilgisi çerezde tutuluyor — menü daraltma durumu da öyle
    // (bkz. panel_nav). Değer kişi kimliğiyle işaretli: merkezdeki ortak
    // bilgisayarda ikinci kişi turu kendi ilk girişinde yine görsün.
    //
    // Kapatmak da görmüş saymak için yeterli: turu kapatan biri onu bir daha
    // istemiyor demektir, her sayfa yüklemesinde önüne çıkarmak ısrar olurdu.
    var remember = function () {
      var key = starters[0].getAttribute('data-tour-key');
      if (!key) return;

      var raw = (document.cookie.match(/(?:^|;\s*)panel_tur=([^;]*)/) || [])[1] || '';
      var list = decodeURIComponent(raw).split(',').filter(function (token) {
        return token !== '' && token !== key;
      });
      list.unshift(key);

      document.cookie = 'panel_tur=' + encodeURIComponent(list.slice(0, 6).join(','))
        + '; path=' + (starters[0].getAttribute('data-tour-path') || '/')
        + '; max-age=31536000; samesite=lax'
        + (window.location.protocol === 'https:' ? '; secure' : '');
    };

    var start = function () {
      if (open) return;

      steps = all.filter(onScreen);
      if (!steps.length) return;
      if (!card) build();

      lastFocus = document.activeElement;
      document.body.appendChild(veil);
      document.body.appendChild(hole);
      document.body.appendChild(card);
      open = true;
      go(0);
    };

    starters.forEach(function (button) {
      button.hidden = false;
      button.addEventListener('click', function () {
        // Mobil menüden başlatıldıysa menü kapanır: açık kalsaydı tur, üstünü
        // örttüğü bir sayfayı anlatıyor olurdu.
        var box = button.closest('details');
        if (box) box.open = false;
        start();
      });
    });

    var onMove = function () {
      if (!open || frame) return;
      frame = window.requestAnimationFrame(function () {
        frame = 0;
        place();
      });
    };
    window.addEventListener('scroll', onMove, true);   // capture: iç kaydırma alanları da
    window.addEventListener('resize', onMove);

    document.addEventListener('keydown', function (event) {
      if (!open) return;

      if (event.key === 'Escape')     { close();          return; }
      if (event.key === 'ArrowRight') { go(index + 1);    return; }
      if (event.key === 'ArrowLeft')  { go(index - 1);    return; }
      if (event.key !== 'Tab') return;

      // Odak kartın içinde kalır: turun arkasındaki sayfa şu an tıklanamıyor,
      // sekmeyle oraya düşen bir odak görünmez bir yerde kaybolurdu.
      var items = Array.prototype.filter.call(card.querySelectorAll('button'), function (b) {
        return !b.disabled;
      });
      if (!items.length) return;

      var first = items[0];
      var last = items[items.length - 1];
      if (event.shiftKey && (document.activeElement === first || document.activeElement === card)) {
        last.focus();
        event.preventDefault();
      } else if (!event.shiftKey && document.activeElement === last) {
        first.focus();
        event.preventDefault();
      }
    });

    // Kendiliğinden açılış: yalnız turu hiç görmemiş kişide ve yalnız "Bugün"
    // ekranında (bkz. views/layout.php). Kısa gecikme sayfanın yerine
    // oturması için — ilk boyamada ölçülen yerler daha kayıyor.
    if (starters.filter(function (b) { return b.hasAttribute('data-tour-auto'); }).length) {
      window.setTimeout(start, 400);
    }
  })();

  // data-confirm taşıyan formlar gönderilmeden önce onay ister.
  document.addEventListener('submit', function (event) {
    // Önceki bir dinleyici gönderimi durdurduysa burada yapılacak iş yok:
    // aşağıdaki düğme kilidi çalışırsa vazgeçilen form bir daha gönderilemez.
    if (event.defaultPrevented) return;

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
