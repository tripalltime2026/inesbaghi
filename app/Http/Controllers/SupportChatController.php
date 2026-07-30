<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\InesAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupportChatController extends Controller
{
    public function bootstrap(Request $request): JsonResponse
    {
        $token = $request->string('token')->trim()->value();
        $conversation = $token !== ''
            ? SupportConversation::query()->where('public_token', $token)->first()
            : null;

        if ($conversation) {
            $this->authorizeConversation($request, $conversation);
            $this->attachAuthenticatedUser($request, $conversation);
        }

        return response()->json([
            'name' => 'Ines AI',
            'subtitle' => 'ინეს ბაღის ციფრული ასისტენტი',
            'conversation' => $conversation ? $this->conversationPayload($conversation) : null,
            'quick_actions' => $this->quickActions(),
        ]);
    }

    public function storeConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'min:2', 'max:120'],
            'phone' => ['nullable', 'regex:/^(?:\+?995)?5\d{8}$/'],
        ]);

        $conversation = DB::transaction(function () use ($request, $validated): SupportConversation {
            $conversation = SupportConversation::create([
                'public_token' => (string) Str::uuid(),
                'user_id' => $request->user()?->id,
                'guest_name' => $validated['name'] ?? $request->user()?->name,
                'guest_phone' => isset($validated['phone']) ? $this->normalizePhone($validated['phone']) : $request->user()?->phone,
                'status' => 'ai_active',
                'mode' => 'ai',
                'topic' => 'ახალი საუბარი',
                'last_message_at' => now(),
            ]);

            $conversation->messages()->create([
                'sender_type' => 'ai',
                'body' => 'გამარჯობა 🌱 მე ვარ Ines AI — ინეს ბაღის ციფრული ასისტენტი. დაგეხმარებით ჯგუფის შერჩევაში, ადგილების შემოწმებაში, ჩარიცხვის პროცესში და ბაღის პროგრამის გაცნობაში. ადმინისტრატორთან დაკავშირება ნებისმიერ დროს შეგიძლიათ.',
                'metadata' => ['welcome' => true],
            ]);

            return $conversation;
        });

        return response()->json([
            'conversation' => $this->conversationPayload($conversation),
            'quick_actions' => $this->quickActions(),
        ], 201);
    }

    public function show(Request $request, SupportConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);
        $this->attachAuthenticatedUser($request, $conversation);
        $conversation->update(['user_last_read_at' => now()]);

        return response()->json([
            'conversation' => $this->conversationPayload($conversation->fresh()),
        ]);
    }

    public function storeMessage(Request $request, SupportConversation $conversation, InesAiService $ai): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);
        $this->attachAuthenticatedUser($request, $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        if ($conversation->status === 'blocked') {
            return response()->json(['message' => 'ეს საუბარი დაბლოკილია.'], 423);
        }

        $userMessage = $conversation->messages()->create([
            'sender_type' => 'user',
            'sender_user_id' => $request->user()?->id,
            'body' => trim($validated['body']),
        ]);

        $conversation->update([
            'status' => $conversation->isHumanManaged() ? $conversation->status : 'ai_active',
            'resolved_at' => null,
            'last_message_at' => now(),
        ]);

        if (! $conversation->isHumanManaged()) {
            $reply = $ai->respond($conversation->fresh(), $userMessage->body);

            $conversation->update([
                'context' => $reply['context'],
                'topic' => $reply['topic'],
                'mode' => $reply['escalate'] ? 'human' : 'ai',
                'status' => $reply['escalate'] ? 'waiting_admin' : 'ai_active',
                'priority' => $reply['escalate'] ? 'high' : $conversation->priority,
                'last_message_at' => now(),
            ]);

            $conversation->messages()->create([
                'sender_type' => 'ai',
                'body' => $reply['body'],
                'metadata' => array_merge($reply['metadata'], [
                    'escalated' => $reply['escalate'],
                    'topic' => $reply['topic'],
                ]),
            ]);
        }

        return response()->json([
            'conversation' => $this->conversationPayload($conversation->fresh()),
        ], 201);
    }

    public function requestHuman(Request $request, SupportConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);
        $this->attachAuthenticatedUser($request, $conversation);

        if ($conversation->status !== 'waiting_admin') {
            $conversation->update([
                'mode' => 'human',
                'status' => 'waiting_admin',
                'priority' => 'high',
                'topic' => $conversation->topic ?: 'ადმინისტრატორთან დაკავშირება',
                'last_message_at' => now(),
            ]);

            $conversation->messages()->create([
                'sender_type' => 'ai',
                'body' => 'ადმინისტრატორს უკვე გადავეცი თქვენი მოთხოვნა. პასუხს ამავე ჩატში მიიღებთ.',
                'metadata' => ['escalated' => true, 'manual_handoff' => true],
            ]);
        }

        return response()->json([
            'conversation' => $this->conversationPayload($conversation->fresh()),
        ]);
    }

    public function updateContact(Request $request, SupportConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'min:2', 'max:120'],
            'phone' => ['nullable', 'regex:/^(?:\+?995)?5\d{8}$/'],
        ]);

        $conversation->update([
            'guest_name' => $validated['name'] ?? $conversation->guest_name,
            'guest_phone' => isset($validated['phone']) ? $this->normalizePhone($validated['phone']) : $conversation->guest_phone,
        ]);

        return response()->json([
            'conversation' => $this->conversationPayload($conversation->fresh()),
        ]);
    }

    private function authorizeConversation(Request $request, SupportConversation $conversation): void
    {
        if ($conversation->user_id && $request->user()?->id !== $conversation->user_id) {
            abort(404);
        }
    }

    private function attachAuthenticatedUser(Request $request, SupportConversation $conversation): void
    {
        if ($request->user() && ! $conversation->user_id) {
            $conversation->update([
                'user_id' => $request->user()->id,
                'guest_name' => $conversation->guest_name ?: $request->user()->name,
                'guest_phone' => $conversation->guest_phone ?: $request->user()->phone,
            ]);
        }
    }

    private function conversationPayload(SupportConversation $conversation): array
    {
        $conversation->loadMissing(['visibleMessages.sender']);

        return [
            'token' => $conversation->public_token,
            'status' => $conversation->status,
            'status_label' => SupportConversation::STATUSES[$conversation->status] ?? $conversation->status,
            'mode' => $conversation->mode,
            'topic' => $conversation->topic,
            'contact' => [
                'name' => $conversation->user?->name ?? $conversation->guest_name,
                'phone' => $conversation->user?->phone ?? $conversation->guest_phone,
            ],
            'messages' => $conversation->visibleMessages
                ->sortBy('id')
                ->values()
                ->map(fn (SupportMessage $message): array => [
                    'id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'sender_label' => match ($message->sender_type) {
                        'ai' => 'Ines AI',
                        'admin' => $message->sender?->name ?? 'ინეს ბაღის ადმინისტრაცია',
                        'system' => 'სისტემა',
                        default => 'თქვენ',
                    },
                    'body' => $message->body,
                    'created_at' => $message->created_at->toIso8601String(),
                ]),
        ];
    }

    private function quickActions(): array
    {
        return [
            'არის ადგილი ჯგუფში?',
            'რომელი ასაკიდან არის მიღება?',
            'რით გამოირჩევა ინეს ბაღი?',
            'როგორი სწავლების სტილი აქვს?',
            'დავგეგმოთ გაცნობითი ვიზიტი',
            'ადმინისტრატორთან საუბარი',
        ];
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '995')) {
            $digits = substr($digits, 3);
        }

        return '+995'.$digits;
    }
}
