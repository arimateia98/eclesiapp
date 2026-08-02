<?php

namespace App\Modules\Organizations\Http\Requests;

use App\Modules\Organizations\Domain\Enums\OrganizationRelationshipType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOrganizationRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'target_organization_id' => ['required', 'ulid', Rule::exists('organizations', 'id')],
            'relationship_type' => ['required', Rule::enum(OrganizationRelationshipType::class)],
        ];
    }
}
