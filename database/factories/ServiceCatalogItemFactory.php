<?php

namespace Database\Factories;

use App\Models\ServiceCatalogItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ServiceCatalogItem>
 */
class ServiceCatalogItemFactory extends Factory
{
    protected $model = ServiceCatalogItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Заміна масла',
                'Діагностика',
                'Планове ТО',
                'Заміна колодок',
            ]),
            'default_price' => fake()->optional()->randomFloat(2, 400, 5000),
        ];
    }
}
