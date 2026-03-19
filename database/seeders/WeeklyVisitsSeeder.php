<?php

namespace Database\Seeders;

use App\Enums\VisitStatus;
use App\Models\Client;
use App\Models\ServiceCatalogItem;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class WeeklyVisitsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ServiceCatalogSeeder::class);

        $weekStart = CarbonImmutable::now()->startOfWeek();
        $services = ServiceCatalogItem::query()->pluck('default_price', 'name');

        $schedule = [
            [
                'client' => ['full_name' => 'Олександр Бондаренко', 'phone' => '+380957192907', 'car_brand' => 'Toyota', 'car_model' => 'Land Cruiser', 'car_number' => 'AB4948IA'],
                'service' => 'Заміна масла в двигуні',
                'day' => 0,
                'start' => '09:00',
                'end' => '10:00',
                'status' => VisitStatus::Completed,
                'came_from_reminder' => true,
            ],
            [
                'client' => ['full_name' => 'Ірина Ковальчук', 'phone' => '+380971234567', 'car_brand' => 'Volkswagen', 'car_model' => 'Passat', 'car_number' => 'KA1203MM'],
                'service' => 'Діагностика ходової',
                'day' => 0,
                'start' => '11:00',
                'end' => '11:30',
                'status' => VisitStatus::Completed,
                'came_from_reminder' => false,
            ],
            [
                'client' => ['full_name' => 'Віталій Мельник', 'phone' => '+380631112233', 'car_brand' => 'BMW', 'car_model' => 'X5', 'car_number' => 'AB1010BM'],
                'service' => 'Розвал-сходження',
                'day' => 1,
                'start' => '10:30',
                'end' => '11:30',
                'status' => VisitStatus::Completed,
                'came_from_reminder' => true,
            ],
            [
                'client' => ['full_name' => 'Марина Савчук', 'phone' => '+380662223344', 'car_brand' => 'Renault', 'car_model' => 'Megane', 'car_number' => 'KA7788OP'],
                'service' => 'Планове ТО',
                'day' => 2,
                'start' => '12:00',
                'end' => '14:00',
                'status' => VisitStatus::Planned,
                'came_from_reminder' => false,
            ],
            [
                'client' => ['full_name' => 'Петро Іванюк', 'phone' => '+380936667788', 'car_brand' => 'Ford', 'car_model' => 'Focus', 'car_number' => 'AB2200PP'],
                'service' => 'Заправка кондиціонера',
                'day' => 2,
                'start' => '15:00',
                'end' => '16:00',
                'status' => VisitStatus::Planned,
                'came_from_reminder' => false,
            ],
            [
                'client' => ['full_name' => 'Юлія Довгань', 'phone' => '+380986665544', 'car_brand' => 'Audi', 'car_model' => 'A4', 'car_number' => 'BC4321IT'],
                'service' => 'Комп’ютерна діагностика',
                'day' => 3,
                'start' => '09:30',
                'end' => '10:30',
                'status' => VisitStatus::Planned,
                'came_from_reminder' => false,
            ],
            [
                'client' => ['full_name' => 'Сергій Кравець', 'phone' => '+380501234789', 'car_brand' => 'Skoda', 'car_model' => 'Octavia', 'car_number' => 'AI9090CT'],
                'service' => 'Заміна передніх гальмівних колодок',
                'day' => 4,
                'start' => '13:00',
                'end' => '14:30',
                'status' => VisitStatus::Planned,
                'came_from_reminder' => true,
            ],
            [
                'client' => ['full_name' => 'Назар Романюк', 'phone' => '+380673339900', 'car_brand' => 'Nissan', 'car_model' => 'Qashqai', 'car_number' => 'CE5555AA'],
                'service' => 'Шиномонтаж R17-R19',
                'day' => 5,
                'start' => '10:00',
                'end' => '11:00',
                'status' => VisitStatus::Cancelled,
                'came_from_reminder' => false,
            ],
        ];

        foreach ($schedule as $item) {
            $client = Client::query()->updateOrCreate(
                ['phone' => $item['client']['phone']],
                $item['client'],
            );

            $visitDate = $weekStart->addDays($item['day'])->setTimeFromTimeString($item['start']);
            $visitEnd = $weekStart->addDays($item['day'])->setTimeFromTimeString($item['end']);

            Visit::query()->updateOrCreate(
                [
                    'client_id' => $client->id,
                    'visit_date' => $visitDate,
                ],
                [
                    'service_type' => $item['service'],
                    'visit_end_at' => $visitEnd,
                    'price' => $services[$item['service']] ?? 1000,
                    'status' => $item['status'],
                    'notes' => 'Тестовий запис для тижневого розкладу',
                    'came_from_reminder' => $item['came_from_reminder'],
                    'did_not_show' => false,
                ],
            );
        }
    }
}
