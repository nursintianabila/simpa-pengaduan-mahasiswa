# SIMPA — Sistem Informasi Pengaduan & Aspirasi Mahasiswa

SIMPA adalah aplikasi berbasis web untuk mengelola pengaduan dan aspirasi mahasiswa. Sistem dibangun menggunakan **Laravel 10** sebagai REST API backend, **Vue 3** sebagai frontend SPA, **MySQL** sebagai database, **Laravel Sanctum** untuk autentikasi API, serta **AtmoRouter AI Gateway** untuk membantu analisis pengaduan.

AI pada sistem ini berfungsi sebagai **alat bantu analisis dan pendukung keputusan**, bukan sebagai pengambil keputusan akhir. Keputusan dan tindak lanjut tetap dilakukan oleh administrator.

---

## 1. Latar Belakang

Sebelum menggunakan sistem, pengaduan mahasiswa berpotensi disampaikan melalui pesan pribadi, lisan, atau media yang tidak terstruktur. Hal tersebut dapat membuat laporan sulit didokumentasikan, sulit dipantau, dan sulit diprioritaskan.

SIMPA dibuat agar proses pengaduan menjadi lebih terstruktur, terdokumentasi, mudah dipantau, serta dapat dibantu oleh analisis AI.

## 2. Tujuan Sistem

- Menyediakan media pengaduan dan aspirasi mahasiswa secara digital.
- Memudahkan mahasiswa memantau status laporan.
- Memudahkan admin mengelola seluruh laporan yang masuk.
- Menyediakan fitur tanggapan admin.
- Membantu admin memahami laporan melalui AI.
- Mengelompokkan laporan berdasarkan kategori, jenis, status, prioritas, dan sentimen.
- Menyimpan seluruh riwayat laporan dalam database.

---

## 3. Teknologi yang Digunakan

### Backend
- Laravel 10
- PHP
- MySQL
- Laravel Sanctum
- Laravel HTTP Client

### Frontend
- Vue 3
- Vue Router
- Axios
- Vite
- HTML, CSS, JavaScript

### AI
- AtmoRouter AI Gateway
- Model: `atmo/deepseek-v4.1-flash`

### Tools
- Visual Studio Code
- Laragon
- PowerShell
- Git
- GitHub

---

## 4. Arsitektur Sistem

```text
Mahasiswa / Admin
        |
        v
   Vue 3 Frontend
        |
        | HTTP / JSON
        v
 Laravel REST API
        |
        +--------------------+
        |                    |
        v                    v
      MySQL           AtmoRouter AI Gateway
                           |
                           v
                 DeepSeek AI Model
```

Frontend bertugas menampilkan antarmuka dan menerima input pengguna. Backend menangani autentikasi, validasi, otorisasi, business logic, database, dan komunikasi dengan AI Gateway.

---

## 5. Role Pengguna

### Mahasiswa
Mahasiswa dapat:
- registrasi akun;
- login;
- melihat dashboard;
- melihat daftar pengaduan miliknya;
- membuat pengaduan atau aspirasi;
- melihat detail laporan;
- melihat ringkasan AI;
- melihat prioritas AI;
- melihat sentimen AI;
- mengedit laporan selama status masih `menunggu`;
- menghapus laporan selama status masih `menunggu`;
- melihat tanggapan admin;
- melihat perkembangan status laporan.

### Admin
Admin dapat:
- login;
- melihat dashboard admin;
- melihat seluruh pengaduan mahasiswa;
- mencari dan memfilter pengaduan;
- melihat hasil AI secara lengkap;
- melihat rekomendasi AI;
- mengubah kategori;
- mengubah status;
- memberikan tanggapan;
- menyelesaikan atau menolak laporan.

---

## 6. Status dan Jenis Laporan

Status pengaduan:

```text
menunggu
Diproses
selesai
ditolak
```

Jenis laporan:

```text
pengaduan
aspirasi
```

Contoh kategori:
- Akademik
- Sarana dan Prasarana
- Keuangan
- Kemahasiswaan
- Teknologi Informasi
- Lainnya

---

# PROSES PEMBUATAN PROYEK

