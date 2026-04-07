<?php

namespace App\Http\Controllers;

use App\Models\ProfileSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileSettingController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $profile = $user->profileSetting;

        return view('settings.profile.index', compact('profile'));
    }

    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'photo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'full_name' => 'required|string|max:150', // Sebaiknya required untuk nama
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string'
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        // 2. Update Data Utama di Tabel `users`
        $user->update([
            'name' => $request->full_name,
            // Jika kolom di tabel users Mas namanya 'nomor', buka komentar di bawah:
            'nomor' => $request->phone,
        ]);

        // 3. Siapkan Data untuk Tabel `profile_settings`
        $profileData = [
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'address' => $request->address,
        ];

        // 4. Proses Upload Foto (beserta hapus foto lama jika ada)
        if ($request->hasFile('photo')) {
            $oldProfile = $user->profileSetting;

            // Hapus foto fisik yang lama agar storage tidak bengkak
            if ($oldProfile && $oldProfile->photo) {
                Storage::disk('public')->delete($oldProfile->photo);
            }

            $profileData['photo'] = $request->file('photo')->store('profiles', 'public');
        }

        // 5. Simpan ke Tabel `profile_settings`
        ProfileSetting::updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        return redirect()->back()->with('success', 'Profil berhasil diperbarui!');
    }
}
