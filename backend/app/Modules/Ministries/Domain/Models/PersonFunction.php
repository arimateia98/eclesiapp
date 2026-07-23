<?php

namespace App\Modules\Ministries\Domain\Models;

use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Models\Organization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'person_id',
    'service_function_id',
    'assigned_by',
])]
class PersonFunction extends Model
{
    public $incrementing = false;

    protected $primaryKey = null;

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return BelongsTo<ServiceFunction, $this> */
    public function serviceFunction(): BelongsTo
    {
        return $this->belongsTo(ServiceFunction::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
