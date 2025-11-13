<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceService
{
    /**
     * Generate invoice PDF for an order
     */
    public function generateInvoice(Order $order): string
    {
        $order->load(['user', 'items.photo.photographer', 'items.photo.category']);

        $invoiceData = $this->prepareInvoiceData($order);
        
        $pdf = Pdf::loadView('invoices.order', $invoiceData);
        
        $fileName = 'invoices/' . $order->order_number . '_' . now()->format('Y-m-d') . '.pdf';
        
        Storage::disk('local')->put($fileName, $pdf->output());
        
        $order->update([
            'invoice_path' => $fileName,
            'invoice_generated_at' => now()
        ]);
        
        return $fileName;
    }

    /**
     * Generate photographer payout invoice
     */
    public function generatePayoutInvoice(array $payoutData): string
    {
        $pdf = Pdf::loadView('invoices.payout', $payoutData);
        
        $fileName = 'invoices/payout_' . $payoutData['photographer_id'] . '_' . now()->format('Y-m-d_His') . '.pdf';
        
        Storage::disk('local')->put($fileName, $pdf->output());
        
        return $fileName;
    }

    /**
     * Prepare invoice data
     */
    private function prepareInvoiceData(Order $order): array
    {
        return [
            'order' => $order,
            'invoice_number' => $this->generateInvoiceNumber($order),
            'invoice_date' => now()->format('d/m/Y'),
            'company' => [
                'name' => config('app.name'),
                'address' => config('invoice.company.address'),
                'city' => config('invoice.company.city'),
                'postal_code' => config('invoice.company.postal_code'),
                'country' => config('invoice.company.country'),
                'siret' => config('invoice.company.siret'),
                'vat_number' => config('invoice.company.vat_number'),
            ],
            'customer' => [
                'name' => $order->user->name,
                'email' => $order->user->email,
            ],
            'items' => $order->items->map(function ($item) {
                return [
                    'description' => $item->photo->title ?? 'Photo',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total_price,
                ];
            }),
            'subtotal' => $order->subtotal,
            'tax' => $order->tax,
            'total' => $order->total,
        ];
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(Order $order): string
    {
        return 'INV-' . now()->format('Y') . '-' . str_pad($order->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get invoice path for download
     */
    public function getInvoicePath(Order $order): ?string
    {
        if (!$order->invoice_path) {
            return null;
        }

        return Storage::disk('local')->path($order->invoice_path);
    }

    /**
     * Check if invoice exists
     */
    public function hasInvoice(Order $order): bool
    {
        return $order->invoice_path && Storage::disk('local')->exists($order->invoice_path);
    }

    /**
     * Delete invoice
     */
    public function deleteInvoice(Order $order): bool
    {
        if (!$order->invoice_path) {
            return false;
        }

        if (Storage::disk('local')->exists($order->invoice_path)) {
            Storage::disk('local')->delete($order->invoice_path);
        }

        $order->update([
            'invoice_path' => null,
            'invoice_generated_at' => null
        ]);

        return true;
    }
}
