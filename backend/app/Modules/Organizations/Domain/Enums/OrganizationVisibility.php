<?php

namespace App\Modules\Organizations\Domain\Enums;

enum OrganizationVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Unlisted = 'unlisted';
}
