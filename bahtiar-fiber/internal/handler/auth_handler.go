package handler

import (
	"time"

	"github.com/gofiber/fiber/v3"
	"golang.org/x/crypto/bcrypt"

	"bahtiar-fiber/internal/auth"
	"bahtiar-fiber/internal/config"
	"bahtiar-fiber/internal/model"
	"bahtiar-fiber/internal/validasi"
)

func Registrasi(c fiber.Ctx) error {
	var permintaan PermintaanRegistrasi

	err := c.Bind().Body(&permintaan)
	if err != nil {
		return fiber.NewError(fiber.StatusBadRequest, "Format body tidak valid")
	}

	galat := validasi.Periksa(permintaan)
	if galat != nil {
		return c.Status(fiber.StatusUnprocessableEntity).JSON(fiber.Map{
			"sukses": false,
			"pesan":  "Data yang dikirim tidak valid",
			"galat":  galat,
		})
	}

	hash, err := bcrypt.GenerateFromPassword([]byte(permintaan.KataSandi), bcrypt.DefaultCost)
	if err != nil {
		return fiber.NewError(fiber.StatusInternalServerError, "Gagal memproses kata sandi")
	}

	pengguna := model.Pengguna{
		Nama:      permintaan.Nama,
		Email:     permintaan.Email,
		KataSandi: string(hash),
		Peran:     "mahasiswa",
	}

	err = config.DB.Create(&pengguna).Error
	if err != nil {
		return fiber.NewError(fiber.StatusUnprocessableEntity, "Email sudah terdaftar")
	}

	return c.Status(fiber.StatusCreated).JSON(fiber.Map{
		"sukses": true,
		"pesan":  "Registrasi berhasil",
		"data":   pengguna,
	})
}

func Login(c fiber.Ctx) error {
	var permintaan PermintaanLogin

	err := c.Bind().Body(&permintaan)
	if err != nil {
		return fiber.NewError(fiber.StatusBadRequest, "Format body tidak valid")
	}

	galat := validasi.Periksa(permintaan)
	if galat != nil {
		return c.Status(fiber.StatusUnprocessableEntity).JSON(fiber.Map{
			"sukses": false,
			"pesan":  "Data yang dikirim tidak valid",
			"galat":  galat,
		})
	}

	var pengguna model.Pengguna

	err = config.DB.Where("email = ?", permintaan.Email).First(&pengguna).Error
	if err != nil {
		return fiber.NewError(fiber.StatusUnauthorized, "Email atau kata sandi tidak sesuai")
	}

	err = bcrypt.CompareHashAndPassword([]byte(pengguna.KataSandi), []byte(permintaan.KataSandi))
	if err != nil {
		return fiber.NewError(fiber.StatusUnauthorized, "Email atau kata sandi tidak sesuai")
	}

	konf := config.Muat()

	token, err := auth.TerbitkanToken(pengguna.ID, pengguna.Peran, konf.JwtSecret, konf.JwtTtlMenit)
	if err != nil {
		return fiber.NewError(fiber.StatusInternalServerError, "Gagal menerbitkan token")
	}

	return c.JSON(fiber.Map{
		"sukses": true,
		"pesan":  "Login berhasil",
		"data": fiber.Map{
			"pengguna": pengguna,
			"token":    token,
			"tipe":     "Bearer",
		},
	})
}

func Profil(c fiber.Ctx) error {
	idPengguna := c.Locals("id_pengguna")

	var pengguna model.Pengguna

	err := config.DB.First(&pengguna, idPengguna).Error
	if err != nil {
		return fiber.NewError(fiber.StatusNotFound, "Pengguna tidak ditemukan")
	}

	return c.JSON(fiber.Map{
		"sukses": true,
		"data":   pengguna,
	})
}

func Segarkan(c fiber.Ctx) error {
	idPengguna, ok := c.Locals("id_pengguna").(uint)
	if !ok {
		return fiber.NewError(fiber.StatusUnauthorized, "Sesi tidak valid")
	}

	peran, _ := c.Locals("peran").(string)
	tokenLama, _ := c.Locals("nilai_token").(string)

	konf := config.Muat()

	klaim, _ := auth.PeriksaToken(tokenLama, konf.JwtSecret)
	if klaim != nil && tokenLama != "" {
		hitam := model.DaftarHitamToken{
			NilaiToken:     tokenLama,
			KedaluarsaPada: klaim.ExpiresAt.Time,
		}
		config.DB.Create(&hitam)
	}

	tokenBaru, err := auth.TerbitkanToken(idPengguna, peran, konf.JwtSecret, konf.JwtTtlMenit)
	if err != nil {
		return fiber.NewError(fiber.StatusInternalServerError, "Gagal menerbitkan token baru")
	}

	return c.JSON(fiber.Map{
		"sukses": true,
		"pesan":  "Token berhasil diperbarui",
		"data": fiber.Map{
			"token":           tokenBaru,
			"tipe":            "Bearer",
			"kedaluarsa_menit": konf.JwtTtlMenit,
		},
	})
}

func Logout(c fiber.Ctx) error {
	nilaiToken, _ := c.Locals("nilai_token").(string)

	if nilaiToken == "" {
		return fiber.NewError(fiber.StatusBadRequest, "Token tidak ditemukan dalam sesi")
	}

	konf := config.Muat()

	klaim, err := auth.PeriksaToken(nilaiToken, konf.JwtSecret)
	if err != nil {
		return fiber.NewError(fiber.StatusUnauthorized, "Token tidak valid")
	}

	hitam := model.DaftarHitamToken{
		NilaiToken:     nilaiToken,
		KedaluarsaPada: klaim.ExpiresAt.Time,
	}

	if err := config.DB.Create(&hitam).Error; err != nil {
		return fiber.NewError(fiber.StatusInternalServerError, "Gagal mencabut token")
	}

	
	config.DB.Where("kedaluarsa_pada < ?", time.Now()).Delete(&model.DaftarHitamToken{})

	return c.JSON(fiber.Map{
		"sukses": true,
		"pesan":  "Logout berhasil, token telah dicabut",
	})
}
