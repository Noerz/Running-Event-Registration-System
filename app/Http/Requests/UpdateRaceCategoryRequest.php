<?php

namespace App\Http\Requests;

use App\Models\RaceCategory;
use Illuminate\Validation\Rule;

class UpdateRaceCategoryRequest extends StoreRaceCategoryRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        /** @var RaceCategory|null $category */
        $category = $this->route('raceCategory');

        if ($category instanceof RaceCategory && $category->participants()->exists()) {
            $rules['event_id'] = ['required', Rule::in([$category->event_id])];
        }

        $rules['code'] = [
            'required',
            'string',
            'max:10',
            Rule::unique('race_categories', 'code')
                ->where('event_id', $this->input('event_id'))
                // Nama parameter route adalah raceCategory (camelCase), bukan race_category.
                ->ignore($category),
        ];

        // bib_counter tidak boleh dikurangi manual; kuota tidak boleh di bawah peserta aktif.
        $minQuota = $category instanceof RaceCategory ? max(1, $category->usedQuota()) : 1;
        $rules['quota'] = ['required', 'integer', 'min:'.$minQuota, 'max:100000'];

        return $rules;
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'event_id.in' => 'Event tidak dapat diubah karena kategori ini sudah memiliki peserta terdaftar.',
            'quota.min' => 'Kuota tidak boleh kurang dari jumlah peserta yang sudah terdaftar (:min peserta).',
        ];
    }
}
