<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes the administrative audit trail. Failures are logged rather than thrown:
 * an audit write must never be the reason a booking approval rolls back.
 */
class AuditLogger
{
    public function record(?User $actor, string $action, string $description): void
    {
        try {
            AuditLog::create([
                'user_id' => $actor?->getKey(),
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Failed to write audit log entry.', [
                'action' => $action,
                'description' => $description,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
