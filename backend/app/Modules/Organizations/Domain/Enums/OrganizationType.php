<?php

namespace App\Modules\Organizations\Domain\Enums;

enum OrganizationType: string
{
    case Diocese = 'diocese';
    case Parish = 'parish';
    case Community = 'community';
    case Chapel = 'chapel';
    case Ministry = 'ministry';
    case Movement = 'movement';
    case Group = 'group';
}
