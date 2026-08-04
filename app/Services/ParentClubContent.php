<?php

namespace App\Services;

use App\Models\KindergartenGroup;
use Illuminate\Support\Collection;

class ParentClubContent
{
    public const TYPES = ['club_post', 'club_event', 'club_poll', 'club_topic'];

    public function forGroup(array $payload, KindergartenGroup $group, Collection $knownGroups): array
    {
        $result = [];

        foreach (self::TYPES as $type) {
            $result[$type] = collect($payload[$type] ?? [])
                ->filter(fn (array $item): bool => $this->isVisibleToGroup($item, $group, $knownGroups))
                ->map(function (array $item) use ($group, $knownGroups): array {
                    $targets = $this->targetGroupIds($item, $knownGroups);

                    $item['visibility_label'] = $targets->isEmpty()
                        ? 'ყველა ჯგუფი'
                        : $group->name;
                    $item['kindergarten_group_ids'] = $targets->values()->all();

                    return $item;
                })
                ->values()
                ->all();
        }

        return $result;
    }

    public function isVisibleToGroup(array $item, KindergartenGroup $group, Collection $knownGroups): bool
    {
        $meta = is_array($item['meta'] ?? null) ? $item['meta'] : [];
        $audience = mb_strtolower(trim((string) ($meta['audience'] ?? '')));
        $targets = $this->targetGroupIds($item, $knownGroups);

        if (in_array($audience, ['all', 'all_groups', 'club'], true)) {
            return true;
        }

        if ($targets->isEmpty() && $this->hasExplicitGroupTarget($item)) {
            return false;
        }

        return $targets->isEmpty() || $targets->contains((int) $group->getKey());
    }

    private function targetGroupIds(array $item, Collection $knownGroups): Collection
    {
        $tokens = $this->targetTokens($item);

        if ($tokens->isEmpty()) {
            return collect();
        }

        $targetIds = collect();

        foreach ($knownGroups as $knownGroup) {
            if (! $knownGroup instanceof KindergartenGroup) {
                continue;
            }

            foreach ($tokens as $token) {
                if ($this->tokenMatchesGroup($token, $knownGroup)) {
                    $targetIds->push((int) $knownGroup->getKey());
                    break;
                }
            }
        }

        return $targetIds->unique()->values();
    }

    private function targetTokens(array $item): Collection
    {
        $meta = is_array($item['meta'] ?? null) ? $item['meta'] : [];
        $tokens = collect();

        foreach (['kindergarten_group_id', 'group_id'] as $key) {
            if (isset($meta[$key]) && $meta[$key] !== '') {
                $tokens->push($meta[$key]);
            }
        }

        foreach (['kindergarten_group_ids', 'group_ids'] as $key) {
            $value = $meta[$key] ?? [];
            if (is_array($value)) {
                $tokens = $tokens->concat($value);
            }
        }

        foreach (['kindergarten_group_slug', 'group_slug', 'group'] as $key) {
            if (filled($meta[$key] ?? null)) {
                $tokens->push($meta[$key]);
            }
        }

        $badge = trim((string) ($item['badge'] ?? ''));
        if ($badge !== '') {
            $tokens->push($badge);
        }

        return $tokens->filter(fn ($token): bool => trim((string) $token) !== '')->values();
    }

    private function hasExplicitGroupTarget(array $item): bool
    {
        $meta = is_array($item['meta'] ?? null) ? $item['meta'] : [];
        $audience = mb_strtolower(trim((string) ($meta['audience'] ?? '')));

        if ($audience === 'group') {
            return true;
        }

        foreach ([
            'kindergarten_group_id',
            'group_id',
            'kindergarten_group_ids',
            'group_ids',
            'kindergarten_group_slug',
            'group_slug',
            'group',
        ] as $key) {
            if (filled($meta[$key] ?? null)) {
                return true;
            }
        }

        $badge = mb_strtolower(trim((string) ($item['badge'] ?? '')));

        return str_contains($badge, 'ჯგუფი:')
            || str_contains($badge, 'group:');
    }

    private function tokenMatchesGroup(mixed $token, KindergartenGroup $group): bool
    {
        if (is_numeric($token) && (int) $token === (int) $group->getKey()) {
            return true;
        }

        $value = mb_strtolower(trim((string) $token));
        if ($value === '') {
            return false;
        }

        $name = mb_strtolower((string) $group->name);
        $slug = mb_strtolower((string) $group->slug);

        return $value === $name
            || $value === $slug
            || str_contains($value, $name)
            || preg_match('/(^|[^0-9])'.preg_quote($slug, '/').'([^0-9]|$)/u', $value) === 1;
    }
}
