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

const BULAN_SINGKAT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const BULAN_PANJANG = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

const keTanggal = (iso) => {
    const [tahun, bulan, hari] = iso.split('-').map(Number);
    return new Date(tahun, bulan - 1, hari);
};

const keIso = (tanggal) => [
    tanggal.getFullYear(),
    String(tanggal.getMonth() + 1).padStart(2, '0'),
    String(tanggal.getDate()).padStart(2, '0'),
].join('-');

const formatTanggalPendek = (iso) => {
    const tanggal = keTanggal(iso);
    return tanggal.getDate() + ' ' + BULAN_SINGKAT[tanggal.getMonth()] + ' ' + tanggal.getFullYear();
};

/** Kontrol native tetap dirender 1px agar validasi browser tidak gagal diam-diam. */
const sembunyikanKontrolAsli = (kontrol) => {
    kontrol.classList.add('kontrol-asli');
    kontrol.setAttribute('tabindex', '-1');
    kontrol.setAttribute('aria-hidden', 'true');
};

const daftarPilihan = [];
const daftarKalender = [];
let pendengarKomponenTerpasang = false;

const tutupPilihanLain = (kecuali) => {
    daftarPilihan.forEach((pilihan) => {
        if (pilihan.akar !== kecuali) pilihan.tutup();
    });
};

const tutupKalenderLain = (kecuali) => {
    daftarKalender.forEach((kalender) => {
        if (kalender.akar !== kecuali) kalender.tutup();
    });
};

