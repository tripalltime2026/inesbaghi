<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ClubEvent;
use App\Models\ClubEventResponse;
use App\Models\ClubNotification;
use App\Models\ClubNotificationPreference;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ClubController extends Controller
{
    public function respondToEvent(Request $request, ClubEvent $event): RedirectResponse
    {
        abort_unless($event->status === 'published' && $event->published_at !== null, 404);

        $groupIds = $this->accessibleGroupIds($request);
        abort_if($event->kindergarten_group_id !== null && ! $groupIds->contains($event->kindergarten_group_id), 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ClubEvent::RESPONSE_STATUSES))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        ClubEventResponse::query()->updateOrCreate(
            ['club_event_id' => $event->id, 'user_id' => $request->user()->id],
            ['status' => $validated['status'], 'note' => $validated['note'] ?? null],
        );

        return back()->with('success', 'ღონისძიებაზე თქვენი პასუხი შენახულია.');
    }

    public function markNotificationRead(Request $request, ClubNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);
        $notification->markRead();

        if ($notification->action_url) {
            return redirect()->to($notification->action_url);
        }

        return back()->with('success', 'შეტყობინება წაკითხულად მოინიშნა.');
    }

    public function markAllNotificationsRead(Request $request): RedirectResponse
    {
        $request->user()->clubNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'ყველა შეტყობინება წაკითხულად მოინიშნა.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_updates' => ['required', 'boolean'],
            'forum_replies' => ['required', 'boolean'],
            'payment_reminders' => ['required', 'boolean'],
            'weekly_digest' => ['required', 'boolean'],
        ]);

        ClubNotificationPreference::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated,
        );

        return back()->with('success', 'შეტყობინებების პარამეტრები შენახულია.');
    }

    private function accessibleGroupIds(Request $request): Collection
    {
        $childIds = $request->user()->children()->pluck('children.id');
        if ($childIds->isEmpty()) {
            return collect();
        }

        return Enrollment::query()
            ->whereIn('child_id', $childIds)
            ->where('status', 'active')
            ->whereHas('group', fn ($query) => $query->where('is_active', true))
            ->pluck('kindergarten_group_id')
            ->unique()
            ->values();
    }
}
