<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;

class ItemsSeeder extends Seeder
{
    public function run(): void
    {
        Item::factory()->count(50)->create();
    }
}
