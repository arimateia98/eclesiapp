<?php

namespace App\Modules\Identity\Domain\Enums;

enum PersonStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
