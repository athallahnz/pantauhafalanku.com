<?php

namespace App\Http\Requests\Musyrif;

use App\Models\TahsinExam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTahsinExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'santri_id' => ['required', 'integer', 'exists:santris,id'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'exam_type' => [
                'required',
                Rule::in(TahsinExam::examTypes()),
            ],
            'buku' => ['required', Rule::in(TahsinExam::books())],
            'grade_label' => [
                'required',
                Rule::in(TahsinExam::grades()),
            ],
            'submission_uuid' => ['required', 'uuid'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
