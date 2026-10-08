# StockECM — Stock Management Gudang

Aplikasi manajemen stok gudang: Stock Utama → Stock Display → OUT → Sisa Display (carry-over harian).

## Tech Stack
- Laravel 12 + Inertia.js (Vue 3) + Tailwind CSS
- MySQL (Laragon, root tanpa password, DB: `stockecm`)
- Chart.js untuk grafik dashboard

## Perintah Penting
```bash
php artisan serve                 # jalankan server dev (port 8000)
php artisan migrate --force       # migrasi database
php artisan db:seed --force       # seed users (Novan, Fan, Monica)
php artisan db:seed --class=DemoDataSeeder --force  # data demo produk + transaksi
npm run build                     # build frontend (pakai `cmd /c "npm ..."` di PowerShell)
php artisan test                  # jalankan test suite (40 test)
```

## Akun Demo (password: `password`)
- monica@stockecm.test — Admin (full access)
- novan@stockecm.test — Gudang
- fan@stockecm.test — Gudang

## Arsitektur
- **Stock engine** (`app/Services/Stock/`): `StockEngine` (saldo, daily row, recalc, trx number, history, audit) + `StockInService`, `StockTransferService`, `StockOutService`, `StockAdjustmentService`. Semua operasi stok dibungkus `DB::transaction` + `lockForUpdate` — atomic.
- **Saldo display harian**: tabel `display_stocks` per (product, tanggal): `opening + in - out + adjust = closing`. Opening hari baru = closing hari sebelumnya. `recalcForward()` menjaga konsistensi untuk entry backdated.
- **OUT tidak mengurangi Stock Utama langsung** — hanya display.
- **Role**: `users.role` enum admin/gudang; middleware `admin` untuk menu Administration + Adjustment + CRUD produk.
- **Histori**: `stock_histories` (ledger semua perubahan, before/after main & display) + `audit_logs` (read-only, jangan diubah).

## Konvensi
- Validasi via Form Request (`app/Http/Requests`).
- Error domain dilempar sebagai `ValidationException::withMessages` agar tampil di form Inertia.
- Nomor transaksi: `OUT-YYYYMMDD-NNN`, `ST-YYYYMMDD-NNN` (auto).
- Produk yang sudah punya transaksi tidak boleh dihapus — gunakan status inactive.
- Frontend: pages di `resources/js/Pages`, komponen shared di `resources/js/Components` (StockFormModal reusable untuk input stok).
- php.ini Laragon: extension `pdo_sqlite` diaktifkan untuk test suite.