## 7. Membuat Struktur Project

Project dipisahkan menjadi dua folder utama:

```text
pengaduan_mahasiswa/
├── backend/
└── frontend/
```

Folder `backend` digunakan untuk Laravel dan folder `frontend` digunakan untuk Vue 3.

---

## 8. Membuat Backend Laravel

Contoh pembuatan project:

```bash
composer create-project laravel/laravel backend
```

Masuk ke folder backend:

```bash
cd backend
```

Jalankan server Laravel:

```bash
php artisan serve
```

Backend berjalan pada:

```text
http://127.0.0.1:8000
```

---

## 9. Konfigurasi Database

Database menggunakan MySQL. Konfigurasi disimpan di:

```text
backend/.env
```

Contoh:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pengaduan_mahasiswa
DB_USERNAME=root
DB_PASSWORD=
```

Setelah konfigurasi selesai:

```bash
php artisan migrate
```

> File `.env` tidak boleh diunggah ke GitHub karena dapat berisi informasi sensitif.

---

## 10. Perancangan Database

### Tabel `users`
Field utama:

```text
id
name
nim
email
password
role
created_at
updated_at
```

Role:

```text
mahasiswa
admin
```

### Tabel `categories`

```text
id
name
description
created_at
updated_at
```

### Tabel `complaints`

```text
id
user_id
category_id
type
title
description
attachment
ai_summary
ai_analysis
ai_recommendation
ai_priority
ai_sentiment
status
created_at
updated_at
```

### Tabel `responses`

```text
id
complaint_id
user_id
response
created_at
updated_at
```

Relasi utama:

```text
User 1 --- N Complaint
Category 1 --- N Complaint
Complaint 1 --- N Response
User 1 --- N Response
```

---

## 11. Autentikasi Laravel Sanctum

Laravel Sanctum digunakan untuk autentikasi berbasis token.

Endpoint utama:

```text
POST /api/register
POST /api/login
GET  /api/me
POST /api/logout
```

Route yang membutuhkan autentikasi dilindungi dengan:

```php
auth:sanctum
```

Registrasi publik hanya digunakan untuk mahasiswa. Admin tidak dibuat melalui form registrasi publik.

---

## 12. REST API Pengaduan

Endpoint utama:

```text
GET    /api/complaints
POST   /api/complaints
GET    /api/complaints/{id}
PUT    /api/complaints/{id}
DELETE /api/complaints/{id}
```

Aturan akses:
- mahasiswa hanya dapat melihat laporan miliknya sendiri;
- mahasiswa hanya dapat mengedit dan menghapus laporan yang masih `menunggu`;
- admin dapat melihat seluruh laporan serta mengubah kategori dan status.

---

# INTEGRASI AI

## 13. Konfigurasi AtmoRouter

Konfigurasi disimpan di `backend/.env`:

```env
ATMOROUTER_BASE_URL=https://atmorouter.dev/v1
ATMOROUTER_API_KEY=ISI_API_KEY_ANDA
ATMOROUTER_MODEL=atmo/deepseek-v4.1-flash
```

Kemudian dipanggil melalui `config/services.php`.

> Jangan pernah menuliskan API key asli di README, frontend, atau repository publik.

---

## 14. Pengujian Koneksi AI

Sebelum digunakan pada laporan mahasiswa, koneksi ke AI Gateway diuji dari Laravel.

Backend meminta AI mengembalikan data JSON dengan struktur seperti:

```json
{
  "summary": "Ringkasan laporan",
  "analysis": "Analisis masalah",
  "recommendation": "Rekomendasi penanganan",
  "priority": "tinggi",
  "sentiment": "negatif"
}
```

Hasil uji digunakan untuk memastikan model, API key, dan format respons berjalan dengan benar.

---

## 15. Service AI

Logic AI dipisahkan ke service khusus agar controller tetap rapi.

AI menerima data seperti:

```text
Jenis laporan
Judul
Deskripsi
```

AI menghasilkan:

```text
summary
analysis
recommendation
priority
sentiment
```

Nilai prioritas:

```text
rendah
sedang
tinggi
```

Nilai sentimen:

```text
positif
netral
negatif
```

---

## 16. Alur AI pada Pengaduan

```text
Mahasiswa mengirim laporan
        |
        v
