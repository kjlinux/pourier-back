<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = User::where('account_type', 'buyer')
            ->where('is_verified', true)
            ->get();

        if ($buyers->isEmpty()) {
            $this->command->warn('⚠️  No verified buyers found. Skipping OrderSeeder.');
            return;
        }

        $paymentMethods = [
            'mobile_money' => ['Orange Money', 'Moov Money', 'Wave', 'MTN Mobile Money'],
            'card' => ['Visa', 'Mastercard'],
        ];

        $totalOrders = 40;
        $completedCount = 0;
        $pendingCount = 0;
        $failedCount = 0;

        for ($i = 0; $i < $totalOrders; $i++) {
            $buyer = $buyers->random();
            $orderDate = now()->subDays(rand(1, 120));

            // 70% completed, 15% pending, 10% failed, 5% refunded
            $statusRand = rand(1, 100);
            if ($statusRand <= 70) {
                $status = 'completed';
                $completedCount++;
            } elseif ($statusRand <= 85) {
                $status = 'pending';
                $pendingCount++;
            } elseif ($statusRand <= 95) {
                $status = 'failed';
                $failedCount++;
            } else {
                $status = 'refunded';
            }

            // Mobile money plus populaire au Burkina (80%)
            $paymentMethod = rand(1, 100) <= 80 ? 'mobile_money' : 'card';
            $paymentProvider = $paymentMethods[$paymentMethod][array_rand($paymentMethods[$paymentMethod])];

            // Subtotal will be calculated in OrderItemSeeder
            $subtotal = rand(1000, 50000);
            $tax = round($subtotal * 0.18); // TVA 18% au Burkina Faso
            $discount = rand(0, 10) > 7 ? rand(500, 5000) : 0;
            $total = $subtotal + $tax - $discount;

            $orderNumber = 'ORD-' . $orderDate->format('Ymd') . '-' . strtoupper(Str::random(6));

            Order::create([
                'id' => Str::uuid(),
                'user_id' => $buyer->id,
                'order_number' => $orderNumber,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'payment_status' => $status,
                'payment_method' => $paymentMethod,
                'payment_id' => $status === 'completed' ? 'pay_' . Str::random(24) : null,
                'cinetpay_transaction_id' => $status === 'completed' ? 'CPY_' . rand(100000, 999999) : null,
                'billing_email' => $buyer->email,
                'billing_first_name' => $buyer->first_name,
                'billing_last_name' => $buyer->last_name,
                'billing_phone' => $buyer->phone,
                'invoice_url' => $status === 'completed' ? "https://pouire-invoices.s3.amazonaws.com/invoices/{$orderNumber}.pdf" : null,
                'invoice_path' => $status === 'completed' ? "invoices/{$orderNumber}.pdf" : null,
                'invoice_generated_at' => $status === 'completed' ? $orderDate->addMinutes(5) : null,
                'paid_at' => $status === 'completed' ? $orderDate->addMinutes(2) : null,
                'created_at' => $orderDate,
                'updated_at' => $orderDate->addMinutes(rand(1, 10)),
            ]);
        }

        $this->command->info("✅ Orders seeded: {$totalOrders} total ({$completedCount} completed, {$pendingCount} pending, {$failedCount} failed)");
    }
}
