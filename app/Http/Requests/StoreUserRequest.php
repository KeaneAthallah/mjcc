<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,operator,viewer'],
            'responder_type' => ['nullable', Rule::in([User::RESPONDER_MEDICAL, User::RESPONDER_FIRE, User::RESPONDER_POLICE])],
        ];
    }
}
