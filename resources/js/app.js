import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import * as MjccCharts from './charts';
import * as MjccMaps from './maps';

window.alpine = Alpine;

window.Mjcc = {};
window.Mjcc.charts = MjccCharts;
window.Mjcc.maps = MjccMaps;

document.addEventListener('alpine:init', () => {
    // Live clock in Indonesian format
    Alpine.data('liveClock', () => ({
        time: '',
        date: '',
        init() {
            this.tick();
            setInterval(() => this.tick(), 1000);
        },
        tick() {
            const now = new Date();
            this.time = now.toLocaleTimeString('id-ID');
            this.date = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
        },
    }));

    // Mobile sidebar toggle
    Alpine.data('sidebarNav', () => ({
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        },
    }));

    // Dropdown (user menu, etc.)
    Alpine.data('dropdown', () => ({
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        },
    }));

    // Collapsible sidebar group with a header (used for nested menus).
    Alpine.data('sidebarGroup', (defaultOpen = false) => ({
        open: defaultOpen,
        toggle() {
            this.open = !this.open;
        },
    }));

    // Notifications / toast stack
    Alpine.data('notifications', () => ({
        items: [],
        init() {
            if (window.flashMessages && window.flashMessages.length) {
                window.flashMessages.forEach((msg) => this.push(msg.type, msg.message));
            }
        },
        push(type = 'success', message = '') {
            const id = Date.now() + Math.random();
            const colors = {
                success: 'bg-emerald-600',
                error: 'bg-red-600',
                warning: 'bg-amber-500',
                info: 'bg-blue-600',
            };
            this.items.push({ id, type, message, color: colors[type] ?? 'bg-blue-600' });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) {
            this.items = this.items.filter((i) => i.id !== id);
        },
        icons: {
            success: '✓',
            error: '✕',
            warning: '!',
            info: 'i',
        },
    }));

    // SOS monitoring: polls the open-alert count for the badge + new-SOS toast.
    Alpine.data('sosMonitor', () => ({
        open: 0,
        active: 0,
        url: '/sos/active-count',
        timer: null,
        init() {
            this.open = Number(this.$el.dataset.open ?? 0);
            this.active = Number(this.$el.dataset.active ?? 0);
            this.tick();
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                } else if (!this.timer) {
                    this.tick();
                }
            });
        },
        tick() {
            this.timer = setInterval(() => this.poll(), 15000);
            this.poll();
        },
        async poll() {
            try {
                const res = await fetch(this.url, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                const previous = this.open;
                this.open = Number(data.open ?? 0);
                this.active = Number(data.active ?? 0);
                if (this.open > previous) {
                    const root = document.querySelector('[x-data="notifications()"]');
                    if (root && root.__x) {
                        root.__x.$data.push('warning', `SOS darurat baru! ${this.open} permintaan sedang dalam penanganan.`);
                    }
                }
            } catch (e) {
                // transient network error: keep the current badge value
            }
        },
    }));

    // Command alert bell: polls the open-alert summary, updates the badge,
    // renders the dropdown list, and toasts when new alerts appear.
    Alpine.data('commandAlertMonitor', () => ({
        open: false,
        openCount: 0,
        recent: [],
        url: '',
        timer: null,
        init() {
            this.openCount = Number(this.$el.dataset.count ?? 0);
            this.recent = this.$el.dataset.recent ? JSON.parse(this.$el.dataset.recent) : [];
            this.url = this.$el.dataset.url || '';
            this.render();
            this.start();
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stop();
                } else {
                    this.start();
                }
            });
        },
        start() {
            this.stop();
            this.poll();
            this.timer = setInterval(() => this.poll(), 20000);
        },
        stop() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        async poll() {
            if (!this.url) {
                return;
            }
            try {
                const res = await fetch(this.url, { headers: { Accept: 'application/json' } });
                if (!res.ok) {
                    return;
                }
                const data = await res.json();
                const total = Number(data.counts?.total_open ?? 0);
                const previous = this.openCount;
                this.openCount = total;
                this.recent = Array.isArray(data.recent) ? data.recent : [];
                this.render();
                if (total > previous) {
                    const root = document.querySelector('[x-data="notifications()"]');
                    if (root && root.__x) {
                        root.__x.$data.push('warning', `${total} command alert sedang terbuka.`);
                    }
                }
            } catch (e) {
                // transient network error: keep the current badge and list
            }
        },
        render() {
            const list = document.getElementById('alert-dropdown-list');
            if (!list) {
                return;
            }
            const sev = {
                critical: { icon: '🔴', soft: 'bg-red-50', border: 'border-l-red-500' },
                warning: { icon: '🟠', soft: 'bg-amber-50', border: 'border-l-amber-500' },
                info: { icon: '🔵', soft: 'bg-blue-50', border: 'border-l-blue-500' },
            };
            if (!this.recent.length) {
                list.innerHTML = '<div class="px-4 py-6 text-center text-[12px] text-gray-400">Tidak ada alert terbuka</div>';
                return;
            }
            list.innerHTML = this.recent.map((a) => {
                const cfg = sev[a.severity] ?? sev.info;
                return `<a href="${a.url}" class="block px-4 py-3 ${cfg.soft} ${cfg.border} border-l-4 hover:bg-gray-50">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 text-sm leading-none">${cfg.icon}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[12px] font-bold text-gray-800 truncate">${a.title}</div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">${a.kecamatan ? '📍 ' + a.kecamatan : ''} ${a.opened_human ? '· ' + a.opened_human : ''}</div>
                                </div>
                                <span class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full ${a.status === 'baru' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'}">${a.status_label}</span>
                            </div>
                        </a>`;
            }).join('');
        },
    }));

    // Dashboard command center: auto-refreshes the overview data periodically
    // so it stays live without manual reloads. The preference persists in
    // localStorage and the countdown restarts when the tab regains focus.
    Alpine.data('dashboardAutoRefresh', () => ({
        enabled: true,
        remaining: 0,
        seconds: 300,
        url: '',
        timer: null,
        init() {
            this.seconds = Math.max(Number(this.$el.dataset.seconds ?? 300), 10);
            this.url = this.$el.dataset.url || '';
            const defaultOn = this.$el.dataset.default === '1';
            let saved = null;
            try {
                saved = localStorage.getItem('mjcc:auto-refresh');
            } catch (e) {
                saved = null;
            }
            this.enabled = saved === null ? defaultOn : saved === 'on';
            this.remaining = this.seconds;
            if (this.enabled) {
                this.start();
            }
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stop();
                } else {
                    this.remaining = this.seconds;
                    if (this.enabled) {
                        this.start();
                    }
                }
            });
        },
        get remainingLabel() {
            const m = Math.floor(this.remaining / 60);
            const s = String(this.remaining % 60).padStart(2, '0');
            return `${m}:${s}`;
        },
        start() {
            this.stop();
            this.timer = setInterval(() => {
                this.remaining -= 1;
                if (this.remaining <= 0) {
                    this.remaining = this.seconds;
                    this.refresh();
                }
            }, 1000);
        },
        stop() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        toggle() {
            this.enabled = !this.enabled;
            this.remaining = this.seconds;
            try {
                localStorage.setItem('mjcc:auto-refresh', this.enabled ? 'on' : 'off');
            } catch (e) {
                // storage unavailable: preference applies for this session only
            }
            if (this.enabled) {
                this.start();
            } else {
                this.stop();
            }
        },
        async refresh() {
            if (!this.url) {
                return;
            }
            try {
                const res = await fetch(this.url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken ?? '',
                    },
                });
                if (!res.ok) {
                    return;
                }
                window.location.reload();
            } catch (e) {
                // transient network error: retry on the next interval
            }
        },
    })),

    // Live SOS command center (index): polls stat counts, the open-alert map,
    // and the list so the page updates with no manual refresh. Filters/paging
    // from the current URL query string are preserved.
    Alpine.data('sosLiveIndex', () => ({
        url: '',
        pollTimer: null,
        init() {
            this.url = this.$el.dataset.url || '/sos/live';
            this.start();
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stop();
                } else {
                    this.start();
                }
            });
        },
        start() {
            this.stop();
            this.poll();
            this.pollTimer = setInterval(() => this.poll(), 15000);
        },
        stop() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },
        async poll() {
            try {
                const res = await fetch(this.url + window.location.search, { headers: { Accept: 'application/json' } });
                if (!res.ok) {
                    return;
                }
                const data = await res.json();

                Object.entries(data.counts ?? {}).forEach(([key, value]) => {
                    const el = document.getElementById('sos-stat-' + key);
                    if (el && value != null) {
                        el.textContent = value;
                    }
                });

                const list = document.getElementById('sos-list');
                if (list && typeof data.listHtml === 'string') {
                    list.innerHTML = data.listHtml;
                }

                const M = window.Mjcc?.maps;
                if (M && window.sosIndexMap && Array.isArray(data.markers)) {
                    M.renderMarkers(window.sosIndexMap, data.markers, { cluster: false, fitBounds: false });
                }
            } catch (e) {
                // transient network error: keep the current DOM
            }
        },
    }));

    // Live SOS detail: status card, timeline, and management buttons refresh
    // in place so viewers see operator updates without reloading.
    Alpine.data('sosLiveDetail', () => ({
        url: '',
        pollTimer: null,
        init() {
            this.url = this.$el.dataset.url || '';
            this.start();
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stop();
                } else {
                    this.start();
                }
            });
        },
        start() {
            this.stop();
            this.poll();
            this.pollTimer = setInterval(() => this.poll(), 15000);
        },
        stop() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },
        async poll() {
            if (!this.url) {
                return;
            }
            try {
                const res = await fetch(this.url, { headers: { Accept: 'application/json' } });
                if (!res.ok) {
                    return;
                }
                const data = await res.json();
                const apply = (id, html) => {
                    const el = document.getElementById(id);
                    if (el && typeof html === 'string') {
                        el.innerHTML = html;
                    }
                };
                apply('sos-status-card', data.statusCardHtml);
                apply('sos-timeline', data.timelineHtml);
                apply('sos-actions', data.actionsHtml);

                const M = window.Mjcc?.maps;
                if (M && window.sosDetailMap && Array.isArray(data.responders)) {
                    M.renderResponders(window.sosDetailMap, window.sosDetailSender, data.responders, {
                        onRoute: ({ distanceKm, etaMin }) => {
                            const info = document.getElementById('sos-route-info');
                            if (info) {
                                info.textContent = `🚓 Rute terdekat ${distanceKm} km · ±${etaMin} mnt`;
                                info.classList.remove('hidden');
                            }
                        },
                    });
                }
            } catch (e) {
                // transient network error: keep the current DOM
            }
        },
    }));

    // Confirmation dialog for delete (also used generically)
    Alpine.data('confirmDialog', () => ({
        open: false,
        title: 'Konfirmasi',
        message: 'Apakah Anda yakin?',
        confirmLabel: 'Ya, lanjutkan',
        cancelLabel: 'Batal',
        url: '',
        method: 'DELETE',
        confirm() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.url;
            form.classList.add('hidden');
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = this.method;
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = window.csrfToken ?? '';
            form.appendChild(methodInput);
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        },
        cancel() {
            this.open = false;
        },
    }));
});

// Global helper to open a confirm dialog from any element (data-confirm)
document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-confirm]');
    if (!trigger) {
        return;
    }
    e.preventDefault();
    const dialog = document.querySelector('[x-data="confirmDialog()"]');
    if (!dialog || !dialog.__x) {
        // fallback: submit directly via a form
        if (trigger.dataset.confirmMethod === 'GET') {
            window.location.href = trigger.getAttribute('href');
        }
        return;
    }
    const component = dialog.__x.$data;
    component.url = trigger.getAttribute('href') || trigger.dataset.action || '';
    component.method = trigger.dataset.confirmMethod || 'DELETE';
    component.title = trigger.dataset.confirmTitle || 'Konfirmasi Hapus';
    component.message = trigger.dataset.confirmMessage || 'Data yang dihapus tidak dapat dikembalikan. Lanjutkan?';
    component.confirmLabel = trigger.dataset.confirmLabel || 'Ya, Hapus';
    component.open = true;
});

Alpine.start();

// Chart defaults
Chart.defaults.font.family = "'Instrument Sans', sans-serif";
