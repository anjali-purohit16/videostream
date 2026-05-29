window.uToast = window.uToast || function(msg, type) {
  const holder = document.getElementById('uToastHolder');
  if (!holder) return;
  const toast = document.createElement('div');
  toast.className = 'u-toast' + (type === 'error' ? ' error' : '');
  toast.textContent = msg;
  holder.appendChild(toast);
  setTimeout(() => toast.remove(), 3200);
};

document.addEventListener('DOMContentLoaded', function () {

// SIDEBAR + SEARCH + NAV
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

  const upage = (window.VS_USER && window.VS_USER.upage) || new URLSearchParams(window.location.search).get('upage') || 'home';
  document.querySelectorAll('.u-nav-item').forEach(el => {
    const href = el.getAttribute('href') || '';
    el.classList.toggle('active', el.dataset.upage === upage || href.includes('upage=' + upage));
  });

  document.addEventListener('click', () => {
    document.getElementById('uNotifDropdown')?.classList.remove('open');
  });

  document.getElementById('uNotifToggle')?.addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('uNotifDropdown')?.classList.toggle('open');
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
  function sendToLogin() {
    window.location.href = `${userMeta.baseUrl}login`;
  }

  async function reloadUserFeed() {
    if (inFlight) return inFlight;
    const url = `${userMeta.baseUrl}?module=user&page=home&action=feed_json`;
    inFlight = fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(r => {
        if (r.status === 401 || r.status === 403) {
          sendToLogin();
          return null;
        }
        return r.ok ? r.json() : null;
      })
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
        if (frame.topic === 'account_status') {
          sendToLogin();
          return;
        }
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

document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('browseSearch')?.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    const grid = document.getElementById('browseGrid');
    if (!grid) return;
    let visible = 0;
    grid.querySelectorAll('.browse-card').forEach(function(c) {
      const fields = [
        c.dataset.title    || '',
        c.dataset.category || '',
        c.dataset.desc     || '',
        c.dataset.year     || '',
        c.dataset.plan     || '',
        c.dataset.views    || '',
        c.dataset.duration || ''
      ];
      const matches = q === '' || fields.some(f => f.toLowerCase().includes(q));
      c.style.display = matches ? '' : 'none';
      if (matches) visible++;
    });
    let noRes = grid.querySelector('.u-search-no-results');
    if (q !== '' && visible === 0) {
      if (!noRes) {
        noRes = document.createElement('div');
        noRes.className = 'u-empty u-search-no-results';
        grid.appendChild(noRes);
      }
      noRes.innerHTML = '<span class="u-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></span>No results for &ldquo;<strong>' + q + '</strong>&rdquo;';
      noRes.style.display = '';
    } else if (noRes) {
      noRes.style.display = 'none';
    }
  });
});

document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('uWishlistBtn')?.addEventListener('click', function() {
    if (!window.currentVideoId) return;
    const baseUrl = (window.VS_USER && window.VS_USER.baseUrl) || '';
    fetch(baseUrl + '?action=wishlist_toggle&id=' + window.currentVideoId, { method: 'POST' })
      .then(r => r.json())
      .then(d => {
        window.uToast(d.message || 'Updated watchlist');
        if (typeof d.count !== 'undefined') {
          document.querySelectorAll('[data-watchlist-count]').forEach(el => el.textContent = Number(d.count).toLocaleString());
        }
      })
      .catch(() => window.uToast('Added to watchlist'));
  });
});

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.js-plan-open').forEach(btn => {
    btn.addEventListener('click', function() {
      document.getElementById('paymentPlanId').value = this.dataset.planId || '';
      document.getElementById('paymentPlanName').textContent = this.dataset.planName || '';
      document.getElementById('paymentPlanPrice').textContent = this.dataset.planPrice || '';
      document.getElementById('paymentRequestMsg').textContent = '';
      const panel = document.getElementById('paymentRequestPanel');
      panel.style.display = 'block';
      panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
  });
  document.getElementById('cancelPlanRequestBtn')?.addEventListener('click', function() {
    document.getElementById('paymentRequestPanel').style.display = 'none';
  });
  document.getElementById('sendPlanRequestBtn')?.addEventListener('click', function() {
    const msg = document.getElementById('paymentRequestMsg');
    const fd = new FormData();
    fd.append('plan_id', document.getElementById('paymentPlanId').value);
    fd.append('payment_method', document.getElementById('paymentMethod').value);
    fd.append('payment_note', document.getElementById('paymentNote').value.trim());
    this.disabled = true;
    msg.textContent = 'Sending request...';
    const baseUrl = (window.VS_USER && window.VS_USER.baseUrl) || '';
    fetch(baseUrl + '?action=subscription_request', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        msg.textContent = d.message || 'Request sent.';
        msg.style.color = d.ok ? 'var(--green)' : 'var(--red)';
        if (d.ok) window.uToast(d.message);
      })
      .catch(() => {
        msg.textContent = 'Could not send request.';
        msg.style.color = 'var(--red)';
      })
      .finally(() => { this.disabled = false; });
  });
});

