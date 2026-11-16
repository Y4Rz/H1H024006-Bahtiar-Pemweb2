# Laporan Praktikum Pemrograman Web II - Modul 7
**Integrasi Fiber v3 dengan GORM dan MySQL**

- **Nama:** Bahtiar Rizqi Efendy
- **NIM:** H1H024006

---

## A. Langkah Praktikum

### Langkah 1 – Menyiapkan Database dan Dependensi

Menambahkan dependensi GORM dan driver MySQL ke `go.mod`:

```bash
go get gorm.io/gorm
go get gorm.io/driver/mysql
go get github.com/joho/godotenv
```

Menyiapkan file `.env` di root project:

```env
APP_NAME=Pemweb2 Fiber API
APP_PORT=3000
APP_ENV=local
API_KEY=rahasia123
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=pemweb2_fiber
DB_USER=root
DB_PASS=
```

### Langkah 2 – Konfigurasi dan Koneksi Database

Memperbarui `internal/config/config.go` dengan menambahkan field konfigurasi database:

```go
type Konfigurasi struct {
    NamaAplikasi string
    Port         string
    Lingkungan   string
    DbHost       string
    DbPort       string
    DbNama       string
    DbUser       string
    DbSandi      string
}
```

Membuat `internal/config/database.go` untuk koneksi GORM ke MySQL:

```go
var DB *gorm.DB

func HubungkanBasisData(konf Konfigurasi) {
    dsn := fmt.Sprintf(
        "%s:%s@tcp(%s:%s)/%s?charset=utf8mb4&parseTime=True&loc=Local",
        konf.DbUser, konf.DbSandi, konf.DbHost, konf.DbPort, konf.DbNama,
    )
    // ...
    DB = koneksi
}
```

### Langkah 3 – Model GORM (Model Layer)

Membuat tiga model di `internal/model/`:

**`mahasiswa.go`** – Model dengan soft delete dan relasi:

```go
type ProgramStudi struct {
    ID      uint   `gorm:"primaryKey"`
    Kode    string `gorm:"size:10;uniqueIndex"`
    Nama    string `gorm:"size:100"`
    Jenjang string `gorm:"size:10"`
    // ...
}

type Mahasiswa struct {
    ID             uint           `gorm:"primaryKey"`
    ProgramStudiID uint           `gorm:"index"`
    ProgramStudi   *ProgramStudi  `gorm:"foreignKey:ProgramStudiID"`
    Nim            string         `gorm:"size:20;uniqueIndex"`
    Email          string         `gorm:"size:100;uniqueIndex"`
    Angkatan       int
    Ipk            float64        `gorm:"type:decimal(4,2)"`
    Aktif          bool
    DeletedAt      gorm.DeletedAt `gorm:"index"` // Soft delete
    // ...
}
```

**`matakuliah.go`** – Model matakuliah dengan relasi ke program studi.

### Langkah 4 – AutoMigrate dan Data Awal

Membuat `internal/config/migrasi.go`:

```go
func Migrasi() {
    DB.AutoMigrate(&model.ProgramStudi{}, &model.Mahasiswa{}, &model.Matakuliah{})
}

func DataAwal() {
    // Seed 3 program studi jika tabel masih kosong
    daftar := []model.ProgramStudi{
        {Kode: "TK", Nama: "Teknik Komputer", Jenjang: "S1"},
        {Kode: "IF", Nama: "Informatika", Jenjang: "S1"},
        {Kode: "TE", Nama: "Teknik Elektro", Jenjang: "S1"},
    }
    DB.Create(&daftar)
}
```

### Langkah 5 – Repository Layer (Pola Repository)

Membuat tiga file repository di `internal/repository/`:

**`mahasiswa_repository.go`** – Operasi CRUD + filter + pagination + soft delete:

```go
type FilterMahasiswa struct {
    Cari       string
    Angkatan   int
    Urut       string
    Halaman    int
    PerHalaman int
}

func Ambil(filter FilterMahasiswa) ([]model.Mahasiswa, int64, error) { ... }
func AmbilPerId(id uint) (model.Mahasiswa, error) { ... }
func AmbilPerNim(nim string) (model.Mahasiswa, error) { ... }
func Simpan(mahasiswa *model.Mahasiswa) error { ... }
func Perbarui(mahasiswa *model.Mahasiswa) error { ... }
func Hapus(mahasiswa *model.Mahasiswa) error { ... }         // Soft delete
func Pulihkan(id uint) (model.Mahasiswa, error) { ... }     // Restore
```

