<?php

namespace App\Http\Controllers;

use App\Models\ProfileSetting;
use Illuminate\Http\Request;

class ProfileSettingController extends Controller
{
    public function index()
    {
        $profile = auth()->user()->profileSetting;

        return view('settings.profile.index', compact('profile'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'photo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'full_name' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string'
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')
                ->store('profiles', 'public');
        }

        ProfileSetting::updateOrCreate(
            ['user_id' => auth()->id()],
            $data
        );

        return redirect()->back()->with('success', 'Profil berhasil diperbarui');
    }
}
