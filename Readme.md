# Laporan Praktikum Pemrograman Web II - Modul 1
**Penyiapan Lingkungan Pengembangan Web Modern (Laravel 13 & Fiber v3)**

- **Nama:** Bahtiar
- **NIM:** H1H024006
- **Program Studi:** Teknik Komputer
- **Instansi:** Universitas Jenderal Soedirman

---

## 1. Tugas Praktikum

### Tabel Perbandingan Laravel 13 vs Fiber v3
| Aspek Perbandingan | Laravel 13 (PHP) | Fiber v3 (Go) |
| :--- | :--- | :--- |
| **Bahasa & Runtime** | Menggunakan PHP 8.4+ yang membutuhkan PHP *interpreter/runtime*. | Menggunakan Go 1.23+ yang dikompilasi menjadi *binary executable* tunggal. |
| **Manajer Dependensi** | Dikelola menggunakan **Composer** (`composer.json`) di folder `vendor/`. | Dikelola menggunakan **Go Modules** (`go.mod`). |
| **Struktur Proyek** | Memiliki arsitektur terstruktur (MVC, ORM Eloquent, Routing, Provider). | Bersifat *micro-framework* minimalis yang ringan dan dinamis. |
| **Cara Eksekusi** | Dijalankan dengan `php artisan serve` pada port default `8000`. | Dijalankan dengan `go run main.go` (Engine Fasthttp) pada port `3000`. |

---

## 2. Pertanyaan Pembahasan

1. **Mengapa folder `vendor` dan berkas binary Go tidak diikutsertakan dalam repositori Git?**
   - **Folder `vendor`:** Ukurannya sangat besar karena berisi library pihak ketiga. Seluruh dependensi cukup dicatat di `composer.json` dan bisa diunduh ulang menggunakan perintah `composer install`.
   - **Berkas Binary Go:** Merupakan hasil kompilasi yang bersifat spesifik pada Sistem Operasi dan arsitektur mesin tertentu. Berkas ini dapat dibuat ulang kapan saja menggunakan perintah `go build`.

2. **Apa fungsi berkas `composer.json` dan `go.mod`, serta apa persamaannya?**
   - **`composer.json`:** Berkas manifest untuk mencatat daftar library PHP, versi, dan konfigurasi *autoloading*.
   - **`go.mod`:** Berkas manifest modul Go yang mencatat nama modul dan dependensi pustaka Go.
   - **Persamaan:** Keduanya merupakan *Dependency Manifest File* yang menjamin konsistensi versi library di seluruh lingkungan pengembangan.

3. **Jelaskan perbedaan port 8000 pada Laravel dan port 3000 pada Fiber.**
   - **Port 8000:** Digunakan oleh server pengembangan PHP/Artisan untuk menangani *request* Laravel.
   - **Port 3000:** Digunakan oleh server Fasthttp untuk menangani *request* Fiber.
   - Perbedaan port ini memungkinkan kedua *service backend* dapat berjalan bersamaan (*concurrent*) di satu mesin lokal tanpa bentrokan (*port conflict*).