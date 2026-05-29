document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('app-ready');

    const sidebar  = document.querySelector('[data-sidebar]');
    const toggle   = document.querySelector('[data-sidebar-toggle]');
    const search   = document.querySelector('[data-dashboard-search]');
    const backdrop = document.getElementById('adminModalBackdrop');

    /* ── SIDEBAR TOGGLE ── */
    toggle?.addEventListener('click', () => {
        const collapsed = sidebar?.classList.toggle('collapsed');
        document.body.classList.toggle('sidebar-collapsed', !!collapsed);
        // Persist preference
        localStorage.setItem('vs_sidebar_collapsed', collapsed ? '1' : '0');
    });

    // Restore on load
    if (localStorage.getItem('vs_sidebar_collapsed') === '1') {
        sidebar?.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
    }

    /* ── GLOBAL SEARCH ── */
    search?.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        document.querySelectorAll('.dashboard-search-item').forEach(item => {
            item.classList.toggle('is-hidden', q !== '' && !item.textContent.toLowerCase().includes(q));
        });
        document.querySelectorAll('[data-search-row]').forEach(row => {
            row.classList.toggle('is-hidden', q !== '' && !row.textContent.toLowerCase().includes(q));
        });
    });

    /* ── MODULE FILTER SEARCH ── */
    document.querySelectorAll('.module-filters input[name="search"]').forEach(input => {
        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            document.querySelectorAll('[data-search-row]').forEach(row => {
                row.classList.toggle('is-hidden', q !== '' && !row.textContent.toLowerCase().includes(q));
            });
        });
    });

    /* ── CONFIRM FORMS ── */
    document.addEventListener('submit', e => {
        const form = e.target.closest('[data-confirm]');
        if (!form) return;
        if (form.dataset.confirmed === '1') {
            delete form.dataset.confirmed;
            return;
        }
        e.preventDefault();
        showAdminConfirm(form.dataset.confirm || 'Are you sure?', () => {
            form.dataset.confirmed = '1';
            form.requestSubmit();
        });
    });

    /* ── TOAST BUTTONS ── */
    document.querySelectorAll('[data-toast]').forEach(btn => {
        btn.addEventListener('click', () => showToast(btn.dataset.toast));
    });

    /* ── CHART BARS ── */
    document.querySelectorAll('.chart-bar').forEach(bar => {
        bar.addEventListener('click', () => showToast(`${bar.dataset.label}: ${bar.dataset.value}`));
    });

    /* ── TOPBAR DROPDOWN MENUS ── */
    document.querySelectorAll('[data-menu-toggle]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const name = btn.dataset.menuToggle;
            document.querySelectorAll('.topbar-dropdown').forEach(menu => {
                menu.classList.toggle('open', menu.dataset.menu === name && !menu.classList.contains('open'));
            });
        });
    });
    document.addEventListener('click', () => {
        document.querySelectorAll('.topbar-dropdown.open').forEach(m => m.classList.remove('open'));
    });
    document.querySelectorAll('.topbar-dropdown').forEach(m => {
        m.addEventListener('click', e => e.stopPropagation());
    });

    /* ── ADMIN MODAL BACKDROP (scoped — does not cover sidebar) ── */
    const stopMedia = root => {
        root?.querySelectorAll('video, audio').forEach(media => {
            try {
                media.pause();
                media.currentTime = 0;
                media.load();
            } catch (e) {}
        });
    };
    // Show backdrop when any Bootstrap modal opens
    document.addEventListener('show.bs.modal', e => {
        if (e.target.classList.contains('admin-modal')) {
            backdrop?.classList.add('show');
            // Prevent body scroll but keep layout stable
            document.body.style.overflow = 'hidden';
        }
    });
    document.addEventListener('hide.bs.modal', e => {
        if (e.target.classList.contains('admin-modal')) {
            stopMedia(e.target);
        }
    });
    // Hide backdrop when modal closes
    document.addEventListener('hidden.bs.modal', e => {
        if (e.target.classList.contains('admin-modal')) {
            stopMedia(e.target);
            // Only hide backdrop if no other modal is open
            if (!document.querySelector('.admin-modal.show')) {
                backdrop?.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
    });
    // Click backdrop to close active modal
    backdrop?.addEventListener('click', () => {
        const openModal = document.querySelector('.admin-modal.show');
        if (openModal) {
            bootstrap.Modal.getInstance(openModal)?.hide();
        }
    });

    window.addEventListener('beforeunload', () => stopMedia(document));
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopMedia(document);
    });

    /* Live admin updates: WebSocket ping → AJAX fetch of admin feed_json → DOM rebuild. */
    
    const baseUrl = document.body?.dataset.baseUrl || '/';
    const params = new URLSearchParams(window.location.search);
    const pathAfterBase = window.location.pathname
        .replace(new RegExp(`^${baseUrl.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`), '')
        .replace(/^\/+/, '');
    const pathParts = pathAfterBase.split('/').filter(Boolean);
    const page = params.get('page') || (pathParts[0] === 'admin' && pathParts[1] ? pathParts[1] : 'dashboard');
    const reloadPages = new Set(['payments', 'subscriptions', 'messages']);
    const feedUrl = `${baseUrl}?module=admin&page=dashboard&action=feed_json`;

    let firstFeed = true;
    let paymentVersion = null;
    let subscriptionVersion = null;
    let messageVersion = null;
    let socket = null;
    let socketConnected = false;
    let retryMs = 1000;
    let inFlight = null;
    let debounceTimer = null;
    let pollTimer = null;

    const applyFeed = data => {
        if (document.hidden) return;
        updateDashboardLive(data.dashboard || {});
        updateReviewLive(data.reviews || {});
        updateReportLive(data.reports || {});
        updateNavCounts(data.navCounts || {});
        updateTopbarLive(data.topbar || {});
        const versions = data.versions || {};
        if (firstFeed) {
            paymentVersion = versions.payments || null;
            subscriptionVersion = versions.subscriptions || null;
            messageVersion = versions.messages || null;
            firstFeed = false;
            return;
        }
        if (reloadPages.has(page) && page === 'payments' && versions.payments && versions.payments !== paymentVersion) {
            window.location.reload(); return;
        }
        if (reloadPages.has(page) && page === 'subscriptions' && versions.subscriptions && versions.subscriptions !== subscriptionVersion) {
            window.location.reload(); return;
        }
        if (reloadPages.has(page) && page === 'messages' && versions.messages && versions.messages !== messageVersion) {
            window.location.reload(); return;
        }
        paymentVersion = versions.payments || paymentVersion;
        subscriptionVersion = versions.subscriptions || subscriptionVersion;
        messageVersion = versions.messages || messageVersion;
    };

    const reloadAdminFeed = () => {
        if (document.hidden) return Promise.resolve();
        if (inFlight) return inFlight;
        inFlight = fetch(feedUrl, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        })
            .then(r => r.ok ? r.json() : null)
            .then(data => { if (data && data.ok) applyFeed(data); })
            .catch(() => {})
            .finally(() => { inFlight = null; });
        return inFlight;
    };

    const scheduleReload = (delay = 200) => {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { debounceTimer = null; reloadAdminFeed(); }, delay);
    };

    const queueNextPoll = () => {
        if (pollTimer) clearTimeout(pollTimer);
        pollTimer = setTimeout(() => {
            pollTimer = null;
            if (!document.hidden) {
                scheduleReload(socketConnected ? 1000 : 0);
            }
            queueNextPoll();
        }, socketConnected ? 30000 : 5000);
    };

    reloadAdminFeed();
    queueNextPoll();
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) return;
        scheduleReload(socketConnected ? 1000 : 0);
    });

    // php controller 
    if ('WebSocket' in window) {
        const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${wsProtocol}//${window.location.hostname}:8080/?role=admin`;

        const connect = () => {
            socket = new WebSocket(wsUrl);
            socket.addEventListener('open', () => {
                socketConnected = true;
                retryMs = 1000;
                reloadAdminFeed();
            });
            socket.addEventListener('message', event => {
                try {
                    const frame = JSON.parse(event.data || '{}');
                    if (frame.event === 'live') scheduleReload();
                } catch (e) { /* ignore */ }
            });
            socket.addEventListener('close', () => {
                socketConnected = false;
                setTimeout(connect, retryMs);
                retryMs = Math.min(retryMs * 2, 15000);
            });
            socket.addEventListener('error', () => { try { socket.close(); } catch (e) {} });
        };
        connect();
    }
});

