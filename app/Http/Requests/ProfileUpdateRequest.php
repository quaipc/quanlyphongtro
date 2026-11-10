<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ho_ten' => ['required', 'string', 'max:100'],
            'email'  => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:150',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'sdt'    => ['required', 'string', 'max:15'],
            'cccd'   => [
                'nullable',
                'regex:/^(\d{9}|\d{12})$/',
                Rule::unique(User::class, 'cccd')->ignore($this->user()->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cccd.regex'  => 'CCCD gồm 9 hoặc 12 chữ số.',
            'cccd.unique' => 'CCCD này đã thuộc về một tài khoản khác.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ho_ten' => 'họ và tên',
            'sdt'    => 'số điện thoại',
            'cccd'   => 'CCCD',
        ];
    }
}
