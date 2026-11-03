@extends('layouts.app')

@section('judul', 'Ubah Akun Staf')
@section('crumb', 'Akun staf')

@section('isi')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink">Ubah akun staf</h1>
                <p class="mt-1 text-sm text-muted">{{ $user->name }}</p>
            </div>
            <a href="{{ route('admin.akun-staf.index') }}" class="text-sm text-muted hover:text-ink">&larr; Kembali</a>
        </div>

        <div class="rounded-[14px] border border-line bg-surface p-6 shadow-[0_1px_2px_rgba(16,24,40,.05)]">
            @include('admin.akun-staf._form')
        </div>
    </div>
@endsection
