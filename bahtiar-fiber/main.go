package main

import (
	"log"

	"github.com/gofiber/fiber/v3"
)

func main() {
	app := fiber.New()

	// Endpoint Default
	app.Get("/", func(c fiber.Ctx) error {
		return c.SendString("Halo Pemrograman Web II")
	})

	// Endpoint Info API
	app.Get("/api/info", func(c fiber.Ctx) error {
		return c.JSON(fiber.Map{
			"aplikasi": "Latihan Fiber",
			"versi":    "1.0.0",
			"status":   "berjalan",
		})
	})

	// Tugas 1: Endpoint GET /api/mahasiswa
	app.Get("/api/mahasiswa", func(c fiber.Ctx) error {
		return c.JSON(fiber.Map{
			"nama":  "Bahtiar Rizqi Efendy",         
			"nim":   "H1H024006",       
			"prodi": "Teknik Komputer",
		})
	})

	// Jalankan server di port 3000
	log.Fatal(app.Listen(":3000"))
}