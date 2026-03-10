<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\Santri;
use App\Models\Musyrif;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use App\Events\UserRegistered;
use Illuminate\Support\Facades\Log;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'role' => ['required', Rule::in(['santri', 'musyrif'])],
        ])->validate();

        // Simpan ke variabel
        $user = DB::transaction(function () use ($input) {
            $newUser = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'role' => $input['role'],
                'is_approved' => false,
            ]);

            if ($input['role'] === 'musyrif') {
                Musyrif::create([
                    'user_id' => $newUser->id,
                    'nama'    => $input['name'],
                ]);
            } else {
                Santri::create([
                    'user_id'  => $newUser->id,
                    'nama'     => $input['name'],
                    'kelas_id' => null,
                ]);
            }
            return $newUser;
        });

        // KIRIM EVENT SETELAH TRANSAKSI SELESAI
        try {
            event(new UserRegistered());
        } catch (\Exception $e) {
            // Biar registrasi nggak gagal cuma gara-gara Pusher error
            Log::error("Pusher Error: " . $e->getMessage());
        }

        return $user;
    }
}