function showToast(message, type = 'info') {
    let holder = document.querySelector('.toast-holder');
    if (!holder) {
        holder = document.createElement('div');
        holder.className = 'toast-holder';
        document.body.appendChild(holder);
    }
    const toast = document.createElement('div');
    toast.className = 'toast-custom';
    toast.textContent = message;
    holder.appendChild(toast);
    setTimeout(() => toast.remove(), 3200);
}

function showAdminConfirm(message, onConfirm) {
    const modal = document.getElementById('adminConfirm');
    if (!modal) {
        if (window.confirm(message)) onConfirm();
        return;
    }

    const messageEl = modal.querySelector('[data-confirm-message]');
    const ok = modal.querySelector('[data-confirm-ok]');
    const cancel = modal.querySelector('[data-confirm-cancel]');
    if (messageEl) messageEl.textContent = message;

    const close = () => {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        ok?.removeEventListener('click', confirm);
        cancel?.removeEventListener('click', close);
        modal.removeEventListener('click', outside);
        document.removeEventListener('keydown', escape);
    };
    const confirm = () => {
        close();
        onConfirm();
    };
    const outside = event => {
        if (event.target === modal) close();
    };
    const escape = event => {
        if (event.key === 'Escape') close();
    };

    ok?.addEventListener('click', confirm);
    cancel?.addEventListener('click', close);
    modal.addEventListener('click', outside);
    document.addEventListener('keydown', escape);
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(() => ok?.focus(), 30);
}

