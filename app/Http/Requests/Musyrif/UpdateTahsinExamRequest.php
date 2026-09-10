<?php

namespace App\Http\Requests\Musyrif;

use App\Models\TahsinExam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTahsinExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'grade_label' => [
                'required',
                Rule::in(TahsinExam::grades()),
            ],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
