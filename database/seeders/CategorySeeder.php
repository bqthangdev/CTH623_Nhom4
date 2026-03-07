<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Thời trang',      'slug' => 'thoi-trang',      'sort_order' => 1],
            ['name' => 'Điện tử',         'slug' => 'dien-tu',         'sort_order' => 2],
            ['name' => 'Gia dụng',        'slug' => 'gia-dung',        'sort_order' => 3],
            ['name' => 'Sách',            'slug' => 'sach',            'sort_order' => 4],
            ['name' => 'Thể thao',        'slug' => 'the-thao',        'sort_order' => 5],
            ['name' => 'Làm đẹp',         'slug' => 'lam-dep',         'sort_order' => 6],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(['slug' => $data['slug']], array_merge($data, ['is_active' => true]));
        }

        // Sub-categories
        $electronics = Category::where('slug', 'dien-tu')->first();
        if ($electronics) {
            $subs = [
                ['name' => 'Điện thoại', 'slug' => 'dien-thoai', 'sort_order' => 1],
                ['name' => 'Laptop',     'slug' => 'laptop',      'sort_order' => 2],
                ['name' => 'Tai nghe',   'slug' => 'tai-nghe',    'sort_order' => 3],
            ];
            foreach ($subs as $sub) {
                Category::updateOrCreate(
                    ['slug' => $sub['slug']],
                    array_merge($sub, ['parent_id' => $electronics->id, 'is_active' => true])
                );
            }
        }
    }
}
