<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanProfilPasienRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user?->punyaRole('pasien')) {
            return false;
        }

        // Data diri yang tersangkut di route harus punya akun yang sedang login.
        $terikat = $this->route('pasien');

        return ! $terikat instanceof Patient || $terikat->user_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'max:100'],
            // Nomor WA wajib: diverifikasi petugas, bukan OTP.
            'no_wa' => ['required', 'string', 'min:9', 'max:20', 'regex:/^[0-9+ -]+$/'],
            // Multi-profile: tiap data diri harus jelas pemiliknya (diri sendiri / keluarga).
            'hubungan_keluarga' => ['required', Rule::in(Patient::HUBUNGAN)],
            'is_utama' => ['nullable', 'boolean'],
            'tgl_lahir' => ['required', 'date', 'before:today'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'alamat' => ['nullable', 'string', 'max:500'],
            'golongan_darah' => ['nullable', Rule::in(['A', 'B', 'AB', 'O', '-'])],
        ];
    }

    public function messages(): array
    {
        return [
            'no_wa.required' => 'Nomor WA wajib diisi — dipakai untuk konfirmasi jadwal.',
            'no_wa.regex' => 'Nomor WA hanya boleh angka, spasi, +, dan -',
            'hubungan_keluarga.required' => 'Tentukan dulu data diri ini milik siapa.',
        ];
    }
}
