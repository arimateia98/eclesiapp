<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreServantFunctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'pastoral_function_id' => [
                'required', 'uuid',
                Rule::exists('pastoral_functions', 'id')
                    ->where('parish_id', (string) $this->route('parishId'))
                    ->where('status', 'ACTIVE'),
            ],
            'status' => ['sometimes', Rule::in(['PENDING', 'QUALIFIED', 'SUSPENDED', 'EXPIRED'])],
            'qualified_on' => ['nullable', 'date_format:Y-m-d'],
            'expires_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:qualified_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
