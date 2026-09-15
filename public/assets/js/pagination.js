/**
 * Kontrol pagination dengan lompat-ke-halaman-tertentu (nomor halaman, dengan "…" kalau
 * halamannya banyak, plus input lompat langsung) — dipakai bersama oleh daftar Perjalanan
 * Dinas & Dana Taktis (admin + publik) supaya UX-nya konsisten dan logikanya tidak ditulis
 * ulang di tiap halaman. Baris-per-halaman tetap diatur lewat <select> masing-masing halaman
 * (di luar fungsi ini) karena pilihannya sudah beda-beda tiap tabel.
 *
 * @param {HTMLElement} container elemen pembungkus kosong tempat kontrol ini dirender
 * @param {{total:number, perPage:number, page:number, itemLabel?:string, onPageChange:(page:number)=>void}} opts
 */
function renderPaginasiHalaman(container, opts) {
  const { total, perPage, page, onPageChange } = opts;
  const itemLabel = opts.itemLabel || 'baris';
  const totalPages = Math.max(1, Math.ceil(total / perPage));

  if (total === 0) { container.innerHTML = ''; return; }

  const from = (page - 1) * perPage + 1;
  const to = Math.min(total, page * perPage);

  const info = document.createElement('div');
  info.className = 'text-xs text-slate-600 dark:text-slate-400';
  info.textContent = `Menampilkan ${from}–${to} dari ${new Intl.NumberFormat('id-ID').format(total)} ${itemLabel}`;

  const controls = document.createElement('div');
  controls.className = 'flex items-center gap-1 flex-wrap';

  const makeButton = (label, targetPage, { active = false, disabled = false } = {}) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = label;
    btn.className = active
      ? 'min-w-[28px] px-2.5 py-1.5 text-xs rounded-md bg-primary-600 text-white font-semibold'
      : 'min-w-[28px] px-2.5 py-1.5 text-xs rounded-md text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700';
    if (disabled) {
      btn.disabled = true;
      btn.classList.add('opacity-40', 'cursor-not-allowed');
    } else {
      btn.addEventListener('click', () => onPageChange(targetPage));
    }
    return btn;
  };

  controls.appendChild(makeButton('Sebelumnya', page - 1, { disabled: page <= 1 }));

  // Nomor halaman yang ditampilkan: halaman pertama, terakhir, dan beberapa di sekitar
  // halaman aktif — sisanya diringkas jadi "…" supaya tidak membanjiri layar kalau
  // jumlah halamannya besar.
  const pagesToShow = new Set([1, totalPages]);
  for (let p = page - 1; p <= page + 1; p++) {
    if (p >= 1 && p <= totalPages) pagesToShow.add(p);
  }
  const sortedPages = Array.from(pagesToShow).sort((a, b) => a - b);

  let previousPage = null;
  for (const p of sortedPages) {
    if (previousPage !== null && p - previousPage > 1) {
      const dots = document.createElement('span');
      dots.className = 'px-1 text-xs text-slate-400 select-none';
      dots.textContent = '…';
      controls.appendChild(dots);
    }
    controls.appendChild(makeButton(String(p), p, { active: p === page }));
    previousPage = p;
  }

  controls.appendChild(makeButton('Berikutnya', page + 1, { disabled: page >= totalPages }));

  // Kalau halamannya banyak, tombol nomor saja kurang praktis — sediakan juga input
  // lompat-langsung-ke-halaman supaya tidak perlu klik berkali-kali.
  if (totalPages > 7) {
    const jumpWrap = document.createElement('div');
    jumpWrap.className = 'flex items-center gap-1.5 ml-1 pl-2 border-l border-slate-200 dark:border-slate-700';

    const jumpLabel = document.createElement('span');
    jumpLabel.className = 'text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap';
    jumpLabel.textContent = 'ke hal.';

    const jumpInput = document.createElement('input');
    jumpInput.type = 'number';
    jumpInput.min = '1';
    jumpInput.max = String(totalPages);
    jumpInput.value = String(page);
    jumpInput.setAttribute('aria-label', 'Lompat ke halaman');
    jumpInput.className = 'form-control form-control-sm w-16 text-center';
    jumpInput.addEventListener('keydown', (e) => {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      const target = Math.min(totalPages, Math.max(1, parseInt(jumpInput.value, 10) || 1));
      onPageChange(target);
    });

    jumpWrap.appendChild(jumpLabel);
    jumpWrap.appendChild(jumpInput);
    controls.appendChild(jumpWrap);
  }

  container.innerHTML = '';
  container.appendChild(info);
  container.appendChild(controls);
}
