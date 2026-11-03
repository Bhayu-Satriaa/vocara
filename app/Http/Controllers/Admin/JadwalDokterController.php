<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JadwalDokterController extends Controller
{
    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    /** Daftar dokter, pilih satu untuk mengatur jadwalnya. */
    public function index(): View
    {
        $dokter = Doctor::query()
            ->with(['user', 'poli'])
            ->withCount(['schedules as jadwal_aktif_count' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('id')
            ->paginate(15);

        return view('admin.jadwal-dokter.index', ['dokter' => $dokter]);
    }

    /** Jadwal satu dokter + form tambah. */
    public function show(Doctor $dokter): View
    {
        $dokter->load(['user', 'poli']);

        $jadwal = $dokter->schedules()->get()->sortBy(
            fn ($j) => array_search($j->hari, self::HARI, true)
        )->values();

        $terpakai = $jadwal->pluck('hari')->all();
        $hariTersedia = array_values(array_diff(self::HARI, $terpakai));

        return view('admin.jadwal-dokter.show', [
            'dokter' => $dokter,
            'jadwal' => $jadwal,
            'hariTersedia' => $hariTersedia,
        ]);
    }

    /** Tambah satu hari praktik ke dokter. Satu dokter satu baris per hari. */
    public function store(Request $request, Doctor $dokter): RedirectResponse
    {
        $data = $this->validasi($request, $dokter);

        $dokter->schedules()->create($data);

        return redirect()->route('admin.jadwal-dokter.show', $dokter)
            ->with('sukses', "Jadwal hari {$data['hari']} berhasil ditambahkan.");
    }

    public function update(Request $request, Doctor $dokter, DoctorSchedule $jadwal): RedirectResponse
    {
        abort_unless($jadwal->doctor_id === $dokter->id, 404);

        $data = $this->validasi($request, $dokter, $jadwal);

        $jadwal->update($data);

        return redirect()->route('admin.jadwal-dokter.show', $dokter)
            ->with('sukses', "Jadwal hari {$data['hari']} diperbarui.");
    }

    public function destroy(Doctor $dokter, DoctorSchedule $jadwal): RedirectResponse
    {
        abort_unless($jadwal->doctor_id === $dokter->id, 404);

        $hari = $jadwal->hari;
        $jadwal->delete();

        return redirect()->route('admin.jadwal-dokter.show', $dokter)
            ->with('sukses', "Jadwal hari {$hari} dihapus.");
    }

    private function validasi(Request $request, Doctor $dokter, ?DoctorSchedule $jadwal = null): array
    {
        $data = $request->validate([
            'hari' => [
                'required',
                Rule::in(self::HARI),
                Rule::unique('doctor_schedules', 'hari')
                    ->where('doctor_id', $dokter->id)
                    ->ignore($jadwal?->id),
            ],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'durasi_slot' => ['required', 'integer', 'min:5', 'max:480'],
            'kuota' => ['required', 'integer', 'min:1', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
