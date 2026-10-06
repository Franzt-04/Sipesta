# SIPesta

SIPesta adalah aplikasi pemesanan produk berbasis web yang menghubungkan **Admin, Penjual, dan Pembeli** dalam satu sistem.

## 🚀 Fitur

### Admin

* Dashboard admin
* Kelola pengguna
* Kelola kategori
* Kelola produk
* Kelola pesanan
* Monitoring sistem

### Penjual

* Dashboard penjual
* Kelola produk
* Kelola stok
* Melihat pesanan
* Mengelola pesanan
* Profil toko

### Pembeli

* Melihat produk
* Melihat detail produk
* Menambahkan produk ke keranjang
* Checkout
* Melihat pesanan
* Melihat detail pesanan
* Membatalkan pesanan sesuai status

## 🛠️ Teknologi

* PHP Native
* MySQL
* HTML5
* CSS3
* JavaScript
* Bootstrap 5
* Bootstrap Icons
* XAMPP
* Git & GitHub

## 📁 Struktur Project

```text
Sipesta/
├── admin/
├── penjual/
├── pembeli/
├── config/
├── assets/
├── uploads/
├── database/
│   └── sipesta.sql
├── docs/
├── .gitignore
├── README.md
└── index.php
```

## 💻 Persyaratan

Sebelum menjalankan project, pastikan sudah tersedia:

* Windows
* XAMPP
* PHP
* MySQL
* Web browser
* Git

## ⚙️ Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/Franzt-04/Sipesta.git
```

### 2. Masuk ke Folder

```bash
cd Sipesta
```

### 3. Pindahkan ke XAMPP

Letakkan folder project di:

```text
C:\xampp\htdocs\Sipesta
```

### 4. Jalankan XAMPP

Aktifkan:

```text
Apache
MySQL
```

### 5. Buat Database

Buka:

```text
http://localhost/phpmyadmin
```

Buat database:

```text
sipesta
```

### 6. Import Database

Pilih database `sipesta`.

Kemudian pilih:

```text
Import
```

dan upload:

```text
database/sipesta.sql
```

Klik **Import**.

### 7. Periksa Konfigurasi Database

Buka:

```text
config/database.php
```

Sesuaikan konfigurasi database dengan komputer masing-masing.

Contoh konfigurasi XAMPP:

```php
$host = "localhost";
$user = "root";
$password = "";
$db = "sipesta";
```

### 8. Jalankan Aplikasi

Buka browser:

```text
http://localhost/Sipesta
```

## 🔄 Update Project

Jika mendapatkan perubahan terbaru dari GitHub:

```bash
git pull origin main
```

## 📤 Upload Perubahan

Setelah melakukan perubahan:

```bash
git add .
```

Kemudian:

```bash
git commit -m "Update fitur"
```

Lalu:

```bash
git push origin main
```

## 👨‍💻 Author

**Muh Farhan**

## 📌 Status

Project masih dalam tahap pengembangan.
