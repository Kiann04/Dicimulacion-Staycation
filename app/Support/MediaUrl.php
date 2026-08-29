<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Turns a stored image path into an absolute URL the Next.js client can put
 * straight into an <img src>.
 *
 * Public staycation images are uploaded to the "public" disk and are therefore
 * served from /storage/<path>, which is what every Blade view already does via
 * asset('storage/'.$path). The database only holds the disk-relative part
 * ("staycations/1234.jpg"), so the prefix has to be re-applied here; without it
 * the API hands out URLs that 404. Older rows that already carry the prefix, and
 * any row holding a full URL from an external host, are passed through unchanged.
 *
 * Payment proofs never go through this helper - they live on a private disk and
 * are streamed by PaymentProofController after an authorization check.
 */
class MediaUrl
{
    public static function public(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (! Str::startsWith($path, 'storage/')) {
            $path = 'storage/'.$path;
        }

        return asset($path);
    }
}
