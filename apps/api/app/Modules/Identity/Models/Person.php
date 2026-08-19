<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\PastoralOrganization\Models\Servant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Person extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /** @return HasMany<Servant, $this> */
    public function servants(): HasMany
    {
        return $this->hasMany(Servant::class);
    }
}
