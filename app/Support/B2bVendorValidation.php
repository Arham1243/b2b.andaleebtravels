<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class B2bVendorValidation
{
    public static function messages(): array
    {
        return [
            'username.unique' => 'This username is already taken. Please choose a unique username.',
            'email.unique' => 'This email address is already registered.',
            'agent_code.unique' => 'This agent code is already in use. Please choose a unique agent code.',
        ];
    }

    public static function usernameRule(?int $ignoreVendorId = null): array
    {
        return [
            'required',
            'string',
            'max:255',
            Rule::unique('b2b_vendors', 'username')->ignore($ignoreVendorId),
        ];
    }

    public static function emailRule(?int $ignoreVendorId = null): array
    {
        return [
            'required',
            'email',
            'max:255',
            Rule::unique('b2b_vendors', 'email')->ignore($ignoreVendorId),
        ];
    }

    public static function agentCodeRule(?int $ignoreVendorId = null): array
    {
        return [
            'required',
            'string',
            'max:255',
            Rule::unique('b2b_vendors', 'agent_code')->ignore($ignoreVendorId),
        ];
    }
}
