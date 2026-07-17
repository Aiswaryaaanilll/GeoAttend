/* GeoAttend – teacher session helpers (progressive enhancement) */
(function () {
  'use strict';

  // Confirm before closing an active session
  document.querySelectorAll('form.ga-close-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var ok = window.confirm(
        'Close this attendance session now? Students will no longer be able to mark attendance.'
      );
      if (!ok) e.preventDefault();
    });
  });

  // Live "ends in" countdown on any element with data-ends-at="YYYY-MM-DD HH:MM:SS"
  var timers = document.querySelectorAll('[data-ends-at]');
  if (!timers.length) return;

  function fmt(ms) {
    if (ms <= 0) return 'ended';
    var s = Math.floor(ms / 1000);
    var m = Math.floor(s / 60);
    var r = s % 60;
    return m + 'm ' + (r < 10 ? '0' : '') + r + 's';
  }

  function tick() {
    var now = Date.now();
    timers.forEach(function (el) {
      var t = el.getAttribute('data-ends-at');
      if (!t) return;
      // Treat as server local time (WAMP default)
      var end = new Date(t.replace(' ', 'T')).getTime();
      if (isNaN(end)) return;
      el.textContent = fmt(end - now);
    });
  }
  tick();
  setInterval(tick, 1000);
})();
