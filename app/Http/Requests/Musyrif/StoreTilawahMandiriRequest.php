<?php

namespace App\Http\Requests\Musyrif;

use App\Models\Tilawah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTilawahMandiriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'submission_uuid' => ['required', 'uuid'],
            'santri_id' => ['required', 'integer', 'exists:santris,id'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'reading_purpose' => [
                'required',
                Rule::in([
                    Tilawah::PURPOSE_CONTINUATION,
                    Tilawah::PURPOSE_REVIEW,
                ]),
            ],
            'juz' => ['required', 'integer', 'between:1,30'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
