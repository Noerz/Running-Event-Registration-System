<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new PhoneNumber],
            'email' => ['required', 'email:rfc', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'identity_number' => ['nullable', 'digits_between:10,16'],
            'address' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'jersey_size' => ['required', Rule::in(config('registration.jersey_sizes'))],
            'running_club' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', new PhoneNumber],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNumber::normalize($this->input('phone')),
            'emergency_contact_phone' => PhoneNumber::normalize($this->input('emergency_contact_phone')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'full_name' => trim((string) $this->input('full_name')),
        ]);
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'nama lengkap',
            'phone' => 'nomor HP/WhatsApp',
            'email' => 'email',
            'gender' => 'jenis kelamin',
            'birth_place' => 'tempat lahir',
            'birth_date' => 'tanggal lahir',
            'identity_number' => 'nomor identitas/NIK',
            'address' => 'alamat lengkap',
            'city' => 'kota/kabupaten',
            'province' => 'provinsi',
            'jersey_size' => 'ukuran jersey',
            'running_club' => 'komunitas/running club',
            'emergency_contact_name' => 'nama emergency contact',
            'emergency_contact_phone' => 'nomor HP emergency contact',
        ];
    }
}
