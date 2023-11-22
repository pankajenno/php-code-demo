<?php

// Faker Factory ----------------------------
public function definition()
{
    return [
        'name' => fake()->name(),
        'status' => rand(0,1),
        'category' => rand(0,2),
        'slug' => fake()->name()
    ];
}


// User Faker ---------------------------------------------
/**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }
