<?php

namespace App\Models;

use App\Enums\ReminderResponseStatus;
use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    /** @use HasFactory<\Database\Factories\ReminderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'visit_id',
        'type',
        'send_at',
        'message',
        'status',
        'sent_at',
        'response_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'send_at' => 'datetime',
            'sent_at' => 'datetime',
            'type' => ReminderType::class,
            'status' => ReminderStatus::class,
            'response_status' => ReminderResponseStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
