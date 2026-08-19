<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ClubEvent;
use App\Models\ClubNotificationPreference;
use App\Models\ForumTopic;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $children = $user
            ->children()
            ->with([
                'enrollments' => fn ($query) => $query
                    ->with([
                        'group',
                        'payments' => fn ($paymentQuery) => $paymentQuery
                            ->whereNotNull('confirmed_at')
                            ->latest('period'),
                    ])
                    ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                    ->latest(),
                'attendanceRecords' => fn ($query) => $query
                    ->with('group')
                    ->latest('attendance_date')
                    ->limit(14),
            ])
            ->orderBy('first_name')
            ->get();

        foreach ($children as $child) {
            $allConfirmedPayments = $child->enrollments
                ->flatMap(fn ($enrollment) => $enrollment->payments)
                ->sortByDesc('period')
                ->values();

            if ($primaryEnrollment = $child->enrollments->first()) {
                $primaryEnrollment->setRelation('payments', $allConfirmedPayments);
            }
        }

        $clubGroups = $children
            ->flatMap(fn ($child) => $child->enrollments
                ->where('status', 'active')
                ->pluck('group')
                ->filter())
            ->filter(fn ($group) => $group->is_active)
            ->unique('id')
            ->sortBy('age_min_months')
            ->values();

        $groupIds = $clubGroups->pluck('id')->map(fn ($id) => (int) $id)->all();

        $events = ClubEvent::query()
            ->published()
            ->visibleToGroups($groupIds)
            ->where('starts_at', '>=', now()->subHours(6))
            ->with([
                'group:id,name,slug',
                'responses' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->withCount([
                'responses as going_count' => fn ($query) => $query->where('status', 'going'),
                'responses as maybe_count' => fn ($query) => $query->where('status', 'maybe'),
            ])
            ->orderByDesc('is_featured')
            ->orderBy('starts_at')
            ->limit(12)
            ->get();

        $notifications = $user->clubNotifications()
            ->latest()
            ->limit(12)
            ->get();

        $myTopics = ForumTopic::query()
            ->where('user_id', $user->id)
            ->with([
                'group:id,name',
                'answeredBy:id,name',
                'comments' => fn ($query) => $query
                    ->where('is_official_answer', true)
                    ->with('author:id,name')
                    ->latest(),
            ])
            ->withCount('comments')
            ->orderByDesc('is_pinned')
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $preferences = $user->clubNotificationPreference
            ?? new ClubNotificationPreference([
                'event_updates' => true,
                'forum_replies' => true,
                'payment_reminders' => true,
                'weekly_digest' => true,
            ]);

        $familyPayments = $children
            ->flatMap(fn ($child) => $child->enrollments)
            ->flatMap(fn ($enrollment) => $enrollment->payments)
            ->unique('id')
            ->reject(fn ($payment) => in_array($payment->status, ['cancelled', 'waived'], true));

        $summary = [
            'unread_notifications' => $notifications->whereNull('read_at')->count(),
            'upcoming_events' => $events->where('starts_at', '>=', now())->count(),
            'open_questions' => $myTopics->where('status', 'open')->count(),
            'outstanding_payment' => $familyPayments->sum(fn ($payment) => $payment->outstandingAmount()),
        ];

        return view('parent.dashboard', compact(
            'user',
            'children',
            'clubGroups',
            'events',
            'notifications',
            'myTopics',
            'preferences',
            'summary',
        ));
    }
}
