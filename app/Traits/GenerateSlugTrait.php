<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait GenerateSlugTrait
{
    /**
     * Generate a unique slug for a given model
     *
     * @param string $text The text to generate slug from
     * @param string $modelClass The model class name (e.g., 'App\Models\PackageCategory')
     * @param string $column The column name to check for uniqueness (default: 'slug')
     * @param int|null $ignoreId The ID to ignore when checking uniqueness (for updates)
     * @return string The unique slug
     */
    protected function generateUniqueSlug(string $text, string $modelClass, string $column = 'slug', ?int $ignoreId = null): string
    {
        $slug = Str::slug($text);
        $originalSlug = $slug;
        $counter = 1;

        $query = $modelClass::where($column, $slug);
        
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
            
            $query = $modelClass::where($column, $slug);
            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }
        }

        return $slug;
    }
}
