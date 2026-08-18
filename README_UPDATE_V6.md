# Update v6 — Migrasi Tailwind + Bug Fix Nominal

## Perubahan besar

### 🐛 Bug ×1000 (data corruption) — FIXED
**Root cause**: kolom DB `DECIMAL(15,2)` mengembalikan string `"1000000.00"`.
Fungsi lama `formatRibuan(el)` membuang semua non-digit → `"100000000"` (×100).
Setiap edit-save menggelembungkan angka. Setelah beberapa siklus jadi ×1000 / ×10000.

**Fix di 3 lapis** (defense-in-depth):
1. JS `formatRibuan()` — buang trailing decimal (`.XX` / `,XX`) **sebelum** strip separator ribuan
2. JS `setRupiahValue(el, raw)` — helper baru pakai `Math.round(parseFloat(raw))` untuk populate modal edit
3. PHP `sanitizeNominal($raw)` — di `DataKeuangan`, `RencanaKeuangan`, `Pengaturan` controllers, menerima format id-ID (`"1.000.000"` / `"1.000.000,50"`) atau angka murni

### 🎨 Migrasi Tailwind CSS
- CSS lama (`app.css`, `admin.css`) dihapus, digantikan `public/assets/css/tailwind.css` **43 KB minified**
- Bukan Play CDN (350KB+ runtime) — sudah pre-compiled untuk performa production
- Tema **instansi**: primary biru BPOM (`#1e5fbe`), accent emerald (`#059669`), font Inter
- Dark mode via `html.dark` class + `localStorage.theme`

### 🧩 Fitur baru
- **KPI period picker canggih**: tab Semua/Tahun/Bulan + selector tahun (5 tahun) + bulan spesifik  
  Endpoint: `GET /admin/dashboard/kpi-periode` & `GET /kpi-periode` (public)
- **Preview bukti** inline di modal detail & edit (image ditampilkan langsung, PDF sebagai tombol)
- **Password show/hide toggle** di login + `admin/pengaturan`
- **Pagination public dashboard**: prev/next + page numbers (dari `renderPagination()`)
- **Filter compact 1 baris**: bulan + tahun + per-page + total data
- **Login button subtle**: dihapus dari navbar publik, disematkan di footer sebagai link `🔒 Admin` kecil

### 🧱 Modal hierarchy rapih
Pattern seragam untuk semua modal (logout, save, delete, bulk-delete, edit, detail, import):
```
modal-backdrop (klik luar → close)
  modal-container
    modal-box (max-w-sm / lg / xl)
      modal-header  → icon+title+subtitle+close
      modal-body    → content scroll
      modal-footer  → aksi right-aligned
```

---

## Cara rebuild Tailwind CSS

Kalau kamu menambah class Tailwind baru di view, CSS perlu di-rebuild.

**Setup sekali:**
```bash
npm install -D tailwindcss@^3.4
```

**Config sudah tersedia** di `tailwind.config.js` (root project — perlu dibuat kalau belum):
```js
module.exports = {
  content: ['./app/Views/**/*.php'],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#1e5fbe',700:'#1a4fa0',800:'#153f80',900:'#0f2e5f',950:'#0a1f42' },
        accent:  { 500:'#059669',600:'#047857',700:'#065f46' },
      },
      fontFamily: { sans: ['Inter','system-ui','sans-serif'] },
      boxShadow: {
        soft: '0 1px 3px 0 rgba(15,46,95,0.06), 0 1px 2px -1px rgba(15,46,95,0.04)',
        card: '0 4px 12px -2px rgba(15,46,95,0.08), 0 2px 4px -2px rgba(15,46,95,0.04)',
        lift: '0 12px 32px -8px rgba(15,46,95,0.14), 0 4px 12px -4px rgba(15,46,95,0.06)',
      },
    },
  },
};
```

**Input file** `tailwind-input.css` (buat file dgn `@tailwind` + `@layer components` seperti struktur di source project — komponen: `.btn`, `.card`, `.form-control`, `.kpi`, `.badge`, `.modal-*`, `.sidebar-link`, `.segment`, dll).

**Build:**
```bash
npx tailwindcss -i tailwind-input.css -o public/assets/css/tailwind.css --minify
```

**Watch mode saat development:**
```bash
npx tailwindcss -i tailwind-input.css -o public/assets/css/tailwind.css --watch
```

---

## Testing checklist

- [ ] Login → toggle password eye button
- [ ] Input pemasukan Rp 1.000.000 → simpan → edit → nominal masih Rp 1.000.000 ✅ (bukan Rp 1.000.000.000)
- [ ] Klik "Detail" di public dashboard → modal terbuka → preview bukti tampil
- [ ] Sidebar collapse (desktop) → state persist setelah reload
- [ ] KPI card "Pemasukan" → klik tab "Tahun" → pilih tahun 2024 → nilai ter-update via AJAX
- [ ] Bulk select 5 pemasukan + 3 pengeluaran → tombol "Hapus terpilih" muncul → konfirmasi → semuanya hilang
- [ ] Pagination public: pilih 10 baris/halaman → klik page 2 → data pindah
- [ ] Modal logout: hierarki icon+title+subtitle → tombol Batal (ghost) + Ya, Keluar (danger)
