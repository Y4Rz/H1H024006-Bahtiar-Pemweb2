package model

import "time"


type DaftarHitamToken struct {
	ID         uint      `gorm:"primaryKey" json:"id"`
	NilaiToken string    `gorm:"size:512;uniqueIndex" json:"nilai_token"`
	KedaluarsaPada time.Time `json:"kedaluarsa_pada"`
	CreatedAt  time.Time `json:"dibuat_pada"`
}
