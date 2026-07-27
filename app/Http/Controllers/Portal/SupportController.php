<?php

namespace App\Http\Controllers\Portal;

use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public function index(Request $request): Response
    {
        $tickets = SupportTicket::where('customer_id', $request->user()->id)
            ->latest('last_message_at')
            ->get()
            ->map(fn (SupportTicket $t) => [
                'id' => $t->ulid,
                'reference' => $t->reference,
                'subject' => $t->subject,
                'status' => $t->status->value,
                'priority' => $t->priority,
                'lastMessageAt' => $t->last_message_at?->toIso8601String(),
            ]);

        return Inertia::render('portal/support/index', ['tickets' => $tickets]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $count = SupportTicket::where('company_id', $user->company_id)->count();

        $ticket = SupportTicket::create([
            'company_id' => $user->company_id,
            'customer_id' => $user->id,
            'branch_id' => $user->branch_id,
            'reference' => 'TKT-'.str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT),
            'subject' => $validated['subject'],
            'category' => $validated['category'] ?? null,
            'priority' => $validated['priority'],
            'status' => SupportTicketStatus::Open->value,
            'last_message_at' => now(),
        ]);

        $ticket->messages()->create([
            'ulid' => (string) Str::ulid(),
            'user_id' => $user->id,
            'body' => $validated['message'],
            'is_staff' => false,
        ]);

        return redirect()->route('portal.support.show', $ticket)->with('success', 'Ticket created.');
    }

    public function show(Request $request, SupportTicket $ticket): Response
    {
        abort_unless($ticket->customer_id === $request->user()->id, 403);
        $ticket->load('messages.author:id,name');

        return Inertia::render('portal/support/show', [
            'ticket' => $this->present($ticket),
        ]);
    }

    public function message(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->customer_id === $request->user()->id, 403);
        $validated = $request->validate(['message' => ['required', 'string', 'max:5000']]);

        $ticket->messages()->create([
            'ulid' => (string) Str::ulid(),
            'user_id' => $request->user()->id,
            'body' => $validated['message'],
            'is_staff' => false,
        ]);
        $ticket->update(['status' => SupportTicketStatus::Open->value, 'last_message_at' => now()]);

        return back()->with('success', 'Message sent.');
    }

    private function present(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->ulid,
            'reference' => $ticket->reference,
            'subject' => $ticket->subject,
            'status' => $ticket->status->value,
            'priority' => $ticket->priority,
            'messages' => $ticket->messages->map(fn ($m) => [
                'id' => $m->ulid,
                'author' => $m->author?->name,
                'isStaff' => $m->is_staff,
                'body' => $m->body,
                'at' => $m->created_at?->toIso8601String(),
            ]),
        ];
    }
}
