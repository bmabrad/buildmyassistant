<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
        $this->call(PromptSegmentSeeder::class);
        $this->call(TestDataSeeder::class);
        $this->call(RandomUsersSeeder::class);
        $this->call(BlogPostSeeder::class);
    }
}
