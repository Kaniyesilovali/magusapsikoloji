// Blog kategori filtresi — /blog/ ve /en/blog/
// Butonlar data-filter, kartlar data-cat taşır; "all" hepsini gösterir.
(function () {
  var bar = document.getElementById('blogFilters');
  var grid = document.getElementById('blogGrid');
  if (!bar || !grid) return;

  var buttons = bar.querySelectorAll('[data-filter]');
  var cards = grid.querySelectorAll('[data-cat]');
  var empty = document.getElementById('blogEmpty');
  var ACTIVE = ['bg-primary', 'text-white', 'border-primary'];
  var INACTIVE = ['bg-white', 'text-ink-muted', 'border-warm-tertiary', 'hover:border-primary', 'hover:text-primary'];

  function apply(filter) {
    var shown = 0;
    cards.forEach(function (card) {
      var match = filter === 'all' || card.getAttribute('data-cat') === filter;
      card.hidden = !match;
      if (match) shown++;
    });
    buttons.forEach(function (btn) {
      var on = btn.getAttribute('data-filter') === filter;
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      ACTIVE.forEach(function (c) { btn.classList.toggle(c, on); });
      INACTIVE.forEach(function (c) { btn.classList.toggle(c, !on); });
    });
    if (empty) empty.hidden = shown > 0;
  }

  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var filter = btn.getAttribute('data-filter');
      apply(filter);
      history.replaceState(null, '', filter === 'all' ? location.pathname : '#' + filter);
    });
  });

  var initial = location.hash.slice(1);
  if (initial && bar.querySelector('[data-filter="' + initial + '"]')) apply(initial);
})();
