<?php

namespace App\Enums;

enum MessageTag: string
{
    case BOOKING = 'BOOKING';
    case CHECK_IN = 'CHECK_IN';
    case COMPLAINT = 'COMPLAINT';
}
