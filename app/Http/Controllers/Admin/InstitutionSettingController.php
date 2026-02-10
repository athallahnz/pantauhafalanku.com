<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstitutionSetting;
use Illuminate\Http\Request;

class InstitutionSettingController extends Controller
{
    public function index()
    {
        $setting = InstitutionSetting::first();

        return view('admin.settings.institution.index', compact('setting'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'website' => 'nullable|string|max:150',
            'head_name' => 'nullable|string|max:150',
            'established_year' => 'nullable|digits:4'
        ]);

        // upload logo
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')
                ->store('institution', 'public');
        }

        InstitutionSetting::updateOrCreate(
            ['id' => 1],
            $data
        );

        return redirect()->back()->with('success', 'Setting lembaga berhasil disimpan');
    }
}
