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

        if ($audience === 'group' && $targets->isEmpty()) {
            return false;
        }

        return $targets->isEmpty() || $targets->contains((int) $group->getKey());
    }

    private function targetGroupIds(array $item, Collection $knownGroups): Collection
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
