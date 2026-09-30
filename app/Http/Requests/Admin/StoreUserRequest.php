<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->usertype === 'admin';
    }

    public function rules(): array
    {
        return [
            'userci'          => ['required', 'string', 'max:50'],
            'username'        => ['required', 'string', 'max:20', 'unique:usertable,username'],
            'userfullname'    => ['required', 'string', 'max:200'],
            'userdesc'        => ['nullable', 'string', 'max:300'],
            'usertype'        => ['required', Rule::in([
                'admin',
                'subadmin',
                'teacher',
                'student',
                'cashier',
                'sterilization',
                'supplies',
                'radiology',
            ])],
            'password'        => ['required', 'string', 'min:3'],
            'userenabled'     => ['sometimes', 'boolean'],
            'userpermitlogin' => ['sometimes', 'boolean'],
            'usermultilogin'  => ['sometimes', 'boolean'],
            'userpermitip'    => ['nullable', 'string', 'max:300'],
            'userinfo'        => ['nullable', 'string', 'max:300'],
        ];
    }
}
