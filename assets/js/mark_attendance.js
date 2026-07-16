/* GeoAttend – Mark attendance (geolocation + fetch) */
(function () {
  'use strict';

  const ENDPOINT = 'mark_attendance.php';

  function setStatus(el, cls, text) {
    if (!el) return;
    el.className = 'geo-status ' + cls;
    el.textContent = text;
  }

  function getPosition() {
    return new Promise((resolve, reject) => {
      if (!('geolocation' in navigator)) {
        return reject(new Error('Geolocation is not supported by this browser.'));
      }
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0,
      });
    });
  }

  async function handleMark(card) {
    const btn    = card.querySelector('.mark-btn');
    const status = card.querySelector('[data-role="status"]');
    const sessionId = parseInt(card.dataset.session, 10);

    btn.disabled = true;
    setStatus(status, 'wait', 'Getting your location…');

    let pos;
    try {
      pos = await getPosition();
    } catch (err) {
      setStatus(status, 'err',
        err.code === 1
          ? 'Location permission denied. Enable it and retry.'
          : 'Could not read your location. Try again.');
      btn.disabled = false;
      return;
    }

    setStatus(status, 'wait', 'Verifying you are in the classroom…');

    try {
      const res = await fetch(ENDPOINT, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          session_id: sessionId,
          latitude:   pos.coords.latitude,
          longitude:  pos.coords.longitude,
          accuracy:   pos.coords.accuracy,
        }),
      });
      const data = await res.json();
      if (data.ok) {
        // Replace actions with a success chip.
        const actions = card.querySelector('.actions');
        actions.innerHTML = '<span class="marked">✓ Attendance marked</span>';
      } else {
        setStatus(status, 'err', data.message || 'Could not mark attendance.');
        btn.disabled = false;
      }
    } catch (e) {
      setStatus(status, 'err', 'Network error. Please retry.');
      btn.disabled = false;
    }
  }

  document.querySelectorAll('.session-card .mark-btn').forEach((btn) => {
    btn.addEventListener('click', () => handleMark(btn.closest('.session-card')));
  });
})();
