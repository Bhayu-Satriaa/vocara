<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanProfilPasienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->punyaRole('pasien');
    }

    public function rules(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'max:100'],
            // Nomor WA wajib: diverifikasi petugas, bukan OTP.
            'no_wa' => ['required', 'string', 'min:9', 'max:20', 'regex:/^[0-9+ -]+$/'],
            'nik' => ['required', 'digits:16', Rule::unique('patients', 'nik')->ignore($this->pasienId())],
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
            'nik.digits' => 'NIK harus 16 angka.',
            'nik.unique' => 'NIK ini sudah terdaftar di sistem. Hubungi petugas.',
        ];
    }

    /** Biodata pasien milik akun yang sedang login (kalau sudah ada). */
    private function pasienId(): ?int
    {
        return $this->user()?->patients()->value('id');
    }
}
