<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Tạo lịch sử đơn hàng demo cho nhiều khách hàng.
 *
 * Mỗi user được thiết kế để mua sản phẩm nhất quán trong 1-2 danh mục,
 * đảm bảo Leave-One-Out evaluation trong evaluate_accuracy.py cho kết quả
 * có ý nghĩa thống kê (Hit Rate@K tăng đáng kể so với dataset chỉ có 1 user).
 *
 * Pattern mua hàng:
 *   alice — chủ yếu Thời trang (3 đơn hàng trải dài 90 ngày)
 *   bob   — chủ yếu Điện tử   (3 đơn hàng trải dài 90 ngày)
 *   carol — Sách + Gia dụng   (3 đơn hàng trải dài 90 ngày)
 *   dave  — đa danh mục       (4 đơn hàng trải dài 120 ngày)
 */
class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $users = $this->createUsers();
        $products = Product::whereNull('deleted_at')
            ->where('status', true)
            ->pluck('id', 'slug');

        $this->seedAlice($users['alice'], $products);
        $this->seedBob($users['bob'], $products);
        $this->seedCarol($users['carol'], $products);
        $this->seedDave($users['dave'], $products);
    }

    // ─────────────────────────────────────────────────────────────────── //
    // Users                                                               //
    // ─────────────────────────────────────────────────────────────────── //

    private function createUsers(): array
    {
        $defs = [
            'alice' => ['email' => 'alice@smartshop.local',  'name' => 'Alice Nguyễn', 'phone' => '0911111111'],
            'bob'   => ['email' => 'bob@smartshop.local',    'name' => 'Bob Trần',     'phone' => '0922222222'],
            'carol' => ['email' => 'carol@smartshop.local',  'name' => 'Carol Lê',     'phone' => '0933333333'],
            'dave'  => ['email' => 'dave@smartshop.local',   'name' => 'Dave Phạm',    'phone' => '0944444444'],
        ];

        $created = [];
        foreach ($defs as $key => $attrs) {
            $created[$key] = User::updateOrCreate(
                ['email' => $attrs['email']],
                [
                    'name'      => $attrs['name'],
                    'password'  => Hash::make('password'),
                    'role'      => 'customer',
                    'phone'     => $attrs['phone'],
                    'address'   => '123 Đường Demo, TP.HCM',
                    'is_active' => true,
                ]
            );
        }

        return $created;
    }

    // ─────────────────────────────────────────────────────────────────── //
    // Alice — Thời trang                                                  //
    // Order 1 (90 ngày trước): Áo Thun Unisex Basic     ← training       //
    // Order 2 (60 ngày trước): Quần Jean Nam Slim Fit   ← training       //
    // Order 3 (30 ngày trước): Đầm Hoa Nữ Mùa Hè       ← held-out (LOO) //
    // Taste profile từ Áo Thun + Quần Jean → nên predict Đầm Hoa         //
    // ─────────────────────────────────────────────────────────────────── //
    private function seedAlice(User $user, $products): void
    {
        $orders = [
            [
                'days_ago' => 90,
                'items'    => [
                    ['slug' => 'ao-thun-unisex-basic',  'qty' => 2],
                ],
            ],
            [
                'days_ago' => 60,
                'items'    => [
                    ['slug' => 'quan-jean-nam-slim-fit', 'qty' => 1],
                ],
            ],
            [
                'days_ago' => 30,
                'items'    => [
                    ['slug' => 'dam-hoa-nu-mua-he',      'qty' => 1],
                ],
            ],
        ];

        $this->createOrders($user, $orders, $products);
    }

    // ─────────────────────────────────────────────────────────────────── //
    // Bob — Điện tử                                                        //
    // Order 1 (90 ngày trước): Pin Dự Phòng 10000mAh   ← training        //
    // Order 2 (60 ngày trước): Tai Nghe Bluetooth SOMO ← training        //
    // Order 3 (30 ngày trước): Cáp Sạc Nhanh USB-C     ← held-out (LOO) //
    // ─────────────────────────────────────────────────────────────────── //
    private function seedBob(User $user, $products): void
    {
        $orders = [
            [
                'days_ago' => 90,
                'items'    => [
                    ['slug' => 'pin-du-phong-10000mah',         'qty' => 1],
                ],
            ],
            [
                'days_ago' => 60,
                'items'    => [
                    ['slug' => 'tai-nghe-bluetooth-somo-x3',    'qty' => 1],
                ],
            ],
            [
                'days_ago' => 30,
                'items'    => [
                    ['slug' => 'cap-sac-nhanh-usb-c-1m',        'qty' => 2],
                ],
            ],
        ];

        $this->createOrders($user, $orders, $products);
    }

    // ─────────────────────────────────────────────────────────────────── //
    // Carol — Sách + Gia dụng                                             //
    // Order 1 (75 ngày trước): Đắc Nhân Tâm             ← training       //
    // Order 2 (45 ngày trước): Bình Giữ Nhiệt Inox      ← training       //
    // Order 3 (15 ngày trước): Sapiens Lược Sử Loài Người ← held-out     //
    // Taste profile = Đắc Nhân Tâm + Bình Giữ Nhiệt; Sapiens gần Đắc Nhân Tâm hơn //
    // ─────────────────────────────────────────────────────────────────── //
    private function seedCarol(User $user, $products): void
    {
        $orders = [
            [
                'days_ago' => 75,
                'items'    => [
                    ['slug' => 'dac-nhan-tam',                    'qty' => 1],
                ],
            ],
            [
                'days_ago' => 45,
                'items'    => [
                    ['slug' => 'binh-giu-nhiet-inox-500ml',       'qty' => 1],
                ],
            ],
            [
                'days_ago' => 15,
                'items'    => [
                    ['slug' => 'sapiens-luoc-su-loai-nguoi',      'qty' => 1],
                ],
            ],
        ];

        $this->createOrders($user, $orders, $products);
    }

    // ─────────────────────────────────────────────────────────────────── //
    // Dave — đa danh mục                                                   //
    // Order 1 (120 ngày): Kem Dưỡng Ẩm + Áo Khoác Denim  ← training     //
    // Order 2 (80 ngày): Dây Nhảy + Găng Tay Gym         ← training     //
    // Order 3 (40 ngày): Son Môi Lì Màu Đỏ Đất           ← held-out     //
    // Taste profile = đa dạng (beauty+fashion+sports)                    //
    // ─────────────────────────────────────────────────────────────────── //
    private function seedDave(User $user, $products): void
    {
        $orders = [
            [
                'days_ago' => 120,
                'items'    => [
                    ['slug' => 'kem-duong-am-han-quoc-spf50',    'qty' => 1],
                    ['slug' => 'ao-khoac-denim-unisex',          'qty' => 1],
                ],
            ],
            [
                'days_ago' => 80,
                'items'    => [
                    ['slug' => 'day-nhay-the-thao-cao-cap',      'qty' => 1],
                    ['slug' => 'gang-tay-tap-gym-nu',            'qty' => 1],
                ],
            ],
            [
                'days_ago' => 40,
                'items'    => [
                    ['slug' => 'son-moi-li-mau-do-dat',          'qty' => 1],
                ],
            ],
        ];

        $this->createOrders($user, $orders, $products);
    }

    // ─────────────────────────────────────────────────────────────────── //
    // Helpers                                                             //
    // ─────────────────────────────────────────────────────────────────── //

    private function createOrders(User $user, array $orderDefs, $products): void
    {
        foreach ($orderDefs as $def) {
            $orderedAt = now()->subDays($def['days_ago']);

            // Tính tổng đơn hàng từ sản phẩm thực tế
            $subtotal = 0;
            $lineItems = [];

            foreach ($def['items'] as $item) {
                $productId = $products[$item['slug']] ?? null;
                if ($productId === null) {
                    continue; // Bỏ qua nếu sản phẩm không tồn tại
                }

                $product = Product::find($productId);
                if ($product === null) {
                    continue;
                }

                $price     = (float) ($product->sale_price ?? $product->price);
                $lineTotal = $price * $item['qty'];
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'price'        => $price,
                    'quantity'     => $item['qty'],
                ];
            }

            if (empty($lineItems)) {
                continue;
            }

            $shippingFee = 30000;
            $total       = $subtotal + $shippingFee;

            $order = Order::create([
                'user_id'          => $user->id,
                'subtotal'         => $subtotal,
                'discount'         => 0,
                'shipping_fee'     => $shippingFee,
                'total'            => $total,
                'status'           => 'delivered',
                'payment_method'   => 'cod',
                'payment_status'   => 'paid',
                'shipping_address' => $user->address ?? '123 Đường Demo, TP.HCM',
                'phone'            => $user->phone,
                'recipient_name'   => $user->name,
                'created_at'       => $orderedAt,
                'updated_at'       => $orderedAt,
            ]);

            foreach ($lineItems as $line) {
                OrderItem::create(array_merge($line, ['order_id' => $order->id]));
            }
        }
    }
}
