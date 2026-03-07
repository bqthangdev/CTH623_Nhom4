<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::whereNull('parent_id')->pluck('id', 'slug');

        $products = [
            // Thời trang
            ['name' => 'Áo Thun Unisex Basic',     'category_slug' => 'thoi-trang', 'price' => 199000,  'sale_price' => 149000, 'stock' => 50, 'is_featured' => true],
            ['name' => 'Quần Jean Nam Slim Fit',    'category_slug' => 'thoi-trang', 'price' => 450000,  'sale_price' => null,   'stock' => 30, 'is_featured' => false],
            ['name' => 'Đầm Hoa Nữ Mùa Hè',        'category_slug' => 'thoi-trang', 'price' => 380000,  'sale_price' => 299000, 'stock' => 25, 'is_featured' => true],
            ['name' => 'Áo Khoác Denim Unisex',     'category_slug' => 'thoi-trang', 'price' => 650000,  'sale_price' => null,   'stock' => 20, 'is_featured' => false],

            // Điện tử
            ['name' => 'Tai Nghe Bluetooth SOMO X3', 'category_slug' => 'dien-tu',  'price' => 890000,   'sale_price' => 750000, 'stock' => 15, 'is_featured' => true],
            ['name' => 'Cáp Sạc Nhanh USB-C 1m',    'category_slug' => 'dien-tu',  'price' => 120000,   'sale_price' => null,   'stock' => 100,'is_featured' => false],
            ['name' => 'Pin Dự Phòng 10000mAh',      'category_slug' => 'dien-tu',  'price' => 350000,   'sale_price' => 280000, 'stock' => 40, 'is_featured' => true],
            ['name' => 'Đèn LED Để Bàn Cảm Ứng',    'category_slug' => 'gia-dung',  'price' => 250000,  'sale_price' => null,   'stock' => 35, 'is_featured' => false],

            // Gia dụng
            ['name' => 'Bình Giữ Nhiệt Inox 500ml',  'category_slug' => 'gia-dung', 'price' => 180000,  'sale_price' => 149000, 'stock' => 80, 'is_featured' => false],
            ['name' => 'Nồi Cơm Điện Mini 1L',        'category_slug' => 'gia-dung', 'price' => 420000, 'sale_price' => null,   'stock' => 18, 'is_featured' => true],

            // Sách
            ['name' => 'Đắc Nhân Tâm',                'category_slug' => 'sach',    'price' => 88000,   'sale_price' => 79000,  'stock' => 200,'is_featured' => true],
            ['name' => 'Sapiens: Lược Sử Loài Người', 'category_slug' => 'sach',    'price' => 145000,  'sale_price' => null,   'stock' => 60, 'is_featured' => false],

            // Thể thao
            ['name' => 'Dây Nhảy Thể Thao Cao Cấp',  'category_slug' => 'the-thao', 'price' => 85000,  'sale_price' => null,   'stock' => 70, 'is_featured' => false],
            ['name' => 'Găng Tay Tập Gym Nữ',         'category_slug' => 'the-thao', 'price' => 130000, 'sale_price' => 99000,  'stock' => 45, 'is_featured' => true],

            // Làm đẹp
            ['name' => 'Kem Dưỡng Ẩm Hàn Quốc SPF50', 'category_slug' => 'lam-dep', 'price' => 320000, 'sale_price' => 265000, 'stock' => 55, 'is_featured' => true],
            ['name' => 'Son Môi Lì Màu Đỏ Đất',        'category_slug' => 'lam-dep', 'price' => 189000, 'sale_price' => null,   'stock' => 90, 'is_featured' => false],
        ];

        foreach ($products as $data) {
            $categoryId = $categories[$data['category_slug']] ?? $categories->first();

            $product = Product::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name'        => $data['name'],
                    'slug'        => Str::slug($data['name']),
                    'category_id' => $categoryId,
                    'price'       => $data['price'],
                    'sale_price'  => $data['sale_price'],
                    'stock'       => $data['stock'],
                    'status'      => true,
                    'is_featured' => $data['is_featured'],
                    'description' => 'Sản phẩm chất lượng cao, được lựa chọn kỹ càng cho khách hàng SmartShop.',
                ]
            );

            // Placeholder image if no images yet
            if ($product->images()->doesntExist()) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'products/placeholder.png',
                    'is_primary' => true,
                ]);
            }
        }
    }
}