**`matakuliah_repository.go`** – Operasi CRUD dengan filter semester.

**`program_studi_repository.go`** – Query dengan subquery jumlah mahasiswa aktif:

```go
func AmbilProgramStudiDenganJumlah() ([]ProgramStudiDenganJumlah, error) {
    // SELECT ..., (SELECT COUNT(*) FROM mahasiswa WHERE ... AND deleted_at IS NULL) AS jumlah_mahasiswa
}
```

### Langkah 6 – Handler Layer

Membuat handler untuk setiap resource di `internal/handler/`:

- **`mahasiswa_handler.go`** – 7 fungsi: `DaftarMahasiswa`, `DetailMahasiswa`, `CariMahasiswaByNim`, `BuatMahasiswa`, `PerbaruiMahasiswa`, `HapusMahasiswa`, `PulihkanMahasiswa`
- **`matakuliah_handler.go`** – 5 fungsi: `DaftarMatakuliah`, `DetailMatakuliah`, `BuatMatakuliah`, `PerbaruiMatakuliah`, `HapusMatakuliah`
- **`program_studi_handler.go`** – 1 fungsi: `DaftarProgramStudi`
- **`dto.go`** – Struct request body: `PermintaanMahasiswa`, `PermintaanMatakuliah`

### Langkah 7 – Router

Memperbarui `internal/router/router.go` dengan menambahkan semua route baru:

```
GET  /api/status               → tanpa auth
GET  /api/program-studi        → KunciApi()

GET    /api/mahasiswa           → DaftarMahasiswa  (pagination, filter, cari)
POST   /api/mahasiswa           → BuatMahasiswa
GET    /api/mahasiswa/nim/:nim  → CariMahasiswaByNim
GET    /api/mahasiswa/:id       → DetailMahasiswa
PUT    /api/mahasiswa/:id       → PerbaruiMahasiswa
DELETE /api/mahasiswa/:id       → HapusMahasiswa (soft delete)
POST   /api/mahasiswa/:id/pulihkan → PulihkanMahasiswa

GET    /api/matakuliah         → DaftarMatakuliah (pagination, filter)
POST   /api/matakuliah         → BuatMatakuliah
GET    /api/matakuliah/:id     → DetailMatakuliah
PUT    /api/matakuliah/:id     → PerbaruiMatakuliah
DELETE /api/matakuliah/:id     → HapusMatakuliah
```

### Langkah 8 – Memperbarui `cmd/api/main.go`

Menambahkan pemanggilan koneksi database, migrasi, dan data awal sebelum server dijalankan:

```go
func main() {
    konf := config.Muat()

    config.HubungkanBasisData(konf)
    config.Migrasi()
    config.DataAwal()

    app := fiber.New(...)
    // ...
}
```

---

## B. Tugas Praktikum

### Tugas 1 – Pengujian API dengan Postman

Koleksi Postman tersimpan di folder `postman/`:
- `Modul7-Fiber-GORM.postman_collection.json` – 20 request dengan automated test
- `Modul7-Local.postman_environment.json` – Environment variables

Daftar skenario pengujian yang dilakukan:

