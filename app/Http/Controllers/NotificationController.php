<?php

namespace App\Http\Controllers;

use App\Enums\NotificationChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * In-app notification center + channel preferences. Works for both staff and
 * customers (the shared app layout adapts the surrounding chrome by user type).
 */
class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->paginate(20)
            ->through(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'url' => $n->data['url'] ?? null,
                'read' => $n->read_at !== null,
                'createdAt' => $n->created_at?->toIso8601String(),
            ]);

        $prefs = $user->notificationPreferences->pluck('enabled', 'channel');
        $preferences = collect(NotificationChannel::cases())->map(fn ($c) => [
            'channel' => $c->value,
            'label' => $c->label(),
            'enabled' => (bool) ($prefs[$c->value] ?? true),
        ]);

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
            'preferences' => $preferences,
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->where('id', $notification)->first()?->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked read.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*' => ['boolean'],
        ]);

        foreach ($validated['preferences'] as $channel => $enabled) {
            if (! in_array($channel, NotificationChannel::values(), true)) {
                continue;
            }
            $request->user()->notificationPreferences()->updateOrCreate(
                ['channel' => $channel],
                ['enabled' => (bool) $enabled],
            );
        }

        return back()->with('success', 'Notification preferences updated.');
    }
}
