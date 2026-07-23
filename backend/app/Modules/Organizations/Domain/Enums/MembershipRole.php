<?php

namespace App\Modules\Organizations\Domain\Enums;

enum MembershipRole: string
{
    case Owner = 'owner';
    case Administrator = 'administrator';
    case Coordinator = 'coordinator';
    case Member = 'member';
    case Guest = 'guest';
}
