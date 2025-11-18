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
use OpenApi\Annotations as OA;

class DownloadController extends Controller
{
    public function __construct(
        protected StorageService $storageService,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/downloads/photo/{photo}",
     *     operationId="getDownloadsPhoto",
     *     tags={"Downloads"},
     *     summary="Download purchased photo (high-resolution)",
     *     description="Download the high-resolution version of a purchased photo. User must have completed order containing this photo.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="photo",
     *         in="path",
     *         description="Photo UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo file download (streamed)",
     *         @OA\MediaType(
     *             mediaType="image/jpeg",
     *             @OA\Schema(type="string", format="binary")
     *         ),
     *         @OA\Header(
     *             header="Content-Disposition",
     *             description="Attachment filename",
     *             @OA\Schema(type="string", example="attachment; filename=photo.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - photo not purchased",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You have not purchased this photo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Photo file not found on server",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Photo file not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/downloads/order/{order}",
     *     operationId="getDownloadsOrder",
     *     tags={"Downloads"},
     *     summary="Download all photos from order (ZIP)",
     *     description="Download all high-resolution photos from a completed order as a ZIP archive. User must be order owner.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ZIP file download (streamed)",
     *         @OA\MediaType(
     *             mediaType="application/zip",
     *             @OA\Schema(type="string", format="binary")
     *         ),
     *         @OA\Header(
     *             header="Content-Disposition",
     *             description="Attachment filename",
     *             @OA\Schema(type="string", example="attachment; filename=order.zip")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - order not completed or not owner",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order is not completed yet")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No photos found in order",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No photos found in this order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error creating ZIP file",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Could not create zip file")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/downloads/invoice/{order}",
     *     operationId="getDownloadsInvoice",
     *     tags={"Downloads"},
     *     summary="Download order invoice (PDF)",
     *     description="Download the PDF invoice for a completed order. User must be order owner.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF invoice download",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         ),
     *         @OA\Header(
     *             header="Content-Disposition",
     *             description="Attachment filename",
     *             @OA\Schema(type="string", example="attachment; filename=invoice.pdf")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - not order owner",
     *         ref="#/components/responses/ForbiddenResponse"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invoice not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/downloads/preview/{photo}",
     *     operationId="getDownloadsPreview",
     *     tags={"Downloads"},
     *     summary="Download watermarked preview (public)",
     *     description="Download a watermarked preview version of any photo. No authentication required. Useful for demos or sharing.",
     *     @OA\Parameter(
     *         name="photo",
     *         in="path",
     *         description="Photo UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Watermarked preview image download",
     *         @OA\MediaType(
     *             mediaType="image/jpeg",
     *             @OA\Schema(type="string", format="binary")
     *         ),
     *         @OA\Header(
     *             header="Content-Disposition",
     *             description="Attachment filename",
     *             @OA\Schema(type="string", example="attachment; filename=preview.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Preview not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Preview not found")
     *         )
     *     )
     * )
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
