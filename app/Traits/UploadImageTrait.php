<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait UploadImageTrait
{
    public function uploadImage($file, string $folder, $previousImage = null, bool $useOriginalName = false)
    {
        if ($previousImage && Storage::disk('public')->exists($previousImage)) {
            Storage::disk('public')->delete($previousImage);
        }

        if ($file instanceof UploadedFile) {
            $filename = $useOriginalName
                ? $file->getClientOriginalName()
                : Str::uuid().'.'.$file->getClientOriginalExtension();

            $folderPath = 'uploads/'.$folder;

            return $file->storeAs($folderPath, $filename, 'public');
        }

        return null;
    }
    
    /**
     * Delete the previous image from storage.
     *
     * @param  string|null  $filePath  The path of the file to be deleted.
     */
    protected function deletePreviousImage(?string $filePath): void
    {
        if ($filePath) {
            Storage::disk('public')->delete($filePath);
        }
    }
}
