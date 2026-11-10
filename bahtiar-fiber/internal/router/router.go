package router

import (
	"github.com/gofiber/fiber/v3"

	"bahtiar-fiber/internal/handler"
	"bahtiar-fiber/internal/middleware"
)

func Daftarkan(app *fiber.App) {
	api := app.Group("/api")

	api.Get("/status", func(c fiber.Ctx) error {
		return c.JSON(fiber.Map{
			"sukses": true,
			"pesan":  "API Fiber aktif",
		})
	})

	mahasiswa := api.Group("/mahasiswa", middleware.KunciApi())

	mahasiswa.Get("/", handler.DaftarMahasiswa)
	mahasiswa.Get("/nim/:nim", handler.CariMahasiswaByNim)
	mahasiswa.Get("/:id", handler.DetailMahasiswa)
}
