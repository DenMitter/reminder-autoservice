<?php

namespace Database\Seeders;

use App\Models\ServiceCatalogItem;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();

        ServiceCatalogItem::query()->upsert(
            [
                ['name' => 'Комп’ютерна діагностика', 'default_price' => 700, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Діагностика ходової', 'default_price' => 450, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна масла в двигуні', 'default_price' => 500, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна масляного фільтра', 'default_price' => 250, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна повітряного фільтра', 'default_price' => 250, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна салонного фільтра', 'default_price' => 300, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Планове ТО', 'default_price' => 1800, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна передніх гальмівних колодок', 'default_price' => 900, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна задніх гальмівних колодок', 'default_price' => 950, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна гальмівних дисків', 'default_price' => 1800, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна гальмівної рідини', 'default_price' => 650, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Розвал-сходження', 'default_price' => 800, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Шиномонтаж R14-R16', 'default_price' => 900, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Шиномонтаж R17-R19', 'default_price' => 1200, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Балансування коліс', 'default_price' => 500, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна акумулятора', 'default_price' => 350, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна свічок запалювання', 'default_price' => 700, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Чистка дросельної заслінки', 'default_price' => 900, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Промивка форсунок', 'default_price' => 1400, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна ременя ГРМ', 'default_price' => 4200, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна ланцюга ГРМ', 'default_price' => 6500, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна антифризу', 'default_price' => 700, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заправка кондиціонера', 'default_price' => 1200, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Чистка кондиціонера', 'default_price' => 850, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна зчеплення', 'default_price' => 5500, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Ремонт підвіски', 'default_price' => 1500, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна амортизаторів', 'default_price' => 2200, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна кульових опор', 'default_price' => 1100, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна стійок стабілізатора', 'default_price' => 800, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['name' => 'Заміна сайлентблоків', 'default_price' => 1800, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ],
            ['name'],
            ['default_price', 'updated_at'],
        );
    }
}
