<?php

namespace App\Enums;

enum ReminderStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
