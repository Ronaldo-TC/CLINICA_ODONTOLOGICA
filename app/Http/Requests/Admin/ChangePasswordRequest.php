<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->usertype === 'admin';
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:3', 'confirmed'],
        ];
    }
}
