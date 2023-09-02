<?php

namespace Database\Seeders;

use App\Statuses\UserType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory()->create([
            "name" => "Super Admin",
            "email" => "superadmin@gmail.com",
            "password" => bcrypt('0123456789'),
            "type" => UserType::SUPER_ADMIN,
        ]);

        \App\Models\User::factory()->create([
            "name" => "Reception",
            "email" => "reception@reception.com",
            "password" => bcrypt('0123456789'),
            "type" => UserType::RECEPTION,
        ]);

        \App\Models\User::factory()->create([
            "name" => "Admin",
            "email" => "admin@admin.com",
            "password" => bcrypt('0123456789'),

            "type" => UserType::ADMIN,
        ]);
    }
}
