<?php 
 
namespace App\Http\Controllers; 
 
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
 
class MahasiswaController extends Controller 
{ 
   public function index() 
    { 
        DB::listen(function ($kueri) {
            logger($kueri->sql);
        });

        $daftarMahasiswa = Mahasiswa::with('programStudi')
            ->orderBy('nama')
            ->paginate(10);

        return view('mahasiswa.data', ['daftarMahasiswa' => $daftarMahasiswa]); 
    }
 
    public function show(string $nim) 
    { 
        return view('mahasiswa.show', ['nim' => $nim]); 
    } 


 public function cari(Request $request) 
    { 
        $kataKunci = $request->query('q', ''); 
 
        return response()->json([ 
            'kata_kunci' => $kataKunci, 
            'metode' => $request->method(), 
            'path' => $request->path(), 
        ]); 
    } 
}