<?php

namespace App\Models;

use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    /** @use HasFactory<\Database\Factories\VisitFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'service_type',
        'visit_date',
        'visit_end_at',
        'price',
        'status',
        'next_service_date',
        'notes',
        'came_from_reminder',
        'did_not_show',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visit_date' => 'datetime',
            'visit_end_at' => 'datetime',
            'next_service_date' => 'date',
            'price' => 'decimal:2',
            'status' => VisitStatus::class,
            'came_from_reminder' => 'boolean',
            'did_not_show' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }
}
