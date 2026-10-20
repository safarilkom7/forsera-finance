import './bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;

const formatRupiah = (value) => {
    const digits = String(value).replace(/\D/g, '');
    if (!digits) {
        return '';
    }
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

const initDarkMode = () => {
    const root = document.documentElement;
    const buttons = document.querySelectorAll('[data-dark-toggle]');

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const dark = !root.classList.contains('dark');
            root.classList.toggle('dark', dark);
            try {
                localStorage.setItem('theme', dark ? 'dark' : 'light');
            } catch (e) {
                // localStorage bisa diblokir; tema tetap berfungsi untuk sesi ini.
            }
        });
    });
};

const initDropdowns = () => {
    document.querySelectorAll('[data-dropdown-toggle]').forEach((trigger) => {
        const target = document.getElementById(trigger.dataset.dropdownToggle);
        if (!target) return;

        trigger.addEventListener('click', (event) => {
            event.stopPropagation();
            document.querySelectorAll('[data-dropdown]').forEach((other) => {
                if (other !== target) other.classList.add('hidden');
            });
            target.classList.toggle('hidden');
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
            if (!dropdown.contains(event.target)) dropdown.classList.add('hidden');
        });
    });
};

const initSidebar = () => {
    const sidebar = document.getElementById('app-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (!sidebar) return;

    const close = () => {
        sidebar.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
    };

    const open = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
    };

    document.querySelectorAll('[data-sidebar-open]').forEach((button) => button.addEventListener('click', open));
    document.querySelectorAll('[data-sidebar-close]').forEach((button) => button.addEventListener('click', close));
    overlay?.addEventListener('click', close);
};

const initRupiahInputs = () => {
    document.querySelectorAll('[data-rupiah]').forEach((input) => {
        input.value = formatRupiah(input.value);
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('autocomplete', 'off');

        input.addEventListener('input', () => {
            const start = input.selectionStart;
            const before = input.value.length;
            input.value = formatRupiah(input.value);
            const after = input.value.length;
            if (start !== null) {
                input.setSelectionRange(Math.max(0, start + (after - before)), Math.max(0, start + (after - before)));
            }
            input.dispatchEvent(new CustomEvent('rupiah-changed', { bubbles: true }));
        });
    });
};

const initConfirmations = () => {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
};

const initDialogs = () => {
    document.querySelectorAll('[data-dialog-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.dialogOpen);
            dialog?.showModal();
        });
    });

    document.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });

    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            const rect = dialog.getBoundingClientRect();
            const inside = event.clientX >= rect.left && event.clientX <= rect.right
                && event.clientY >= rect.top && event.clientY <= rect.bottom;
            if (!inside) dialog.close();
        });
    });
};

const initCharts = () => {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const dataElement = document.getElementById(canvas.dataset.chart);
        if (!dataElement) return;

        let config;
        try {
            config = JSON.parse(dataElement.textContent);
        } catch (e) {
            return;
        }

        if (canvas.dataset.chartReady === '1') return;
        canvas.dataset.chartReady = '1';
        new Chart(canvas, config);
    });
};

const initFilters = () => {
    document.querySelectorAll('[data-auto-submit]').forEach((element) => {
        element.addEventListener('change', () => element.closest('form')?.submit());
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    initDropdowns();
    initSidebar();
    initRupiahInputs();
    initConfirmations();
    initDialogs();
    initFilters();
    initCharts();
});

window.formatRupiah = formatRupiah;
