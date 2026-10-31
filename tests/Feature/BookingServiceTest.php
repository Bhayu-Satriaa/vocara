<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Poli;
use App\Models\User;
use App\Models\Visit;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * BookingService = satu-satunya pintu pembuatan janji temu (dipakai chatbot & form web).
 * Rancangan: docs/SPEC-BOOKING-CHATBOT.md
 */
class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->booking = app(BookingService::class);
    }

    /** Dokter dengan jadwal praktik pada hari tertentu. */
    private function dokter(string $hari = 'Senin', string $mulai = '08:00', string $selesai = '10:00'): Doctor
    {
        $poli = Poli::query()->firstOrCreate(['nama' => 'Poli Umum'], ['deskripsi' => 'Pemeriksaan umum']);
        $user = User::factory()->role('dokter')->create();

        $dokter = Doctor::query()->create([
            'user_id' => $user->id,
            'poli_id' => $poli->id,
            'no_str' => 'STR-UJI',
            'spesialisasi' => 'Dokter Umum',
            'tarif_konsultasi' => 25000,
        ]);

        DoctorSchedule::query()->create([
            'doctor_id' => $dokter->id,
            'hari' => $hari,
            'jam_mulai' => $mulai,
            'jam_selesai' => $selesai,
            'durasi_slot' => 30,
            'kuota' => 10,
            'is_active' => true,
        ]);

        return $dokter;
    }

    private function pasien(string $email = 'pasien@klinik.test'): Patient
    {
        $user = User::factory()->pasien()->create(['email' => $email]);

        return Patient::query()->create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Budi Santoso',
            'no_wa' => '081234567890',
            'tgl_lahir' => '1995-05-20',
            'jenis_kelamin' => 'L',
        ]);
    }

    /** Tanggal Senin berikutnya supaya cocok dengan jadwal uji. */
    private function seninDepan(): string
    {
        return Carbon::parse('next monday')->toDateString();
    }

    public function test_nama_hari_dihitung_backend(): void
    {
        $this->assertSame('Senin', $this->booking->namaHari($this->seninDepan()));
    }

    public function test_janji_tersimpan_dengan_sumber_chatbot(): void
    {
        $dokter = $this->dokter();
        $pasien = $this->pasien();
        $tanggal = $this->seninDepan();

        $visit = $this->booking->buat($pasien, $dokter, $tanggal, '08:30', 'demam tiga hari', 'chatbot');

        $this->assertSame('booking', $visit->status);
        $this->assertSame('chatbot', $visit->sumber);
        $this->assertSame($pasien->id, $visit->patient_id);
        $this->assertSame($dokter->poli_id, $visit->poli_id);
        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'jam' => '08:30']);
    }

    public function test_slot_sama_pada_dokter_yang_sama_ditolak_database(): void
    {
        $dokter = $this->dokter();
        $tanggal = $this->seninDepan();

        $this->booking->buat($this->pasien('a@klinik.test'), $dokter, $tanggal, '08:00');

        try {
            $this->booking->buat($this->pasien('b@klinik.test'), $dokter, $tanggal, '08:00');
            $this->fail('janji kedua seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('jam', $e->errors());
        }

        $this->assertSame(1, Visit::query()->where('doctor_id', $dokter->id)->count());
    }

    public function test_slot_bisa_dipesan_lagi_setelah_dibatalkan(): void
    {
        $dokter = $this->dokter();
        $tanggal = $this->seninDepan();

        $pertama = $this->booking->buat($this->pasien('a@klinik.test'), $dokter, $tanggal, '09:00');
        $this->assertNotContains('09:00', $this->booking->slotTersedia($dokter, $tanggal), 'slot terisi');

        $this->booking->batal($pertama);

        $this->assertContains('09:00', $this->booking->slotTersedia($dokter, $tanggal), 'slot bebas lagi');

        $kedua = $this->booking->buat($this->pasien('b@klinik.test'), $dokter, $tanggal, '09:00');
        $this->assertSame('booking', $kedua->status);
    }

    public function test_jam_di_luar_jadwal_dan_tanggal_lampau_ditolak(): void
    {
        $dokter = $this->dokter();
        $pasien = $this->pasien();

        // 07:00 di luar jam praktik 08:00–10:00.
        try {
            $this->booking->buat($pasien, $dokter, $this->seninDepan(), '07:00');
            $this->fail('jam di luar jadwal seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('jam', $e->errors());
        }

        // Tanggal kemarin (walau harinya sama, tanggalnya sudah lewat).
        $kemarin = Carbon::yesterday()->toDateString();

        try {
            $this->booking->buat($pasien, $dokter, $kemarin, '08:00');
            $this->fail('tanggal lampau seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('jam', $e->errors());
        }

        $this->assertDatabaseCount('visits', 0);
    }

    public function test_dokter_yang_tidak_praktik_tidak_muncul(): void
    {
        $dokter = $this->dokter('Senin');
        $selasa = Carbon::parse('next tuesday')->toDateString();

        $this->assertEmpty($this->booking->slotTersedia($dokter, $selasa));
        $this->assertFalse(
            $this->booking->dokterPraktikPada($selasa)->contains('id', $dokter->id),
            'dokter tidak praktik hari Selasa',
        );

        $this->assertTrue(
            $this->booking->dokterPraktikPada($this->seninDepan())->contains('id', $dokter->id),
            'dokter muncul pada hari praktiknya',
        );
    }

    public function test_janji_milik_hanya_menampilkan_janji_aktif_pasien_itu(): void
    {
        $dokter = $this->dokter();
        $pasien = $this->pasien();
        $tanggal = $this->seninDepan();

        $aktif = $this->booking->buat($pasien, $dokter, $tanggal, '08:00');
        $dibatalkan = $this->booking->buat($this->pasien('lain@klinik.test'), $dokter, $tanggal, '08:30');
        $this->booking->batal($dibatalkan);

        $this->assertSame([$aktif->id], $this->booking->janjiMilik($pasien)->pluck('id')->all());
    }
}
