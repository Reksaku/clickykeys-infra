// js/track.js

function isUUIDv4(uuid) {
  const uuidV4Regex = 
    /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
  return uuidV4Regex.test(uuid);
}

function getSessionId() {
  // per-tab, per-session
  let id = sessionStorage.getItem('ck_session_id');
  if (!id) {
    if (window.crypto && crypto.randomUUID) {
      id = crypto.randomUUID();
    } else {
      id = Date.now().toString(36) + Math.random().toString(36).slice(2);
    }
    sessionStorage.setItem('ck_session_id', id);
  }

  if (isUUIDv4(id) == false) {
    id = "modified"
  }

  return id;
}

const sessionId = getSessionId();

document.addEventListener('DOMContentLoaded', () => {
  const data = {
    session_id: sessionId,
    path: window.location.pathname + window.location.search,
    referrer: document.referrer || null,
    viewport_width: window.innerWidth || null,
    viewport_height: window.innerHeight || null,
    load_time_ms: null
  };

  if (performance && performance.timing) {
    const t = performance.timing;
    data.load_time_ms = t.domContentLoadedEventEnd - t.navigationStart;
    if (data.load_time_ms < 0 || !isFinite(data.load_time_ms)) {
      data.load_time_ms = null;
    }
  }

  fetch('/db_php/track.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    keepalive: true,
    body: JSON.stringify(data)
  })
    .then(r => r.json())
    .then(res => {
      if (res && typeof res.page_view_id === 'number') {
        window.__ck_page_view_id = res.page_view_id;
      }
    })
    .catch(() => {});
});



function sendClickEvent({ element, eventType = 'button_click' }) {
  const payload = {
    session_id: sessionId,
    page_view_id: window.__ck_page_view_id || null,
    path: window.location.pathname + window.location.search,
    event_type: eventType,
    element_id: element.id || null,
    label: element.getAttribute('data-track-label')
      || element.textContent.trim().slice(0, 100),
    extra: {
      tag: element.tagName,
    }
  };

  const body = JSON.stringify(payload);

  if (navigator.sendBeacon) {
    const blob = new Blob([body], { type: 'application/json' });
    navigator.sendBeacon('/db_php/event.php', blob);
  } else {
    fetch('/db_php/event.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      keepalive: true,
      body
    }).catch(() => {});
  }
}

document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-track-click]');
  if (!btn) return;
  sendClickEvent({ element: btn, eventType: 'button_click' });
});
