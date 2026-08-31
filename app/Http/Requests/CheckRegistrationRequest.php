<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class CheckRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $identifier = trim((string) $this->input('identifier'));

        // Jika input berupa nomor HP, normalisasi agar pencocokan konsisten.
        if (preg_match('/^[+0-9][0-9\s\-+.()]{7,}$/', $identifier)) {
            $identifier = PhoneNumber::normalize($identifier);
        }

        $this->merge([
            'identifier' => $identifier,
        ]);
    }

    public function attributes(): array
    {
        return [
            'identifier' => 'email / nomor HP',
        ];
    }
}
