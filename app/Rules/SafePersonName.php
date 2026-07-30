<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafePersonName implements ValidationRule
{
    /**
     * Normalisasi ringan tanpa mengubah payload berbahaya menjadi data yang
     * seolah-olah valid. Validasi tetap bertugas menolak markup, URL, dan
     * karakter yang tidak lazim untuk nama orang.
     */
    public static function normalize(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return is_string($normalized) ? $normalized : trim($value);
    }

    /**
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('Nama lengkap harus berupa teks.');

            return;
        }

        $name = self::normalize($value);

        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            $fail('Nama lengkap harus terdiri dari 2 sampai 150 karakter.');

            return;
        }

        // Menolak tag HTML, URL, entitas tag, dan skema yang umum dipakai pada XSS/spam.
        if (preg_match(
            '/(?:<\s*\/?\s*[a-z][^>]*>|https?:\/\/|www\.|javascript\s*:|data\s*:|href\s*=|src\s*=|&(?:lt|gt|#0*60|#0*62|#x0*3c|#x0*3e);)/iu',
            $name
        )) {
            $fail('Nama lengkap mengandung pola yang tidak diizinkan.');

            return;
        }

        // Menolak control character dan karakter tak terlihat seperti zero-width space.
        if (preg_match('/[\p{Cc}\p{Cf}]/u', $name)) {
            $fail('Nama lengkap mengandung karakter tersembunyi yang tidak diizinkan.');

            return;
        }

        // Mendukung nama Indonesia, Arab, gelar, apostrof, tanda hubung, dan angka seperlunya.
        if (!preg_match("/\A[\p{L}\p{M}\p{N}][\p{L}\p{M}\p{N}\p{Zs}.,'’()\-]*\z/u", $name)) {
            $fail('Nama lengkap hanya boleh berisi huruf, angka, spasi, titik, koma, apostrof, kurung, dan tanda hubung.');
        }
    }
}
