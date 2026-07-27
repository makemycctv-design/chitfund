<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payments\ProcessGatewayWebhook;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public gateway webhook receiver. No session/token auth — authenticity is
 * proven by the provider's signature verified against the RAW request body.
 * Payments are only marked successful here (server-to-server), never via a
 * browser redirect.
 */
class WebhookController extends Controller
{
    public function handle(Request $request, string $gateway, ProcessGatewayWebhook $processor): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature')
            ?? $request->header('X-Webhook-Signature', '');

        try {
            $event = $processor->handle($gateway, $payload, (string) $signature);
        } catch (\RuntimeException $e) {
            // Invalid signature or malformed event — do not leak details.
            Log::warning('Rejected payment webhook', ['gateway' => $gateway, 'reason' => $e->getMessage()]);

            return ApiResponse::error('Invalid webhook.', 400);
        }

        return ApiResponse::success(['status' => $event->status]);
    }
}
