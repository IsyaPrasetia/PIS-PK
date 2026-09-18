<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $unique = Rule::unique('users', 'email');

        if ($this->route('user') instanceof User) {
            $unique = $unique->ignore($this->route('user'));
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $unique],
            'password' => [$this->route('user') ? 'nullable' : 'required', 'string', 'min:6'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'kecamatan' => ['nullable', 'string', 'max:255', Rule::requiredIf(function () {
                return UserRole::tryFrom((string) $this->input('role')) !== UserRole::Superadmin;
            })],
            'desa' => ['nullable', 'string', 'max:255'],
            'rw' => ['nullable', 'string', 'max:10'],
            'rt' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'password' => 'kata sandi',
            'role' => 'peran',
            'kecamatan' => 'kecamatan',
            'desa' => 'desa/kelurahan',
            'rw' => 'RW',
            'rt' => 'RT',
        ];
    }
}
