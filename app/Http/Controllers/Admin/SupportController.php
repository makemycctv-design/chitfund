<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Notifications\SupportTicketUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    private function companyId(Request $request): int
    {
        return (int) $request->user()->company_id;
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('customers.view'), 403);

        $tickets = SupportTicket::where('company_id', $this->companyId($request))
            ->with('customer:id,name')
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->latest('last_message_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (SupportTicket $t) => [
                'id' => $t->ulid,
                'reference' => $t->reference,
                'subject' => $t->subject,
                'customer' => $t->customer?->name,
                'status' => $t->status->value,
                'priority' => $t->priority,
                'lastMessageAt' => $t->last_message_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/support/index', [
            'tickets' => $tickets,
            'filters' => $request->only('status'),
        ]);
    }

    public function show(Request $request, SupportTicket $ticket): Response
    {
        abort_unless($request->user()->can('customers.view'), 403);
        abort_unless($ticket->company_id === $this->companyId($request), 403);
        $ticket->load('messages.author:id,name', 'customer:id,name,email');

        return Inertia::render('admin/support/show', [
            'ticket' => [
                'id' => $ticket->ulid,
                'reference' => $ticket->reference,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
                'priority' => $ticket->priority,
                'customer' => $ticket->customer?->name,
                'messages' => $ticket->messages->map(fn ($m) => [
                    'id' => $m->ulid,
                    'author' => $m->author?->name,
                    'isStaff' => $m->is_staff,
                    'body' => $m->body,
                    'at' => $m->created_at?->toIso8601String(),
                ]),
            ],
            'statuses' => array_map(fn ($s) => $s->value, SupportTicketStatus::cases()),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($request->user()->can('customers.view'), 403);
        abort_unless($ticket->company_id === $this->companyId($request), 403);

        $validated = $request->validate(['message' => ['required', 'string', 'max:5000']]);

        $ticket->messages()->create([
            'ulid' => (string) Str::ulid(),
            'user_id' => $request->user()->id,
            'body' => $validated['message'],
            'is_staff' => true,
        ]);
        $ticket->update([
            'status' => SupportTicketStatus::Pending->value,
            'assigned_to' => $ticket->assigned_to ?? $request->user()->id,
            'last_message_at' => now(),
        ]);

        $ticket->customer?->notify(new SupportTicketUpdated($ticket, 'Support has replied to your ticket.'));

        return back()->with('success', 'Reply sent.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($request->user()->can('customers.view'), 403);
        abort_unless($ticket->company_id === $this->companyId($request), 403);

        $validated = $request->validate(['status' => ['required', Rule::in(array_map(fn ($s) => $s->value, SupportTicketStatus::cases()))]]);
        $ticket->update(['status' => $validated['status']]);

        $ticket->customer?->notify(new SupportTicketUpdated($ticket, "Your ticket status is now {$validated['status']}."));

        return back()->with('success', 'Status updated.');
    }
}
