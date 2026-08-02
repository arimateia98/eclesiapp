<?php

namespace App\Modules\Missions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreInternalMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'ministry_type_id' => ['required', 'ulid'],
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'slots' => ['required', 'array', 'min:1', 'max:20'],
            'slots.*.service_function_id' => ['required', 'ulid', 'distinct'],
            'slots.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'slots.*.required' => ['sometimes', 'boolean'],
        ];
    }
}
