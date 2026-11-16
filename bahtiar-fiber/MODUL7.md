# Laporan Praktikum Pemrograman Web II - Modul 7
**RESTful API CRUD dengan Fiber v3 dan GORM**

- **Nama:** Bahtiar Rizqi Efendy
- **NIM:** H1H024006

---

## A. Langkah Praktikum

### Langkah 1 – Memasang Dependensi

```bash
go get gorm.io/gorm
go get gorm.io/driver/mysql
```

### Langkah 2 – Menyiapkan Basis Data

Membuat database MySQL:

```sql
CREATE DATABASE pemweb2_fiber CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Menambahkan konfigurasi pada `.env`:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=pemweb2_fiber
DB_USER=root
DB_PASS=
```

### Langkah 3 – Memperluas Konfigurasi

Memperbarui `internal/config/config.go` dengan menambahkan field basis data pada struct `Konfigurasi` dan fungsi `Muat()`:

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

### Langkah 4 – Membuat Koneksi Basis Data

Membuat `internal/config/database.go`:

```go
var DB *gorm.DB

func HubungkanBasisData(konf Konfigurasi) {
    dsn := fmt.Sprintf(
        "%s:%s@tcp(%s:%s)/%s?charset=utf8mb4&parseTime=True&loc=Local",
        konf.DbUser, konf.DbSandi, konf.DbHost, konf.DbPort, konf.DbNama,
    )
    koneksi, err := gorm.Open(mysql.Open(dsn), &gorm.Config{})
    if err != nil {
        log.Fatal("Gagal terhubung ke basis data: ", err)
    }
    DB = koneksi
    log.Println("Koneksi basis data berhasil")
}
```

### Langkah 5 – Mendefinisikan Model GORM

Mengganti isi `internal/model/mahasiswa.go` dengan model GORM:

```go
type ProgramStudi struct {
    ID        uint      `gorm:"primaryKey" json:"id"`
    Kode      string    `gorm:"size:10;uniqueIndex" json:"kode"`
    Nama      string    `gorm:"size:100" json:"nama"`
    Jenjang   string    `gorm:"size:10" json:"jenjang"`
    CreatedAt time.Time `json:"dibuat_pada"`
    UpdatedAt time.Time `json:"diubah_pada"`
}

type Mahasiswa struct {
    ID             uint          `gorm:"primaryKey" json:"id"`
    ProgramStudiID uint          `gorm:"index" json:"program_studi_id"`
    ProgramStudi   *ProgramStudi `gorm:"foreignKey:ProgramStudiID" json:"program_studi,omitempty"`
    Nim            string        `gorm:"size:20;uniqueIndex" json:"nim"`
    Nama           string        `gorm:"size:100" json:"nama"`
    Email          string        `gorm:"size:100;uniqueIndex" json:"email"`
    Angkatan       int           `json:"angkatan"`
    Ipk            float64       `gorm:"type:decimal(4,2)" json:"ipk"`
    Aktif          bool          `gorm:"default:true" json:"aktif"`
    CreatedAt      time.Time     `json:"dibuat_pada"`
    UpdatedAt      time.Time     `json:"diubah_pada"`
}
```

### Langkah 6 – Menjalankan Migrasi dan Data Awal

Membuat `internal/config/migrasi.go`:

```go
func Migrasi() {
    err := DB.AutoMigrate(&model.ProgramStudi{}, &model.Mahasiswa{})
    if err != nil {
        log.Fatal("Gagal menjalankan migrasi: ", err)
    }
    log.Println("Migrasi selesai")
}

func DataAwal() {
    var jumlah int64
    DB.Model(&model.ProgramStudi{}).Count(&jumlah)
    if jumlah > 0 {
        return
    }
    daftar := []model.ProgramStudi{
        {Kode: "TK", Nama: "Teknik Komputer", Jenjang: "S1"},
        {Kode: "IF", Nama: "Informatika", Jenjang: "S1"},
        {Kode: "TE", Nama: "Teknik Elektro", Jenjang: "S1"},
    }
    DB.Create(&daftar)
    log.Println("Data awal program studi dibuat")
}
```

