<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'number' => $this->faker->unique()->phoneNumber(),
            'password' => Hash::make('password'),
            'profile_image' => $this->faker->optional()->imageUrl(200, 200, 'people'),
            'fcm_token' => $this->faker->optional()->uuid(),
        ];
    }
    public function withRole(string $roleName): static
    {
        return $this->afterCreating(function (User $user) use ($roleName) {
            $role = \App\Models\Role::firstOrCreate(['name' => $roleName]);
            $user->roles()->attach($role->id);
        });
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => []);
    }

}
