<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Notifications\NewSale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NewSaleNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public Order $order
    ) {}

    public function handle(): void
    {
        try {
            $photographers = $this->order->items()
                ->with('photo.photographer')
                ->get()
                ->pluck('photo.photographer')
                ->unique('id')
                ->filter();

            foreach ($photographers as $photographer) {
                $photographer->notify(new NewSale($this->order));
            }

            Log::info('New sale notifications sent', [
                'order_id' => $this->order->id,
                'photographers_count' => $photographers->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send new sale notification', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('NewSaleNotification job failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage()
        ]);
    }
}
