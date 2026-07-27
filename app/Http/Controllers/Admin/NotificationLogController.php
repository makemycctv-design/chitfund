<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('notification-templates.manage') || $request->user()->can('audit-logs.view'), 403);

        $logs = NotificationLog::where('company_id', (int) $request->user()->company_id)
            ->with('user:id,name')
            ->when($request->string('channel')->toString(), fn ($q, $c) => $q->where('channel', $c))
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (NotificationLog $l) => [
                'id' => $l->ulid,
                'user' => $l->user?->name,
                'eventKey' => $l->event_key,
                'channel' => $l->channel,
                'status' => $l->status->value,
                'providerMessageId' => $l->provider_message_id,
                'error' => $l->error,
                'sentAt' => $l->sent_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/notifications/logs', [
            'logs' => $logs,
            'filters' => $request->only(['channel', 'status']),
        ]);
    }
}
