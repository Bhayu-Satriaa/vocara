<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanStafRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    /** Role staf yang boleh dibuat dari panel admin. */
    public const ROLE_STAF = ['admin', 'petugas', 'dokter', 'apoteker'];

    /** Daftar akun staf. Akun pasien tidak dikelola di sini. */
    public function index(): View
    {
        $pengguna = User::query()
            ->whereIn('role', self::ROLE_STAF)
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.pengguna.index', ['pengguna' => $pengguna]);
    }

    public function create(): View
    {
        return view('admin.pengguna.create', ['daftarRole' => self::ROLE_STAF]);
    }

    public function store(SimpanStafRequest $request): RedirectResponse
    {
        $user = User::query()->create($request->validated());

        ActivityLog::catat('tambah_akun', "Buat akun {$user->role}: {$user->email}", $user);

        return redirect()
            ->route('admin.pengguna')
            ->with('sukses', "Akun {$user->email} ({$user->role}) berhasil dibuat.");
    }
}
