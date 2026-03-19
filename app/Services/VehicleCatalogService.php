<?php

namespace App\Services;

use App\Models\Client;

class VehicleCatalogService
{
    /**
     * @return list<string>
     */
    public function brands(): array
    {
        return collect(array_keys(config('vehicle-catalog.brands', [])))
            ->merge(
                Client::query()
                    ->whereNotNull('car_brand')
                    ->where('car_brand', '!=', '')
                    ->distinct()
                    ->pluck('car_brand'),
            )
            ->map(fn (mixed $brand): string => trim((string) $brand))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function modelsForBrand(string $brand): array
    {
        if ($brand === '') {
            return [];
        }

        return collect(config("vehicle-catalog.brands.{$brand}", []))
            ->merge(
                Client::query()
                    ->where('car_brand', $brand)
                    ->whereNotNull('car_model')
                    ->where('car_model', '!=', '')
                    ->distinct()
                    ->pluck('car_model'),
            )
            ->map(fn (mixed $model): string => trim((string) $model))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
