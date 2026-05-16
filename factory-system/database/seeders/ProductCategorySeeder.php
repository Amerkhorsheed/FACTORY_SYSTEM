<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds default product categories.
 */
class ProductCategorySeeder extends Seeder
{
    /** @var array<int, array<string, mixed>> */
    private const CATEGORIES = [
        ['name' => 'مواد غذائية', 'sort_order' => 1],
        ['name' => 'مشروبات', 'sort_order' => 2],
        ['name' => 'منظفات ومواد تنظيف', 'sort_order' => 3],
        ['name' => 'مواد بناء', 'sort_order' => 4],
        ['name' => 'أدوات ومعدات', 'sort_order' => 5],
        ['name' => 'مواد خام', 'sort_order' => 6],
        ['name' => 'منتجات طازجة', 'sort_order' => 7],
        ['name' => 'متنوع', 'sort_order' => 8],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            ProductCategory::firstOrCreate(
                ['name' => $category['name']],
                array_merge($category, ['is_active' => true])
            );
        }

        $this->command->info('Product categories seeded ('.count(self::CATEGORIES).').');
    }
}
