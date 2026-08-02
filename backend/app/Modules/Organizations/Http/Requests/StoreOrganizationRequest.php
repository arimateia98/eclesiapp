<?php

namespace App\Modules\Organizations\Http\Requests;

use App\Modules\Organizations\Domain\Enums\OrganizationType;
use App\Modules\Organizations\Domain\Enums\OrganizationVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('organizations', 'slug')],
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'visibility' => ['required', Rule::enum(OrganizationVisibility::class)],
            'timezone' => ['required', 'timezone:all'],
            'parent_organization_id' => ['nullable', 'ulid', Rule::exists('organizations', 'id')],
        ];
    }
}
