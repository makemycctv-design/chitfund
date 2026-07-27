<?php

namespace App\Notifications\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Default push provider (log driver). Real delivery uses Expo push (mobile app,
 * Phase 5) or FCM/APNs; this keeps the pipeline working without a provider.
 */
class LogPushProvider implements PushProvider
{
    public function send(string $deviceToken, string $title, string $body): string
    {
        Log::channel(config('logging.default'))->info('Push (log driver)', [
            'token' => $deviceToken,
            'title' => $title,
            'body' => $body,
        ]);

        return 'log-'.Str::ulid();
    }
}
