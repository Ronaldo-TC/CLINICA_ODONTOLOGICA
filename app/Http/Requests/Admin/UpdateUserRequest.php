<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->usertype === 'admin';
    }

    public function rules(): array
    {
        $usernumber = $this->route('usernumber');

        return [
            'userci'          => ['sometimes', 'string', 'max:50'],
            'username'        => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('usertable', 'username')->ignore($usernumber, 'usernumber')
            ],
            'userfullname'    => ['sometimes', 'string', 'max:200'],
            'userdesc'        => ['nullable', 'string', 'max:300'],
            'usertype'        => ['sometimes', Rule::in([
                'admin',
                'subadmin',
                'teacher',
                'student',
                'cashier',
                'sterilization',
                'supplies',
                'radiology',
            ])],
            'userenabled'     => ['sometimes', 'boolean'],
            'userpermitlogin' => ['sometimes', 'boolean'],
            'usermultilogin'  => ['sometimes', 'boolean'],
            'userpermitip'    => ['nullable', 'string', 'max:300'],
            'userinfo'        => ['nullable', 'string', 'max:300'],
        ];
    }
}
