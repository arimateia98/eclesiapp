<?php

namespace App\Modules\Missions\Domain\Enums;

enum MissionSlotType: string
{
    case Person = 'person';
    case Organization = 'organization';
}
