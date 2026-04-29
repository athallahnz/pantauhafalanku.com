<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHafalanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $status = $this->input('status');
        $rules = [
            'santri_id' => ['required', 'exists:santris,id'],
            'status'    => ['required', Rule::in(['lulus', 'ulang', 'hadir_tidak_setor', 'alpha', 'sakit', 'izin'])],
            'catatan'   => ['nullable', 'string'],
        ];

        // Tambahkan 'mardud' di validasi in:
        $nilaiOptions = ['mumtaz', 'jayyid_jiddan', 'jayyid', 'mardud'];

        if (in_array($status, ['lulus', 'ulang'], true)) {
            $rules['hafalan_template_id'] = ['required', 'exists:hafalan_templates,id'];
            $rules['nilai_label'] = ['required', Rule::in($nilaiOptions)];
        } else {
            $rules['hafalan_template_id'] = ['nullable', 'exists:hafalan_templates,id'];
            $rules['nilai_label'] = ['nullable', Rule::in($nilaiOptions)];
        }

        // Field legacy
        $rules['juz'] = ['nullable', 'integer', 'min:1', 'max:30'];
        $rules['surah'] = ['nullable', 'string', 'max:100'];
        $rules['ayat_awal'] = ['nullable', 'integer', 'min:1'];
        $rules['ayat_akhir'] = ['nullable', 'integer', 'min:1', 'gte:ayat_awal'];
        $rules['rentang_ayat_label'] = ['nullable', 'string', 'max:150'];
        $rules['nilai'] = ['nullable', 'integer', 'min:0', 'max:100'];
        $rules['tahap'] = ['nullable', Rule::in(['tahap_1', 'tahap_2', 'tahap_3'])];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'hafalan_template_id.required' => 'Surah/Ayat wajib dipilih untuk status Lulus/Ulang.',
            'nilai_label.required' => 'Nilai wajib dipilih untuk status Lulus/Ulang.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('status')) {
            $status = trim((string) $this->status);
            // Jika status ULANG, paksa nilai_label menjadi MARDUD sebelum divalidasi
            if ($status === 'ulang') {
                $this->merge([
                    'status' => $status,
                    'nilai_label' => 'mardud'
                ]);
            } else {
                $this->merge(['status' => $status]);
            }
        }
    }
}
