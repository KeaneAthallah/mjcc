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