function htmlEscape(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[ch]));
}

function updateTopbarLive(topbar) {
    const notificationCount = Number(topbar.notificationCount || 0);
    const messageCount = Number(topbar.messageCount || 0);
    updateCountBadge('notifications', notificationCount);
    updateCountBadge('messages', messageCount);
    renderLiveMenu('notifications', topbar.notifications || []);
    renderLiveMenu('messages', topbar.messages || []);
}

function updateCountBadge(name, count) {
    document.querySelectorAll(`[data-topbar-count="${name}"], [data-nav-count="${name}"]`).forEach(el => {
        el.textContent = count.toLocaleString();
        el.classList.toggle('is-hidden', count <= 0);
    });
}

function renderLiveMenu(name, items) {
    const menu = document.querySelector(`[data-live-menu="${name}"]`);
    if (!menu) return;
    const head = name === 'messages'
        ? `<div class="dropdown-head"><strong>Messages</strong><span class="dropdown-actions"><form method="post" action="${adminActionUrl('messages', 'read')}"><button type="submit">Mark read</button></form><form method="post" action="${adminActionUrl('messages', 'clear')}" data-confirm="Clear all messages?"><button type="submit">Clear</button></form></span></div>`
        : `<div class="dropdown-head"><strong>Notifications</strong><span class="dropdown-actions"><form method="post" action="${adminActionUrl('notifications', 'read')}"><button type="submit">Mark read</button></form><form method="post" action="${adminActionUrl('notifications', 'clear')}" data-confirm="Clear all notifications?"><button type="submit">Clear</button></form></span></div>`;

    const body = Array.isArray(items) && items.length
        ? items.map(item => name === 'messages' ? renderMessageMenuItem(item) : renderNotificationMenuItem(item)).join('')
        : `<div class="dropdown-empty">No ${name === 'messages' ? 'messages' : 'notifications'}</div>`;
    menu.innerHTML = head + body;
}

function renderNotificationMenuItem(item) {
    return `<a class="dropdown-item" href="${htmlEscape(item.link_url || '#')}">
        <strong>${htmlEscape(item.title || '')}</strong>
        <span>${htmlEscape(item.body || '')}</span>
    </a>`;
}

function renderMessageMenuItem(item) {
     return `<a class="dropdown-item" href="${adminActionUrl('messages', 'index')}&id=${Number(item.id || 0)}">
        <strong>${htmlEscape(item.sender_name || 'User')}</strong>
        <span>${htmlEscape(item.subject || '')}</span>
    </a>`;
}

function statusClass(status) {
    return {
        published: 'pill-green',
        active: 'pill-green',
        success: 'pill-green',
        approved: 'pill-green',
        processing: 'pill-amber',
        pending: 'pill-amber',
        draft: 'pill-blue',
        resolved: 'pill-green',
        dismissed: 'pill-red',
        rejected: 'pill-red',
        failed: 'pill-red',
        refunded: 'pill-red'
    }[String(status || '').toLowerCase()] || 'pill-red';
}

function shortDate(value) {
    if (!value) return '-';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '-';
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function compactNumber(value) {
    return new Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 }).format(Number(value || 0));
}

function money(value) {
    return '$' + new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(Number(value || 0));
}

