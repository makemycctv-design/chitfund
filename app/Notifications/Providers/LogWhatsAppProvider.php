<?php

namespace App\Notifications\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Default WhatsApp provider used when no real provider is configured (local /
 * this sandbox). It "delivers" by logging and returns a synthetic message id so
 * the rest of the pipeline (logging, status) behaves identically to production.
 */
class LogWhatsAppProvider implements WhatsAppProvider
{
    public function send(string $toPhone, string $message): string
    {
        Log::channel(config('logging.default'))->info('WhatsApp (log driver)', [
            'to' => $toPhone,
            'message' => $message,
        ]);

        return 'log-'.Str::ulid();
    }
}
