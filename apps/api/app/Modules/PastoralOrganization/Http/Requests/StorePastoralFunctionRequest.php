<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePastoralFunctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $parishId = (string) $this->route('parishId');

        return [
            'pastoral_area_id' => [
                'required', 'uuid',
                Rule::exists('pastoral_areas', 'id')->where('parish_id', $parishId)->where('status', 'ACTIVE'),
            ],
            'code' => [
                'required', 'string', 'max:80', 'regex:/^[A-Z0-9_]+$/',
                Rule::unique('pastoral_functions', 'code')
                    ->where('pastoral_area_id', (string) $this->input('pastoral_area_id')),
            ],
            'name' => ['required', 'string', 'min:2', 'max:140'],
            'assignment_mode' => ['required', Rule::in(['PERSON', 'TEAM', 'EITHER'])],
            'requires_qualification' => ['required', 'boolean'],
        ];
    }
}
