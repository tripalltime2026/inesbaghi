<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ClubPoll;
use App\Models\ClubPollVote;
use App\Models\Enrollment;
use App\Models\ForumTopic;
use App\Models\KindergartenGroup;
use App\Models\User;
use App\Services\ClubNotificationService;
use App\Services\ManagedContent;
use App\Services\ParentClubContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ForumController extends Controller
{
    public function index(
        Request $request,
        ManagedContent $content,
        ParentClubContent $clubContent,
    ): JsonResponse {
        $groupIds = $this->accessibleGroupIds($request->user());

        $groups = KindergartenGroup::query()
            ->whereIn('id', $groupIds)
            ->where('is_active', true)
            ->orderBy('age_min_months')
            ->get(['id', 'name', 'slug', 'academic_year']);

        if ($request->filled('group_id')) {
            abort_unless($groups->contains('id', (int) $request->integer('group_id')), 404);
        }

        $selectedGroup = $groups->firstWhere('id', (int) $request->integer('group_id'))
            ?? $groups->first();

        if (! $selectedGroup) {
            return response()->json([
                'groups' => [],
                'active_group' => null,
                'categories' => ForumTopic::CATEGORIES,
                'statuses' => ForumTopic::STATUSES,
                'topics' => [],
                'polls' => [],
                'club_post' => [],
                'club_event' => [],
                'club_poll' => [],
                'club_topic' => [],
                'members' => [],
                'can_create' => false,
                'contact_policy' => 'ჯგუფური კომუნიკაცია ხელმისაწვდომია მხოლოდ აქტიურ ჯგუფში ჩარიცხული მშობლებისთვის.',
            ]);
        }

        $topics = ForumTopic::query()
            ->where('kindergarten_group_id', $selectedGroup->id)
            ->with([
                'group:id,name,slug',
                'author:id,name',
                'answeredBy:id,name',
                'comments.author:id,name,role',
            ])
            ->withCount('comments')
            ->orderByDesc('is_pinned')
            ->orderByDesc(DB::raw('COALESCE(last_activity_at, created_at)'))
            ->limit(50)
            ->get()
            ->map(fn (ForumTopic $topic) => [
                'id' => $topic->id,
                'group_id' => $topic->kindergarten_group_id,
                'group_name' => $topic->group?->name,
                'category' => $topic->category,
                'category_label' => ForumTopic::CATEGORIES[$topic->category] ?? $topic->category,
                'status' => $topic->status,
                'status_label' => ForumTopic::STATUSES[$topic->status] ?? $topic->status,
                'priority' => $topic->priority,
                'is_pinned' => $topic->is_pinned,
                'title' => $topic->title,
                'body' => $topic->body,
                'author' => $topic->author?->name ?? 'მშობელი',
                'answered_by' => $topic->answeredBy?->name,
                'answered_at' => $topic->answered_at?->format('d.m.Y H:i'),
                'created_at' => $topic->created_at?->format('d.m.Y H:i'),
                'sort_at' => ($topic->last_activity_at ?? $topic->created_at)?->timestamp,
                'comments_count' => $topic->comments_count,
                'is_locked' => $topic->is_locked || $topic->status === 'closed',
                'comments' => $topic->comments->sortBy('created_at')->map(fn ($comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'author' => $comment->author?->name ?? 'მშობელი',
                    'is_official_answer' => $comment->is_official_answer,
                    'created_at' => $comment->created_at?->format('d.m.Y H:i'),
                ])->values(),
            ]);

        $polls = ClubPoll::query()
            ->published()
            ->where('kindergarten_group_id', $selectedGroup->id)
            ->with([
                'options' => fn ($query) => $query->withCount('votes'),
                'votes' => fn ($query) => $query->where('user_id', $request->user()->id),
            ])
            ->latest('published_at')
            ->limit(20)
            ->get()
            ->map(function (ClubPoll $poll): array {
                $totalVotes = $poll->options->sum('votes_count');
                $myOptionId = $poll->votes->first()?->club_poll_option_id;

                return [
                    'id' => $poll->id,
                    'group_id' => $poll->kindergarten_group_id,
                    'question' => $poll->question,
                    'description' => $poll->description,
                    'status' => $poll->status,
                    'closes_at' => $poll->closes_at?->format('d.m.Y H:i'),
                    'published_at' => $poll->published_at?->format('d.m.Y H:i'),
                    'sort_at' => $poll->published_at?->timestamp,
                    'total_votes' => $totalVotes,
                    'my_option_id' => $myOptionId,
                    'can_vote' => $poll->isOpen(),
                    'options' => $poll->options->map(fn ($option) => [
                        'id' => $option->id,
                        'label' => $option->label,
                        'votes' => $option->votes_count,
                        'percent' => $totalVotes > 0
                            ? (int) round(($option->votes_count / $totalVotes) * 100)
                            : 0,
                        'selected' => $myOptionId === $option->id,
                    ])->values(),
                ];
            });

        $knownGroups = KindergartenGroup::query()
            ->where('is_active', true)
            ->get(['id', 'name', 'slug']);

        $scopedContent = $clubContent->forGroup(
            $content->publicPayload(),
            $selectedGroup,
            $knownGroups,
        );

        $members = User::query()
            ->where('status', 'active')
            ->whereNotNull('club_access_approved_at')
            ->whereHas('children.enrollments', fn ($query) => $query
                ->where('status', 'active')
                ->where('kindergarten_group_id', $selectedGroup->id))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->unique('id')
            ->values()
            ->map(fn (User $member) => [
                'name' => $member->name,
                'initial' => mb_substr($member->name, 0, 1),
                'is_you' => $member->is($request->user()),
            ]);

        return response()->json([
            'groups' => $groups,
            'active_group' => $selectedGroup,
            'categories' => ForumTopic::CATEGORIES,
            'statuses' => ForumTopic::STATUSES,
            'topics' => $topics,
            'polls' => $polls,
            ...$scopedContent,
            'members' => $members,
            'can_create' => true,
            'contact_policy' => 'თქვენ ხედავთ მხოლოდ ამ ჯგუფის მშობლებს, კითხვებსა და გამოკითხვებს. სხვა ასაკობრივი ჯგუფის სივრცე მიუწვდომელია.',
        ])->header('Cache-Control', 'no-store, private');
    }

    public function storeTopic(Request $request): JsonResponse|RedirectResponse
    {
        $groupIds = $this->accessibleGroupIds($request->user());
        abort_if($groupIds->isEmpty(), 403, 'თემის შესაქმნელად ბავშვის აქტიური ჯგუფი უნდა იყოს დაკავშირებული.');

        $validated = $request->validate([
            'kindergarten_group_id' => ['required', 'integer', Rule::in($groupIds->all())],
            'category' => ['required', Rule::in(array_keys(ForumTopic::CATEGORIES))],
            'title' => ['required', 'string', 'min:4', 'max:180'],
            'body' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        $topic = ForumTopic::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'status' => 'open',
            'priority' => 'normal',
            'last_activity_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'topic_id' => $topic->id,
                'message' => 'კითხვა გამოქვეყნდა მხოლოდ თქვენი ჯგუფის ფიდში.',
            ], 201);
        }

        return redirect()->to(route('parent.dashboard').'#forum-topic-'.$topic->id)
            ->with('success', 'კითხვა გამოქვეყნდა მხოლოდ თქვენი ჯგუფის ფიდში.');
    }

    public function storeComment(
        Request $request,
        ForumTopic $topic,
        ClubNotificationService $notifications,
    ): JsonResponse|RedirectResponse {
        $groupIds = $this->accessibleGroupIds($request->user());
        abort_unless($groupIds->contains($topic->kindergarten_group_id), 404);
        abort_if($topic->is_locked || $topic->status === 'closed', 403, 'ამ თემაზე კომენტარები დახურულია.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $comment = DB::transaction(function () use ($request, $topic, $validated) {
            $comment = $topic->comments()->create([
                'user_id' => $request->user()->id,
                'body' => $validated['body'],
                'is_official_answer' => false,
            ]);
            $topic->update(['last_activity_at' => now()]);

            return $comment;
        });

        $notifications->topicReply($topic->fresh(), $request->user(), false);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'comment_id' => $comment->id,
                'message' => 'პასუხი დაემატა.',
            ], 201);
        }

        return redirect()->to(route('parent.dashboard').'#forum-topic-'.$topic->id)
            ->with('success', 'პასუხი დაემატა.');
    }

    public function votePoll(Request $request, ClubPoll $poll): JsonResponse
    {
        $groupIds = $this->accessibleGroupIds($request->user());
        abort_unless($groupIds->contains($poll->kindergarten_group_id), 404);
        abort_unless($poll->isOpen(), 422, 'ეს გამოკითხვა უკვე დახურულია.');

        $validated = $request->validate([
            'option_id' => [
                'required',
                'integer',
                Rule::exists('club_poll_options', 'id')->where('club_poll_id', $poll->id),
            ],
        ]);

        ClubPollVote::query()->updateOrCreate(
            [
                'club_poll_id' => $poll->id,
                'user_id' => $request->user()->id,
            ],
            ['club_poll_option_id' => $validated['option_id']],
        );

        return response()->json([
            'ok' => true,
            'message' => 'თქვენი პასუხი შენახულია.',
        ]);
    }

    private function accessibleGroupIds(User $user): Collection
    {
        $childIds = $user->children()->pluck('children.id');

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
