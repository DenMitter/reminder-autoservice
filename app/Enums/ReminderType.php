<?php

namespace App\Enums;

enum ReminderType: string
{
    case Appointment = 'appointment';
    case AppointmentConfirmation = 'appointment_confirmation';
    case RepeatService = 'repeat_service';
}