### Langkah 7 – Membuat Repository

Membuat `internal/repository/mahasiswa_repository.go`:

```go
type FilterMahasiswa struct {
    Cari       string
    Angkatan   int
    Halaman    int
    PerHalaman int
}

func Ambil(filter FilterMahasiswa) ([]model.Mahasiswa, int64, error) { ... }
func AmbilPerId(id uint) (model.Mahasiswa, error) { ... }
func Simpan(mahasiswa *model.Mahasiswa) error { ... }
func Perbarui(mahasiswa *model.Mahasiswa) error { ... }
func Hapus(mahasiswa *model.Mahasiswa) error { ... }
```

### Langkah 8 – Membuat DTO Permintaan

Membuat `internal/handler/dto.go`:

```go
type PermintaanMahasiswa struct {
    ProgramStudiID uint    `json:"program_studi_id"`
    Nim            string  `json:"nim"`
    Nama           string  `json:"nama"`
    Email          string  `json:"email"`
    Angkatan       int     `json:"angkatan"`
    Ipk            float64 `json:"ipk"`
    Aktif          *bool   `json:"aktif"`
}
```

Field `Aktif` memakai pointer agar dapat dibedakan antara nilai `false` yang dikirim secara sengaja dan field yang tidak dikirim sama sekali.

### Langkah 9 – Menulis Ulang Handler

Mengganti isi `internal/handler/mahasiswa_handler.go` dengan handler yang menggunakan repository:
- `DaftarMahasiswa` – pagination, filter angkatan, pencarian
- `DetailMahasiswa` – get by ID dengan preload ProgramStudi
- `BuatMahasiswa` – validasi + simpan
- `PerbaruiMahasiswa` – partial update
- `HapusMahasiswa` – hapus data

### Langkah 10 – Melengkapi Router

Memperbarui `internal/router/router.go`:

```go
mahasiswa := api.Group("/mahasiswa")
mahasiswa.Get("/", handler.DaftarMahasiswa)
mahasiswa.Post("/", handler.BuatMahasiswa)
mahasiswa.Get("/:id", handler.DetailMahasiswa)
mahasiswa.Put("/:id", handler.PerbaruiMahasiswa)
mahasiswa.Delete("/:id", handler.HapusMahasiswa)
```

### Langkah 11 – Memanggil Koneksi pada Berkas Utama

Menambahkan pada `cmd/api/main.go` tepat setelah `konf := config.Muat()`:

```go
config.HubungkanBasisData(konf)
config.Migrasi()
config.DataAwal()
```

Jalankan aplikasi:

```bash
go mod tidy
go run ./cmd/api
```

### Langkah 12 – Menguji Seluruh Endpoint

```bash
# Membuat data baru
curl -X POST http://localhost:3000/api/mahasiswa \
  -H "Content-Type: application/json" \
  -d '{"program_studi_id":1,"nim":"H1A125001","nama":"Eko Saputra","email":"eko@example.com","angkatan":2025,"ipk":3.55}'

# Mengambil daftar dengan pagination
curl -s "http://localhost:3000/api/mahasiswa?halaman=1&per_halaman=5"

# Mengubah data
curl -X PUT http://localhost:3000/api/mahasiswa/1 \
  -H "Content-Type: application/json" \
  -d '{"ipk":3.85}'

# Menghapus data
curl -X DELETE http://localhost:3000/api/mahasiswa/1
```

### Langkah 13 – Mengamati Kueri GORM

Mengubah `gorm.Open` pada `internal/config/database.go` untuk mengaktifkan logging SQL. Pada project ini sudah diterapkan level log adaptif berdasarkan environment:

```go
koneksi, err := gorm.Open(mysql.Open(dsn), &gorm.Config{
    Logger: gormLogger.Default.LogMode(gormLogger.Info), // level Info di environment local
})
```

