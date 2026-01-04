<?php

namespace App\Enums;

enum MessageSendStatus: string
{
    case ACTIVE = 'ACTIVE';
    case CLOSED = 'CLOSED';
}
