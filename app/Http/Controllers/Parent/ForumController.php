<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ForumController extends Controller
{
    public function storeTopic(Request $request): RedirectResponse
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

        return redirect()->to(route('parent.dashboard').'#forum-topic-'.$topic->id)
            ->with('success', 'თემა შეიქმნა და მხოლოდ არჩეული ჯგუფის მშობლებს გამოუჩნდებათ.');
    }

    public function storeComment(Request $request, ForumTopic $topic): RedirectResponse
    {
        $groupIds = $this->accessibleGroupIds($request->user());
        abort_unless($groupIds->contains($topic->kindergarten_group_id), 404);
        abort_if($topic->is_locked, 403, 'ამ თემაზე კომენტარები დახურულია.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $topic->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

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
