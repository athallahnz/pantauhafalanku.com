<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHafalanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Anda bisa tambah cek role musyrif di sini jika mau.
        return true;
    }

    public function rules(): array
    {
        $status = $this->input('status');

        $rules = [
            'santri_id' => ['required', 'exists:santris,id'],
            'status' => ['required', Rule::in(['lulus', 'ulang', 'hadir_tidak_setor', 'alpha'])],
            'catatan' => ['nullable', 'string'],
        ];

        // Jika setor (lulus/ulang), wajib template + nilai_label
        if (in_array($status, ['lulus', 'ulang'], true)) {
            $rules['hafalan_template_id'] = ['required', 'exists:hafalan_templates,id'];
            $rules['nilai_label'] = ['required', Rule::in(['mumtaz', 'jayyid_jiddan', 'jayyid'])];
        } else {
            // Jika bukan setor, pastikan field ini tidak wajib
            $rules['hafalan_template_id'] = ['nullable', 'exists:hafalan_templates,id'];
            $rules['nilai_label'] = ['nullable', Rule::in(['mumtaz', 'jayyid_jiddan', 'jayyid'])];
        }

        // Field legacy: kalau masih ada di request (misal dari modal lama), biarkan nullable
        // dan nanti kita abaikan pada controller agar tidak jadi sumber kebenaran ganda.
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
        // Normalisasi ringan
        if ($this->has('status')) {
            $this->merge(['status' => trim((string) $this->status)]);
        }
    }
}
