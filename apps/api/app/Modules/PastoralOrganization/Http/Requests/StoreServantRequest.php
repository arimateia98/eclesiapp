<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreServantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:2', 'max:180'],
            'preferred_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'joined_on' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
