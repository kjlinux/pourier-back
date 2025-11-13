<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Photo;
use App\Services\InvoiceService;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DownloadController extends Controller
{
    public function __construct(
        protected StorageService $storageService,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Download a single photo
     */
    public function downloadPhoto(Request $request, Photo $photo): StreamedResponse
    {
        Gate::authorize('download', $photo);

        // Vérifier si l'utilisateur a acheté cette photo
        $hasPurchased = Order::where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->whereHas('items', function ($query) use ($photo) {
                $query->where('photo_id', $photo->id);
            })
            ->exists();

        if (!$hasPurchased) {
            abort(403, 'You have not purchased this photo');
        }

        $filePath = $this->storageService->getHighResPath($photo);

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'Photo file not found');
        }

        return Storage::disk('public')->download(
            $filePath,
            $photo->title . '.jpg',
            ['Content-Type' => 'image/jpeg']
        );
    }

    /**
     * Download all photos from an order
     */
    public function downloadOrder(Request $request, Order $order): StreamedResponse
    {
        Gate::authorize('view', $order);

        if ($order->status !== 'completed') {
            abort(403, 'Order is not completed yet');
        }

        $photos = $order->items()->with('photo')->get()->pluck('photo');

        if ($photos->isEmpty()) {
            abort(404, 'No photos found in this order');
        }

        // Créer un fichier ZIP temporaire
        $zipFileName = 'order_' . $order->order_number . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // S'assurer que le dossier temp existe
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create zip file');
        }

        foreach ($photos as $photo) {
            $filePath = $this->storageService->getHighResPath($photo);
            $fullPath = Storage::disk('public')->path($filePath);

            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $photo->title . '_' . $photo->id . '.jpg');
            }
        }

        $zip->close();

        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
            unlink($zipPath);
        }, $zipFileName, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Download invoice for an order
     */
    public function downloadInvoice(Request $request, Order $order): StreamedResponse
    {
        Gate::authorize('view', $order);

        if (!$this->invoiceService->hasInvoice($order)) {
            abort(404, 'Invoice not found');
        }

        $invoicePath = $this->invoiceService->getInvoicePath($order);

        return response()->download(
            $invoicePath,
            'invoice_' . $order->order_number . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Download watermarked preview (for non-purchased photos)
     */
    public function downloadPreview(Photo $photo): StreamedResponse
    {
        $filePath = $this->storageService->getWatermarkPath($photo);

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'Preview not found');
        }

        return Storage::disk('public')->download(
            $filePath,
            'preview_' . $photo->title . '.jpg',
            ['Content-Type' => 'image/jpeg']
        );
    }
}