function updateDashboardLive(dashboard) {
    if (!dashboard.stats) return;
    const stats = dashboard.stats;
    const statMap = {
        total_videos: Number(stats.total_videos || 0).toLocaleString(),
        active_users: Number(stats.active_users || 0).toLocaleString(),
        monthly_revenue: money(stats.monthly_revenue || 0),
        total_views: compactNumber(stats.total_views || 0),
        revenue_growth_pct: `↑ ${Number(stats.revenue_growth_pct || 0).toFixed(1)}% vs last month`
    };
    Object.entries(statMap).forEach(([key, value]) => {
        const el = document.querySelector(`[data-live-stat="${key}"]`);
        if (el) el.textContent = value;
    });

    if (window.VSCharts?.revenue && Array.isArray(dashboard.revenueChart)) {
        window.VSCharts.revenue.data.labels = dashboard.revenueChart.map(row => row.month_label);
        window.VSCharts.revenue.data.datasets[0].data = dashboard.revenueChart.map(row => Number(row.revenue || 0));
        window.VSCharts.revenue.update('none');
    }

    if (window.VSCharts?.subscribers && Array.isArray(dashboard.subscriptions)) {
        window.VSCharts.subscribers.data.labels = dashboard.subscriptions.map(row => row.plan_name);
        window.VSCharts.subscribers.data.datasets[0].data = dashboard.subscriptions.map(row => Number(row.subscriber_count || 0));
        window.VSCharts.subscribers.update('none');
    }
}

function updateReviewLive(reviews) {
    const avg = document.querySelector('[data-live-review-avg]');
    if (avg && reviews.avgRating !== undefined) {
        avg.textContent = `★ ${Number(reviews.avgRating || 0).toFixed(1)} Avg Rating`;
    }

    const body = document.querySelector('[data-live-review-rows]');
    if (!body || !Array.isArray(reviews.items)) return;
    body.innerHTML = reviews.items.map(review => {
        const rating = Math.max(0, Math.min(5, Number(review.rating || 0)));
        const stars = '★'.repeat(rating) + '☆'.repeat(5 - rating);
        return `<tr data-search-row>
            <td><strong>${htmlEscape(review.user)}</strong></td>
            <td>${htmlEscape(review.video)}</td>
            <td><span class="stars">${stars}</span></td>
            <td>${htmlEscape(review.comment)}</td>
            <td>${shortDate(review.created_at)}</td>
            <td><span class="pill ${statusClass(review.status)}">${htmlEscape(capitalise(review.status))}</span></td>
            <td class="action-cell">
                <form method="post" action="${adminActionUrl('reviews', 'approve')}">
                    <input type="hidden" name="id" value="${Number(review.id || 0)}">
                    <button class="mini-btn ${review.status === 'approved' ? 'mini-btn-success' : 'mini-btn-success-outline'}" type="submit">Approve</button>
                </form>
                <form method="post" action="${adminActionUrl('reviews', 'delete')}">
                    <input type="hidden" name="id" value="${Number(review.id || 0)}">
                    <button class="mini-btn ${review.status === 'rejected' ? 'mini-btn-danger' : 'mini-btn-danger-outline'}" type="submit">Delete</button>
                </form>
            </td>
        </tr>`;
    }).join('');
}

function updateReportLive(reports) {
    const body = document.querySelector('[data-live-report-rows]');
    if (!body || !Array.isArray(reports.items)) return;
    body.innerHTML = reports.items.map(report => `<tr data-search-row>
        <td>${htmlEscape(report.report_code)}</td>
        <td><span class="pill pill-gray">${htmlEscape(report.type)}</span></td>
        <td>${htmlEscape(report.reporter)}</td>
        <td><strong>${htmlEscape(report.content_ref)}</strong></td>
        <td>${htmlEscape(report.reason)}</td>
        <td>${shortDate(report.created_at)}</td>
        <td><span class="pill ${statusClass(report.status)}">${htmlEscape(capitalise(report.status))}</span></td>
        <td class="action-cell">
            <form method="post" action="${adminActionUrl('reports', 'resolve')}">
                <input type="hidden" name="id" value="${Number(report.id || 0)}">
                <button class="mini-btn" type="submit">Review</button>
            </form>
            <form method="post" action="${adminActionUrl('reports', 'dismiss')}">
                <input type="hidden" name="id" value="${Number(report.id || 0)}">
                <button class="mini-btn" type="submit">Dismiss</button>
            </form>
        </td>
    </tr>`).join('');
}

function updateNavCounts(counts) {
    Object.entries(counts || {}).forEach(([key, value]) => {
        const count = Number(value || 0);
        document.querySelectorAll(`[data-nav-count="${key}"]`).forEach(el => {
            el.textContent = count.toLocaleString();
            el.classList.toggle('is-hidden', count <= 0);
        });
    });
}

function adminActionUrl(page, action) {
    const baseUrl = document.body?.dataset.baseUrl || '/';
    return `${baseUrl}admin/${encodeURIComponent(page)}/${encodeURIComponent(action)}`;
}

function capitalise(value) {
    value = String(value || '');
    return value.charAt(0).toUpperCase() + value.slice(1);
}
