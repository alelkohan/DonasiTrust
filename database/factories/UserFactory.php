<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= bcrypt('password'),
            'role' => User::ROLE_DONATUR,
            'verification_status' => User::VERIFICATION_UNVERIFIED,
            'remember_token' => Str::random(10),
        ];
    }

    public function pengaju(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_PENGAJU,
            'verification_status' => User::VERIFICATION_VERIFIED,
            'verified_at' => now(),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_ADMIN,
            'verification_status' => User::VERIFICATION_VERIFIED,
            'verified_at' => now(),
        ]);
    }
}
