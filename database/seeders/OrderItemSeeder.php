<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Photo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::all();

        if ($orders->isEmpty()) {
            $this->command->warn('⚠️  No orders found. Skipping OrderItemSeeder.');
            return;
        }

        $approvedPhotos = Photo::where('status', 'approved')->get();

        if ($approvedPhotos->isEmpty()) {
            $this->command->warn('⚠️  No approved photos found. Skipping OrderItemSeeder.');
            return;
        }

        $totalItems = 0;

        foreach ($orders as $order) {
            // 1-4 items per order
            $itemsCount = rand(1, 4);
            $orderSubtotal = 0;

            $selectedPhotos = $approvedPhotos->random(min($itemsCount, $approvedPhotos->count()));

            foreach ($selectedPhotos as $photo) {
                // 70% standard license, 30% extended
                $licenseType = rand(1, 100) <= 70 ? 'standard' : 'extended';
                $price = $licenseType === 'standard' ? $photo->price_standard : $photo->price_extended;

                // Commission: 80% photographer, 20% platform
                $photographerAmount = round($price * 0.80);
                $platformCommission = $price - $photographerAmount;

                $photographer = $photo->photographer;
                $photographerName = $photographer->first_name . ' ' . $photographer->last_name;

                $isPaid = $order->payment_status === 'completed' && rand(1, 100) <= 70;

                OrderItem::create([
                    'id' => Str::uuid(),
                    'order_id' => $order->id,
                    'photo_id' => $photo->id,
                    'photographer_id' => $photo->photographer_id,
                    'photo_title' => $photo->title,
                    'photo_thumbnail' => $photo->thumbnail_url,
                    'photographer_name' => $photographerName,
                    'license_type' => $licenseType,
                    'price' => $price,
                    'photographer_amount' => $photographerAmount,
                    'platform_commission' => $platformCommission,
                    'download_url' => $order->payment_status === 'completed'
                        ? "https://pourier-downloads.s3.amazonaws.com/photos/{$photo->id}/download"
                        : null,
                    'download_expires_at' => $order->payment_status === 'completed'
                        ? now()->addDays(30)
                        : null,
                    'photographer_paid' => $isPaid,
                    'photographer_paid_at' => $isPaid ? $order->paid_at->addDays(rand(30, 45)) : null,
                ]);

                $orderSubtotal += $price;
                $totalItems++;

                // Update photo stats
                if ($order->payment_status === 'completed') {
                    $photo->increment('sales_count');
                    $photo->increment('downloads_count', rand(1, 3));
                }
            }

            // Update order totals
            $tax = round($orderSubtotal * 0.18);
            $discount = $order->discount;
            $total = $orderSubtotal + $tax - $discount;

            $order->update([
                'subtotal' => $orderSubtotal,
                'tax' => $tax,
                'total' => $total,
            ]);
        }

        $this->command->info("✅ Order items seeded: {$totalItems} items across {$orders->count()} orders");
    }
}
