<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Idempotent: re-running updates names/icons but keeps admin ordering and activation.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array<string, mixed>> $groups */
        $groups = require database_path('seeders/data/categories.php');

        foreach ($groups as $groupIndex => $group) {
            $emergency = (bool) ($group['emergency'] ?? false);
            $parent = $this->upsert($group, null, $groupIndex, $emergency);

            foreach ($group['children'] as $childIndex => $child) {
                $this->upsert($child, $parent, $childIndex, $emergency);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsert(array $data, ?Category $parent, int $sortOrder, bool $emergency): Category
    {
        $category = Category::query()->firstOrNew(['slug' => $data['slug']]);

        $category->fill([
            'parent_id' => $parent?->getKey(),
            'name' => ['fr' => $data['fr'], 'ar' => $data['ar'], 'en' => $data['en']],
            'icon' => $data['icon'],
            'is_emergency' => $emergency,
            'schema_type' => $data['schema'] ?? null,
        ]);

        if (! $category->exists) {
            $category->sort_order = $sortOrder;
            $category->is_active = true;
        }

        $category->save();

        return $category;
    }
}
