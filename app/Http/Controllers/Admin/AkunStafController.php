<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Kelola akun staf (admin, petugas, dokter, apoteker) dari dashboard admin.
 *
 * Sebelumnya fitur ini memakai tampilan default panel Filament; sekarang
 * bentuknya Blade + Tailwind seperti halaman admin lain. Akun pasien tidak
 * pernah muncul di sini karena pasien mendaftar sendiri lewat Google.
 */
class AkunStafController extends Controller
{
    public function index(Request $request): View
    {
        $role = (string) $request->query('role', 'semua');

        $akun = User::query()
            ->whereIn('role', User::ROLE_STAF)
            ->when(in_array($role, User::ROLE_STAF, true), fn ($query) => $query->where('role', $role))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.akun-staf', [
            'akun' => $akun,
            'roleFilter' => $role,
        ]);
    }

    public function create(): View
    {
        return view('admin.akun-staf-form', ['staf' => new User(['role' => 'petugas'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in(User::ROLE_STAF)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        // Password di-hash otomatis oleh cast 'hashed' di model User.
        $staf = User::create($data);

        ActivityLog::catat('tambah_akun', "Buat akun {$staf->role}: {$staf->email}", $staf);

        return redirect()->route('admin.akun-staf.index')->with('sukses', "Akun {$staf->email} dibuat.");
    }

    public function edit(User $staf): View
    {
        abort_unless($staf->staf(), 404);

        return view('admin.akun-staf-form', ['staf' => $staf]);
    }

    public function update(Request $request, User $staf): RedirectResponse
    {
        abort_unless($staf->staf(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($staf->id)],
            'role' => ['required', Rule::in(User::ROLE_STAF)],
            // Dikosongkan = password lama tetap dipakai.
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $staf->update($data);

        ActivityLog::catat('ubah_akun', "Ubah akun {$staf->role}: {$staf->email}", $staf);

        return redirect()->route('admin.akun-staf.index')->with('sukses', "Akun {$staf->email} diperbarui.");
    }

    /** Akun nonaktif tidak bisa masuk, baik ke dashboard maupun panel admin. */
    public function toggleAktif(User $staf): RedirectResponse
    {
        abort_unless($staf->staf(), 404);

        $staf->update(['is_active' => ! $staf->is_active]);

        $aksi = $staf->is_active ? 'Aktifkan' : 'Nonaktifkan';
        ActivityLog::catat('ubah_akun', "{$aksi} akun {$staf->role}: {$staf->email}", $staf);

        return back()->with('sukses', "Akun {$staf->email} ".($staf->is_active ? 'diaktifkan' : 'dinonaktifkan').'.');
    }
}
