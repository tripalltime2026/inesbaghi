<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClubEvent;
use App\Models\ClubEventResponse;
use App\Models\ClubPoll;
use App\Models\ForumTopic;
use App\Models\KindergartenGroup;
use App\Models\User;
use App\Services\ClubNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClubController extends Controller
{
    public function index(Request $request): View
    {
        $groups = KindergartenGroup::query()
            ->where('is_active', true)
            ->orderBy('age_min_months')
            ->get();

        $events = ClubEvent::query()
            ->with(['group', 'creator:id,name', 'responses.user:id,name'])
            ->withCount([
                'responses',
                'responses as going_count' => fn ($query) => $query->where('status', 'going'),
                'responses as maybe_count' => fn ($query) => $query->where('status', 'maybe'),
                'responses as not_going_count' => fn ($query) => $query->where('status', 'not_going'),
            ])
            ->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")
            ->orderBy('starts_at')
            ->limit(40)
            ->get();

        $topicQuery = ForumTopic::query()
            ->with([
                'group:id,name,slug',
                'author:id,name,phone,email',
                'answeredBy:id,name',
                'comments' => fn ($query) => $query->with('author:id,name')->latest(),
            ])
            ->withCount('comments')
            ->orderByDesc('is_pinned')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'important' THEN 1 ELSE 2 END")
            ->orderByDesc(DB::raw('COALESCE(last_activity_at, created_at)'));

        if ($request->filled('topic_status')) {
            $topicQuery->where('status', $request->string('topic_status')->toString());
        }
        if ($request->filled('group_id')) {
            $topicQuery->where('kindergarten_group_id', $request->integer('group_id'));
        }
        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->squish()->toString().'%';
            $topicQuery->where(fn ($query) => $query
                ->where('title', 'like', $search)
                ->orWhere('body', 'like', $search));
        }

        $topics = $topicQuery->limit(60)->get();

        $metrics = [
            'active_parents' => User::query()
                ->where('status', 'active')
                ->whereNotNull('club_access_approved_at')
                ->whereHas('children.enrollments', fn ($query) => $query->where('status', 'active'))
                ->count(),
            'unanswered_topics' => ForumTopic::query()->where('status', 'open')->count(),
            'upcoming_events' => ClubEvent::query()
                ->published()
                ->where('starts_at', '>=', now())
                ->count(),
            'going_responses' => ClubEventResponse::query()
                ->where('status', 'going')
                ->whereHas('event', fn ($query) => $query
                    ->published()
                    ->where('starts_at', '>=', now()))
                ->count(),
        ];

        return view('admin.club.index', compact('groups', 'events', 'topics', 'metrics'));
    }

    public function polls(): View
    {
        $groups = KindergartenGroup::query()
            ->where('is_active', true)
            ->orderBy('age_min_months')
            ->get();

        $polls = ClubPoll::query()
            ->with([
                'group:id,name,academic_year',
                'creator:id,name',
                'options' => fn ($query) => $query->withCount('votes'),
            ])
            ->withCount('votes')
            ->latest()
            ->limit(60)
            ->get();

        return view('admin.club.polls', compact('groups', 'polls'));
    }

    public function storeEvent(Request $request, ClubNotificationService $notifications): RedirectResponse
    {
        $validated = $this->validateEvent($request);
        $event = ClubEvent::query()->create([
            ...$validated,
            'created_by_user_id' => $request->user()->id,
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        if ($event->status === 'published') {
            $notifications->eventPublished($event);
        }

        return back()->with('success', 'ღონისძიება შეიქმნა და მშობელთა კლუბში დაემატა.');
    }

    public function updateEvent(
        Request $request,
        ClubEvent $event,
        ClubNotificationService $notifications,
    ): RedirectResponse {
        $validated = $this->validateEvent($request);
        $wasPublished = $event->status === 'published' && $event->published_at !== null;
        $event->fill($validated);

        if ($event->status === 'published' && $event->published_at === null) {
            $event->published_at = now();
        }
        if ($event->status === 'draft') {
            $event->published_at = null;
        }

        $event->save();

        if ((! $wasPublished && $event->status === 'published') || $request->boolean('notify_parents')) {
            $notifications->eventPublished($event);
        }

        return back()->with('success', 'ღონისძიების ინფორმაცია განახლდა.');
    }

    public function destroyEvent(ClubEvent $event): RedirectResponse
    {
        $event->delete();

        return back()->with('success', 'ღონისძიება წაიშალა.');
    }

    public function storePoll(Request $request, ClubNotificationService $notifications): RedirectResponse
    {
        $options = collect($request->input('options', []))
            ->map(fn ($option) => trim((string) $option))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $request->merge(['options' => $options]);

        $validated = $request->validate([
            'kindergarten_group_id' => [
                'required',
                'integer',
                Rule::exists('kindergarten_groups', 'id')->where('is_active', true),
            ],
            'question' => ['required', 'string', 'min:4', 'max:240'],
            'description' => ['nullable', 'string', 'max:2000'],
            'closes_at' => ['nullable', 'date', 'after:now'],
            'status' => ['required', Rule::in(array_keys(ClubPoll::STATUSES))],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*' => ['required', 'string', 'max:180'],
        ]);

        $poll = DB::transaction(function () use ($request, $validated): ClubPoll {
            $poll = ClubPoll::query()->create([
                'kindergarten_group_id' => $validated['kindergarten_group_id'],
                'created_by_user_id' => $request->user()->id,
                'question' => $validated['question'],
                'description' => $validated['description'] ?? null,
                'closes_at' => $validated['closes_at'] ?? null,
                'status' => $validated['status'],
                'published_at' => $validated['status'] === 'published' ? now() : null,
            ]);

            foreach ($validated['options'] as $position => $label) {
                $poll->options()->create([
                    'label' => $label,
                    'position' => $position + 1,
                ]);
            }

            return $poll;
        });

        if ($poll->status === 'published') {
            $notifications->pollPublished($poll);
        }

        return back()->with('success', 'გამოკითხვა შეიქმნა მხოლოდ არჩეული ჯგუფისთვის.');
    }

    public function updatePoll(
        Request $request,
        ClubPoll $poll,
        ClubNotificationService $notifications,
    ): RedirectResponse {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:4', 'max:240'],
            'description' => ['nullable', 'string', 'max:2000'],
            'closes_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(ClubPoll::STATUSES))],
        ]);

        $wasPublished = $poll->status === 'published' && $poll->published_at !== null;
        $poll->fill($validated);

        if ($poll->status === 'published' && $poll->published_at === null) {
            $poll->published_at = now();
        }
        if ($poll->status === 'draft') {
            $poll->published_at = null;
        }

        $poll->save();

        if ((! $wasPublished && $poll->status === 'published') || $request->boolean('notify_parents')) {
            $notifications->pollPublished($poll);
        }

        return back()->with('success', 'გამოკითხვა განახლდა.');
    }

    public function destroyPoll(ClubPoll $poll): RedirectResponse
    {
        $poll->delete();

        return back()->with('success', 'გამოკითხვა წაიშალა.');
    }

    public function replyTopic(
        Request $request,
        ForumTopic $topic,
        ClubNotificationService $notifications,
    ): RedirectResponse {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        DB::transaction(function () use ($request, $topic, $validated): void {
            $topic->comments()->create([
                'user_id' => $request->user()->id,
                'body' => $validated['body'],
                'is_official_answer' => true,
            ]);

            $topic->update([
                'status' => 'answered',
                'answered_by_user_id' => $request->user()->id,
                'answered_at' => now(),
                'last_activity_at' => now(),
            ]);
        });

        $notifications->topicReply($topic->fresh(), $request->user(), true);

        return back()->with('success', 'ოფიციალური პასუხი გამოქვეყნდა და მშობელს შეტყობინება გაეგზავნა.');
    }

    public function updateTopic(Request $request, ForumTopic $topic): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ForumTopic::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(ForumTopic::PRIORITIES))],
            'is_pinned' => ['required', 'boolean'],
            'is_locked' => ['required', 'boolean'],
        ]);

        $topic->update($validated);

        return back()->with('success', 'კითხვის სტატუსი განახლდა.');
    }

    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'kindergarten_group_id' => [
                'nullable',
                'integer',
                Rule::exists('kindergarten_groups', 'id')->where('is_active', true),
            ],
            'title' => ['required', 'string', 'min:3', 'max:180'],
            'description' => ['required', 'string', 'min:5', 'max:5000'],
            'location' => ['nullable', 'string', 'max:180'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'status' => ['required', Rule::in(array_keys(ClubEvent::STATUSES))],
            'is_featured' => ['required', 'boolean'],
        ]);
    }
}
