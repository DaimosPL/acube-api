<?php

declare(strict_types=1);

namespace App\Enum;

enum NotificationStatus: string
{
    case PENDING = 'pending';
    case SENDED = 'sended';
}

