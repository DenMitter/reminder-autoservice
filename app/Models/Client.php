<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'phone',
        'car_brand',
        'car_model',
        'car_number',
        'notes',
    ];

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(ClientVehicle::class);
    }

    public function primaryVehicle(): HasOne
    {
        return $this->hasOne(ClientVehicle::class)->oldestOfMany();
    }

    public function syncPrimaryVehicleAttributes(): void
    {
        $primaryVehicle = $this->vehicles()
            ->oldest('id')
            ->first();

        $this->forceFill([
            'car_brand' => $primaryVehicle?->car_brand ?? '',
            'car_model' => $primaryVehicle?->car_model ?? '',
            'car_number' => $primaryVehicle?->car_number,
        ])->save();
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }
}
