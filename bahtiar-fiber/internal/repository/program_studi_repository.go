package repository

import (
	"bahtiar-fiber/internal/config"
	"bahtiar-fiber/internal/model"
)

type ProgramStudiDenganJumlah struct {
	ID              uint   `json:"id"`
	Kode            string `json:"kode"`
	Nama            string `json:"nama"`
	Jenjang         string `json:"jenjang"`
	JumlahMahasiswa int64  `json:"jumlah_mahasiswa"`
}

func AmbilProgramStudiDenganJumlah() ([]ProgramStudiDenganJumlah, error) {
	var hasil []ProgramStudiDenganJumlah

	err := config.DB.Model(&model.ProgramStudi{}).
		Select("program_studi.id, program_studi.kode, program_studi.nama, program_studi.jenjang, (SELECT COUNT(*) FROM mahasiswa WHERE mahasiswa.program_studi_id = program_studi.id AND mahasiswa.deleted_at IS NULL) AS jumlah_mahasiswa").
		Order("program_studi.nama ASC").
		Scan(&hasil).Error

	return hasil, err
}
