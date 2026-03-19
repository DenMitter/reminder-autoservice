<?php

namespace App\Enums;

enum ReminderResponseStatus: string
{
    case NoResponse = 'no_response';
    case ClientBooked = 'client_booked';
    case ClientCame = 'client_came';
}