Laravel menyimpan laporan
        |
        v
Backend mengirim isi laporan ke AI
        |
        v
AI menganalisis laporan
        |
        v
Backend menerima JSON
        |
        v
Hasil AI disimpan ke database
```

Field AI yang disimpan:

```text
ai_summary
ai_analysis
ai_recommendation
ai_priority
ai_sentiment
```

Jika AI gagal merespons, laporan utama tetap dapat disimpan. Dengan demikian kegagalan AI tidak menggagalkan proses pengaduan.

---

## 17. Fungsi AI

AI membantu admin melalui:

- **Ringkasan**: memberikan inti laporan secara singkat.
- **Analisis**: membantu menjelaskan masalah pada laporan.
- **Rekomendasi**: memberikan saran awal penanganan.
- **Prioritas**: mengelompokkan tingkat urgensi.
- **Sentimen**: mengidentifikasi kecenderungan isi laporan.

AI hanya menjadi sistem pendukung keputusan. Keputusan akhir tetap berada pada admin.

---

# FRONTEND VUE

## 18. Membuat Frontend

Contoh pembuatan Vue 3 dengan Vite:

```bash
npm create vite@latest frontend
```

Kemudian:

```bash
cd frontend
npm install
npm run dev
```

Frontend berjalan pada:

```text
http://localhost:5173
```

---

## 19. Axios dan API

Axios digunakan untuk berkomunikasi dengan Laravel.

Base URL:

```text
http://127.0.0.1:8000/api
```

Setelah login, token disimpan pada `localStorage` dan dikirim melalui header:

```text
Authorization: Bearer TOKEN
```

---

## 20. Vue Router dan Route Guard

Route utama mahasiswa:

```text
/login
/register
/dashboard
/complaints
/complaints/create
/complaints/:id
/complaints/:id/edit
```

Route admin:

```text
/admin/dashboard
/admin/complaints
/admin/complaints/:id
```

Aturan route guard:

```text
Belum login -> /login
Mahasiswa membuka halaman admin -> /dashboard
Admin membuka halaman mahasiswa -> /admin/dashboard
```

---

# FITUR MAHASISWA

## 21. Dashboard Mahasiswa

Dashboard menampilkan:
- profil mahasiswa;
- statistik laporan berdasarkan status;
- statistik prioritas AI;
- laporan terbaru.

## 22. Daftar Pengaduan

Fitur:
- pencarian;
- filter status;
- filter jenis;
- filter prioritas AI;
- melihat detail laporan.

## 23. Membuat Pengaduan

Mahasiswa mengisi:

```text
Jenis
Kategori
Judul
Deskripsi
Lampiran (jika tersedia)
```

Setelah disimpan, AI melakukan analisis otomatis.

## 24. Detail Pengaduan Mahasiswa

Mahasiswa dapat melihat:

```text
Judul
Jenis
Kategori
Deskripsi
Status
Tanggapan admin
Ringkasan AI
Prioritas AI
Sentimen AI
```

Mahasiswa tidak perlu melihat analisis internal dan rekomendasi AI selengkap admin.

## 25. Edit dan Hapus

Mahasiswa dapat mengedit atau menghapus laporan jika status masih:

```text
menunggu
```

Apabila judul, jenis, atau deskripsi diubah, AI dapat melakukan analisis ulang.

---

# FITUR ADMIN

## 26. Dashboard Admin

Dashboard admin menampilkan:
- total pengaduan;
- jumlah `menunggu`;
- jumlah `diproses`;
- jumlah `selesai`;
- jumlah `ditolak`;
- statistik prioritas AI;
- laporan terbaru;
- laporan prioritas tinggi.

## 27. Daftar Pengaduan Admin

Admin dapat memfilter berdasarkan:

```text
Status
Jenis
Prioritas AI
Pencarian
```

## 28. Detail Pengaduan Admin

Admin dapat melihat seluruh hasil AI:

```text
Ringkasan
Analisis
Rekomendasi
Prioritas
Sentimen
```

## 29. Tanggapan dan Status

Admin dapat memberikan tanggapan kepada mahasiswa.

Contoh alur status:

```text
menunggu -> diproses -> selesai
```

Atau:

```text
menunggu / diproses -> ditolak
```

---

# ALUR SISTEM

## 30. Alur Mahasiswa

```text
Register
   |
   v
