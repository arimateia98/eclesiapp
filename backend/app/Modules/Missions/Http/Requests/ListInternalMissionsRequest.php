<?php

namespace App\Modules\Missions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListInternalMissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['per_page' => ['sometimes', 'integer', 'min:1', 'max:100']];
    }
}
