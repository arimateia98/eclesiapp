<?php

namespace App\Modules\Scheduling\Domain\Enums;

enum AssignmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Replaced = 'replaced';
}
