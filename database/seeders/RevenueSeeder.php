<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Revenue;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RevenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $photographers = User::whereHas('photographerProfile', function ($query) {
            $query->where('status', 'approved');
        })->get();

        if ($photographers->isEmpty()) {
            $this->command->warn('⚠️  No approved photographers found. Skipping RevenueSeeder.');
            return;
        }

        $totalRevenues = 0;

        // Generate revenues for the last 12 months
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->startOfMonth();
        }

        foreach ($photographers as $photographer) {
            foreach ($months as $month) {
                // Get all completed order items for this photographer in this month
                $orderItems = OrderItem::where('photographer_id', $photographer->id)
                    ->whereHas('order', function ($query) use ($month) {
                        $query->where('payment_status', 'completed')
                            ->whereYear('paid_at', $month->year)
                            ->whereMonth('paid_at', $month->month);
                    })
                    ->get();

                if ($orderItems->isEmpty()) {
                    continue;
                }

                $totalSales = $orderItems->sum('price');
                $commission = $orderItems->sum('platform_commission');
                $netRevenue = $orderItems->sum('photographer_amount');
                $salesCount = $orderItems->count();
                $photosSold = $orderItems->pluck('photo_id')->unique()->count();

                // Calculate available vs pending balance (30-day security period)
                $monthEnd = $month->copy()->endOfMonth();
                $thirtyDaysAgo = now()->subDays(30);

                $availableItems = $orderItems->filter(function ($item) use ($thirtyDaysAgo) {
                    return $item->order->paid_at <= $thirtyDaysAgo;
                });

                $pendingItems = $orderItems->filter(function ($item) use ($thirtyDaysAgo) {
                    return $item->order->paid_at > $thirtyDaysAgo;
                });

                $availableBalance = $availableItems->sum('photographer_amount');
                $pendingBalance = $pendingItems->sum('photographer_amount');

                // Calculate withdrawn amount (randomly for demo)
                $withdrawn = rand(0, 10) > 3 ? rand(0, (int)($availableBalance * 0.8)) : 0;

                Revenue::create([
                    'id' => Str::uuid(),
                    'photographer_id' => $photographer->id,
                    'month' => $month,
                    'total_sales' => $totalSales,
                    'commission' => $commission,
                    'net_revenue' => $netRevenue,
                    'available_balance' => max(0, $availableBalance - $withdrawn),
                    'pending_balance' => $pendingBalance,
                    'withdrawn' => $withdrawn,
                    'sales_count' => $salesCount,
                    'photos_sold' => $photosSold,
                ]);

                $totalRevenues++;
            }
        }

        $this->command->info("✅ Revenues seeded: {$totalRevenues} monthly revenue records for {$photographers->count()} photographers");
    }
}