Login
   |
   v
Dashboard
   |
   v
Buat Pengaduan
   |
   v
Data Disimpan
   |
   v
AI Menganalisis
   |
   v
Mahasiswa Melihat Detail
   |
   v
Menunggu Tanggapan Admin
   |
   v
Melihat Status dan Tanggapan
```

## 31. Alur Admin

```text
Login Admin
     |
     v
Dashboard Admin
     |
     v
Melihat Pengaduan
     |
     v
Membuka Detail
     |
     v
Membaca Hasil AI
     |
     v
Menentukan Tindakan
     |
     v
Memberikan Tanggapan
     |
     v
Mengubah Status
     |
     v
Selesai / Ditolak
```

## 32. Alur AI

```text
Pengaduan Mahasiswa
        |
        v
Backend Laravel
        |
        v
AtmoRouter AI Gateway
        |
        v
DeepSeek Model
        |
        v
JSON Hasil Analisis
        |
        v
Database
        |
        v
Ditampilkan kepada Admin
```

---

# PENGUJIAN SISTEM

## 33. Pengujian Register

Data uji:

```text
Nama
NIM
Email
Password
Konfirmasi Password
```

Hasil yang diharapkan: akun mahasiswa berhasil dibuat.

## 34. Pengujian Login

```text
Mahasiswa -> Dashboard Mahasiswa
Admin     -> Dashboard Admin
```

## 35. Pengujian Pengaduan

Contoh:

```text
Jenis: Pengaduan
Kategori: Sarana dan Prasarana
Judul: Koneksi WiFi Kampus Sering Terputus
```

Hasil yang diharapkan:

```text
Pengaduan tersimpan
Status = menunggu
Hasil AI tersimpan
```

## 36. Pengujian AI

Field yang diperiksa:

```text
ai_summary
ai_analysis
ai_recommendation
ai_priority
ai_sentiment
```

Kelima field harus terisi ketika AI berhasil.

## 37. Pengujian Hak Akses

- mahasiswa hanya dapat melihat laporan sendiri;
- admin dapat melihat seluruh laporan;
- mahasiswa tidak dapat membuka dashboard admin;
- admin diarahkan ke dashboard admin.

## 38. Pengujian Edit dan Delete

Mahasiswa dapat mengedit dan menghapus laporan yang masih `menunggu`. Laporan yang sudah diproses tidak dapat diedit atau dihapus oleh mahasiswa.

---

# STRUKTUR PROJECT

## 39. Backend

```text
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   ├── Models/
│   └── Services/
├── config/
│   └── services.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── .env
├── composer.json
└── artisan
```

## 40. Frontend

```text
frontend/
├── src/
│   ├── api/
│   │   └── axios.js
│   ├── components/
│   ├── router/
│   │   └── index.js
│   ├── views/
│   ├── App.vue
│   └── main.js
├── package.json
├── package-lock.json
└── vite.config.js
```

---

# INSTALASI PROJECT DARI GITHUB

## 41. Clone Repository

```bash
git clone URL_REPOSITORY
cd pengaduan_mahasiswa
```

## 42. Setup Backend

```bash
cd backend
composer install
```

Salin `.env.example` menjadi `.env`.

PowerShell:

```powershell
Copy-Item .env.example .env
```

Generate key:

```bash
php artisan key:generate
```

Atur konfigurasi database dan AI di `.env`, lalu jalankan:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Jika project tidak menggunakan seeder, perintah `php artisan db:seed` dapat dilewati.

## 43. Setup Frontend

Buka terminal baru:

```bash
cd frontend
npm install
npm run dev
```

Buka:

```text
http://localhost:5173
```

---

# KEAMANAN

## 44. File yang Tidak Boleh Masuk GitHub

```text
backend/.env
backend/vendor/
frontend/node_modules/
frontend/dist/
```

Contoh `.gitignore` root:

```gitignore
backend/.env
backend/vendor/
backend/node_modules/

