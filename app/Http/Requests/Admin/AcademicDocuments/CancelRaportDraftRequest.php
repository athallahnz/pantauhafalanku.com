<?php

namespace App\Http\Requests\Admin\AcademicDocuments;

use Illuminate\Foundation\Http\FormRequest;

class CancelRaportDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'cancellation_reason' =>
                'alasan pembatalan',
        ];
    }

    public function reason(): string
    {
        $validated = $this->validated();

        return trim(
            (string) $validated['cancellation_reason']
        );
    }
}
