<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRaceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', Rule::exists(Event::class, 'id')],
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('race_categories', 'code')->where('event_id', $this->input('event_id')),
            ],
            'bib_format' => ['required', 'string', 'max:25', 'regex:/\{number\}/'],
            'bib_digits' => ['required', 'integer', 'min:3', 'max:8'],
            'distance' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'price' => ['required', 'integer', 'min:0'],
            'quota' => ['required', 'integer', 'min:1', 'max:100000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'bib_format' => trim((string) ($this->input('bib_format') ?: '{code}-{number}')),
            'bib_digits' => $this->input('bib_digits') ?: 4,
        ]);
    }

    public function messages(): array
    {
        return [
            'bib_format.regex' => 'Format BIB wajib menyertakan placeholder {number} agar nomor peserta tetap unik.',
        ];
    }

    public function attributes(): array
    {
        return [
            'event_id' => 'event',
            'name' => 'nama kategori',
            'code' => 'kode kategori',
            'bib_format' => 'format nomor BIB',
            'bib_digits' => 'jumlah digit nomor BIB',
            'distance' => 'jarak',
            'price' => 'harga',
            'quota' => 'kuota',
            'status' => 'status',
        ];
    }
}
