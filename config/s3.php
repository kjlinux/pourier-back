<?php

return [

    /*
    |--------------------------------------------------------------------------
    | S3 Signed URL Expiration Times
    |--------------------------------------------------------------------------
    |
    | These values control how long S3 signed URLs remain valid before expiring.
    | Times are specified in minutes. Adjust based on your application's needs
    | and security requirements.
    |
    */

    'signed_url_expiration' => [
        // Photo previews with watermark (public display)
        'preview' => env('S3_PREVIEW_EXPIRATION', 60), // 1 hour

        // Photo thumbnails (public display)
        'thumbnail' => env('S3_THUMBNAIL_EXPIRATION', 60), // 1 hour

        // Original photos (after purchase, high-resolution)
        'original' => env('S3_ORIGINAL_EXPIRATION', 1440), // 24 hours

        // User avatars
        'avatar' => env('S3_AVATAR_EXPIRATION', 1440), // 24 hours

        // Cover photos
        'cover' => env('S3_COVER_EXPIRATION', 1440), // 24 hours

        // Invoice PDFs
        'invoice' => env('S3_INVOICE_EXPIRATION', 60), // 1 hour

        // Download links (sent to customers)
        'download' => env('S3_DOWNLOAD_EXPIRATION', 1440), // 24 hours
    ],

];
