<?php

namespace App\Notifications\Providers;

interface PushProvider
{
    /**
     * Send a push notification to a device token. Returns a provider ticket id.
     *
     * @throws \RuntimeException on failure
     */
    public function send(string $deviceToken, string $title, string $body): string;
}
