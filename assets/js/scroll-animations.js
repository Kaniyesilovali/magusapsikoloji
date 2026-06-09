(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var style = document.createElement('style');
  style.textContent =
    '[data-animate]{opacity:0;transform:translate3d(0,24px,0);transition:opacity .45s cubic-bezier(0.4,0,0.2,1),transform .45s cubic-bezier(0.4,0,0.2,1);backface-visibility:hidden;will-change:opacity,transform}' +
    '[data-animate].is-visible{opacity:1;transform:translate3d(0,0,0);will-change:auto}';
  document.head.appendChild(style);

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('main .grid > *').forEach(function (el) {
      el.setAttribute('data-animate', '');
      var idx = Array.from(el.parentElement.children).indexOf(el);
      el.style.transitionDelay = (idx % 3) * 60 + 'ms';
    });

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          requestAnimationFrame(function () {
            entry.target.classList.add('is-visible');
          });
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.05, rootMargin: '0px 0px -20px 0px' });

    document.querySelectorAll('[data-animate]').forEach(function (el) {
      observer.observe(el);
    });
  });
}());
