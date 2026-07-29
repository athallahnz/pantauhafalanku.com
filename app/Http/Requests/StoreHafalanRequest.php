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
        $status = trim((string) $this->input('status', ''));

        return [
            'santri_id' => [
                'required',
                'exists:santris,id',
            ],
            'status' => [
                'required',
                Rule::in([
                    'lulus',
                    'ulang',
                    'hadir_tidak_setor',
                    'alpha',
                    'sakit',
                    'izin',
                ]),
            ],
            'hafalan_template_id' => in_array(
                $status,
                ['lulus', 'ulang'],
                true
            )
                ? [
                    'required',
                    'exists:hafalan_templates,id',
                ]
                : [
                    'nullable',
                    'exists:hafalan_templates,id',
                ],
            'nilai_label' => $status === 'lulus'
                ? [
                    'required',
                    Rule::in([
                        'mumtaz',
                        'jayyid_jiddan',
                        'jayyid',
                    ]),
                ]
                : [
                    'nullable',
                ],
            'catatan' => [
                'nullable',
                'string',
            ],

            /*
             * Field legacy tetap diterima agar frontend lama tidak langsung
             * gagal, tetapi controller tidak menggunakannya untuk penyimpanan.
             */
            'surah_id' => [
                'nullable',
                'exists:surahs,id',
            ],
            'surah_segment_id' => [
                'nullable',
                'exists:surah_segments,id',
            ],
            'ayat_mulai' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'ayat_selesai' => [
                'nullable',
                'integer',
                'min:1',
                'gte:ayat_mulai',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'santri_id.required' =>
                'Santri wajib dipilih.',
            'santri_id.exists' =>
                'Data santri tidak ditemukan.',
            'status.required' =>
                'Status setoran wajib dipilih.',
            'status.in' =>
                'Status setoran tidak valid.',
            'hafalan_template_id.required' =>
                'Materi hafalan wajib dipilih untuk status Lulus atau Ulang.',
            'hafalan_template_id.exists' =>
                'Materi hafalan tidak ditemukan.',
            'nilai_label.required' =>
                'Nilai wajib dipilih untuk status Lulus.',
            'nilai_label.in' =>
                'Nilai hafalan tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('status')) {
            return;
        }

        $status = trim(
            (string) $this->input('status')
        );

        $payload = [
            'status' => $status,
        ];

        /*
         * Materi hanya relevan untuk aktivitas setoran: lulus atau ulang.
         */
        if (!in_array($status, ['lulus', 'ulang'], true)) {
            $payload['hafalan_template_id'] = null;
        }

        /*
         * Hanya transaksi lulus yang boleh mempunyai nilai.
         * Hidden input atau nilai lama dari form edit ikut dibersihkan.
         */
        if ($status !== 'lulus') {
            $payload['nilai_label'] = null;
        }

        $this->merge($payload);
    }
}
