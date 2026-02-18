<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    use UploadImageTrait;

    public function logoManagement()
    {
        $title = 'Logo Management';
        $logo = Config::where('config_key', 'SITE_LOGO')->first();
        return view('admin.site-settings.logo', compact('title', 'logo'));
    }

    public function saveLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
        ]);

        $config = Config::where('config_key', 'SITE_LOGO')->first();

        if ($request->hasFile('logo')) {
            $previousImage = $config->config_value ?? null;
            $imagePath = $this->uploadImage($request->file('logo'), 'logo', $previousImage);

            Config::updateOrCreate(
                ['config_key' => 'SITE_LOGO'],
                ['config_value' => $imagePath]
            );
        }

        return redirect()->back()->with('notify_success', 'Logo updated successfully!');
    }

    public function details()
    {
        $title = 'Update Details';
        $config = Config::pluck('config_value', 'config_key')->toArray();

        return view('admin.site-settings.details', compact('title', 'config'));
    }

    public function saveDetails(Request $request)
    {
        foreach ($request->all() as $field => $value) {
            Config::updateOrCreate(
                ['config_key' => $field],
                ['config_value' => $value]
            );
        }

        return redirect()->back()->with('notify_success', 'Details updated successfully!');
    }
}
