<?php

namespace App\Modules\Organizations\Domain\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
