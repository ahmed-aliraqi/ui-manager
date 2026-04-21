<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'fields'   => 'required|array',
            'fields.*' => 'nullable',
        ];
    }
}
