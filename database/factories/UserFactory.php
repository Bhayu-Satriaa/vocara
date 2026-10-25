<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /** Password awal semua akun buatan factory (cukup untuk test). */
    public const PASSWORD = 'password';

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            // Model User sudah punya cast 'hashed', jadi cukup teks biasa.
            'password' => self::PASSWORD,
            'role' => 'petugas',
            'is_active' => true,
        ];
    }

    public function role(string $role): static
    {
        return $this->state(fn () => ['role' => $role]);
    }

    public function pasien(): static
    {
        return $this->state(fn () => ['role' => 'pasien', 'password' => null]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
