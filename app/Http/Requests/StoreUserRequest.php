<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    } // already admin-guarded
    public function rules(): array
    {
        return [
            'username'  => ['required', 'string', 'max:100', 'unique:users,username'],
            'mat_no'    => ['required', 'string', 'max:100', 'unique:users,mat_no'],
            'email'     => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:6'],
            'user_type' => ['nullable', 'in:user,admin'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'], // if campuses table
        ];
    }
}
