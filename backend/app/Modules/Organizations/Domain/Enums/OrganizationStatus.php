<?php

namespace App\Modules\Organizations\Domain\Enums;

enum OrganizationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