frontend/node_modules/
frontend/dist/
frontend/.env
frontend/.env.*

.vscode/
.idea/
.DS_Store
Thumbs.db

*.log
```

API key hanya disimpan di backend dan tidak boleh diletakkan di frontend atau repository publik.

---

# GIT DAN GITHUB

## 45. Inisialisasi Git

```bash
git init
git branch -M main
git add .
git commit -m "Initial commit SIMPA"
```

## 46. Menghubungkan ke GitHub

```bash
git remote add origin URL_REPOSITORY_GITHUB
git push -u origin main
```

## 47. Update Project Selanjutnya

```bash
git status
git add .
git commit -m "Deskripsi perubahan"
git push
```

---

# CONTOH PENGGUNAAN AI

Mahasiswa mengirim:

```text
Judul:
Koneksi WiFi Kampus Sering Terputus

Deskripsi:
Koneksi WiFi di area kampus sering terputus terutama saat jam
perkuliahan sehingga mengganggu mahasiswa mengakses materi dan
mengikuti pembelajaran daring.
```

AI dapat menghasilkan:

```text
Ringkasan:
Gangguan koneksi WiFi kampus menghambat proses pembelajaran.

Analisis:
Gangguan terjadi pada jam sibuk dan mempengaruhi akses layanan
akademik berbasis internet.

Rekomendasi:
Melakukan pengecekan access point, kapasitas jaringan, dan
kestabilan koneksi.

Prioritas:
Tinggi

Sentimen:
Negatif
```

Admin kemudian memeriksa hasil AI dan menentukan tindakan akhir.

---

# KETERBATASAN DAN PENGEMBANGAN SELANJUTNYA

## 48. Keterbatasan

- AI bergantung pada koneksi internet dan layanan pihak ketiga.
- Hasil AI tidak selalu sempurna.
- Keputusan akhir tetap membutuhkan admin.
- Notifikasi real-time belum menjadi fitur utama.
- Sistem belum memiliki aplikasi mobile native.

## 49. Pengembangan Selanjutnya

Fitur yang dapat ditambahkan:
- lupa password;
- verifikasi email;
- notifikasi email;
- notifikasi real-time;
- export laporan PDF;
- export laporan Excel;
- dashboard statistik yang lebih lengkap;
- audit log aktivitas admin;
- pagination server-side;
- pencarian lanjutan;
- manajemen user;
- pengaturan kategori melalui dashboard;
- riwayat perubahan status;
- evaluasi akurasi hasil AI.

---

# KESIMPULAN

SIMPA berhasil dibangun sebagai aplikasi pengaduan dan aspirasi mahasiswa berbasis web dengan frontend dan backend terpisah.

Fitur utama yang telah diterapkan meliputi:

```text
Registrasi
Login
Role Mahasiswa
Role Admin
Dashboard
CRUD Pengaduan
Kategori
Status Pengaduan
Tanggapan Admin
REST API
Laravel Sanctum
Vue Router
Axios
MySQL
AI Gateway
Analisis AI
Prioritas AI
Sentimen AI
Git
GitHub
```

Integrasi AI membantu administrator memahami laporan lebih cepat melalui ringkasan, analisis, rekomendasi, prioritas, dan sentimen. Namun, keputusan akhir tetap berada pada administrator.

---

## Identitas Project

**Nama Sistem:** SIMPA — Sistem Informasi Pengaduan & Aspirasi Mahasiswa  
**Jenis Aplikasi:** Web Application  
**Backend:** Laravel 10  
**Frontend:** Vue 3  
**Database:** MySQL  
**Authentication:** Laravel Sanctum  
**AI Gateway:** AtmoRouter  
**AI Model:** `atmo/deepseek-v4.1-flash`

---

## Catatan

Repository ini dibuat untuk keperluan pembelajaran dan pengembangan sistem informasi berbasis web.

Jangan mengunggah file `.env`, password, credential database, API key, atau informasi rahasia lainnya ke repository publik.
