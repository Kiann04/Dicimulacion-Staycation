<?php

namespace App\Services\Payment;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payment proofs are screenshots of bank and e-wallet transfers. They carry
 * account numbers and customer names, so they are written to a private disk
 * (storage/app/private) under an unguessable name and are only ever released
 * through an authorized controller action — never a public URL.
 *
 * Proofs uploaded before this change live in public/payment_proofs. They are
 * still readable through this class so historic bookings keep working, but no
 * new file is ever written there. See migrateLegacyFile() for moving them.
 */
class PaymentProofStorage
{
    public function disk(): string
    {
        return (string) config('booking.proof_disk');
    }

    private function directory(): string
    {
        return trim((string) config('booking.proof_directory'), '/');
    }

    /**
     * Stores an uploaded proof and returns the disk-relative path to persist.
     * The filename is a random UUID, so a proof cannot be discovered by guessing
     * a booking id or a timestamp.
     */
    public function store(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $filename = Str::uuid()->toString().'.'.$extension;

        $file->storeAs($this->directory(), $filename, ['disk' => $this->disk()]);

        return $this->directory().'/'.$filename;
    }

    public function exists(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        return Storage::disk($this->disk())->exists($path) || $this->legacyPath($path) !== null;
    }

    /**
     * Streams the proof to an already-authorized caller. Returns null when the
     * file is missing so the controller can answer 404 rather than 500.
     */
    public function download(?string $path, ?string $downloadName = null): ?StreamedResponse
    {
        if ($path === null || $path === '') {
            return null;
        }

        $disk = Storage::disk($this->disk());

        if ($disk->exists($path)) {
            return $disk->response($path, $downloadName, ['Cache-Control' => 'private, max-age=0, no-store']);
        }

        $legacy = $this->legacyPath($path);

        if ($legacy !== null) {
            return response()->stream(
                function () use ($legacy): void {
                    readfile($legacy);
                },
                200,
                [
                    'Content-Type' => mime_content_type($legacy) ?: 'application/octet-stream',
                    'Content-Disposition' => 'inline; filename="'.($downloadName ?? basename($legacy)).'"',
                    'Cache-Control' => 'private, max-age=0, no-store',
                ],
            );
        }

        return null;
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk($this->disk())->delete($path);
    }

    /**
     * Resolves a pre-migration proof stored under public/payment_proofs.
     * basename() is applied so a stored value can never traverse out of that
     * directory, even if the database row was tampered with.
     */
    private function legacyPath(string $path): ?string
    {
        $candidate = public_path('payment_proofs'.DIRECTORY_SEPARATOR.basename($path));

        return is_file($candidate) ? $candidate : null;
    }
}
