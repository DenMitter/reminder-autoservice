<?php

namespace App\Enums;

enum VisitStatus: string
{
    case Planned = 'planned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
