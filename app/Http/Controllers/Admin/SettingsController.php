<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\ActivityService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = array_merge(SystemSetting::defaults(), SystemSetting::getAll());
        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $keys = array_keys(SystemSetting::defaults());
        foreach ($keys as $key) {
            if ($request->has($key)) {
                SystemSetting::setValue($key, $request->input($key));
            }
        }
        ActivityService::log('Konfigurasi sistem diperbarui', 'admin.settings', 'system');
        return redirect()->route('admin.settings')->with('success', 'Pengaturan berhasil disimpan!');
    }
}
