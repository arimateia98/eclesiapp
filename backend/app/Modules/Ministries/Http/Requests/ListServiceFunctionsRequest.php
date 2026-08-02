<?php

namespace App\Modules\Ministries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListServiceFunctionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'ministry_type_id' => ['sometimes', 'ulid'],
        ];
    }
}
