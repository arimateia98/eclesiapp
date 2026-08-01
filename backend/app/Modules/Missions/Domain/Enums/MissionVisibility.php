<?php

namespace App\Modules\Missions\Domain\Enums;

enum MissionVisibility: string
{
    case Public = 'public';
    case Restricted = 'restricted';
    case Private = 'private';
    case Unlisted = 'unlisted';
}
