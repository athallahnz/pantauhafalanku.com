<?php

namespace App\Http\Requests\Admin\AcademicDocuments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegenerateRaportDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'predikat' => [
                'sometimes',
                'nullable',
                Rule::in([
                    'mumtaz',
                    'jayyid_jiddan',
                    'jayyid',
                    'mardud',
                ]),
            ],

            'catatan_musyrif' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            'catatan_admin' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            'rekomendasi' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function manualFields(): array
    {
        return collect($this->validated())
            ->only([
                'predikat',
                'catatan_musyrif',
                'catatan_admin',
                'rekomendasi',
            ])
            ->all();
    }
}