window.selectedRating = window.selectedRating || 0;
window.updateStars = window.updateStars || function(val) {
  document.querySelectorAll('.u-star').forEach(s => {
    s.style.color = parseInt(s.dataset.v) <= val ? '#f5c518' : 'var(--muted2)';
  });
};

document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('uStarRow')?.addEventListener('mouseover', e => {
    const s = e.target.closest('.u-star'); if (s) window.updateStars(parseInt(s.dataset.v));
  });
  document.getElementById('uStarRow')?.addEventListener('mouseout', () => window.updateStars(window.selectedRating));
  document.getElementById('uStarRow')?.addEventListener('click', e => {
    const s = e.target.closest('.u-star');
    if (s) { window.selectedRating = parseInt(s.dataset.v); window.updateStars(window.selectedRating); }
  });
  document.getElementById('uReviewToggleBtn')?.addEventListener('click', function() {
    const reviewForm = document.getElementById('uReviewForm');
    const reportForm = document.getElementById('uReportForm');
    const isOpen = reviewForm.style.display !== 'none';
    reviewForm.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) { reportForm.style.display = 'none'; }
  });
  document.getElementById('uReviewSubmitBtn')?.addEventListener('click', function() {
    if (!window.currentVideoId) return;
    if (window.selectedRating < 1) { window.uToast('Please select a star rating first.', 'error'); return; }
    const fd = new FormData();
    fd.append('video_id', window.currentVideoId);
    fd.append('rating', window.selectedRating);
    fd.append('comment', document.getElementById('uReviewComment').value.trim());
    const msg = document.getElementById('uReviewMsg');
    msg.textContent = 'Submitting...';
    this.disabled = true;
    const baseUrl = (window.VS_USER && window.VS_USER.baseUrl) || '';
    fetch(baseUrl + '?action=save_review', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        msg.textContent = d.message || (d.ok ? 'Submitted!' : 'Error');
        msg.style.color = d.ok ? 'var(--green)' : 'var(--red)';
        if (d.ok) {
          window.uToast(d.message || 'Review submitted!');
          setTimeout(() => {
            document.getElementById('uReviewForm').style.display = 'none';
            msg.textContent = '';
            document.getElementById('uReviewComment').value = '';
            window.selectedRating = 0; window.updateStars(0);
          }, 2000);
        }
      })
      .catch(() => { msg.textContent = 'Error.'; msg.style.color = 'var(--red)'; })
      .finally(() => { this.disabled = false; });
  });
  document.getElementById('uReportToggleBtn')?.addEventListener('click', function() {
    const reportForm = document.getElementById('uReportForm');
    const reviewForm = document.getElementById('uReviewForm');
    const isOpen = reportForm.style.display !== 'none';
    reportForm.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) { reviewForm.style.display = 'none'; }
  });
  document.getElementById('uReportSubmitBtn')?.addEventListener('click', function() {
    if (!window.currentVideoId) return;
    const reason = document.getElementById('uReportReason').value.trim();
    if (!reason) { window.uToast('Please add a short report reason.', 'error'); return; }
    const fd = new FormData();
    fd.append('video_id', window.currentVideoId);
    fd.append('reason', reason);
    const msg = document.getElementById('uReportMsg');
    msg.textContent = 'Submitting...';
    this.disabled = true;
    const baseUrl = (window.VS_USER && window.VS_USER.baseUrl) || '';
    fetch(baseUrl + '?action=save_report', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        msg.textContent = d.message || (d.ok ? 'Report sent.' : 'Error');
        msg.style.color = d.ok ? 'var(--green)' : 'var(--red)';
        if (d.ok) {
          window.uToast(d.message || 'Report sent.');
          setTimeout(() => {
            document.getElementById('uReportForm').style.display = 'none';
            msg.textContent = '';
            document.getElementById('uReportReason').value = '';
          }, 2000);
        }
      })
      .catch(() => { msg.textContent = 'Error.'; msg.style.color = 'var(--red)'; })
      .finally(() => { this.disabled = false; });
  });
});

