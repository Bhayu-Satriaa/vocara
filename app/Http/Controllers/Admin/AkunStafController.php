<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AkunStafController extends Controller
{
    /** Daftar akun staf (pasien tidak muncul di sini). */
    public function index(Request $request): View
    {
        $role = $request->query('role', 'semua');
        $jumlah = User::query()->whereIn('role', User::ROLE_STAF)->count();

        $akun = User::query()
            ->whereIn('role', User::ROLE_STAF)
            ->when(in_array($role, User::ROLE_STAF, true), fn ($query) => $query->where('role', $role))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.akun-staf.index', [
            'akun' => $akun,
            'roleFilter' => in_array($role, User::ROLE_STAF, true) ? $role : 'semua',
            'jumlah' => $jumlah,
        ]);
    }

    public function create(): View
    {
        return view('admin.akun-staf.create', [
            'user' => new User(['role' => 'petugas', 'is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        User::create($data);

        return redirect()->route('admin.akun-staf.index')
            ->with('sukses', "Akun {$data['name']} berhasil dibuat.");
    }

    public function edit(User $user): View
    {
        abort_unless($user->staf(), 404);

        return view('admin.akun-staf.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->staf(), 404);

        $user->update($this->validasi($request, $user));

        return redirect()->route('admin.akun-staf.index')
            ->with('sukses', "Akun {$user->name} diperbarui.");
    }

    /** Saat edit, password boleh dikosongkan dan password lama tetap dipakai. */
    private function validasi(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(User::ROLE_STAF)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }
}
