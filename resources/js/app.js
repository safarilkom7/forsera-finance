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

const KELAS_IKON_KONFIRMASI = {
    warning: 'flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30',
    danger: 'flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/30',
    info: 'flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sky-600 ring-1 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-400 dark:ring-sky-500/30',
};

const KELAS_TOMBOL_KONFIRMASI = {
    warning: 'btn-warning',
    danger: 'btn-danger',
    info: 'btn-primary',
};

const LABEL_KONFIRMASI = {
    warning: 'Ya, lanjutkan',
    danger: 'Ya, hapus',
    info: 'Mengerti',
};

const initConfirmations = () => {
    const dialog = document.getElementById('confirm-dialog');
    const judul = dialog?.querySelector('[data-confirm-title]');
    const pesan = dialog?.querySelector('[data-confirm-message]');
    const ikon = dialog?.querySelector('[data-confirm-icon]');
    const tombolTerima = dialog?.querySelector('[data-confirm-accept]');
    const tombolBatal = dialog?.querySelector('[data-confirm-cancel]');
    const tombolTutup = dialog?.querySelector('[data-confirm-close]');
    let selesaikan = null;

    const opsiDari = (elemen) => {
        const severity = ['danger', 'warning', 'info'].includes(elemen.dataset.confirmSeverity)
            ? elemen.dataset.confirmSeverity
            : 'warning';

        return {
            judul: elemen.dataset.confirmTitle || 'Konfirmasi',
            pesan: elemen.dataset.confirm || elemen.dataset.confirmMessage || 'Lanjutkan tindakan ini?',
            severity,
            labelTerima: elemen.dataset.confirmLabel || LABEL_KONFIRMASI[severity],
            labelBatal: elemen.dataset.confirmCancelLabel || 'Batal',
        };
    };

    const terapkanOpsi = (opsi) => {
        if (judul) judul.textContent = opsi.judul;
        if (pesan) pesan.textContent = opsi.pesan;
        if (tombolTerima) {
            tombolTerima.textContent = opsi.labelTerima;
            tombolTerima.className = KELAS_TOMBOL_KONFIRMASI[opsi.severity] || KELAS_TOMBOL_KONFIRMASI.warning;
        }
        if (tombolBatal) tombolBatal.textContent = opsi.labelBatal;
        if (ikon) {
            ikon.className = KELAS_IKON_KONFIRMASI[opsi.severity] || KELAS_IKON_KONFIRMASI.warning;
            ikon.querySelectorAll('[data-confirm-icon-svg]').forEach((svg) => {
                svg.classList.toggle('hidden', svg.dataset.confirmIconSvg !== opsi.severity);
            });
        }
    };

    const buka = (opsi = {}) => new Promise((resolve) => {
        const lengkap = {
            judul: opsi.judul || 'Konfirmasi',
            pesan: opsi.pesan || 'Lanjutkan tindakan ini?',
            severity: opsi.severity || 'warning',
            labelTerima: opsi.labelTerima || LABEL_KONFIRMASI[opsi.severity] || 'Ya, lanjutkan',
            labelBatal: opsi.labelBatal || 'Batal',
        };

        if (!dialog || typeof dialog.showModal !== 'function') {
            resolve(window.confirm(lengkap.pesan));
            return;
        }

        if (dialog.open) {
            resolve(false);
            return;
        }

        selesaikan = resolve;
        terapkanOpsi(lengkap);
        dialog.returnValue = '';
        dialog.showModal();
        window.requestAnimationFrame(() => tombolTerima?.focus());
    });

    window.konfirmasi = buka;

    dialog?.addEventListener('close', () => {
        const diterima = dialog.returnValue === 'confirm';
        const lanjutkan = selesaikan;
        selesaikan = null;
        lanjutkan?.(diterima);
    });

    tombolTerima?.addEventListener('click', () => dialog.close('confirm'));
    tombolBatal?.addEventListener('click', () => dialog.close('cancel'));
    tombolTutup?.addEventListener('click', () => dialog.close('cancel'));

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (form.dataset.konfirmasiDisetujui === '1') {
                delete form.dataset.konfirmasiDisetujui;
                return;
            }

            event.preventDefault();

            if (await buka(opsiDari(form))) {
                form.dataset.konfirmasiDisetujui = '1';
                form.requestSubmit();
            }
        });
    });

    document.querySelectorAll('[data-confirm-form]').forEach((tombol) => {
        tombol.addEventListener('click', async (event) => {
            const form = tombol.form;
            if (!form) return;

            event.preventDefault();

            if (await buka(opsiDari(tombol))) {
                form.requestSubmit(tombol);
            }
        });
    });

    document.querySelectorAll('a[data-confirm]').forEach((tautan) => {
        tautan.addEventListener('click', async (event) => {
            event.preventDefault();

            if (await buka(opsiDari(tautan))) {
                window.location.href = tautan.href;
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

const initPasswordToggles = () => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = button.parentElement?.querySelector('input');
        if (!input) return;

        button.addEventListener('click', () => {
            const tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';

            button.setAttribute('aria-pressed', tampil ? 'true' : 'false');
            button.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
            button.title = tampil ? 'Sembunyikan password' : 'Tampilkan password';

            button.querySelector('[data-password-icon="show"]')?.classList.toggle('hidden', tampil);
            button.querySelector('[data-password-icon="hide"]')?.classList.toggle('hidden', !tampil);

            input.focus({ preventScroll: true });
            input.setSelectionRange?.(input.value.length, input.value.length);
        });
    });
};

const formatBytes = (bytes) => {
    const nilai = Number(bytes) || 0;
    if (nilai >= 1048576) {
        return (nilai / 1048576).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' MB';
    }
    if (nilai >= 1024) {
        return Math.round(nilai / 1024).toLocaleString('id-ID') + ' KB';
    }
    return nilai + ' B';
};

const initFileUploads = () => {
    document.querySelectorAll('[data-file-upload]').forEach((root) => {
        const input = root.querySelector('[data-input]');
        const camera = root.querySelector('[data-camera]');
        const list = root.querySelector('[data-list]');
        const listWrap = root.querySelector('[data-terpilih]');
        const dropzone = root.querySelector('[data-dropzone]');
        const errorBox = root.querySelector('[data-client-error]');
        const counter = root.querySelector('[data-counter]');
        if (!input || !list || !listWrap) return;

        const max = Number(root.dataset.max || 5);
        const maxBytes = Number(root.dataset.maxBytes || 0);
        const allowed = String(root.dataset.extensions || '').split(',').filter(Boolean);
        const files = [];

        const hapusToggles = () => Array.from(root.querySelectorAll('[data-hapus-toggle]'));
        const jumlahLama = () => hapusToggles().filter((checkbox) => !checkbox.checked).length;

        const showError = (pesan) => {
            if (!errorBox) return;
            errorBox.textContent = pesan;
            errorBox.classList.remove('hidden');
        };

        const clearError = () => {
            if (!errorBox) return;
            errorBox.textContent = '';
            errorBox.classList.add('hidden');
        };

        const sync = () => {
            try {
                const transfer = new DataTransfer();
                files.forEach((file) => transfer.items.add(file));
                input.files = transfer.files;
            } catch (e) {
                // DataTransfer tidak tersedia: input tetap berisi berkas terakhir.
            }

            if (counter) {
                const total = jumlahLama() + files.length;
                counter.textContent = total + '/' + max + ' berkas';
                counter.classList.toggle('badge-red', total > max);
                counter.classList.toggle('badge-gray', total <= max);
            }
        };

        const buatBaris = (file, index) => {
            const row = document.createElement('li');
            row.className = 'flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-900/50';

            const thumb = document.createElement('span');
            thumb.className = 'flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100 text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700';

            const bisaPratinjau = file.type.startsWith('image/') && !/heic|heif/i.test(file.type);
            if (bisaPratinjau) {
                const img = document.createElement('img');
                img.className = 'h-full w-full object-cover';
                img.alt = file.name;
                const url = URL.createObjectURL(file);
                img.src = url;
                img.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
                thumb.appendChild(img);
            } else {
                const ext = (file.name.split('.').pop() || 'file').slice(0, 4).toUpperCase();
                const badge = document.createElement('span');
                badge.className = 'text-[10px] font-bold';
                badge.textContent = ext;
                thumb.appendChild(badge);
            }

            const info = document.createElement('div');
            info.className = 'min-w-0 flex-1';
            const nama = document.createElement('p');
            nama.className = 'truncate text-sm font-medium text-slate-800 dark:text-slate-100';
            nama.textContent = file.name;
            const meta = document.createElement('p');
            meta.className = 'mt-0.5 text-[11px] text-slate-400';
            meta.textContent = formatBytes(file.size) + ' · siap diunggah';
            info.append(nama, meta);

            const hapus = document.createElement('button');
            hapus.type = 'button';
            hapus.className = 'rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10';
            hapus.setAttribute('aria-label', 'Batalkan ' + file.name);
            hapus.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="h-4 w-4" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>';
            hapus.addEventListener('click', () => {
                files.splice(index, 1);
                clearError();
                render();
            });

            row.append(thumb, info, hapus);

            return row;
        };

        const render = () => {
            list.textContent = '';
            files.forEach((file, index) => list.appendChild(buatBaris(file, index)));
            listWrap.classList.toggle('hidden', files.length === 0);
            sync();
        };

        const tambah = (incoming) => {
            clearError();
            let ditolak = null;

            Array.from(incoming || []).forEach((file) => {
                const ekstensi = (file.name.split('.').pop() || '').toLowerCase();

                if (allowed.length > 0 && !allowed.includes(ekstensi)) {
                    ditolak = ditolak || 'Berkas "' + file.name + '" formatnya belum didukung.';
                    return;
                }

                if (maxBytes > 0 && file.size > maxBytes) {
                    ditolak = ditolak || 'Berkas "' + file.name + '" berukuran ' + formatBytes(file.size) + ', melebihi batas ' + formatBytes(maxBytes) + '.';
                    return;
                }

                if (files.some((lain) => lain.name === file.name && lain.size === file.size && lain.lastModified === file.lastModified)) {
                    return;
                }

                if (jumlahLama() + files.length >= max) {
                    ditolak = ditolak || 'Maksimal ' + max + ' berkas. Hapus salah satu berkas terlebih dahulu.';
                    return;
                }

                files.push(file);
            });

            if (ditolak) showError(ditolak);
            render();
        };

        root.querySelectorAll('[data-browse]').forEach((button) => {
            button.addEventListener('click', () => input.click());
        });

        root.querySelectorAll('[data-camera-open]').forEach((button) => {
            button.addEventListener('click', () => camera?.click());
        });

        dropzone?.addEventListener('click', (event) => {
            if (event.target.closest('button, a')) return;
            input.click();
        });

        ['dragenter', 'dragover'].forEach((nama) => {
            dropzone?.addEventListener(nama, (event) => {
                event.preventDefault();
                dropzone.classList.add('border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-500/10');
            });
        });

        ['dragleave', 'dragend'].forEach((nama) => {
            dropzone?.addEventListener(nama, () => {
                dropzone.classList.remove('border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-500/10');
            });
        });

        dropzone?.addEventListener('drop', (event) => {
            event.preventDefault();
            dropzone.classList.remove('border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-500/10');
            tambah(event.dataTransfer?.files);
        });

        input.addEventListener('change', () => tambah(input.files));

        camera?.addEventListener('change', () => {
            tambah(camera.files);
            camera.value = '';
        });

        hapusToggles().forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                const item = checkbox.closest('[data-existing-item]');
                item?.classList.toggle('opacity-60', checkbox.checked);
                item?.classList.toggle('border-red-200', checkbox.checked);
                item?.querySelector('[data-nama-berkas]')?.classList.toggle('line-through', checkbox.checked);
                sync();
            });
        });

        root.closest('form')?.addEventListener('submit', (event) => {
            if (jumlahLama() + files.length > max) {
                event.preventDefault();
                showError('Total lampiran melebihi ' + max + ' berkas. Hapus beberapa lampiran lama terlebih dahulu.');
                errorBox?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        render();
    });
};

const initPilihanMassal = () => {
    document.querySelectorAll('[data-pilih-scope]').forEach((scope) => {
        const kotak = Array.from(scope.querySelectorAll('[data-pilih]'));

        if (kotak.length === 0) return;

        const bar = scope.querySelector('[data-pilih-bar]');
        const jumlah = bar?.querySelector('[data-pilih-jumlah]');
        const tombolHapus = bar?.querySelector('[data-pilih-hapus]');
        const semua = scope.querySelector('[data-pilih-semua]');
        const nilaiUnik = Array.from(new Set(kotak.map((k) => k.value)));

        const dipilih = () => Array.from(new Set(kotak.filter((k) => k.checked).map((k) => k.value)));

        const perbarui = () => {
            const n = dipilih().length;

            if (jumlah) jumlah.textContent = String(n);
            if (tombolHapus) tombolHapus.disabled = n === 0;
            bar?.classList.toggle('hidden', n === 0);

            if (semua) {
                semua.checked = n > 0 && n === nilaiUnik.length;
                semua.indeterminate = n > 0 && n < nilaiUnik.length;
            }

            if (bar?.dataset.confirmTemplate) {
                bar.dataset.confirm = bar.dataset.confirmTemplate.replace('{n}', String(n));
            }
        };

        const setNilai = (nilai, dicentang) => {
            kotak.forEach((k) => {
                if (k.value === nilai) k.checked = dicentang;
            });
        };

        kotak.forEach((k) => {
            k.addEventListener('change', () => {
                // Tampilan tabel dan tampilan kartu mobile punya checkbox untuk baris yang sama.
                setNilai(k.value, k.checked);
                perbarui();
            });
        });

        semua?.addEventListener('change', () => {
            kotak.forEach((k) => {
                k.checked = semua.checked;
            });
            perbarui();
        });

        bar?.querySelector('[data-pilih-kosongkan]')?.addEventListener('click', () => {
            kotak.forEach((k) => {
                k.checked = false;
            });
            perbarui();
        });

        perbarui();
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
    initPilihanMassal();
    initConfirmations();
    initDialogs();
    initFilters();
    initFileUploads();
    initPasswordToggles();
    initCharts();
});

window.formatRupiah = formatRupiah;
