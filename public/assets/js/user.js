/* VideoStream — user.js
   Live updates pattern: WebSocket pushes a tiny event → JS fetches feed_json
   over AJAX → re-renders the relevant DOM targets. */
document.addEventListener('DOMContentLoaded', function () {

  /* ──────────────────────────────────────────────
     SIDEBAR + SEARCH + NAV (unchanged behavior)
  ────────────────────────────────────────────── */
  const sidebar = document.getElementById('uSidebar');
  const collapsedPref = localStorage.getItem('vs_user_sidebar_collapsed') || localStorage.getItem('u_sidebar_collapsed');
  if (sidebar && collapsedPref === '1') sidebar.classList.add('collapsed');

  document.getElementById('uSidebarToggle')?.addEventListener('click', function () {
    const collapsed = sidebar?.classList.toggle('collapsed');
    if (sidebar) {
      localStorage.setItem('vs_user_sidebar_collapsed', collapsed ? '1' : '0');
      localStorage.setItem('u_sidebar_collapsed', collapsed ? '1' : '0');
    }
  });

  const search = document.getElementById('uSearchInput');

  function cardMatchesQuery(card, q) {
    if (q === '') return true;
    const fields = [
      card.dataset.title || '', card.dataset.category || '', card.dataset.desc || '',
      card.dataset.year || '', card.dataset.plan || '', card.dataset.views || '',
      card.dataset.duration || ''
    ];
    return fields.some(f => f.toLowerCase().includes(q));
  }

  function filterMovieCards(query) {
    const q = (query || '').trim().toLowerCase();
    document.querySelectorAll('.u-video-card').forEach(card => {
      card.classList.toggle('is-hidden', !cardMatchesQuery(card, q));
    });
    document.querySelectorAll('.u-cat-card').forEach(chip => {
      if (q === '') { chip.style.opacity = ''; chip.style.outline = ''; return; }
      const name = (chip.querySelector('.u-cat-name')?.textContent || '').toLowerCase();
      chip.style.opacity = name.includes(q) ? '1' : '0.35';
      chip.style.outline = name.includes(q) ? '2px solid var(--red, #e50914)' : '';
    });
    document.querySelectorAll('.u-content-row, #browseGrid').forEach(row => {
      const cards = row.querySelectorAll('.u-video-card');
      const visible = Array.from(cards).filter(c => !c.classList.contains('is-hidden'));
      let noRes = row.querySelector('.u-search-no-results');
      if (q !== '' && cards.length > 0 && visible.length === 0) {
        if (!noRes) {
          noRes = document.createElement('div');
          noRes.className = 'u-empty u-search-no-results';
          row.appendChild(noRes);
        }
        noRes.innerHTML = '<span class="u-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></span>No results for &ldquo;<strong>' + q + '</strong>&rdquo;';
        noRes.style.display = '';
      } else if (noRes) {
        noRes.style.display = 'none';
      }
    });
  }
  search?.addEventListener('input', () => filterMovieCards(search.value));
  search?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); filterMovieCards(search.value); } });
  if (search && search.value) filterMovieCards(search.value);

  const upage = new URLSearchParams(window.location.search).get('upage') || 'home';
  document.querySelectorAll('.u-nav-item').forEach(el => {
    const href = el.getAttribute('href') || '';
    el.classList.toggle('active', href.includes('upage=' + upage));
  });

  document.addEventListener('click', () => {
    document.getElementById('uNotifDropdown')?.classList.remove('open');
  });

  /* ──────────────────────────────────────────────
     LIVE: WebSocket ping → AJAX fetch → DOM rebuild
  ────────────────────────────────────────────── */
  const userMeta = window.VS_USER || { id: 0 };
  if (userMeta.id <= 0 || !('WebSocket' in window)) return;

  const PLAN_RANK = { free: 0, basic: 1, premium: 2 };
  const userPlan  = (userMeta.planLevel || 'free').toLowerCase();

  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[c]));
  const setText = (sel, value) => document.querySelectorAll(sel).forEach(el => { el.textContent = value; });

  const fmtDuration = sec => {
    sec = Number(sec) || 0;
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    return (h > 0 ? h + 'h ' : '') + m + 'm';
  };
  const fmtAgo = iso => {
    if (!iso) return '—';
    const t = new Date(String(iso).replace(' ', 'T')).getTime();
    if (Number.isNaN(t)) return '—';
    const s = Math.max(0, (Date.now() - t) / 1000);
    if (s < 60) return Math.floor(s) + 's ago';
    if (s < 3600) return Math.floor(s / 60) + 'm ago';
    if (s < 86400) return Math.floor(s / 3600) + 'h ago';
    return Math.floor(s / 86400) + 'd ago';
  };
  const fmtCompact = n => new Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 }).format(Number(n) || 0);
  const planBadgeClass = p => ({ free: 'badge-plan-free', basic: 'badge-plan-basic', premium: 'badge-plan-premium' }[p] || 'badge-plan-free');

  const videoPayload = v => {
    const access = (v.access_level || 'free').toLowerCase();
    return {
      id: Number(v.id || 0),
      title: v.title || '',
      filePath: v.file_path || '',
      thumbUrl: v.thumbnail || '',
      desc: v.description || '',
      category: v.category || '',
      accessLevel: access,
      canWatch: (PLAN_RANK[userPlan] ?? 0) >= (PLAN_RANK[access] ?? 0),
      durationSec: Number(v.duration_sec || 0),
    };
  };

  function cardHTML(v, opts) {
    opts = opts || {};
    const access = (v.access_level || 'free').toLowerCase();
    const payload = esc(JSON.stringify(videoPayload(v)));
    const year = (v.created_at ? new Date(String(v.created_at).replace(' ', 'T')).getFullYear() : new Date().getFullYear()) || '';
    const thumb = v.thumbnail
      ? `<img src="${esc(v.thumbnail)}" alt="">`
      : '<i class="bi bi-play-fill" aria-hidden="true"></i>';
    const meta = opts.metaText !== undefined
      ? opts.metaText
      : `${esc(v.category || '')} &middot; ${fmtDuration(v.duration_sec)}`;
    const badges = [];
    if (opts.rank !== undefined) {
      badges.push(`<div class="u-card-badge u-badge-plan u-badge-plan-left ${planBadgeClass(access)}">${esc(access.charAt(0).toUpperCase() + access.slice(1))}</div>`);
      badges.push(`<div class="u-card-badge u-card-rank-badge">#${opts.rank}</div>`);
    } else {
      if ((Number(v.duration_sec) || 0) >= 60 * 60) badges.push('<div class="u-card-badge badge-hd">HD</div>');
      badges.push(`<div class="u-card-badge u-card-badge-right u-badge-plan ${planBadgeClass(access)}">${esc(access.charAt(0).toUpperCase() + access.slice(1))}</div>`);
    }
    const extraClass = opts.extraClass ? ' ' + opts.extraClass : '';
    return `
      <div class="u-video-card${extraClass} js-open-video"
           data-title="${esc((v.title || '').toLowerCase())}"
           data-category="${esc((v.category || '').toLowerCase())}"
           data-desc="${esc(((v.description || '') + '').toLowerCase().slice(0, 200))}"
           data-year="${year}"
           data-plan="${esc(access)}"
           data-views="${Number(v.views) || 0}"
           data-duration="${Number(v.duration_sec) || 0}"
           data-video="${payload}">
        ${badges.join('')}
        <div class="u-card-thumb">
          ${thumb}
          <div class="u-card-play-overlay"><div class="u-card-play-btn"><i class="bi bi-play-fill" aria-hidden="true"></i></div></div>
        </div>
        <div class="u-card-body">
          <div class="u-card-title">${esc(v.title || '')}</div>
          <div class="u-card-meta">${meta}</div>
        </div>
      </div>`;
  }

  function renderFeatured(items) {
    const target = document.querySelector('[data-feed-target="featured"]');
    if (!target) return;
    const list = (items || []).slice(0, 6);
    target.innerHTML = list.length
      ? list.map(v => cardHTML(v)).join('')
      : '<div class="u-empty"><span class="u-empty-icon"><i class="bi bi-collection-play"></i></span>No videos available yet.</div>';
  }

  function renderTrending(items) {
    const target = document.querySelector('[data-feed-target="trending"]');
    if (!target) return;
    const list = (items || []).slice(0, 5);
    target.innerHTML = list.map((v, i) => cardHTML(v, { rank: i + 1, metaText: fmtCompact(v.views) + ' views' })).join('');
  }

  function renderBrowse(items) {
    const target = document.querySelector('[data-feed-target="browse"]');
    if (!target) return;
    const list = items || [];
    target.innerHTML = list.length
      ? list.map(v => cardHTML(v, { extraClass: 'browse-card' })).join('')
      : '<div class="u-empty"><span class="u-empty-icon"><i class="bi bi-collection-play"></i></span>No videos published yet.</div>';
  }

  function renderWishlist(items) {
    const target = document.querySelector('[data-feed-target="wishlist"] .u-panel-body');
    if (!target) return;
    const list = items || [];
    if (!list.length) {
      target.innerHTML = '<div class="u-empty"><span class="u-empty-icon"><i class="bi bi-bookmark"></i></span>Your watchlist is empty. Browse videos and add them.</div>';
      return;
    }
    target.innerHTML = list.map(v => {
      const thumb = v.thumbnail ? `<img src="${esc(v.thumbnail)}" alt="">` : '<i class="bi bi-play-fill"></i>';
      const payload = esc(JSON.stringify(videoPayload(v)));
      return `
      <div class="u-list-row">
        <div class="u-list-thumb">${thumb}</div>
        <div class="u-list-info">
          <div class="u-list-title">${esc(v.title || '')}</div>
          <div class="u-list-meta">${esc(v.category || '')} &middot; Added ${fmtAgo(v.created_at)}</div>
        </div>
        <div class="u-list-action" style="display:flex;gap:8px;">
          <button class="u-btn u-btn-red js-open-video" type="button" style="padding:6px 14px;font-size:12px;" data-video="${payload}">
            <i class="bi bi-play-fill"></i> Play
          </button>
          <form method="post" action="${esc(userMeta.baseUrl)}?module=user&page=home&action=remove_wishlist" style="margin:0;">
            <input type="hidden" name="video_id" value="${Number(v.id) || 0}">
            <button type="submit" class="u-btn u-btn-ghost" style="padding:6px 14px;font-size:12px;">Remove</button>
          </form>
        </div>
      </div>`;
    }).join('');
  }

  function renderHistory(items) {
    const target = document.querySelector('[data-feed-target="history"] .u-panel-body');
    if (!target) return;
    const list = items || [];
    if (!list.length) {
      target.innerHTML = '<div class="u-empty"><span class="u-empty-icon"><i class="bi bi-tv"></i></span>No watch history yet. Start streaming.</div>';
      return;
    }
    target.innerHTML = list.map(v => {
      const thumb = v.thumbnail ? `<img src="${esc(v.thumbnail)}" alt="">` : '<i class="bi bi-play-fill"></i>';
      const payload = esc(JSON.stringify(videoPayload(v)));
      const progress = Math.max(0, Math.min(100, Number(v.progress) || 0));
      return `
      <div class="u-list-row">
        <div class="u-list-thumb">${thumb}</div>
        <div class="u-list-info">
          <div class="u-list-title">${esc(v.title || '')}</div>
          <div class="u-list-meta">${esc(v.category || '')} &middot; ${fmtAgo(v.created_at)} &middot; <span style="color:var(--red)">${progress}% watched</span></div>
          <div class="u-progress-bar" style="margin-top:5px;"><div class="u-progress-fill" style="width:${progress}%"></div></div>
        </div>
        <div class="u-list-action">
          <button class="u-btn u-btn-red js-open-video" type="button" style="padding:6px 14px;font-size:12px;" data-video="${payload}">
            <i class="bi bi-play-fill"></i> Resume
          </button>
        </div>
      </div>`;
    }).join('');
  }

  function renderSubscription(sub) {
    const target = document.querySelector('[data-feed-target="subscription"]');
    if (!target) return;
    if (!sub) {
      target.innerHTML = `
        <div class="u-upgrade-banner" style="margin-bottom:20px;">
          <div>
            <div class="u-upgrade-kicker">No Active Plan</div>
            <div class="u-upgrade-title">You are on the Free plan</div>
            <div class="u-upgrade-sub">Upgrade to unlock HD streaming, downloads and no ads.</div>
          </div>
        </div>`;
      return;
    }
    const status = (sub.sub_status || 'active');
    const daysLeft = Math.max(0, Number(sub.days_remaining) || 0);
    const priceStr = `${esc(sub.currency || 'USD')} ${(Number(sub.price) || 0).toFixed(2)}/mo`;
    const fmtDate = d => d ? new Date(String(d).replace(' ', 'T')).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
    target.innerHTML = `
      <div class="u-sub-card" style="margin-bottom:20px;">
        <div class="u-sub-header">
          <div>
            <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--red);margin-bottom:4px;">Current Plan</div>
            <div class="u-sub-plan">${esc(sub.plan_name || 'Free')}</div>
          </div>
          <span class="u-sub-badge">${esc(status.charAt(0).toUpperCase() + status.slice(1))}</span>
        </div>
        <div class="u-sub-grid">
          <div class="u-sub-item"><div class="u-sub-label">Price</div><div class="u-sub-value">${priceStr}</div></div>
          <div class="u-sub-item"><div class="u-sub-label">Started</div><div class="u-sub-value">${fmtDate(sub.starts_at)}</div></div>
          <div class="u-sub-item"><div class="u-sub-label">Expires</div><div class="u-sub-value">${fmtDate(sub.expires_at)}</div></div>
          <div class="u-sub-item"><div class="u-sub-label">Days Left</div><div class="u-sub-value" style="color:var(--${daysLeft < 7 ? 'red' : 'green'})">${daysLeft} days</div></div>
          <div class="u-sub-item"><div class="u-sub-label">Status</div><div class="u-sub-value"><span class="u-pill u-pill-${status === 'active' ? 'green' : 'red'}">${esc(status.charAt(0).toUpperCase() + status.slice(1))}</span></div></div>
        </div>
      </div>`;
  }

  function renderNotifications(items) {
    const target = document.querySelector('[data-feed-target="notifications"]');
    if (!target) return;
    const list = items || [];
    const clearForm = '<form method="post" action="' + esc(userMeta.baseUrl) + '?module=user&page=home&action=clear_notifications"><button class="u-dropdown-clear" type="submit">Clear</button></form>';
    const head = '<div class="u-dropdown-head"><strong>Notifications</strong>' + (list.length ? clearForm : '') + '</div>';
    if (!list.length) {
      target.innerHTML = head + '<div class="u-dropdown-empty">No notifications yet.</div>';
    } else {
      target.innerHTML = head + list.map(n => `
        <a class="u-dropdown-item" href="${esc(n.link_url || '#')}">
          <span class="u-dropdown-icon"><i class="bi ${esc(n.icon || 'bi-info-circle')}" aria-hidden="true"></i></span>
          <span>
            <strong>${esc(n.title || '')}</strong>
            <small>${esc(n.body || '')}</small>
          </span>
        </a>`).join('');
    }
    const dot = document.querySelector('#uNotifToggle .u-notif-dot');
    const hasItems = list.length > 0;
    if (hasItems && !dot) {
      document.getElementById('uNotifToggle')?.insertAdjacentHTML('beforeend', '<span class="u-notif-dot"></span>');
    } else if (!hasItems && dot) {
      dot.remove();
    }
  }

  function renderCounters(d) {
    if (typeof d.publishedCount === 'number') {
      const el = document.querySelector('.u-stats-row .c-red .u-stat-value');
      if (el) el.textContent = d.publishedCount.toLocaleString();
    }
    if (typeof d.wishlistCount === 'number') setText('[data-watchlist-count]', d.wishlistCount.toLocaleString());
    if (typeof d.historyCount === 'number')  setText('[data-history-count]',   d.historyCount.toLocaleString());
  }

  let inFlight = null;
  async function reloadUserFeed() {
    if (inFlight) return inFlight;
    const url = `${userMeta.baseUrl}?module=user&page=home&action=feed_json`;
    inFlight = fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(r => r.ok ? r.json() : null)
      .then(data => {
        if (!data || !data.ok) return;
        renderCounters(data);
        renderFeatured(data.featured);
        renderTrending(data.trending);
        renderBrowse(data.featured);
        renderWishlist(data.wishlistItems);
        renderHistory(data.historyItems);
        renderSubscription(data.subscription);
        renderNotifications(data.notifications);
      })
      .catch(() => {})
      .finally(() => { inFlight = null; });
    return inFlight;
  }

  let debounceTimer = null;
  const scheduleReload = () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => { debounceTimer = null; reloadUserFeed(); }, 200);
  };

  /* ── WebSocket connection ── */
  const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
  const wsUrl = `${wsProtocol}//${window.location.hostname}:8080/?role=user&uid=${userMeta.id}`;
  let socket = null;
  let retryMs = 1000;

  const userToast = msg => {
    let holder = document.querySelector('.u-toast-holder');
    if (!holder) {
      holder = document.createElement('div');
      holder.className = 'u-toast-holder';
      holder.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
      document.body.appendChild(holder);
    }
    const toast = document.createElement('div');
    toast.style.cssText = 'background:#1a1a1a;color:#fff;padding:12px 18px;border-radius:8px;border-left:3px solid #e50914;box-shadow:0 8px 24px rgba(0,0,0,.4);font:500 14px/1.4 system-ui;max-width:320px;';
    toast.textContent = msg;
    holder.appendChild(toast);
    setTimeout(() => toast.remove(), 4500);
  };

  const connect = () => {
    socket = new WebSocket(wsUrl);
    socket.addEventListener('open', () => { retryMs = 1000; });
    socket.addEventListener('message', event => {
      try {
        const frame = JSON.parse(event.data || '{}');
        if (frame.event !== 'live') return;
        if (frame.topic === 'videos') userToast('New videos available');
        if (frame.topic === 'subscription') userToast('Your subscription was updated');
        scheduleReload();
      } catch (e) {}
    });
    socket.addEventListener('close', () => {
      setTimeout(connect, retryMs);
      retryMs = Math.min(retryMs * 2, 15000);
    });
    socket.addEventListener('error', () => { try { socket.close(); } catch (e) {} });
  };
  connect();
});
