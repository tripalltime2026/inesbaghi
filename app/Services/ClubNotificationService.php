<?php

namespace App\Services;

use App\Models\ClubEvent;
use App\Models\ClubNotification;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Support\Collection;

class ClubNotificationService
{
    public function eventPublished(ClubEvent $event): int
    {
        $userIds = $this->eligibleParents($event->kindergarten_group_id, 'event_updates');

        return $this->send(
            $userIds,
            'event_published',
            'ახალი ღონისძიება: '.$event->title,
            trim(($event->starts_at?->format('d.m.Y H:i') ?? '').($event->location ? ' · '.$event->location : '')),
            route('parent.dashboard').'#events',
            ['event_id' => $event->id],
        );
    }

    public function topicReply(ForumTopic $topic, User $actor, bool $official): int
    {
        $userIds = collect([$topic->user_id])
            ->merge($topic->comments()->pluck('user_id'))
            ->filter()
            ->unique()
            ->reject(fn (int $userId) => $userId === $actor->id)
            ->values();

        if ($userIds->isEmpty()) {
            return 0;
        }

        $allowedIds = User::query()
            ->whereIn('id', $userIds)
            ->where(function ($query): void {
                $query->whereDoesntHave('clubNotificationPreference')
                    ->orWhereHas('clubNotificationPreference', fn ($preference) => $preference
                        ->where('forum_replies', true));
            })
            ->pluck('id');

        return $this->send(
            $allowedIds,
            $official ? 'official_answer' : 'forum_reply',
            $official ? 'ადმინისტრაციამ თქვენს კითხვას უპასუხა' : 'თქვენს საუბარს ახალი პასუხი აქვს',
            $topic->title,
            route('parent.dashboard').'#forum-topic-'.$topic->id,
            ['topic_id' => $topic->id, 'official' => $official],
        );
    }

    public function send(
        Collection|array $userIds,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        array $data = [],
    ): int {
        $ids = collect($userIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return 0;
        }

        $now = now();
        $rows = $ids->map(fn (int $userId) => [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'data' => $data === [] ? null : json_encode($data, JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        ClubNotification::query()->insert($rows);

        return count($rows);
    }

    private function eligibleParents(?int $groupId, string $preference): Collection
    {
        return User::query()
            ->where('status', 'active')
            ->whereNotNull('club_access_approved_at')
            ->whereHas('children.enrollments', function ($query) use ($groupId): void {
                $query->where('status', 'active');
                if ($groupId !== null) {
                    $query->where('kindergarten_group_id', $groupId);
                }
            })
            ->where(function ($query) use ($preference): void {
                $query->whereDoesntHave('clubNotificationPreference')
                    ->orWhereHas('clubNotificationPreference', fn ($settings) => $settings
                        ->where($preference, true));
            })
            ->pluck('id')
            ->unique()
            ->values();
    }
}
