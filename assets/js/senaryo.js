// Blog "Tanıdık geliyor mu?" senaryo kartı (TR beyin-beden / EN brain-body-connection).
// Metinler sayfadaki #scen-data JSON'unda, ikonlar #scen-icons şablonunda ({% icon %} ile
// üretiliyor) — böylece TR ve EN aynı betiği kullanıyor.
(function () {
  'use strict';

  var dataEl = document.getElementById('scen-data');
  var stepBody = document.getElementById('step-body');
  if (!dataEl || !stepBody) return;

  var SCENARIOS = JSON.parse(dataEl.textContent);
  var iconTpl   = document.getElementById('scen-icons');
  var stepIcon  = document.getElementById('step-icon');
  var stepLabel = document.getElementById('step-label');
  var stepHL    = document.getElementById('step-headline');
  var stepDesc  = document.getElementById('step-desc');
  var stepBar   = document.getElementById('step-bar');
  var dotsWrap  = document.getElementById('step-dots-wrap');
  var btnPrev   = document.getElementById('btn-prev');
  var btnNext   = document.getElementById('btn-next');
  var tabs      = document.querySelectorAll('.scen-tab');

  var NEXT_LABEL = btnNext.dataset.next;
  var DONE_LABEL = btnNext.dataset.done;
  var ARROW = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>';
  var CHECK = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
  var TAB_ON  = 'scen-tab active px-5 py-2.5 rounded-full text-sm font-medium transition-colors bg-primary text-white';
  var TAB_OFF = 'scen-tab px-5 py-2.5 rounded-full text-sm font-medium transition-colors bg-white border border-warm-tertiary text-ink hover:border-primary hover:text-primary';

  var currentScen = 0;
  var currentStep = 0;

  function iconHtml(name) {
    var el = iconTpl && iconTpl.content.querySelector('[data-icon="' + name + '"]');
    return el ? el.innerHTML : '';
  }

  function build(scen) {
    stepBar.innerHTML = '';
    dotsWrap.innerHTML = '';
    SCENARIOS[scen].steps.forEach(function () {
      var bar = document.createElement('div');
      bar.style.cssText = 'flex:1;height:4px;background:var(--color-warm-tertiary);transition:background 0.3s ease;';
      stepBar.appendChild(bar);
      var dot = document.createElement('span');
      dot.className = 'sdot';
      dotsWrap.appendChild(dot);
    });
  }

  function render(scen, step) {
    stepBody.classList.add('fading');
    setTimeout(function () {
      var steps = SCENARIOS[scen].steps;
      var s = steps[step];
      stepIcon.innerHTML    = iconHtml(s.icon);
      stepLabel.textContent = s.label;
      stepHL.textContent    = s.headline;
      stepDesc.textContent  = s.desc || '';

      Array.prototype.forEach.call(stepBar.children, function (bar, i) {
        bar.style.background = i <= step ? 'var(--color-primary)' : 'var(--color-warm-tertiary)';
      });
      Array.prototype.forEach.call(dotsWrap.children, function (dot, i) {
        dot.classList.toggle('on', i === step);
      });

      var isLast = step === steps.length - 1;
      btnPrev.disabled = step === 0;
      btnNext.disabled = isLast;
      btnNext.innerHTML = isLast ? CHECK + ' ' + DONE_LABEL : NEXT_LABEL + ' ' + ARROW;

      stepBody.classList.remove('fading');
    }, 170);
  }

  function go(step) {
    if (step < 0 || step >= SCENARIOS[currentScen].steps.length) return;
    currentStep = step;
    render(currentScen, currentStep);
  }

  function switchScenario(scen) {
    currentScen = scen;
    currentStep = 0;
    build(scen);
    render(scen, 0);
    Array.prototype.forEach.call(tabs, function (t) {
      var on = parseInt(t.dataset.scen, 10) === scen;
      t.className = on ? TAB_ON : TAB_OFF;
      t.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  Array.prototype.forEach.call(tabs, function (t) {
    t.addEventListener('click', function () { switchScenario(parseInt(t.dataset.scen, 10)); });
  });
  btnNext.addEventListener('click', function () { go(currentStep + 1); });
  btnPrev.addEventListener('click', function () { go(currentStep - 1); });
  document.addEventListener('keydown', function (e) {
    var tag = document.activeElement && document.activeElement.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'BUTTON' || tag === 'A') return;
    if (e.key === 'ArrowRight') go(currentStep + 1);
    if (e.key === 'ArrowLeft')  go(currentStep - 1);
  });

  switchScenario(0);
})();
