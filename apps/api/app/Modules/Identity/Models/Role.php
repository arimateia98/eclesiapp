<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Role extends Model
{
    use HasUuids;

    protected $table = 'role_catalog';

    protected $guarded = [];
}
