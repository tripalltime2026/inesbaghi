<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportKnowledgeArticle;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\InesAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(SupportConversation::STATUSES))],
            'mode' => ['nullable', Rule::in(['ai', 'human'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $query = SupportConversation::query()
            ->with(['user', 'assignedTo'])
            ->with(['messages' => fn ($builder) => $builder->latest('id')->limit(1)])
            ->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status))
            ->when($filters['mode'] ?? null, fn ($builder, $mode) => $builder->where('mode', $mode))
            ->when($filters['search'] ?? null, function ($builder, string $search) {
                $builder->where(function ($nested) use ($search) {
                    $nested->where('guest_name', 'like', "%{$search}%")
                        ->orWhere('guest_phone', 'like', "%{$search}%")
                        ->orWhere('topic', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('messages', fn ($messageQuery) => $messageQuery->where('body', 'like', "%{$search}%"));
                });
            });

        return view('admin.support.index', [
            'conversations' => $query->orderByRaw("case when status = 'waiting_admin' then 0 when status = 'new' then 1 when status = 'in_progress' then 2 else 3 end")
                ->orderByDesc('last_message_at')
                ->paginate(25)
                ->withQueryString(),
            'statuses' => SupportConversation::STATUSES,
            'statusCounts' => SupportConversation::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'filters' => $filters,
        ]);
    }

    public function show(SupportConversation $conversation): View
    {
        $conversation->update(['admin_last_read_at' => now()]);
        $conversation->load(['messages.sender', 'user', 'assignedTo']);

        return view('admin.support.show', [
            'conversation' => $conversation,
            'statuses' => SupportConversation::STATUSES,
            'assignableUsers' => User::query()
                ->where('status', 'active')
                ->whereIn('role', ['admin', 'teacher'])
                ->orderBy('name')
                ->get(['id', 'name', 'role']),
            'knowledgeArticles' => SupportKnowledgeArticle::query()
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(),
        ]);
    }

    public function storeMessage(Request $request, SupportConversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:4000'],
        ]);

        $message = $conversation->messages()->create([
            'sender_type' => 'admin',
            'sender_user_id' => $request->user()->id,
            'body' => trim($validated['body']),
        ]);

        $conversation->update([
            'assigned_to_user_id' => $conversation->assigned_to_user_id ?: $request->user()->id,
            'mode' => 'human',
            'status' => 'in_progress',
            'resolved_at' => null,
            'last_message_at' => now(),
            'admin_last_read_at' => now(),
        ]);

        $this->audit($request, 'support.admin_replied', $conversation, ['message_id' => $message->id]);

        return back()->with('success', 'პასუხი გაიგზავნა.');
    }

    public function update(Request $request, SupportConversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(SupportConversation::STATUSES))],
            'mode' => ['required', Rule::in(['ai', 'human'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $validated['resolved_at'] = $validated['status'] === 'resolved' ? now() : null;
        if ($validated['mode'] === 'ai' && $validated['status'] === 'waiting_admin') {
            $validated['status'] = 'ai_active';
        }

        $conversation->update($validated);
        $this->audit($request, 'support.conversation_updated', $conversation, $validated);

        return back()->with('success', 'საუბრის სტატუსი განახლდა.');
    }

    public function draft(SupportConversation $conversation, InesAiService $ai): JsonResponse
    {
        return response()->json(['draft' => $ai->adminDraft($conversation)]);
    }

    public function storeKnowledge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:160'],
            'category' => ['required', 'string', 'max:50'],
            'content' => ['required', 'string', 'min:10', 'max:8000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        SupportKnowledgeArticle::create([
            'key' => Str::slug($validated['title']).'-'.Str::lower(Str::random(6)),
            'title' => $validated['title'],
            'category' => $validated['category'],
            'content' => $validated['content'],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => 100,
            'updated_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Ines AI-ის ცოდნას ახალი ჩანაწერი დაემატა.');
    }

    public function updateKnowledge(Request $request, SupportKnowledgeArticle $article): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:160'],
            'category' => ['required', 'string', 'max:50'],
            'content' => ['required', 'string', 'min:10', 'max:8000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $article->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
            'updated_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'ცოდნის ჩანაწერი განახლდა.');
    }

    public function destroyKnowledge(SupportKnowledgeArticle $article): RedirectResponse
    {
        $article->delete();

        return back()->with('success', 'ცოდნის ჩანაწერი წაიშალა.');
    }

    public function promoteMessage(Request $request, SupportMessage $message): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:160'],
            'category' => ['required', 'string', 'max:50'],
        ]);

        SupportKnowledgeArticle::create([
            'key' => Str::slug($validated['title']).'-'.Str::lower(Str::random(6)),
            'title' => $validated['title'],
            'category' => $validated['category'],
            'content' => $message->body,
            'is_active' => true,
            'sort_order' => 100,
            'updated_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'ადმინისტრატორის პასუხი Ines AI-ის დამტკიცებულ ცოდნაში დაემატა.');
    }

    private function audit(Request $request, string $action, SupportConversation $conversation, array $metadata = []): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $request->user()?->id,
            'action' => $action,
            'subject_type' => SupportConversation::class,
            'subject_id' => $conversation->id,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
