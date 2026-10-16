<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResubmitProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'payment_proof' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'extensions:jpg,jpeg,png',
                'max:'.config('registration.payment_proof_max_kb'),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'payment_proof' => 'bukti pembayaran',
        ];
    }
}