Dengan import `gormLogger "gorm.io/gorm/logger"`. Setelah dijalankan ulang, kueri SQL tercetak di terminal. Ketika `Preload("ProgramStudi")` dihapus dari repository, jumlah kueri berkurang dari 2 menjadi 1, namun field `program_studi` menjadi `nil` pada respons JSON.

---

## B. Tugas Praktikum

### Tugas 1 – Resource Matakuliah (CRUD Lengkap)

Mengikuti pola yang sama dengan mahasiswa, dibuat:
- `internal/model/matakuliah.go` – Model dengan relasi ke `ProgramStudi`
- `internal/repository/matakuliah_repository.go` – Filter semester, pagination, CRUD
- `internal/handler/matakuliah_handler.go` – 5 fungsi handler (Daftar, Detail, Buat, Perbarui, Hapus)
- Route: `GET/POST/GET/:id/PUT/:id/DELETE/:id /api/matakuliah`

### Tugas 2 – Endpoint GET /api/program-studi dengan Jumlah Mahasiswa

Dibuat `internal/repository/program_studi_repository.go` dengan subquery untuk menghitung jumlah mahasiswa aktif per prodi:

```go
SELECT program_studi.*,
  (SELECT COUNT(*) FROM mahasiswa
   WHERE mahasiswa.program_studi_id = program_studi.id
   AND mahasiswa.deleted_at IS NULL) AS jumlah_mahasiswa
```

### Tugas 3 – Soft Delete dan Endpoint Pemulihan

Menambahkan `gorm.DeletedAt` pada model `Mahasiswa`:

```go
DeletedAt gorm.DeletedAt `gorm:"index" json:"dihapus_pada,omitempty"`
```

Juga menambahkan ke `AutoMigrate` di `migrasi.go`. Dibuat endpoint `POST /api/mahasiswa/:id/pulihkan` yang menggunakan `DB.Unscoped()` untuk menghapus nilai `deleted_at`, sehingga data kembali aktif.

### Tugas 4 – Perbandingan Jumlah Baris Kode Laravel vs Fiber

Membangun satu resource CRUD penuh (Mahasiswa dengan 5 endpoint):

| Komponen | Laravel | Fiber (Go) |
|:---------|--------:|----------:|
| Migration | ~25 baris | - (AutoMigrate otomatis) |
| Model/Eloquent | ~15 baris | ~20 baris (dengan struct tags) |
| Controller/Handler | ~80 baris | ~150 baris |
| Route | ~10 baris | ~8 baris |
| **Total** | **~130 baris** | **~178 baris** |

**Analisis penyebab perbedaan:**
- **Laravel lebih ringkas** karena Eloquent ORM menggunakan _convention over configuration_ — tidak perlu menulis query sama sekali untuk operasi standar. Method `all()`, `find()`, `create()`, `update()`, `delete()` sudah tersedia otomatis melalui pewarisan dari `Model`.
- **Fiber lebih verbose** karena Go adalah bahasa yang _explicit_ — setiap operasi database, penanganan error, dan konversi tipe harus ditulis secara eksplisit. Go tidak mengenal inheritance, sehingga tidak ada "base handler" yang bisa diwariskan.
- **Migrasi Laravel** perlu file tersendiri karena tidak ada AutoMigrate, namun memberikan kontrol penuh dan riwayat perubahan skema yang jelas.
- **Trade-off:** Meskipun Fiber membutuhkan lebih banyak baris kode, performa eksekusinya jauh lebih tinggi karena dikompilasi menjadi binary native dan tidak memerlukan overhead interpreter PHP.

---

## C. Pertanyaan Pembahasan

**1. Mengapa field `Aktif` pada DTO menggunakan pointer (`*bool`) sedangkan field lain tidak?**

Karena nilai `bool` memiliki zero value `false`, sehingga tidak bisa dibedakan antara:
- Pengguna **tidak mengirim** field `aktif` (tidak ingin mengubah nilai)
- Pengguna mengirim `aktif: false` (ingin menonaktifkan mahasiswa)

