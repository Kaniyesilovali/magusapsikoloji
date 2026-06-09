(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var style = document.createElement('style');
  style.textContent =
    '[data-animate]{opacity:0;transform:translateY(28px);transition:opacity .55s ease,transform .55s ease;will-change:opacity,transform}' +
    '[data-animate].is-visible{opacity:1;transform:none;will-change:auto}';
  document.head.appendChild(style);

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('main .grid > *').forEach(function (el) {
      el.setAttribute('data-animate', '');
      var idx = Array.from(el.parentElement.children).indexOf(el);
      el.style.transitionDelay = (idx % 3) * 80 + 'ms';
    });

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('[data-animate]').forEach(function (el) {
      observer.observe(el);
    });
  });
}());
