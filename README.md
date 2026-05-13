# WA Greeting Chat

Plugin WordPress untuk menampilkan floating WhatsApp chat widget dengan form greeting dan penyimpanan data di WP-Admin.

## Deskripsi

Plugin ini menambahkan widget chat WhatsApp yang mengambang di pojok kanan bawah website. Pengunjung dapat mengisi form dengan informasi kontak mereka sebelum diarahkan ke WhatsApp untuk memulai percakapan dengan admin.

## Fitur

- **Floating Chat Widget** - Tombol WhatsApp yang mengambang di pojok kanan bawah
- **Form Lengkap** - Mengumpulkan data: Nama, Email, Company, Service, Nomor WhatsApp, dan Pesan
- **Validasi Form** - Validasi client-side dan server-side
- **Penyimpanan Data** - Semua submission disimpan sebagai Custom Post Type di WP-Admin
- **Custom Taxonomy** - Service/layanan dapat dikelola melalui taxonomy
- **Email Notification** - Notifikasi email ke admin untuk setiap submission baru
- **Multiple Notification Emails** - Support pengiriman notifikasi ke beberapa email sekaligus
- **Blocked Email Domains** - Proteksi spam dengan memblokir domain email tertentu (contoh: gmail.com, yahoo.com)
- **Privacy Policy** - Checkbox persetujuan privacy policy
- **Pre-filled WhatsApp Message** - Pesan WhatsApp otomatis terisi dengan data form

## Instalasi

1. Upload folder `wa-greeting-chat` ke direktori `/wp-content/plugins/`
2. Aktifkan plugin melalui menu 'Plugins' di WordPress
3. Konfigurasi pengaturan di **WA Submissions > Settings**

## Konfigurasi

Setelah aktivasi, buka **WA Submissions > Settings** di admin panel:

| Setting | Deskripsi |
|---------|-----------|
| Header Label | Teks yang ditampilkan di header chat box |
| Agent Image URL | URL gambar agent/avatar |
| Admin WhatsApp Number | Nomor WhatsApp admin (tanpa +62/0, contoh: 81234567890) |
| Notification Emails | Email untuk menerima notifikasi (pisahkan dengan koma untuk multiple) |
| Privacy Policy URL | URL halaman privacy policy |
| Blocked Email Domains | Domain email yang diblokir (pisahkan dengan koma) |

## Mengelola Services

1. Buka **WA Submissions > Services**
2. Tambahkan service/layanan yang akan ditampilkan di dropdown form
3. Service akan otomatis muncul di form chat widget

## Melihat Submissions

1. Buka **WA Submissions > All Submissions**
2. Klik pada submission untuk melihat detail lengkap
3. Data yang tersimpan: Nama, Email, Company, Service, Nomor WhatsApp, Pesan, dan URL halaman

## Struktur File

```
wa-greeting-chat/
├── wa-greeting-chat.php           # File utama plugin
├── composer.json                  # Composer dependencies
├── google-sheet-api.php           # Google Sheets API integration
├── script.js                      # JavaScript untuk interaksi form
├── style.css                      # Styling widget dan form
├── README.md                      # Dokumentasi
├── admin/
│   ├── admin-style.css            # Styling halaman admin
│   ├── admin-script.js            # JavaScript halaman admin
│   └── dashboard.js               # Chart.js dashboard charts
├── assets/
│   ├── logo.svg                   # Logo plugin (full, dengan teks)
│   └── icon.svg                   # Icon plugin (tanpa teks)
└── includes/
    ├── class-github-updater.php   # GitHub auto-updater
    ├── class-google-sheets.php   # Spreadsheets authorization
    └── class-submissions-table.php # Custom admin list table
```

## Requirements

- WordPress 5.0 atau lebih tinggi
- PHP 7.4 atau lebih tinggi

---

## Auto-Update dari GitHub

Plugin ini mendukung auto-update langsung dari GitHub Releases.

### Setup GitHub Repository

