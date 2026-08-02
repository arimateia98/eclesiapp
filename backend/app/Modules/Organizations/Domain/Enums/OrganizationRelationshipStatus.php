<?php

namespace App\Modules\Organizations\Domain\Enums;

enum OrganizationRelationshipStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
