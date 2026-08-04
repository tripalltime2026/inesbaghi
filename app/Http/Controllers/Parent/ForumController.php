<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\ForumTopic;
use App\Models\KindergartenGroup;
use App\Models\User;
use App\Services\ManagedContent;
use App\Services\ParentClubContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            ->get(['id', 'name', 'slug']);

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
                'topics' => [],
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
                'comments.author:id,name',
            ])
            ->withCount('comments')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ForumTopic $topic) => [
                'id' => $topic->id,
                'group_id' => $topic->kindergarten_group_id,
                'group_name' => $topic->group?->name,
                'category' => $topic->category,
                'category_label' => ForumTopic::CATEGORIES[$topic->category] ?? $topic->category,
                'title' => $topic->title,
                'body' => $topic->body,
                'author' => $topic->author?->name ?? 'მშობელი',
                'created_at' => $topic->created_at?->format('d.m.Y H:i'),
                'comments_count' => $topic->comments_count,
                'is_locked' => $topic->is_locked,
                'comments' => $topic->comments->map(fn ($comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'author' => $comment->author?->name ?? 'მშობელი',
                    'created_at' => $comment->created_at?->format('d.m.Y H:i'),
                ])->values(),
            ]);

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
            'topics' => $topics,
            ...$scopedContent,
            'members' => $members,
            'can_create' => true,
            'contact_policy' => 'პირადი ტელეფონი და ელფოსტა არ გამოჩნდება. კომუნიკაცია შესაძლებელია მხოლოდ ამ ჯგუფის დახურულ ფორუმში.',
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
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'topic_id' => $topic->id,
                'message' => 'თემა შეიქმნა და მხოლოდ არჩეული ჯგუფის მშობლებს გამოუჩნდებათ.',
            ], 201);
        }

        return redirect()->to(route('parent.dashboard').'#forum-topic-'.$topic->id)
            ->with('success', 'თემა შეიქმნა და მხოლოდ არჩეული ჯგუფის მშობლებს გამოუჩნდებათ.');
    }

    public function storeComment(Request $request, ForumTopic $topic): JsonResponse|RedirectResponse
    {
        $groupIds = $this->accessibleGroupIds($request->user());
        abort_unless($groupIds->contains($topic->kindergarten_group_id), 404);
        abort_if($topic->is_locked, 403, 'ამ თემაზე კომენტარები დახურულია.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $comment = $topic->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'comment_id' => $comment->id,
                'message' => 'კომენტარი დაემატა.',
            ], 201);
        }

        return redirect()->to(route('parent.dashboard').'#forum-topic-'.$topic->id)
            ->with('success', 'კომენტარი დაემატა.');
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