window.togglePw = window.togglePw || function(inputId, icon) {
  const inp = document.getElementById(inputId);
  if (!inp) return;
  inp.type = inp.type === 'password' ? 'text' : 'password';
  icon.style.opacity = inp.type === 'text' ? '1' : '0.5';
};

document.addEventListener('DOMContentLoaded', function () {
  const closeEditMode = function() {
    document.getElementById('profileEditMode').style.display = 'none';
    document.getElementById('profileViewMode').style.display = 'block';
    document.getElementById('profileCurrentPw').value = '';
    document.getElementById('profileNewPw').value = '';
    document.getElementById('profileConfirmPw').value = '';
    document.getElementById('profileEditMsg').style.display = 'none';
    document.getElementById('pwStrengthWrap').style.display = 'none';
  };

  const showProfileMsg = function(text, ok) {
    const el = document.getElementById('profileEditMsg');
    if (!el) return;
    el.style.display = 'block';
    el.textContent = text;
    el.style.background = ok ? 'rgba(34,197,94,.12)' : 'rgba(229,9,20,.12)';
    el.style.color = ok ? '#22c55e' : '#e50914';
    el.style.border = '1px solid ' + (ok ? 'rgba(34,197,94,.25)' : 'rgba(229,9,20,.25)');
  };

  document.getElementById('profileEditOpenBtn')?.addEventListener('click', function () {
    document.getElementById('profileViewMode').style.display = 'none';
    document.getElementById('profileEditMode').style.display = 'block';
    document.getElementById('profileEditMsg').style.display = 'none';
  });
  document.getElementById('profileEditCancelBtn')?.addEventListener('click', closeEditMode);
  document.getElementById('profileEditCancelBtn2')?.addEventListener('click', closeEditMode);

  document.getElementById('profileNewPw')?.addEventListener('input', function () {
    const val = this.value;
    const wrap = document.getElementById('pwStrengthWrap');
    const bar = document.getElementById('pwStrengthBar');
    const lbl = document.getElementById('pwStrengthLbl');
    if (!val) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'block';
    let score = 0;
    if (val.length >= 6) score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
      { w:'20%', bg:'#e50914', txt:'Very weak' },
      { w:'40%', bg:'#f97316', txt:'Weak' },
      { w:'60%', bg:'#eab308', txt:'Fair' },
      { w:'80%', bg:'#22c55e', txt:'Strong' },
      { w:'100%', bg:'#16a34a', txt:'Very strong' },
    ];
    const lvl = levels[Math.min(score, 4)];
    bar.style.width = lvl.w; bar.style.background = lvl.bg;
    lbl.textContent = lvl.txt; lbl.style.color = lvl.bg;
  });

  document.getElementById('profileSaveBtn')?.addEventListener('click', function () {
    const name = document.getElementById('profileName')?.value.trim();
    const currentPw = document.getElementById('profileCurrentPw')?.value;
    const newPw = document.getElementById('profileNewPw')?.value;
    const confirmPw = document.getElementById('profileConfirmPw')?.value;
    if (!name) { showProfileMsg('Display name cannot be empty.', false); return; }
    if (newPw) {
      if (!currentPw) { showProfileMsg('Please enter your current password.', false); return; }
      if (newPw.length < 6) { showProfileMsg('New password must be at least 6 characters.', false); return; }
      if (newPw !== confirmPw) { showProfileMsg('Passwords do not match.', false); return; }
    }
    const fd = new FormData();
    fd.append('name', name);
    fd.append('current_password', currentPw);
    fd.append('new_password', newPw);
    const btn = this; btn.textContent = 'Saving...'; btn.disabled = true;
    const baseUrl = (window.VS_USER && window.VS_USER.baseUrl) || '';
    fetch(baseUrl + '?action=update_profile', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        showProfileMsg(d.message, d.ok);
        if (d.ok) {
          window.uToast('Profile updated!');
          const dn = document.getElementById('profileDispName');
          if (dn) dn.textContent = d.name;
          document.querySelectorAll('.u-profile-name').forEach(el => el.textContent = d.name);
          document.getElementById('profileCurrentPw').value = '';
          document.getElementById('profileNewPw').value = '';
          document.getElementById('profileConfirmPw').value = '';
          document.getElementById('pwStrengthWrap').style.display = 'none';
          setTimeout(closeEditMode, 1600);
        }
      })
      .catch(() => showProfileMsg('Network error. Please try again.', false))
      .finally(() => { btn.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i> Save Changes'; btn.disabled = false; });
  });
});

