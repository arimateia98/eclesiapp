<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePastoralAreaRequest extends FormRequest
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
        return [
            'code' => [
                'required', 'string', 'max:60', 'regex:/^[A-Z0-9_]+$/',
                Rule::unique('pastoral_areas', 'code')->where('parish_id', (string) $this->route('parishId')),
            ],
            'name' => ['required', 'string', 'min:2', 'max:140'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
