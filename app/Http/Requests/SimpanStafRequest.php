<?php

namespace App\Http\Requests;

use App\Http\Controllers\Admin\UserController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanStafRequest extends FormRequest
{
    /** Hanya admin yang boleh membuat akun staf. */
    public function authorize(): bool
    {
        return (bool) $this->user()?->punyaRole('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in(UserController::ROLE_STAF)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'role.in' => 'Role yang dipilih tidak dikenal.',
        ];
    }
}
