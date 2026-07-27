<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationChannel;
use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NotificationTemplateController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user()->can('notification-templates.manage'), 403);
    }

    public function index(Request $request): Response
    {
        $this->guard($request);
        $companyId = (int) $request->user()->company_id;

        // Show this company's overrides plus the global defaults.
        $templates = NotificationTemplate::where(fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'))
            ->orderBy('event_key')->orderBy('channel')
            ->get()
            ->map(fn (NotificationTemplate $t) => [
                'id' => $t->ulid,
                'eventKey' => $t->event_key,
                'channel' => $t->channel,
                'name' => $t->name,
                'subject' => $t->subject,
                'body' => $t->body,
                'isActive' => $t->is_active,
                'isGlobal' => $t->company_id === null,
                'whatsappApprovalStatus' => $t->whatsapp_approval_status,
            ]);

        return Inertia::render('admin/notifications/templates', [
            'templates' => $templates,
            'channels' => array_map(fn (NotificationChannel $c) => ['value' => $c->value, 'label' => $c->label()], NotificationChannel::cases()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard($request);
        $companyId = (int) $request->user()->company_id;

        $validated = $request->validate([
            'event_key' => ['required', 'string', 'max:100'],
            'channel' => ['required', Rule::in(NotificationChannel::values())],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        NotificationTemplate::updateOrCreate(
            ['company_id' => $companyId, 'event_key' => $validated['event_key'], 'channel' => $validated['channel']],
            [
                'name' => $validated['name'],
                'subject' => $validated['subject'] ?? null,
                'body' => $validated['body'],
                'is_active' => $validated['is_active'] ?? true,
            ],
        );

        return back()->with('success', 'Template saved.');
    }

    public function update(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $this->guard($request);
        abort_unless($template->company_id === (int) $request->user()->company_id, 403);

        $template->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]));

        return back()->with('success', 'Template updated.');
    }
}
