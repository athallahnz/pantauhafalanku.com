<?php

namespace App\Actions\Fortify;

use App\Events\UserRegistered;
use App\Models\User;
use App\Rules\SafePersonName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Throwable;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        $input['name'] = SafePersonName::normalize($input['name'] ?? '');
        $input['email'] = mb_strtolower(trim((string) ($input['email'] ?? '')));

        Validator::make(
            $input,
            [
                'name' => [
                    'bail',
                    'required',
                    'string',
                    'min:2',
                    'max:150',
                    new SafePersonName(),
                ],
                'email' => [
                    'bail',
                    'required',
                    'string',
                    'email:rfc',
                    'max:255',
                    Rule::unique(User::class, 'email'),
                ],
                'password' => $this->passwordRules(),
                'role' => [
                    'required',
                    Rule::in(['santri', 'musyrif']),
                ],
            ],
            [
                'email.unique' => 'Alamat email tersebut sudah terdaftar.',
                'role.in' => 'Jenis akun yang dipilih tidak valid.',
            ]
        )->validate();

        /*
         * Registrasi publik hanya membuat akun pending.
         * Profile santri/musyrif baru dibuat ketika Super Admin menyetujui akun,
         * sehingga bot tidak mencemari tabel struktur akademik.
         */
        $user = DB::transaction(function () use ($input): User {
            $newUser = new User();

            $newUser->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'role' => $input['role'],
                'is_approved' => false,
                'account_status' => 'pending',
            ])->save();

            return $newUser;
        });

        try {
            event(new UserRegistered());
        } catch (Throwable $exception) {
            // Notifikasi real-time tidak boleh menggagalkan registrasi akun.
            Log::error('UserRegistered broadcast failed.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return $user;
    }
}
