/**
 * Kontrol pagination dengan lompat-ke-halaman-tertentu (nomor halaman, dengan "…" kalau
 * halamannya banyak, plus input lompat langsung) — dipakai bersama oleh daftar Perjalanan
 * Dinas & Dana Taktis (admin + publik) supaya UX-nya konsisten dan logikanya tidak ditulis
 * ulang di tiap halaman. Baris-per-halaman tetap diatur lewat <select> masing-masing halaman
 * (di luar fungsi ini) karena pilihannya sudah beda-beda tiap tabel. Gaya tombolnya sengaja
 * disamakan dengan pagination tabel Transaksi (tab_transaksi.php) — jumlah-halaman jarang
 * banyak di sana jadi tidak butuh baris lompat-halaman terpisah, tapi tombolnya harus terasa
 * satu keluarga; kontrol lompat-halaman ditaruh di barisnya sendiri (bukan dempet di ujung
 * tombol nomor) supaya tidak terlihat berantakan saat jumlah halamannya banyak.
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

  const wrapper = document.createElement('div');
  wrapper.className = 'flex flex-col gap-2 w-full';

  const topRow = document.createElement('div');
  topRow.className = 'flex items-center justify-between gap-3 flex-wrap';

  const info = document.createElement('div');
  info.className = 'text-xs text-slate-600 dark:text-slate-400';
  info.textContent = `Menampilkan ${from}–${to} dari ${new Intl.NumberFormat('id-ID').format(total)} ${itemLabel}`;

  const controls = document.createElement('div');
  controls.className = 'flex items-center gap-1 flex-wrap';

  const makeButton = (label, targetPage, { active = false, disabled = false } = {}) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = label;
    if (disabled) {
      btn.disabled = true;
      btn.className = 'px-3 py-1.5 text-xs rounded-md text-slate-400 cursor-not-allowed';
    } else if (active) {
      btn.className = 'px-3 py-1.5 text-xs rounded-md bg-primary-600 text-white font-medium';
    } else {
      btn.className = 'px-3 py-1.5 text-xs rounded-md text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700';
      btn.addEventListener('click', () => onPageChange(targetPage));
    }
    return btn;
  };

  controls.appendChild(makeButton('‹ Sebelumnya', page - 1, { disabled: page <= 1 }));

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

  controls.appendChild(makeButton('Selanjutnya ›', page + 1, { disabled: page >= totalPages }));

  topRow.appendChild(info);
  topRow.appendChild(controls);
  wrapper.appendChild(topRow);

  // Kalau halamannya banyak, tombol nomor saja kurang praktis — sediakan juga input
  // lompat-langsung-ke-halaman, di barisnya sendiri (rata kanan, dipisah garis tipis)
  // supaya tidak dempet dengan tombol nomor halaman di baris atas.
  if (totalPages > 7) {
    const jumpWrap = document.createElement('div');
    jumpWrap.className = 'flex items-center justify-end gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800';

    const jumpLabel = document.createElement('span');
    jumpLabel.className = 'text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap';
    jumpLabel.textContent = 'Lompat ke halaman';

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
    wrapper.appendChild(jumpWrap);
  }

  container.innerHTML = '';
  container.appendChild(wrapper);
}
