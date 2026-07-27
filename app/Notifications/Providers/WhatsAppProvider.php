<?php

namespace App\Notifications\Providers;

/**
 * Provider-agnostic WhatsApp sender. Concrete drivers wrap Meta WhatsApp Cloud
 * API, Twilio, Interakt, Gupshup, etc. The app codes against this interface so
 * the provider is swappable and secrets stay server-side.
 */
interface WhatsAppProvider
{
    /**
     * Send a text message. Returns a provider message id on success.
     *
     * @throws \RuntimeException on delivery failure
     */
    public function send(string $toPhone, string $message): string;
}
