<?php

namespace App\Http\Requests\Admin\AcademicDocuments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateRaportDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'santri_id' => [
                'required',
                'integer',
                'exists:santris,id',
            ],

            'semester_id' => [
                'required',
                'integer',
                'exists:semesters,id',
            ],

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
