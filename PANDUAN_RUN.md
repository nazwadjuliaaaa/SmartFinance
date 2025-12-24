# Panduan Menjalankan Project SmartFinance

Berikut adalah langkah-langkah untuk menjalankan website SmartFinance di komputer Anda.

## Prasyarat
Pastikan Anda sudah memiliki:
1. **XAMPP** (untuk PHP dan MySQL) yang sudah terinstall dan berjalan.
2. **Terminal / PowerShell**.

## Langkah-Langkah

### 1. Jalankan Database
- Buka **XAMPP Control Panel**.
- Start modul **Apache** dan **MySQL**.

### 2. Konfigurasi Project
1. Buka folder project di terminal:
   ```powershell
   cd "C:\Users\NAZWA DJULIA\OneDrive\Dokumen\project_AIML\SmartFinance"
   ```
2. Pastikan file `.env` sudah terkonfigurasi (sudah dilakukan otomatis oleh sistem).
   - Database: `smart_finance`
   - DB Host: `127.0.0.1`

### 3. Migrasi Database
Jika Anda belum pernah menjalankan migrasi atau database masih kosong, jalankan perintah ini untuk membuat tabel:
```powershell
C:\xampp\php\php.exe artisan migrate
```

### 4. Jalankan Aplikasi
Jalankan development server Laravel dengan perintah:
```powershell
C:\xampp\php\php.exe artisan serve
```

### 5. Akses Website
- Buka browser dan kunjungi: [http://127.0.0.1:8000](http://127.0.0.1:8000)
- Halaman Login akan muncul.

## Fitur yang Tersedia
1. **Login & Register**: 
   - Anda bisa mendaftar akun baru.
   - Login menggunakan username dan password.
2. **Input Data Awal**:
   - Setelah register, Anda akan diarahkan ke halaman input modal awal, aset, dan bahan baku.
3. **Rekap Data**:
   - Melihat ringkasan data yang baru diinput.
4. **Dashboard**:
   - Menampilkan grafik (dummy data untuk demo) dan ringkasan keuangan.

## Troubleshooting
- Jika terjadi error `zip extension missing`, pastikan extension zip di `php.ini` sudah diaktifkan (sudah dilakukan otomatis).
- Jika ada masalah permission, pastikan folder `bootstrap/cache` dan `storage` writable.
