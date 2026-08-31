<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\RaceCategory;
use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jerseySizes = ['S', 'M', 'L', 'XL', 'XXL'];

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

            'race_category_id' => [
                'required',
                Rule::exists('race_categories', 'id')
                    ->where('status', 'active')
                    ->whereIn('event_id', Event::query()->openForRegistration()->select('id')),
            ],
            'jersey_size' => ['required', Rule::in($jerseySizes)],
            'running_club' => ['nullable', 'string', 'max:100'],

            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', new PhoneNumber],

            'payment_method' => ['required', 'string', Rule::in(array_keys(config('registration.payment_methods')))],
            'amount' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $category = RaceCategory::find($this->input('race_category_id'));

                    if ($category && (int) $value !== (int) $category->price) {
                        $fail("Nominal pembayaran harus tepat {$category->formattedPrice()} untuk kategori {$category->name}.");
                    }
                },
            ],
            'payment_proof' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'extensions:jpg,jpeg,png',
                'max:'.config('registration.payment_proof_max_kb'),
            ],

            'agree_terms' => ['accepted'],
        ];
    }

    /**
     * Normalisasi input sebelum validasi & penyimpanan.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNumber::normalize($this->input('phone')),
            'emergency_contact_phone' => PhoneNumber::normalize($this->input('emergency_contact_phone')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'full_name' => trim((string) $this->input('full_name')),
        ]);
    }

    public function messages(): array
    {
        return [
            'agree_terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan event.',
            'amount.min' => 'Nominal pembayaran tidak boleh kosong.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal :max KB.',
        ];
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
            'race_category_id' => 'kategori lomba',
            'jersey_size' => 'ukuran jersey',
            'running_club' => 'komunitas/running club',
            'emergency_contact_name' => 'nama emergency contact',
            'emergency_contact_phone' => 'nomor HP emergency contact',
            'payment_method' => 'metode pembayaran',
            'amount' => 'nominal pembayaran',
            'payment_proof' => 'bukti pembayaran',
            'agree_terms' => 'persetujuan syarat dan ketentuan',
        ];
    }
}
