<?php

namespace App\Enums;

enum MessageHistoryStatus: string
{
    case NEW = 'NEW';
    case MODIFIED = 'MODIFIED';
    case CANCELLED = 'CANCELLED';
}
