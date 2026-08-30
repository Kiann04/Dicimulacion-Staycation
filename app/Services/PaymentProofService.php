<?php

namespace App\Services;

use App\Exceptions\PaymentProofStorageFailure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Stores and serves payment proofs, which are sensitive customer documents.
 *
 * New proofs go to the private disk under an unguessable name. Proofs uploaded
 * before this change still live in public/payment_proofs, so reads fall back
 * there rather than breaking historic records.
 */
class PaymentProofService
{
    public const DISK = 'local';

    public const DIRECTORY = 'payment_proofs';

    /**
     * Store an uploaded proof privately and return the path to record.
     *
     * The local disk is configured with `throw => false`, so a failed write
     * returns false rather than raising. Returning a path for a file that was
     * never written would let a booking be saved claiming a proof it does not
     * have, so the result is checked and the file confirmed present.
     *
     * @throws PaymentProofStorageFailure
     */
    public function store(UploadedFile $file): string
    {
        $name = Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $path = self::DIRECTORY.'/'.$name;

        try {
            $stored = Storage::disk(self::DISK)->putFileAs(self::DIRECTORY, $file, $name);
        } catch (Throwable $exception) {
            throw PaymentProofStorageFailure::whileWriting($exception);
        }

        if ($stored === false || ! Storage::disk(self::DISK)->exists($path)) {
            throw PaymentProofStorageFailure::whileWriting();
        }

        return $path;
    }

    /**
     * Whether the stored proof can still be read from either location.
     */
    public function exists(?string $storedPath): bool
    {
        if (blank($storedPath)) {
            return false;
        }

        return Storage::disk(self::DISK)->exists($this->privatePath($storedPath))
            || is_file($this->legacyPublicPath($storedPath));
    }

    /**
     * Stream the proof to an already-authorized viewer.
     */
    public function response(?string $storedPath): Response
    {
        abort_if(blank($storedPath), 404, 'Payment proof not found.');

        $privatePath = $this->privatePath($storedPath);

        if (Storage::disk(self::DISK)->exists($privatePath)) {
            return Storage::disk(self::DISK)->response($privatePath);
        }

        $legacyPath = $this->legacyPublicPath($storedPath);

        abort_unless(is_file($legacyPath), 404, 'Payment proof not found.');

        return response()->file($legacyPath);
    }

    /**
     * Delete a proof from wherever it is stored.
     */
    public function delete(?string $storedPath): void
    {
        if (blank($storedPath)) {
            return;
        }

        Storage::disk(self::DISK)->delete($this->privatePath($storedPath));

        $legacyPath = $this->legacyPublicPath($storedPath);

        if (is_file($legacyPath)) {
            @unlink($legacyPath);
        }
    }

    private function privatePath(string $storedPath): string
    {
        return self::DIRECTORY.'/'.$this->safeBasename($storedPath);
    }

    private function legacyPublicPath(string $storedPath): string
    {
        return public_path(self::DIRECTORY.'/'.$this->safeBasename($storedPath));
    }

    /**
     * The stored value has historically been a relative path; only ever trust
     * its basename so a crafted value cannot escape the proof directory.
     */
    private function safeBasename(string $storedPath): string
    {
        return basename(strtr($storedPath, ['\\' => '/']));
    }
}
