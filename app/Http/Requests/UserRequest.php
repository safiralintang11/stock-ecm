<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $passwordRule = $this->isMethod('post')
            ? ['required', 'string', 'min:8']
            : ['nullable', 'string', 'min:8'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => $passwordRule,
            'role' => ['required', 'in:admin,gudang'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
