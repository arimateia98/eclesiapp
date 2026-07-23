<?php

namespace App\Modules\Ministries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreMinistryTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
