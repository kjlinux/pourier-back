<?php

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\OrderStatusChanged;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderStatusNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function handle(): void
    {
        try {
            if ($this->order->user) {
                $this->order->user->notify(
                    new OrderStatusChanged($this->order, $this->oldStatus, $this->newStatus)
                );

                Log::info('Order status notification sent', [
                    'order_id' => $this->order->id,
                    'user_id' => $this->order->user_id,
                    'old_status' => $this->oldStatus,
                    'new_status' => $this->newStatus
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order status notification', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('OrderStatusNotification job failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage()
        ]);
    }
}
