<?php

namespace App\Http\Requests\Admin\AcademicDocuments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRaportDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'predikat' => [
                'nullable',
                Rule::in([
                    'mumtaz',
                    'jayyid_jiddan',
                    'jayyid',
                    'mardud',
                ]),
            ],

            'catatan_musyrif' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'catatan_admin' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'rekomendasi' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'predikat' => 'predikat',
            'catatan_musyrif' => 'catatan musyrif',
            'catatan_admin' => 'catatan admin',
            'rekomendasi' => 'rekomendasi',
        ];
    }
}
