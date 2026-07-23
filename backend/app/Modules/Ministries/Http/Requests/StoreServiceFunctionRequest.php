<?php

namespace App\Modules\Ministries\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreServiceFunctionRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:120'],
        ];
    }
}