1. **Buat Repository di GitHub**
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin https://github.com/USERNAME/wa-greeting-chat.git
   git push -u origin main
   ```

2. **Konfigurasi Plugin**

   Edit file `wa-greeting-chat.php` dan ganti nilai berikut:
   ```php
   define('WA_GREETING_CHAT_GITHUB_USERNAME', 'USERNAME_ANDA');
   define('WA_GREETING_CHAT_GITHUB_REPO', 'wa-greeting-chat');
   ```

   Update juga Plugin URI di header:
   ```php
   * Plugin URI: https://github.com/USERNAME_ANDA/wa-greeting-chat
   ```

3. **Untuk Private Repository (Opsional)**

   Jika repository bersifat private, buat Personal Access Token di GitHub:
   - Buka GitHub > Settings > Developer settings > Personal access tokens
   - Generate token baru dengan scope `repo`
   - Uncomment dan isi token di `wa-greeting-chat.php`:
   ```php
   $updater->set_access_token('ghp_xxxxxxxxxxxx');
   ```

### Membuat Release Baru

Setiap kali ada update plugin:

1. **Update Version Number**

   Edit `wa-greeting-chat.php`:
   ```php
   * Version: 1.7
   ```
   Dan:
   ```php
   define('WA_GREETING_CHAT_VERSION', '1.7');
   ```

2. **Commit dan Push**
   ```bash
   git add .
   git commit -m "Release v1.7 - Deskripsi perubahan"
   git push
   ```

3. **Buat Release di GitHub**
   - Buka repository di GitHub
   - Klik **Releases** > **Create a new release**
   - Tag version: `v1.7` (harus sama dengan versi di plugin)
   - Release title: `v1.7`
   - Description: Tulis changelog/perubahan
   - Klik **Publish release**

4. **Upload ZIP (Opsional tapi Disarankan)**

   Untuk performa lebih baik, buat file ZIP plugin:
   ```bash
   zip -r wa-greeting-chat-v1.7.zip wa-greeting-chat/ -x "*.git*"
   ```
   Upload ZIP ini sebagai asset di GitHub Release.

### Cara Kerja Auto-Update

- WordPress akan cek GitHub API secara berkala
- Jika ada release baru dengan versi lebih tinggi, notifikasi update muncul di WP-Admin
- Admin bisa klik update seperti plugin biasa
- Plugin akan didownload dan diinstall otomatis

### Troubleshooting

| Masalah | Solusi |
|---------|--------|
| Update tidak muncul | Pastikan tag version di GitHub sama dengan format `v1.x` |
| Error saat update | Cek permission folder `/wp-content/plugins/` |
| Private repo tidak bisa update | Pastikan access token valid dan memiliki scope `repo` |

---

## Changelog
### Version 1.11
- **Release 1.11** — Integrating with Google Sheets API for data storage and adding sanitazion rules.


### Version 1.10
- **Change Logo** — Change logo Floating whatsapp Modern

### Version 1.9
- **Change Logo** — Change logo Floating whatsapp tobe logo offical Whatsapp

### Version 1.8
- **Fixing Bug** — Fixing direct form to Message to Whatsapp

### Version 1.7
- **Dashboard Analytics** — Halaman dashboard baru dengan summary cards (total all, bulan ini, minggu ini, hari ini), doughnut chart distribusi per Service Group, line chart trend submission 12 bulan terakhir, dan tabel Top 10 Companies
- **Chart.js Integration** — Visualisasi data menggunakan Chart.js v4.4.7 via CDN dengan animated counters
- **Plugin Branding** — Logo SVG plugin ditampilkan di Settings page dengan badge versi, icon di halaman View Detail
- **Performance Optimization** — Transient caching untuk dashboard data (5 menit) dan service tree frontend (1 jam), single SQL query untuk summary counts, cache invalidation otomatis saat data berubah
- **Service Group Filter** — Dropdown filter Service Group di halaman All Submissions, terintegrasi dengan search dan CSV export
- **Dashboard sebagai Default Page** — Klik menu WA Submissions langsung mengarah ke Dashboard
- **Submenu Reorder** — Urutan submenu: Dashboard, Services, All Submissions, Settings
- **Enhanced Detail View** — Textarea untuk pesan, mailto link untuk email, URL halaman yang clickable
- **Version Cache Busting** — Frontend CSS/JS menggunakan version tag untuk cache busting
- Penghapusan `error_log()` dari submission handler
- add code country code  field WA phone number

### Version 1.6
- Custom admin submissions table (menggantikan default WordPress list table)
- Export CSV dengan filter date range dan search
- Search submissions across semua field (name, email, company, dll)
- Date range filter untuk memfilter submissions berdasarkan tanggal
- Bulk delete submissions
- Tombol "View Detail" untuk melihat detail lengkap submission
- Service Group dengan cascading dropdown (parent-child taxonomy)
- Perbaikan taxonomy assignment (hanya child term di kolom Services)
- Perbaikan encoding &amp; pada nama taxonomy
- Perbaikan tampilan header chat box (alignment)

### Version 1.5
- Penambahan fitur blocked email domains
- Validasi minimum 5 kata untuk pesan
- Multiple notification emails support
- Perbaikan tampilan privacy policy checkbox
- Loading state pada tombol submit
- GitHub auto-updater integration

## Author

**Gio Fandi Idstar**

## License

GPL v2 or later
