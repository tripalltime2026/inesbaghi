<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\SiteItem;
use App\Services\ManagedContent;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PublicContentController extends Controller
{
    public function index(ManagedContent $content): JsonResponse
    {
        return response()->json($content->publicPayload())
            ->header('Cache-Control', 'no-store, private');
    }

    public function itemImage(SiteItem $item): Response
    {
        abort_unless($item->image && $item->image_mime, 404);

        return response($this->readStoredValue($item->image), 200, [
            'Content-Type' => $item->image_mime,
            'Content-Disposition' => 'inline; filename="'.($item->image_name ?: 'image').'"',
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
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

    private function readStoredValue(mixed $value): string
    {
        if (is_resource($value)) {
            $contents = stream_get_contents($value);
            return is_string($contents) ? $contents : '';
        }

        return is_string($value) ? $value : '';
    }
}