document.addEventListener('DOMContentLoaded', function () {
  let currentVideoId = null;
  window.currentVideoId = null;
  let progressTimer = null;
  let controlsTimeout = null;
  let videoEl = null;

  const baseUrl = () => (window.VS_USER && window.VS_USER.baseUrl) || '';
  const planRank = window.PLAN_RANK || { free: 0, basic: 1, premium: 2 };

  function fmtTime(sec) {
    if (!isFinite(sec)) return '0:00';
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = Math.floor(sec % 60);
    return h > 0
      ? `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`
      : `${m}:${String(s).padStart(2,'0')}`;
  }

  function updateSeekUI() {
    if (!videoEl || !videoEl.duration) return;
    const pct = (videoEl.currentTime / videoEl.duration) * 100;
    document.getElementById('uSeekFill').style.width = pct + '%';
    document.getElementById('uSeekThumb').style.left = pct + '%';
    document.getElementById('uTimeDisplay').textContent =
      fmtTime(videoEl.currentTime) + ' / ' + fmtTime(videoEl.duration);
  }

  function updatePlayBtn() {
    const btn = document.getElementById('uPlayPauseBtn');
    if (!btn) return;
    btn.innerHTML = (videoEl && !videoEl.paused)
      ? '<i class="bi bi-pause-fill" aria-hidden="true"></i>'
      : '<i class="bi bi-play-fill" aria-hidden="true"></i>';
  }

  function showControls() {
    const ctrl = document.getElementById('uPlayerControls');
    if (ctrl) ctrl.style.opacity = '1';
    clearTimeout(controlsTimeout);
    controlsTimeout = setTimeout(hideControls, 3000);
  }

  function hideControls() {
    const ctrl = document.getElementById('uPlayerControls');
    if (ctrl && videoEl && !videoEl.paused) ctrl.style.opacity = '0';
  }

  function saveProgress(videoId, pct) {
    const fd = new FormData();
    fd.append('video_id', videoId);
    fd.append('progress', pct);
    fetch(baseUrl() + '?action=save_progress', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (typeof d.count !== 'undefined') {
          document.querySelectorAll('[data-history-count]').forEach(el => el.textContent = Number(d.count).toLocaleString());
        }
      })
      .catch(()=>{});
  }

  function videoMimeType(url) {
    const clean = String(url || '').split('?')[0].toLowerCase();
    if (clean.endsWith('.webm')) return 'video/webm';
    if (clean.endsWith('.ogg') || clean.endsWith('.ogv')) return 'video/ogg';
    if (clean.endsWith('.mov')) return 'video/quicktime';
    if (clean.endsWith('.m4v')) return 'video/x-m4v';
    return 'video/mp4';
  }

  function recordVideoView(videoId) {
    const fd = new FormData();
    fd.append('video_id', videoId);
    fetch(baseUrl() + '?action=record_view', { method: 'POST', body: fd }).catch(()=>{});
  }

  function openVideoModal(id, title, filePath, thumbUrl, desc, category, durationSec) {
    closeVideoModal(false);
    currentVideoId = id;
    window.currentVideoId = id;

    document.getElementById('uModalTitle').textContent = title;
    const durFmt = durationSec > 0 ? fmtTime(durationSec).replace(/^0:/,'') : '';
    document.getElementById('uModalMeta').textContent = [category, durFmt ? durFmt + ' min' : ''].filter(Boolean).join(' · ');
    document.getElementById('uModalDesc').textContent = desc || 'No description available.';

    document.getElementById('uReviewForm').style.display = 'none';
    document.getElementById('uReviewComment').value = '';
    document.getElementById('uReviewMsg').textContent = '';
    document.getElementById('uReportForm').style.display = 'none';
    document.getElementById('uReportReason').value = '';
    document.getElementById('uReportMsg').textContent = '';
    window.selectedRating = 0;
    window.updateStars(0);

    const wrap = document.getElementById('uModalVideoWrap');

    if (filePath) {
      const vid = document.createElement('video');
      vid.id = 'uModalVideo';
      vid.style.cssText = 'width:100%;height:100%;display:block;background:#000;';
      vid.preload = 'metadata';
      vid.autoplay = true;
      vid.controls = false;
      vid.disablePictureInPicture = true;
      vid.setAttribute('controlsList', 'nodownload noplaybackrate');
      vid.playsInline = true;
      if (thumbUrl) vid.poster = thumbUrl;
      const src = document.createElement('source');
      src.src = filePath;
      src.type = videoMimeType(filePath);
      vid.appendChild(src);
      document.getElementById('uModalVideoPlaceholder').style.display = 'none';
      wrap.appendChild(vid);
      videoEl = vid;
      recordVideoView(id);
      saveProgress(id, 0);

      document.getElementById('uPlayerControls').style.display = 'flex';
      document.getElementById('uPlayerControls').style.flexDirection = 'column';

      vid.addEventListener('play', updatePlayBtn);
      vid.addEventListener('pause', updatePlayBtn);
      vid.addEventListener('error', () => {
        window.uToast('This video file could not be loaded. Please check the saved video path.', 'error');
      });
      vid.addEventListener('timeupdate', () => {
        updateSeekUI();
        if (!progressTimer && vid.duration > 0) {
          progressTimer = setInterval(() => {
            saveProgress(id, Math.round((vid.currentTime / vid.duration) * 100));
          }, 10000);
        }
      });
      vid.addEventListener('ended', () => {
        saveProgress(id, 100);
        clearInterval(progressTimer); progressTimer = null;
        updatePlayBtn();
        document.getElementById('uPlayerControls').style.opacity = '1';
      });
      vid.addEventListener('pause', () => {
        if (vid.duration > 0) saveProgress(id, Math.round((vid.currentTime / vid.duration) * 100));
        updatePlayBtn();
        document.getElementById('uPlayerControls').style.opacity = '1';
        clearTimeout(controlsTimeout);
      });
      vid.addEventListener('volumechange', () => {
        const btn = document.getElementById('uMuteBtn');
        const sl = document.getElementById('uVolumeSlider');
        if (btn) btn.innerHTML = vid.muted || vid.volume === 0 ? '<i class="bi bi-volume-mute" aria-hidden="true"></i>' : '<i class="bi bi-volume-up" aria-hidden="true"></i>';
        if (sl) sl.value = vid.muted ? 0 : vid.volume;
      });

      wrap.addEventListener('mousemove', showControls);
      wrap.addEventListener('mouseleave', hideControls);
      vid.addEventListener('click', () => { vid.paused ? vid.play() : vid.pause(); });

      showControls();
      const playPromise = vid.play();
      if (playPromise && typeof playPromise.catch === 'function') {
        playPromise.catch(() => {
          document.getElementById('uPlayerControls').style.opacity = '1';
          updatePlayBtn();
        });
      }
    } else if (thumbUrl) {
      document.getElementById('uModalVideoPlaceholder').innerHTML =
        '<img src="' + thumbUrl + '" alt="' + title.replace(/"/g,'') + '" style="width:100%;height:100%;object-fit:contain;">';
      document.getElementById('uPlayerControls').style.display = 'none';
    } else {
      document.getElementById('uModalVideoPlaceholder').style.display = 'flex';
      document.getElementById('uPlayerControls').style.display = 'none';
    }

    document.getElementById('uVideoModal').style.display = 'flex';
  }

  function closeVideoModal(hide = true) {
    if (videoEl) {
      if (!videoEl.paused && videoEl.duration > 0) {
        saveProgress(currentVideoId, Math.round((videoEl.currentTime / videoEl.duration) * 100));
      }
      videoEl.pause();
      videoEl.removeAttribute('src');
      videoEl.load();
      videoEl = null;
    }
    clearInterval(progressTimer); progressTimer = null;
    clearTimeout(controlsTimeout);

    const existVid = document.getElementById('uModalVideo');
    if (existVid) existVid.remove();
    const ph = document.getElementById('uModalVideoPlaceholder');
    if (ph) { ph.style.display = 'flex'; ph.innerHTML = '<i class="bi bi-play-circle" aria-hidden="true"></i>'; }
    document.getElementById('uPlayerControls').style.display = 'none';
    if (hide) document.getElementById('uVideoModal').style.display = 'none';
    currentVideoId = null;
    window.currentVideoId = null;
  }

  function toggleFullscreen() {
    const box = document.getElementById('uModalBox');
    if (!box) return;
    if (!document.fullscreenElement) {
      (box.requestFullscreen || box.webkitRequestFullscreen || box.mozRequestFullScreen).call(box);
    } else {
      (document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen).call(document);
    }
  }

  document.addEventListener('click', function(e) {
    const opener = e.target.closest('.js-open-video');
    if (!opener) return;
    e.preventDefault();
    try {
      const data = JSON.parse(opener.dataset.video || '{}');
      const needed = (data.accessLevel || 'free').toLowerCase();
      const userPlanLevel = window.USER_PLAN_LEVEL || 'free';
      const userRank = planRank[userPlanLevel] ?? 0;
      const needRank = planRank[needed] ?? 0;
      console.log('[VS] plan check - user:', userPlanLevel, '('+userRank+')', 'needed:', needed, '('+needRank+')', 'filePath:', data.filePath);
      if (userRank < needRank) {
        window.uToast('This video requires a ' + needed + ' plan. Please upgrade.', 'error');
        window.location.href = window.SUBSCRIPTION_URL || baseUrl();
        return;
      }
      if (!data.filePath) {
        window.uToast('Video file not available.', 'error');
        return;
      }
      openVideoModal(data.id, data.title, data.filePath, data.thumbUrl, data.desc, data.category, data.durationSec);
    } catch (err) {
      window.uToast('Unable to open this video.', 'error');
    }
  });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden && videoEl && !videoEl.paused) {
      videoEl.pause();
      if (currentVideoId && videoEl.duration > 0) {
        saveProgress(currentVideoId, Math.round((videoEl.currentTime / videoEl.duration) * 100));
      }
    }
  });

  document.getElementById('uModalClose')?.addEventListener('click', closeVideoModal);
  document.getElementById('uModalBackBtn')?.addEventListener('click', closeVideoModal);
  document.getElementById('uVideoModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeVideoModal();
  });
  document.addEventListener('keydown', e => {
    const target = e.target;
    if (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return;
    if (target && target.isContentEditable) return;
    if (!videoEl) return;
    if (e.key === 'Escape') { closeVideoModal(); return; }
    if (e.key === ' ') { e.preventDefault(); videoEl.paused ? videoEl.play() : videoEl.pause(); }
    if (e.key === 'ArrowRight') { videoEl.currentTime = Math.min(videoEl.duration, videoEl.currentTime + 10); }
    if (e.key === 'ArrowLeft') { videoEl.currentTime = Math.max(0, videoEl.currentTime - 10); }
    if (e.key === 'ArrowUp') { videoEl.volume = Math.min(1, videoEl.volume + 0.1); }
    if (e.key === 'ArrowDown') { videoEl.volume = Math.max(0, videoEl.volume - 0.1); }
    if (e.key === 'm' || e.key === 'M') { videoEl.muted = !videoEl.muted; }
    if (e.key === 'f' || e.key === 'F') { toggleFullscreen(); }
  });

  document.getElementById('uPlayPauseBtn')?.addEventListener('click', () => {
    if (!videoEl) return;
    videoEl.paused ? videoEl.play() : videoEl.pause();
    showControls();
  });
  document.getElementById('uSkipBackBtn')?.addEventListener('click', () => {
    if (videoEl) { videoEl.currentTime = Math.max(0, videoEl.currentTime - 10); showControls(); }
  });
  document.getElementById('uSkipFwdBtn')?.addEventListener('click', () => {
    if (videoEl) { videoEl.currentTime = Math.min(videoEl.duration, videoEl.currentTime + 10); showControls(); }
  });

  (function () {
    const bar = document.getElementById('uSeekBar');
    if (!bar) return;
    let seeking = false;
    function seek(e) {
      if (!videoEl || !videoEl.duration) return;
      const rect = bar.getBoundingClientRect();
      const pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
      videoEl.currentTime = pct * videoEl.duration;
      updateSeekUI();
    }
    bar.addEventListener('mousedown', e => { seeking = true; seek(e); showControls(); });
    document.addEventListener('mousemove', e => { if (seeking) { seek(e); showControls(); } });
    document.addEventListener('mouseup', () => { seeking = false; });
    bar.addEventListener('touchstart', e => { seeking = true; seek(e.touches[0]); }, { passive: true });
    document.addEventListener('touchmove', e => { if (seeking) seek(e.touches[0]); }, { passive: true });
    document.addEventListener('touchend', () => { seeking = false; });
  })();

  document.getElementById('uVolumeSlider')?.addEventListener('input', function() {
    if (videoEl) { videoEl.volume = parseFloat(this.value); videoEl.muted = videoEl.volume === 0; }
    showControls();
  });
  document.getElementById('uMuteBtn')?.addEventListener('click', () => {
    if (!videoEl) return;
    videoEl.muted = !videoEl.muted;
    const sl = document.getElementById('uVolumeSlider');
    if (sl) sl.value = videoEl.muted ? 0 : videoEl.volume;
    showControls();
  });
  document.getElementById('uSpeedSelect')?.addEventListener('change', function() {
    if (videoEl) videoEl.playbackRate = parseFloat(this.value);
    showControls();
  });
  document.getElementById('uFullscreenBtn')?.addEventListener('click', () => { toggleFullscreen(); showControls(); });
  document.addEventListener('fullscreenchange', () => {
    const btn = document.getElementById('uFullscreenBtn');
    if (btn) btn.innerHTML = document.fullscreenElement ? '<i class="bi bi-fullscreen-exit" aria-hidden="true"></i>' : '<i class="bi bi-fullscreen" aria-hidden="true"></i>';
  });
});
