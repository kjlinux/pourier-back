<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueService
{
    /**
     * Période de sécurité en jours (30 jours)
     */
    const SECURITY_PERIOD_DAYS = 30;

    /**
     * Get photographer available revenue (after security period)
     */
    public function getAvailableRevenue(User $photographer): float
    {
        $securityDate = Carbon::now()->subDays(self::SECURITY_PERIOD_DAYS);

        return OrderItem::whereHas('photo', function ($query) use ($photographer) {
                $query->where('photographer_id', $photographer->id);
            })
            ->whereHas('order', function ($query) use ($securityDate) {
                $query->where('status', 'completed')
                    ->where('completed_at', '<=', $securityDate);
            })
            ->where('photographer_paid', false)
            ->sum('photographer_amount');
    }

    /**
     * Get photographer pending revenue (within security period)
     */
    public function getPendingRevenue(User $photographer): float
    {
        $securityDate = Carbon::now()->subDays(self::SECURITY_PERIOD_DAYS);

        return OrderItem::whereHas('photo', function ($query) use ($photographer) {
                $query->where('photographer_id', $photographer->id);
            })
            ->whereHas('order', function ($query) use ($securityDate) {
                $query->where('status', 'completed')
                    ->where('completed_at', '>', $securityDate);
            })
            ->where('photographer_paid', false)
            ->sum('photographer_amount');
    }

    /**
     * Get photographer total revenue (paid + unpaid)
     */
    public function getTotalRevenue(User $photographer, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = OrderItem::whereHas('photo', function ($query) use ($photographer) {
                $query->where('photographer_id', $photographer->id);
            })
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed');
                
                if ($startDate) {
                    $query->where('completed_at', '>=', $startDate);
                }
                
                if ($endDate) {
                    $query->where('completed_at', '<=', $endDate);
                }
            });

        return $query->sum('photographer_amount');
    }

    /**
     * Get revenue breakdown by period
     */
    public function getRevenueBreakdown(User $photographer): array
    {
        $securityDate = Carbon::now()->subDays(self::SECURITY_PERIOD_DAYS);

        return [
            'available' => $this->getAvailableRevenue($photographer),
            'pending' => $this->getPendingRevenue($photographer),
            'paid' => $this->getPaidRevenue($photographer),
            'total' => $this->getTotalRevenue($photographer),
            'security_date' => $securityDate->toDateString(),
            'security_period_days' => self::SECURITY_PERIOD_DAYS,
        ];
    }

    /**
     * Get photographer paid revenue
     */
    public function getPaidRevenue(User $photographer): float
    {
        return OrderItem::whereHas('photo', function ($query) use ($photographer) {
                $query->where('photographer_id', $photographer->id);
            })
            ->whereHas('order', function ($query) {
                $query->where('status', 'completed');
            })
            ->where('photographer_paid', true)
            ->sum('photographer_amount');
    }

    /**
     * Mark photographer items as paid
     */
    public function markAsPaid(User $photographer, array $orderItemIds = null): int
    {
        $securityDate = Carbon::now()->subDays(self::SECURITY_PERIOD_DAYS);

        $query = OrderItem::whereHas('photo', function ($query) use ($photographer) {
                $query->where('photographer_id', $photographer->id);
            })
            ->whereHas('order', function ($query) use ($securityDate) {
                $query->where('status', 'completed')
                    ->where('completed_at', '<=', $securityDate);
            })
            ->where('photographer_paid', false);

        if ($orderItemIds) {
            $query->whereIn('id', $orderItemIds);
        }

        return $query->update([
            'photographer_paid' => true,
            'photographer_paid_at' => now()
        ]);
    }

    /**
     * Get items ready for payout
     */
    public function getPayoutItems(User $photographer): \Illuminate\Support\Collection
    {
        $securityDate = Carbon::now()->subDays(self::SECURITY_PERIOD_DAYS);

        return OrderItem::with(['order', 'photo'])
            ->whereHas('photo', function ($query) use ($photographer) {
                $query->where('photographer_id', $photographer->id);
            })
            ->whereHas('order', function ($query) use ($securityDate) {
                $query->where('status', 'completed')
                    ->where('completed_at', '<=', $securityDate);
            })
            ->where('photographer_paid', false)
            ->get();
    }

    /**
     * Get revenue statistics
     */
    public function getStatistics(User $photographer): array
    {
        $breakdown = $this->getRevenueBreakdown($photographer);
        
        $totalSales = OrderItem::whereHas('photo', function ($query) use ($photographer) {
                $query->where('photographer_id', $photographer->id);
            })
            ->whereHas('order', function ($query) {
                $query->where('status', 'completed');
            })
            ->count();

        $averagePerSale = $totalSales > 0 ? $breakdown['total'] / $totalSales : 0;

        return [
            'breakdown' => $breakdown,
            'total_sales' => $totalSales,
            'average_per_sale' => round($averagePerSale, 2),
            'last_payout_date' => $this->getLastPayoutDate($photographer),
        ];
    }

    /**
     * Get last payout date
     */
    private function getLastPayoutDate(User $photographer): ?string
    {
        $lastPayout = OrderItem::whereHas('photo', function ($query) use ($photographer) {
                $query->where('photographer_id', $photographer->id);
            })
            ->where('photographer_paid', true)
            ->orderBy('photographer_paid_at', 'desc')
            ->first();

        return $lastPayout?->photographer_paid_at?->toDateString();
    }

    /**
     * Can request payout
     */
    public function canRequestPayout(User $photographer, float $minimumAmount = 50.00): bool
    {
        $available = $this->getAvailableRevenue($photographer);
        return $available >= $minimumAmount;
    }

    /**
     * Get days until amount is available
     */
    public function getDaysUntilAvailable(OrderItem $item): int
    {
        if (!$item->order->completed_at) {
            return self::SECURITY_PERIOD_DAYS;
        }

        $availableDate = $item->order->completed_at->addDays(self::SECURITY_PERIOD_DAYS);
        $now = Carbon::now();

        if ($now->gte($availableDate)) {
            return 0;
        }

        return $now->diffInDays($availableDate);
    }
}
