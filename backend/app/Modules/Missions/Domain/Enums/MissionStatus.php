<?php

namespace App\Modules\Missions\Domain\Enums;

enum MissionStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Filled = 'filled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
