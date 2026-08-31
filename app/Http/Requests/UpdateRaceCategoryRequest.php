<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRaceCategoryRequest extends StoreRaceCategoryRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['code'] = [
            'required',
            'string',
            'max:10',
            Rule::unique('race_categories', 'code')
                ->where('event_id', $this->input('event_id'))
                // Nama parameter route adalah raceCategory (camelCase), bukan race_category.
                ->ignore($this->route('raceCategory')),
        ];
        // bib_counter tidak boleh dikurangi manual; hanya lewat proses approval.
        $rules['quota'] = ['required', 'integer', 'min:1', 'max:100000'];

        return $rules;
    }
}
