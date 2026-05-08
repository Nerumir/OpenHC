<?php

namespace App\Http\Requests\AdminPortal;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminPortalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
