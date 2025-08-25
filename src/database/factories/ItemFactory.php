<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'title'       => $this->faker->sentence(3),
            'status'      => $this->faker->randomElement(['draft','active','archived']),
            'description' => $this->faker->optional()->paragraph(),
            'created_by'  => null,
        ];
    }
}
