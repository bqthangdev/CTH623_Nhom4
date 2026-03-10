<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            ['title' => 'Khuyến mãi mùa hè — Giảm đến 50%',   'image' => null, 'sort_order' => 1],
            ['title' => 'Bộ sưu tập mới — Thời trang Thu Đông', 'image' => null, 'sort_order' => 2],
            ['title' => 'Miễn phí vận chuyển toàn quốc',        'image' => null, 'sort_order' => 3],
        ];

        foreach ($banners as $data) {
            Banner::updateOrCreate(
                ['title' => $data['title']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
