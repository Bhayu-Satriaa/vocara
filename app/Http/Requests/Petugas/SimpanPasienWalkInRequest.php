<?php

namespace App\Http\Requests\Petugas;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanPasienWalkInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->punyaRole('petugas', 'admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'tgl_lahir' => ['required', 'date', 'before_or_equal:today'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'no_wa' => ['required', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'golongan_darah' => ['nullable', Rule::in(['A', 'B', 'AB', 'O', '-'])],
            'alergi' => ['nullable', 'string', 'max:500'],
            'verifikasi_langsung' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap pasien wajib diisi.',
            'nama_lengkap.max' => 'Nama lengkap maksimal 100 karakter.',
            'tgl_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tgl_lahir.date' => 'Format tanggal lahir tidak valid.',
            'tgl_lahir.before_or_equal' => 'Tanggal lahir tidak boleh lebih dari hari ini.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki (L) atau Perempuan (P).',
            'no_wa.required' => 'Nomor WhatsApp / HP wajib diisi.',
            'no_wa.max' => 'Nomor WhatsApp maksimal 20 digit.',
            'alamat.max' => 'Alamat maksimal 500 karakter.',
            'golongan_darah.in' => 'Golongan darah tidak valid.',
            'alergi.max' => 'Catatan riwayat alergi maksimal 500 karakter.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Cegah duplikasi data pasien dengan nama, tanggal lahir, dan no_wa yang sama
            $adaDuplikat = Patient::query()
                ->where('nama_lengkap', trim($this->input('nama_lengkap')))
                ->whereDate('tgl_lahir', $this->input('tgl_lahir'))
                ->where('no_wa', trim($this->input('no_wa')))
                ->exists();

            if ($adaDuplikat) {
                $validator->errors()->add(
                    'nama_lengkap',
                    'Pasien dengan nama, tanggal lahir, dan nomor WA tersebut sudah terdaftar di sistem.'
                );
            }
        });
    }
}

