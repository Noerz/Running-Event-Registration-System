<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('events', 'slug')],
            'description' => ['required', 'string', 'max:5000'],
            'event_date' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'registration_start' => ['required', 'date'],
            'registration_end' => ['required', 'date', 'after_or_equal:registration_start'],
            'status' => ['required', Rule::enum(\App\Enums\EventStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name') && ! $this->filled('slug')) {
            $this->merge(['slug' => Str::slug($this->input('name'))]);
        }
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama event',
            'slug' => 'slug',
            'description' => 'deskripsi',
            'event_date' => 'tanggal event',
            'location' => 'lokasi',
            'registration_start' => 'pendaftaran dibuka',
            'registration_end' => 'pendaftaran ditutup',
            'status' => 'status',
        ];
    }
}
