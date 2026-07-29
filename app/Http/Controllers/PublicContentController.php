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

        return response($item->image, 200, [
            'Content-Type' => $item->image_mime,
            'Content-Disposition' => 'inline; filename="'.($item->image_name ?: 'image').'"',
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function blogCover(BlogPost $post): Response
    {
        abort_unless($post->cover_image && $post->cover_mime, 404);

        return response($post->cover_image, 200, [
            'Content-Type' => $post->cover_mime,
            'Content-Disposition' => 'inline; filename="'.($post->cover_name ?: 'cover').'"',
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
