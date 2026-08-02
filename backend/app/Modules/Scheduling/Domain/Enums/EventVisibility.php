<?php

namespace App\Modules\Scheduling\Domain\Enums;

enum EventVisibility: string
{
    case Public = 'public';
    case Restricted = 'restricted';
    case Private = 'private';
    case Unlisted = 'unlisted';
}
