<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignResponsibleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->usertype === 'admin';
    }

    public function rules(): array
    {
        return [
            'userid'     => ['required', 'integer', 'exists:usertable,usernumber'],
            'clinicalid' => ['required', 'integer', 'exists:clinicaltable,clinicalid'],
            'responsibleadmission' => ['required', 'boolean'],
        ];
    }
}