| No | Request | Method | URL | Expected |
|:--:|:--------|:------:|:----|:---------|
| 1 | Status API | GET | `/api/status` | 200 OK |
| 2 | Tanpa API Key | GET | `/api/mahasiswa` | 401 Unauthorized |
| 3 | Daftar Program Studi | GET | `/api/program-studi` | 200 + jumlah_mahasiswa |
| 4 | Buat Mahasiswa | POST | `/api/mahasiswa` | 201 Created |
| 5 | Validasi NIM kosong | POST | `/api/mahasiswa` | 400 Bad Request |
| 6 | Validasi IPK > 4 | POST | `/api/mahasiswa` | 400 Bad Request |
| 7 | Daftar Mahasiswa (pagination) | GET | `/api/mahasiswa?halaman=1` | 200 + meta |
| 8 | Filter Angkatan | GET | `/api/mahasiswa?angkatan=2024` | 200 |
| 9 | Pencarian Nama/NIM | GET | `/api/mahasiswa?cari=Bahtiar` | 200 |
| 10 | Urut tidak valid | GET | `/api/mahasiswa?urut=invalid` | 400 |
| 11 | Detail by ID | GET | `/api/mahasiswa/:id` | 200 + program_studi |
| 12 | Detail tidak ditemukan | GET | `/api/mahasiswa/99999` | 404 |
| 13 | Cari by NIM | GET | `/api/mahasiswa/nim/:nim` | 200 |
| 14 | Perbarui Mahasiswa | PUT | `/api/mahasiswa/:id` | 200 |
| 15 | Hapus Mahasiswa | DELETE | `/api/mahasiswa/:id` | 200 (soft delete) |
| 16 | Detail setelah dihapus | GET | `/api/mahasiswa/:id` | 404 |
| 17 | Pulihkan Mahasiswa | POST | `/api/mahasiswa/:id/pulihkan` | 200 |
| 18 | Buat Matakuliah | POST | `/api/matakuliah` | 201 Created |
| 19 | Daftar Matakuliah | GET | `/api/matakuliah` | 200 + meta |
| 20 | Hapus Matakuliah | DELETE | `/api/matakuliah/:id` | 200 |

### Tugas 2 – Pertanyaan Pembahasan

**1. Apa keuntungan menggunakan pola Repository dibanding mengakses `config.DB` langsung di handler?**

Pola Repository memisahkan logika akses data dari logika bisnis/HTTP. Keuntungannya:
- **Separation of Concerns:** Handler hanya menangani HTTP request/response, Repository menangani query database.
- **Kemudahan Testing:** Repository bisa di-mock saat unit testing tanpa butuh database nyata.
- **Reusabilitas:** Fungsi `AmbilPerId()` bisa dipanggil dari handler manapun tanpa duplikasi query.
- **Keterbacaan:** Kode lebih bersih dan mudah dipahami karena setiap lapisan punya tanggung jawab yang jelas.

**2. Jelaskan perbedaan Soft Delete dan Hard Delete pada GORM. Kapan masing-masing digunakan?**

| Aspek | Soft Delete | Hard Delete |
|:------|:------------|:------------|
| **Mekanisme** | Mengisi kolom `deleted_at` dengan timestamp | Menghapus baris dari database secara permanen |
| **Cara Kerja di GORM** | Otomatis jika model punya field `gorm.DeletedAt` | `DB.Unscoped().Delete()` atau model tanpa `DeletedAt` |
| **Data** | Masih ada di database, tidak muncul di query normal | Data hilang selamanya |
| **Kapan Digunakan** | Data penting yang mungkin perlu dipulihkan (mahasiswa, pengguna) | Data sementara/log yang tidak perlu dipulihkan (matakuliah, session) |

Pada proyek ini, **Mahasiswa** menggunakan soft delete karena ada kemungkinan data perlu dipulihkan. **Matakuliah** menggunakan hard delete karena dianggap tidak memerlukan pemulihan.

**3. Apa fungsi `Preload("ProgramStudi")` pada query GORM?**

`Preload` melakukan **Eager Loading** — GORM secara otomatis menjalankan query kedua untuk mengambil data relasi (ProgramStudi) dan menggabungkannya ke dalam struct hasil utama. Tanpa `Preload`, field `ProgramStudi` di struct `Mahasiswa` akan bernilai `nil`. Ini mencegah masalah **N+1 query** jika dibandingkan lazy loading manual di dalam loop.

**4. Mengapa `PermintaanMahasiswa.Aktif` bertipe `*bool` (pointer) bukan `bool`?**

Karena nilai `bool` memiliki zero value `false`, sehingga tidak bisa dibedakan antara:
- Pengguna tidak mengirim field `aktif` (tidak ingin mengubah)
- Pengguna mengirim `aktif: false` (ingin menonaktifkan)

Dengan tipe `*bool` (pointer), jika field tidak dikirim maka nilainya `nil`, sehingga handler bisa melakukan pengecekan `if permintaan.Aktif != nil` sebelum memperbarui field tersebut.