const pasangPendengarKomponen = () => {
    if (pendengarKomponenTerpasang) return;
    pendengarKomponenTerpasang = true;

    document.addEventListener('mousedown', (event) => {
        daftarPilihan.forEach((pilihan) => {
            if (!pilihan.akar.contains(event.target)) pilihan.tutup();
        });
        daftarKalender.forEach((kalender) => {
            if (!kalender.akar.contains(event.target)) kalender.tutup();
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        daftarPilihan.forEach((pilihan) => pilihan.tutup());
        daftarKalender.forEach((kalender) => kalender.tutup());
    });
};

/**
 * Dropdown custom dari <select> asli. Pilihan tetap dibaca dari <option>,
 * jadi tidak ada data yang diduplikasi di markup maupun di server.
 */
const initPilihan = (root = document) => {
    pasangPendengarKomponen();

    root.querySelectorAll('[data-pilihan]').forEach((akar) => {
        if (akar.dataset.pilihanSiap === '1') return;

        const asli = akar.querySelector('[data-pilihan-asli]');
        const kontrol = akar.querySelector('[data-pilihan-kontrol]');
        const tombol = akar.querySelector('[data-pilihan-tombol]');
        const label = akar.querySelector('[data-pilihan-label]');
        const menu = akar.querySelector('[data-pilihan-menu]');
        const wadahDaftar = akar.querySelector('[data-pilihan-daftar]');
        const wadahCari = akar.querySelector('[data-pilihan-cari-wrap]');
        const cari = akar.querySelector('[data-pilihan-cari]');

        if (!asli || !kontrol || !tombol || !label || !menu || !wadahDaftar) return;

        akar.dataset.pilihanSiap = '1';

        const bisaCari = wadahCari !== null && cari !== null && !wadahCari.hasAttribute('hidden');
        let aktif = -1;

        const perbaruiLabel = () => {
            const opsi = asli.options[asli.selectedIndex] ?? null;
            const teks = opsi ? opsi.textContent.trim() : '';

            label.textContent = teks;
            label.classList.toggle('text-slate-500', teks === '');
            label.classList.toggle('dark:text-slate-400', teks === '');
        };

        const opsiTampil = () => Array.from(wadahDaftar.querySelectorAll('[role="option"]'))
            .filter((item) => item.getAttribute('aria-disabled') !== 'true');

        const sorot = (posisi) => {
            const item = opsiTampil();

            if (item.length === 0) return;

            aktif = Math.max(0, Math.min(posisi, item.length - 1));

            item.forEach((li, urutan) => {
                const menyala = urutan === aktif;
                li.classList.toggle('bg-emerald-100', menyala);
                li.classList.toggle('dark:bg-emerald-500/20', menyala);
            });

            tombol.setAttribute('aria-activedescendant', item[aktif].id);
            item[aktif].scrollIntoView({ block: 'nearest' });
        };

        const gambarDaftar = () => {
            wadahDaftar.textContent = '';

            const terpilih = asli.selectedIndex;
            const kunci = (cari?.value ?? '').trim().toLowerCase();

            Array.from(asli.options).forEach((opsi, indeks) => {
                // Opsi yang disembunyikan skrip halaman (mis. kategori per unit) ikut disembunyikan.
                if (opsi.hidden) return;
                if (kunci !== '' && !opsi.textContent.toLowerCase().includes(kunci)) return;

                const li = document.createElement('li');
                li.id = wadahDaftar.id + '-opsi-' + indeks;
                li.dataset.indeks = String(indeks);
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', String(indeks === terpilih));
                li.className = 'flex cursor-pointer items-center gap-2 px-3 py-2 text-sm text-slate-700 transition hover:bg-emerald-50 dark:text-slate-200 dark:hover:bg-emerald-500/10';

                if (indeks === terpilih) {
                    li.classList.add('bg-emerald-50', 'font-semibold', 'text-emerald-800', 'dark:bg-emerald-500/10', 'dark:text-emerald-300');
                }

                if (opsi.disabled) {
                    li.classList.add('cursor-not-allowed', 'opacity-50');
                    li.setAttribute('aria-disabled', 'true');
                }

                const teks = document.createElement('span');
                teks.className = 'truncate';
                teks.textContent = opsi.textContent.trim();
                li.append(teks);
                wadahDaftar.append(li);
            });

            if (wadahDaftar.children.length === 0) {
                const kosong = document.createElement('li');
                kosong.className = 'px-3 py-2 text-sm text-slate-500 dark:text-slate-400';
                kosong.textContent = 'Tidak ada pilihan yang cocok.';
                wadahDaftar.append(kosong);
            }

            aktif = -1;
        };

        const tutup = (fokusKembali = false) => {
            if (menu.hidden) return;

            menu.hidden = true;
            tombol.setAttribute('aria-expanded', 'false');
            tombol.removeAttribute('aria-activedescendant');
            tombol.classList.remove('[&>svg]:rotate-180');
            aktif = -1;

            if (fokusKembali) tombol.focus();
        };

        const buka = () => {
            if (asli.disabled) return;

            tutupPilihanLain(akar);

            if (cari) cari.value = '';
            gambarDaftar();
            menu.hidden = false;
            tombol.setAttribute('aria-expanded', 'true');
            tombol.classList.add('[&>svg]:rotate-180');

            const posisiTerpilih = opsiTampil().findIndex((li) => Number(li.dataset.indeks) === asli.selectedIndex);
            sorot(posisiTerpilih >= 0 ? posisiTerpilih : 0);

            if (bisaCari) {
                cari.focus();
                cari.select();
            }
        };

        const pilih = (indeks) => {
            const opsi = asli.options[indeks];

            if (!opsi || opsi.disabled || opsi.hidden) return;

            asli.selectedIndex = indeks;
            perbaruiLabel();
            tutup();
            tombol.focus();
            asli.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const tanganiTombol = (event) => {
            if (menu.hidden) {
                if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
                    event.preventDefault();
                    buka();
                }

                return;
            }

            const item = opsiTampil();

            if (event.key === 'Escape') {
                event.preventDefault();
                tutup(true);
            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                sorot(aktif + 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                sorot(aktif - 1);
            } else if (event.key === 'Home') {
                event.preventDefault();
                sorot(0);
            } else if (event.key === 'End') {
                event.preventDefault();
                sorot(item.length - 1);
            } else if (event.key === 'Enter' || (event.key === ' ' && event.currentTarget === tombol)) {
                event.preventDefault();
                if (aktif >= 0 && item[aktif]) pilih(Number(item[aktif].dataset.indeks));
            } else if (event.key === 'Tab') {
                tutup();
            }
        };

        tombol.addEventListener('click', () => (menu.hidden ? buka() : tutup(true)));
        tombol.addEventListener('keydown', tanganiTombol);

        wadahDaftar.addEventListener('click', (event) => {
            const li = event.target.closest('[role="option"]');
            if (li) pilih(Number(li.dataset.indeks));
        });

        wadahDaftar.addEventListener('mousemove', (event) => {
            const li = event.target.closest('[role="option"]');
            if (!li) return;

            const posisi = opsiTampil().indexOf(li);
            if (posisi >= 0 && posisi !== aktif) sorot(posisi);
        });

        if (cari) {
            cari.addEventListener('input', gambarDaftar);
            cari.addEventListener('keydown', tanganiTombol);
        }

        asli.addEventListener('change', perbaruiLabel);

        if (asli.disabled) {
            tombol.setAttribute('aria-disabled', 'true');
            tombol.classList.add('cursor-not-allowed', 'opacity-60');
        }

        // Nama aksesibel diambil dari <label for="…"> yang sudah ada di halaman.
        let punyaLabel = false;

        if (asli.id) {
            const labelAsli = document.querySelector('label[for="' + CSS.escape(asli.id) + '"]');
            if (labelAsli) {
                if (!labelAsli.id) labelAsli.id = asli.id + '-label';
                tombol.setAttribute('aria-labelledby', labelAsli.id);
                punyaLabel = true;
            }
        }

        // Baris dinamis (mis. detail pengeluaran) hanya punya label tanpa atribut for.
        if (!punyaLabel) {
            const labelSekitar = akar.parentElement?.querySelector('label');
            const teksLabel = labelSekitar?.textContent.trim();

            if (teksLabel) tombol.setAttribute('aria-label', teksLabel);
        }

        if (asli.required) tombol.setAttribute('aria-required', 'true');

        sembunyikanKontrolAsli(asli);
        kontrol.hidden = false;
        perbaruiLabel();
        gambarDaftar();

        daftarPilihan.push({ akar, tutup });
    });
};

/** Input tanggal custom dengan popup kalender berbahasa Indonesia. */
const initKalender = (root = document) => {
    pasangPendengarKomponen();

    root.querySelectorAll('[data-kalender]').forEach((akar) => {
        if (akar.dataset.kalenderSiap === '1') return;

        const asli = akar.querySelector('[data-kalender-asli]');
        const kontrol = akar.querySelector('[data-kalender-kontrol]');
        const tombol = akar.querySelector('[data-kalender-tombol]');
        const label = akar.querySelector('[data-kalender-label]');
        const popup = akar.querySelector('[data-kalender-popup]');
        const judul = akar.querySelector('[data-kalender-judul]');
        const wadahHari = akar.querySelector('[data-kalender-hari]');
        const tombolMundur = akar.querySelector('[data-kalender-mundur]');
        const tombolMaju = akar.querySelector('[data-kalender-maju]');

        if (!asli || !kontrol || !tombol || !label || !popup || !judul || !wadahHari) return;

        akar.dataset.kalenderSiap = '1';

        const hariIni = keIso(new Date());
        let tampilan = asli.value ? keTanggal(asli.value) : new Date();
        tampilan = new Date(tampilan.getFullYear(), tampilan.getMonth(), 1);

        const perbaruiLabel = () => {
            const kosong = asli.value === '';

            label.textContent = kosong ? 'Pilih tanggal' : formatTanggalPendek(asli.value);
            label.classList.toggle('text-slate-500', kosong);
            label.classList.toggle('dark:text-slate-400', kosong);
        };

        const gambar = () => {
            judul.textContent = BULAN_PANJANG[tampilan.getMonth()] + ' ' + tampilan.getFullYear();
            wadahHari.textContent = '';

            const awalBulan = new Date(tampilan.getFullYear(), tampilan.getMonth(), 1);
            const geser = (awalBulan.getDay() + 6) % 7;
            const jumlahHari = new Date(tampilan.getFullYear(), tampilan.getMonth() + 1, 0).getDate();

            for (let kosong = 0; kosong < geser; kosong++) {
                wadahHari.append(document.createElement('span'));
            }

            for (let tanggal = 1; tanggal <= jumlahHari; tanggal++) {
                const iso = keIso(new Date(tampilan.getFullYear(), tampilan.getMonth(), tanggal));
                const terpilih = iso === asli.value;
                const hari = document.createElement('button');

                hari.type = 'button';
                hari.dataset.nilai = iso;
                hari.textContent = String(tanggal);
                hari.setAttribute('aria-pressed', String(terpilih));
                hari.setAttribute('aria-label', tanggal + ' ' + BULAN_PANJANG[tampilan.getMonth()] + ' ' + tampilan.getFullYear());
                hari.className = terpilih
                    ? 'grid h-9 w-full place-items-center rounded-lg bg-emerald-700 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:bg-emerald-600 dark:hover:bg-emerald-500'
                    : 'grid h-9 w-full place-items-center rounded-lg text-sm text-slate-700 transition hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-slate-200 dark:hover:bg-emerald-500/10';

                if (!terpilih && iso === hariIni) {
                    hari.classList.add('ring-1', 'ring-emerald-500');
                }

                wadahHari.append(hari);
            }
        };

        const tutup = (fokusKembali = false) => {
            if (popup.hidden) return;

            popup.hidden = true;
            tombol.setAttribute('aria-expanded', 'false');

            if (fokusKembali) tombol.focus();
        };

        const pilih = (iso) => {
            asli.value = iso;
            perbaruiLabel();
            tutup(true);
            asli.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const buka = () => {
            if (asli.disabled) return;

            tutupKalenderLain(akar);

            const dasar = asli.value ? keTanggal(asli.value) : new Date();
            tampilan = new Date(dasar.getFullYear(), dasar.getMonth(), 1);
            gambar();
            popup.hidden = false;
            tombol.setAttribute('aria-expanded', 'true');

            const terpilih = wadahHari.querySelector('[aria-pressed="true"]') ?? wadahHari.querySelector('button');
            terpilih?.focus();
        };

        const geserBulan = (langkah) => {
            tampilan = new Date(tampilan.getFullYear(), tampilan.getMonth() + langkah, 1);
            gambar();

            const terpilih = wadahHari.querySelector('[aria-pressed="true"]') ?? wadahHari.querySelector('button');
            terpilih?.focus();
        };

        tombol.addEventListener('click', () => (popup.hidden ? buka() : tutup(true)));

        tombolMundur?.addEventListener('click', () => geserBulan(-1));
        tombolMaju?.addEventListener('click', () => geserBulan(1));
        akar.querySelector('[data-kalender-hari-ini]')?.addEventListener('click', () => pilih(hariIni));
        akar.querySelector('[data-kalender-kosong]')?.addEventListener('click', () => pilih(''));

        wadahHari.addEventListener('keydown', (event) => {
            const hari = event.target.closest('button[data-nilai]');
            if (!hari) return;

            const semuaHari = Array.from(wadahHari.querySelectorAll('button[data-nilai]'));
            const posisi = semuaHari.indexOf(hari);
            const kolom = (keTanggal(hari.dataset.nilai).getDay() + 6) % 7;

            if (event.key === 'Escape') {
                event.preventDefault();
                tutup(true);
                return;
            }

            if (event.key === 'PageUp') {
                event.preventDefault();
                geserBulan(-1);
                return;
            }

            if (event.key === 'PageDown') {
                event.preventDefault();
                geserBulan(1);
                return;
            }

            const tujuan = {
                ArrowRight: posisi + 1,
                ArrowLeft: posisi - 1,
                ArrowDown: posisi + 7,
                ArrowUp: posisi - 7,
                Home: posisi - kolom,
                End: posisi + (6 - kolom),
            }[event.key];

            if (tujuan === undefined) return;

            event.preventDefault();

            if (tujuan < 0 || tujuan >= semuaHari.length) {
                geserBulan(tujuan < 0 ? -1 : 1);
                return;
            }

            semuaHari[tujuan].focus();
        });

        sembunyikanKontrolAsli(asli);
        kontrol.hidden = false;
        perbaruiLabel();
        gambar();

        daftarKalender.push({ akar, tutup });
    });
};

/** ID komponen pada <template> akan terduplikasi saat baris dikloning, jadi diperbarui dulu. */
const beriIdUnik = (akar) => {
    const daftar = akar.querySelector('[data-pilihan-daftar]');
    const asli = akar.querySelector('[data-pilihan-asli]');

    if (!daftar) return;

    const baru = daftar.id + '-' + Math.random().toString(36).slice(2, 7);
    daftar.id = baru;
    akar.querySelector('[data-pilihan-tombol]')?.setAttribute('aria-controls', baru);

    if (asli) {
        asli.id = '';
    }
};

/** Dipakai halaman yang menambah baris form secara dinamis (mis. detail pengeluaran). */
window.siapkanKomponen = (root = document) => {
    root.querySelectorAll?.('[data-pilihan]').forEach(beriIdUnik);
    initPilihan(root);
    initKalender(root);
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
    initPilihan();
    initKalender();
    initPilihanMassal();
    initConfirmations();
    initDialogs();
    initFilters();
    initFileUploads();
    initPasswordToggles();
    initCharts();
});

window.formatRupiah = formatRupiah;