Dengan tipe `*bool` (pointer), jika field tidak dikirim nilainya `nil`, sehingga handler dapat melakukan pengecekan `if permintaan.Aktif != nil` sebelum memperbarui field tersebut. Field lain seperti `Nama` (string) tidak perlu pointer karena string kosong `""` sudah cukup menjadi tanda "tidak dikirim".

**2. Jelaskan perbedaan `Save`, `Update`, dan `Updates` pada GORM.**

| Method | Perilaku |
|:-------|:---------|
| `DB.Save(&obj)` | Menyimpan **seluruh field** objek ke database (upsert — INSERT jika baru, UPDATE jika sudah ada primary key). Hati-hati: bisa menimpa nilai field yang tidak ingin diubah jika objek tidak diambil dari DB terlebih dahulu. |
| `DB.Model(&obj).Update("field", nilai)` | Memperbarui **satu kolom** saja berdasarkan nama field. |
| `DB.Model(&obj).Updates(map/struct)` | Memperbarui **beberapa kolom** sekaligus. Jika menggunakan struct, field dengan zero value (`0`, `""`, `false`) akan **diabaikan**. Gunakan `map` agar zero value tetap disimpan. |

Pada project ini digunakan `Save` di dalam `Perbarui()`. Karena objek `Mahasiswa` terlebih dahulu diambil dari DB (`AmbilPerId`) lalu field-nya dimodifikasi satu per satu di handler, `Save` aman digunakan karena keseluruhan objek sudah sesuai sebelum disimpan.

**3. Apa risiko penggunaan `AutoMigrate` pada lingkungan produksi?**

- **Tidak dapat menghapus kolom atau tabel** — Jika sebuah field dihapus dari struct, `AutoMigrate` tidak akan menghapus kolom yang sudah ada di database, sehingga bisa menimbulkan inkonsistensi skema antara kode dan database.
- **Tidak ada riwayat perubahan** — Berbeda dengan migration file (Laravel/Flyway/golang-migrate), `AutoMigrate` tidak mencatat kapan dan apa yang diubah, sehingga sulit untuk di-rollback jika terjadi kesalahan.
- **Potensi downtime** — Pada tabel besar, penambahan kolom atau indeks bisa mengunci tabel (_table lock_) dan menyebabkan gangguan layanan (downtime) pada produksi.
- **Rekomendasi:** Gunakan `AutoMigrate` hanya di lingkungan `local`/`development`. Untuk produksi, gunakan alat migrasi terpisah seperti `golang-migrate` agar setiap perubahan skema terdokumentasi dan dapat di-rollback dengan aman.

**4. Bandingkan mekanisme eager loading pada GORM dengan `with()` pada Eloquent.**

| Aspek | GORM (`Preload`) | Eloquent (`with`) |
|:------|:-----------------|:-----------------|
| **Sintaks** | `DB.Preload("ProgramStudi").Find(&list)` | `Mahasiswa::with('programStudi')->get()` |
| **Mekanisme** | Menjalankan 2 query terpisah: 1 untuk data utama, 1 untuk relasi, kemudian digabungkan di memori | Sama — menjalankan 2 query terpisah lalu digabungkan di PHP |
| **Tanpa eager loading** | Field relasi bernilai `nil` (pointer kosong) | Field relasi bernilai `null` / tidak di-load (lazy load bisa terpicu) |
| **Lazy loading** | Tidak didukung secara default, harus selalu eksplisit dengan `Preload` | Didukung otomatis, namun rawan masalah N+1 query |
| **Kondisi pada relasi** | `Preload("MK", "semester = ?", 1)` | `with(['mk' => fn($q) => $q->where('semester', 1)])` |

Keduanya menyelesaikan masalah **N+1 query** dengan cara yang serupa. Perbedaan utama: Eloquent mendukung lazy loading secara default (lebih mudah tapi berbahaya karena N+1 bisa muncul tanpa disadari), sedangkan GORM mengharuskan developer untuk selalu eksplisit menggunakan `Preload` — pendekatan yang lebih aman dan transparan.
