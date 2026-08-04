<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\SiteItem;
use App\Services\ManagedContent;
use App\Services\ParentClubContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicContentController extends Controller
{
    public function index(ManagedContent $content): JsonResponse
    {
        $payload = $content->publicPayload();

        foreach (ParentClubContent::TYPES as $privateType) {
            unset($payload[$privateType]);
        }

        return response()->json($payload)
            ->header('Cache-Control', 'no-store, private');
    }

    public function itemImage(
        Request $request,
        SiteItem $item,
        ParentClubContent $clubContent,
    ): Response {
        if (in_array($item->type, ParentClubContent::TYPES, true)) {
            $this->authorizePrivateClubItem($request, $item, $clubContent);
        }

        abort_unless($item->image && $item->image_mime, 404);

        return response($this->readStoredValue($item->image), 200, [
            'Content-Type' => $item->image_mime,
            'Content-Disposition' => 'inline; filename="'.($item->image_name ?: 'image').'"',
            'Cache-Control' => in_array($item->type, ParentClubContent::TYPES, true)
                ? 'private, no-store'
                : 'public, max-age=86400, stale-while-revalidate=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function blogCover(BlogPost $post): Response
    {
        abort_unless($post->cover_image && $post->cover_mime, 404);

        $stored = $this->readStoredValue($post->cover_image);
        $bytes = $post->cover_encoding === 'base64' ? base64_decode($stored, true) : $stored;
        abort_unless(is_string($bytes) && $bytes !== '', 404);

        return response($bytes, 200, [
            'Content-Type' => $post->cover_mime,
            'Content-Disposition' => 'inline; filename="'.($post->cover_name ?: 'cover').'"',
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizePrivateClubItem(
        Request $request,
        SiteItem $item,
        ParentClubContent $clubContent,
    ): void {
        $user = $request->user();
        abort_unless($user && $user->canAccessParentClub(), 403);

        $childIds = $user->children()->pluck('children.id');
        $groupIds = Enrollment::query()
            ->whereIn('child_id', $childIds)
            ->where('status', 'active')
            ->pluck('kindergarten_group_id')
            ->unique();

        $accessibleGroups = KindergartenGroup::query()
            ->whereIn('id', $groupIds)
            ->where('is_active', true)
            ->get(['id', 'name', 'slug']);

        $knownGroups = KindergartenGroup::query()
            ->where('is_active', true)
            ->get(['id', 'name', 'slug']);

        $itemPayload = [
            'badge' => $item->badge,
            'meta' => $item->meta ?: [],
        ];

        abort_unless(
            $accessibleGroups->contains(fn (KindergartenGroup $group): bool => $clubContent->isVisibleToGroup(
                $itemPayload,
                $group,
                $knownGroups,
            )),
            404,
        );
    }

    private function readStoredValue(mixed $value): string
    {
        if (is_resource($value)) {
            $contents = stream_get_contents($value);
            return is_string($contents) ? $contents : '';
        }

        return is_string($value) ? $value : '';
    }
}
