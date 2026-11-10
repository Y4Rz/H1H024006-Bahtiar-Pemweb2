package middleware

import (
	"os"

	"github.com/gofiber/fiber/v3"
)

func KunciApi() fiber.Handler {
	return func(c fiber.Ctx) error {
		kunci := c.Get("X-Api-Key")

		if kunci == "" || kunci != os.Getenv("API_KEY") {
			return fiber.NewError(fiber.StatusUnauthorized, "API key tidak valid")
		}

		return c.Next()
	}
}
