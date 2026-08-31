<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends StoreEventRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['slug'] = [
            'nullable',
            'string',
            'max:255',
            Rule::unique('events', 'slug')->ignore($this->route('event')),
        ];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge(['slug' => $this->filled('slug') ? Str::slug($this->input('slug')) : Str::slug($this->input('name'))]);
        }
    }
}
