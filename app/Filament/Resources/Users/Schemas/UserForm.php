<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    /**
     * Form akun staf. Akun pasien tidak dibuat dari sini (pasien daftar lewat Google).
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(100),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(150)
                    ->unique(ignoreRecord: true),

                Select::make('role')
                    ->label('Role')
                    ->options(array_combine(User::ROLE_STAF, User::ROLE_STAF))
                    ->default('petugas')
                    ->required(),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->confirmed()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    // Saat edit boleh dikosongkan: password lama tetap dipakai.
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Minimal 8 karakter. Kosongkan kalau tidak ingin mengganti password.'),

                TextInput::make('password_confirmation')
                    ->label('Ulangi password')
                    ->password()
                    ->revealable()
                    ->dehydrated(false),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Akun nonaktif tidak bisa masuk, baik ke panel maupun halaman staf.'),
            ]);
    }
}
