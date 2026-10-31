<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya pintu pembuatan janji temu. Dipakai form web, chatbot, dan (nanti)
 * jalur suara — supaya aturan slot hanya hidup di satu tempat.
 *
 * Anti double-booking DITEGAKKAN DATABASE lewat `uq_slot_dokter`
 * (doctor_id, tgl_kunjungan, jam, slot_aktif); kelas ini hanya menerjemahkan
 * pelanggarannya menjadi pesan yang ramah.
 *
 * Rancangan: docs/SPEC-BOOKING-CHATBOT.md
 */
class BookingService
{
    /** Dokter yang punya jadwal aktif pada tanggal tersebut. */
    public function dokterPraktikPada(string $tanggal): Collection
    {
        $hari = $this->namaHari($tanggal);

        return Doctor::query()
            ->whereHas('schedules', fn ($q) => $q->where('hari', $hari)->where('is_active', true))
            ->with(['user', 'poli'])
            ->get();
    }

    /** Jadwal dokter pada hari dari tanggal tersebut (null kalau tidak praktik). */
    public function jadwalPada(int $dokterId, string $tanggal): ?DoctorSchedule
    {
        return DoctorSchedule::query()
            ->where('doctor_id', $dokterId)
            ->where('hari', $this->namaHari($tanggal))
            ->where('is_active', true)
            ->first();
    }

    /** Pilihan jam yang masih kosong pada tanggal itu, urut dari paling pagi. */
    public function slotTersedia(Doctor $dokter, string $tanggal): array
    {
        $jadwal = $this->jadwalPada($dokter->id, $tanggal);

        if (! $jadwal) {
            return [];
        }

        $terisi = Visit::query()
            ->aktif()
            ->where('doctor_id', $dokter->id)
            ->whereDate('tgl_kunjungan', $tanggal)
            ->pluck('jam')
            ->map(fn ($jam) => substr((string) $jam, 0, 5))
            ->all();

        $slot = [];
        $batas = Carbon::parse($tanggal.' '.$jadwal->jam_selesai);

        for ($jam = Carbon::parse($tanggal.' '.$jadwal->jam_mulai);
            $jam->copy()->addMinutes($jadwal->durasi_slot)->lte($batas);
            $jam->addMinutes($jadwal->durasi_slot)) {
            $teks = $jam->format('H:i');

            if (! in_array($teks, $terisi, true)) {
                $slot[] = $teks;
            }
        }

        return $slot;
    }

    /** Buat janji temu baru. `$sumber`: online (web) atau chatbot. */
    public function buat(
        Patient $pasien,
        Doctor $dokter,
        string $tanggal,
        string $jam,
        ?string $keluhan = null,
        string $sumber = 'online',
    ): Visit {
        if (Carbon::parse($tanggal)->startOfDay()->lt(Carbon::today())) {
            throw ValidationException::withMessages(['jam' => 'Tanggal itu sudah lewat.']);
        }

        $jadwal = $this->jadwalPada($dokter->id, $tanggal);

        if (! $jadwal) {
            throw ValidationException::withMessages(['jam' => 'Dokter tidak praktik pada tanggal itu.']);
        }

        if (! in_array($jam, $this->slotTersedia($dokter, $tanggal), true)) {
            throw ValidationException::withMessages(['jam' => 'Jam itu tidak tersedia lagi. Pilih jam lain.']);
        }

        try {
            return Visit::query()->create([
                'patient_id' => $pasien->id,
                'doctor_id' => $dokter->id,
                'poli_id' => $dokter->poli_id,
                'schedule_id' => $jadwal->id,
                'tgl_kunjungan' => $tanggal,
                'jam' => $jam,
                'status' => 'booking',
                'sumber' => $sumber,
                'keluhan_awal' => $keluhan,
            ]);
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                throw ValidationException::withMessages(['jam' => 'Jam itu baru saja diambil orang lain. Pilih jam lain.']);
            }

            throw $e;
        }
    }

    /** Batalkan janji: slotnya otomatis bebas lagi (slot_aktif jadi NULL). */
    public function batal(Visit $visit): void
    {
        $visit->update(['status' => 'batal']);
    }

    /** Janji yang masih berjalan milik satu data diri. */
    public function janjiMilik(Patient $pasien): Collection
    {
        return Visit::query()
            ->where('patient_id', $pasien->id)
            ->whereIn('status', ['booking', 'menunggu'])
            ->with(['doctor.user', 'poli'])
            ->orderBy('tgl_kunjungan')
            ->orderBy('jam')
            ->get();
    }

    /**
     * Nama hari dalam bahasa Indonesia. Selalu dihitung backend — satu model di
     * prototipe lama salah menyebut hari kalau disuruh menghitung sendiri.
     */
    public function namaHari(string $tanggal): string
    {
        return Carbon::parse($tanggal)->locale('id')->translatedFormat('l');
    }
}
