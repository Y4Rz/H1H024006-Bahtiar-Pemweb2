package repository

import (
	"bahtiar-fiber/internal/config"
	"bahtiar-fiber/internal/model"
)

type FilterMatakuliah struct {
	Cari       string
	Semester   int
	Halaman    int
	PerHalaman int
}

func AmbilMatakuliah(filter FilterMatakuliah) ([]model.Matakuliah, int64, error) {
	var daftar []model.Matakuliah
	var total int64

	kueri := config.DB.Model(&model.Matakuliah{})

	if filter.Cari != "" {
		pola := "%" + filter.Cari + "%"
		kueri = kueri.Where("nama LIKE ? OR kode LIKE ?", pola, pola)
	}

	if filter.Semester > 0 {
		kueri = kueri.Where("semester = ?", filter.Semester)
	}

	if err := kueri.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	lompat := (filter.Halaman - 1) * filter.PerHalaman

	err := kueri.Preload("ProgramStudi").
		Order("nama ASC").
		Limit(filter.PerHalaman).
		Offset(lompat).
		Find(&daftar).Error

	if err != nil {
		return nil, 0, err
	}

	return daftar, total, nil
}

func AmbilMatakuliahPerId(id uint) (model.Matakuliah, error) {
	var matakuliah model.Matakuliah

	err := config.DB.Preload("ProgramStudi").First(&matakuliah, id).Error

	return matakuliah, err
}

func SimpanMatakuliah(matakuliah *model.Matakuliah) error {
	return config.DB.Create(matakuliah).Error
}

func PerbaruiMatakuliah(matakuliah *model.Matakuliah) error {
	return config.DB.Omit("ProgramStudi").Save(matakuliah).Error
}

func HapusMatakuliah(matakuliah *model.Matakuliah) error {
	return config.DB.Delete(matakuliah).Error
}
