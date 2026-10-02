// Ana sayfa hero'su: beynin çevresinde nefes alan üç katman.
// Halkalar 12 sn'lik bir ritimle içten dışa genişleyip daralır; her 6 sn'de döngüdeki
// sıradaki kavram (düşünce → duygu → davranış → ilişki → yaşam deneyimi) hafifçe öne
// çıkar ve öncekine ince bir iplikle bağlanır. Kavramlar data-key ile eşleşir (TR/EN ortak).
(function(){
  var TAU = Math.PI * 2;
  var C = 240;                        // sahne merkezi (480 × 480)
  var RINGS = [100, 158, 214];        // iç · orta · dış yarıçap
  var SPEEDS = [1.4, 1.1, 0.85];      // derece / sn
  var BREATH = 12;                    // bir nefes, sn
  var WAVE = 6;                       // döngüde bir adım, sn
  var CYCLE = ['thoughts', 'emotions', 'behaviours', 'relationships', 'experiences'];
  var PAIRS = [
    ['thoughts', 'emotions'], ['emotions', 'behaviours'], ['behaviours', 'relationships'],
    ['relationships', 'experiences'], ['experiences', 'thoughts'],
    ['body', 'emotions'], ['family', 'relationships'], ['values', 'behaviours'],
    ['selfesteem', 'self'], ['culture', 'values'], ['needs', 'relationships'],
    ['transitions', 'self'], ['body', 'needs'], ['family', 'selfesteem']
  ];

  // 0 → 1 → 0 yumuşak zarf, [start, start + len] aralığında
  function envelope(x, start, len){
    var p = (x - start) / len;
    if (p <= 0 || p >= 1) return 0;
    var s = Math.sin(Math.PI * p);
    return s * s;
  }

  function init(wrap){
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var ringEls = Array.prototype.slice.call(wrap.querySelectorAll('.zh-ring'));
    var threadLayer = wrap.querySelector('.zh-threads');
    var core = wrap.querySelector('.zh-core');
    var glow = wrap.querySelector('.zh-glow');

    var labels = Array.prototype.map.call(wrap.querySelectorAll('.zh-label'), function(el, i){
      return {
        el: el, key: el.dataset.key, ring: +el.dataset.ring, a0: +el.dataset.angle,
        w: el.offsetWidth, h: el.offsetHeight,
        // her kavramın birbirine denk gelmeyen kendi salınım periyotları
        pA: 19 + (i * 7.3) % 13, pR: 23 + (i * 5.9) % 11, ph: i * 1.7,
        cyc: CYCLE.indexOf(el.dataset.key),
        ox: 0, oy: 0, x: C, y: C, emph: 0, hov: 0
      };
    });
    var byKey = {};
    labels.forEach(function(l){ byKey[l.key] = l; });

    var threads = PAIRS.map(function(p, i){
      var el = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      el.setAttribute('class', 'zh-thread');
      threadLayer.appendChild(el);
      return { a: byKey[p[0]], b: byKey[p[1]], el: el, k: i, o: 0 };
    });

    function related(a, b){
      return a === b || threads.some(function(th){
        return (th.a === a && th.b === b) || (th.a === b && th.b === a);
      });
    }

    // İmleç sahneye girince hareket yavaşlar; kavramın üstündeyse bağlı kavramlar öne çıkar
    var hovered = null, target = 1;
    wrap.addEventListener('mouseenter', function(){ target = 0.3; });
    wrap.addEventListener('mouseleave', function(){ target = 1; hovered = null; });
    labels.forEach(function(l){
      l.el.addEventListener('mouseenter', function(){ hovered = l; });
      l.el.addEventListener('mouseleave', function(){ if (hovered === l) hovered = null; });
    });

    var t = 0, speed = 1, last = null, running = true, first = true;

    function frame(now){
      if (last === null) last = now;
      var dt = Math.min((now - last) / 1000, 0.05);
      last = now;
      speed += (target - speed) * Math.min(dt * 1.2, 1);     // ~1,5 sn'de yavaşlar/hızlanır
      t += dt * speed;
      step(dt);
      if (running) requestAnimationFrame(frame);
    }

    function step(dt){
      var k = first ? 1 : Math.min(dt * 2.5, 1);

      // Halkalar: içten dışa yayılan nefes
      var rr = RINGS.map(function(R, i){
        return R + (3 + i * 2.5) * Math.sin(TAU * t / BREATH - i * 0.9);
      });
      ringEls.forEach(function(el, i){ el.setAttribute('r', rr[i].toFixed(2)); });
      core.style.transform = 'scale(' + (1 + 0.018 * Math.sin(TAU * t / BREATH + 0.5)).toFixed(4) + ')';
      glow.style.transform = 'scale(' + (1 + 0.06 * Math.sin(TAU * t / BREATH + 0.2)).toFixed(4) + ')';
      glow.style.opacity = (0.75 + 0.25 * Math.sin(TAU * t / BREATH + 0.2)).toFixed(3);

      // Döngü vurgusu
      var n = CYCLE.length, cyc = (t / WAVE) % n;
      function along(idx){ return ((cyc - idx) % n + n) % n; }
      labels.forEach(function(l){
        var e = 0;
        if (l.cyc >= 0){
          var d = along(l.cyc);
          e = envelope(d, -0.6, 1.9) + envelope(d - n, -0.6, 1.9);
        }
        l.emph += (e - l.emph) * k;
        l.hov += ((hovered ? (related(hovered, l) ? 1 : -1) : 0) - l.hov) * k;
      });
      threads.forEach(function(th){
        var o = 0;
        if (th.k < n){
          var d = along(th.k);
          o = 0.35 * (envelope(d, 0.15, 1.5) + envelope(d - n, 0.15, 1.5));
        }
        if (hovered && (th.a === hovered || th.b === hovered)) o = Math.max(o, 0.6);
        th.o += (o - th.o) * k;
      });

      // Yörüngedeki yer: halka dönüşü + kendi yavaş salınımı
      labels.forEach(function(l){
        var ang = l.a0 + SPEEDS[l.ring] * t + 3.5 * Math.sin(TAU * t / l.pA + l.ph);
        var r = rr[l.ring] + 4 * Math.sin(TAU * t / l.pR + l.ph * 0.6);
        var a = ang * Math.PI / 180;
        l.tx = C + r * Math.sin(a);
        l.ty = C - r * Math.cos(a);
        l.ax = 0; l.ay = 0; l.px = 0; l.py = 0;
      });

      // Bağ belirdiğinde iki kavram birbirine hafifçe yaklaşır
      threads.forEach(function(th){
        if (th.o < 0.01) return;
        var dx = th.b.tx - th.a.tx, dy = th.b.ty - th.a.ty, d = Math.hypot(dx, dy) || 1;
        var pull = 10 * th.o;
        th.a.ax += dx / d * pull; th.a.ay += dy / d * pull;
        th.b.ax -= dx / d * pull; th.b.ay -= dy / d * pull;
      });

      // Yumuşak itme: kavramlar üst üste binmez, birbirine yer açar
      for (var it = 0; it < 4; it++){
        for (var i = 0; i < labels.length; i++){
          for (var j = i + 1; j < labels.length; j++){
            var A = labels[i], B = labels[j];
            var dx = (B.tx + B.ax + B.px) - (A.tx + A.ax + A.px);
            var dy = (B.ty + B.ay + B.py) - (A.ty + A.ay + A.py);
            var ovX = (A.w + B.w) / 2 + 10 - Math.abs(dx);
            var ovY = (A.h + B.h) / 2 + 8 - Math.abs(dy);
            if (ovX > 0 && ovY > 0){
              if (ovX < ovY){ var sx = (dx < 0 ? -1 : 1) * ovX / 2; A.px -= sx; B.px += sx; }
              else          { var sy = (dy < 0 ? -1 : 1) * ovY / 2; A.py -= sy; B.py += sy; }
            }
          }
        }
      }

      var kk = first ? 1 : Math.min(dt * 2, 1);
      first = false;
      labels.forEach(function(l){
        l.ox += (l.ax + l.px - l.ox) * kk;
        l.oy += (l.ay + l.py - l.oy) * kk;
        l.x = l.tx + l.ox; l.y = l.ty + l.oy;
        var s = 1 + 0.045 * l.emph + 0.05 * Math.max(l.hov, 0);
        l.el.style.transform = 'translate(' + l.x.toFixed(2) + 'px,' + l.y.toFixed(2) + 'px) translate(-50%,-50%) scale(' + s.toFixed(4) + ')';
        l.el.style.setProperty('--emph', Math.max(l.emph, l.hov, 0).toFixed(3));
        l.el.style.opacity = hovered && l.hov <= 0 ? (0.4 + 0.6 * (1 + l.hov)).toFixed(3) : '';
      });

      threads.forEach(function(th){
        var a = th.a, b = th.b;
        var mx = (a.x + b.x) / 2, my = (a.y + b.y) / 2;
        var cx = mx + (C - mx) * 0.22, cy = my + (C - my) * 0.22;   // merkeze doğru hafif kavis
        th.el.setAttribute('d', 'M' + a.x.toFixed(1) + ' ' + a.y.toFixed(1) + 'Q' + cx.toFixed(1) + ' ' + cy.toFixed(1) + ' ' + b.x.toFixed(1) + ' ' + b.y.toFixed(1));
        th.el.style.opacity = th.o.toFixed(3);
      });
    }

    // Hareket azaltma tercihinde durağan bir kare
    if (reduce){ t = 3; step(0); return; }

    // Ekranda değilken çalışmaz
    if ('IntersectionObserver' in window){
      new IntersectionObserver(function(es){
        var vis = es[0].isIntersecting;
        if (vis && !running){ running = true; last = null; requestAnimationFrame(frame); }
        running = vis;
      }).observe(wrap);
    }
    requestAnimationFrame(frame);
  }

  // lg altında sahne gizli ve ölçülemiyor; pencere genişleyince başlar
  function start(){
    Array.prototype.forEach.call(document.querySelectorAll('.zh-wrap'), function(wrap){
      if (wrap.dataset.zhReady || !wrap.offsetWidth) return;
      wrap.dataset.zhReady = '1';
      init(wrap);
    });
  }
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(start);
  else window.addEventListener('load', start);
  window.addEventListener('resize', start);
})();
