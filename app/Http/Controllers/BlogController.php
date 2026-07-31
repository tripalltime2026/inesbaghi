<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Services\ManagedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function show(string $slug, ManagedContent $content): Response
    {
        $content->ensureDefaults();

        $post = $this->publishedPosts()
            ->where('slug', $slug)
            ->firstOrFail();

        $related = $this->relatedPosts($post);
        $canonical = rtrim((string) config('seo.site_url'), '/').'/blogi/'.$post->slug;
        $description = filled($post->excerpt)
            ? Str::limit(trim(strip_tags((string) $post->excerpt)), 180, '')
            : Str::limit(trim(strip_tags((string) $post->body)), 180, '');
        $bodyBlocks = collect(preg_split('/\R{2,}/u', trim((string) $post->body)) ?: [])
            ->map(fn (string $block): string => trim($block))
            ->filter()
            ->values();
        $wordCount = count(preg_split('/\s+/u', trim(strip_tags((string) $post->body)), -1, PREG_SPLIT_NO_EMPTY) ?: []);
        $readMinutes = max(1, (int) ceil($wordCount / 180));
        $imageUrl = $post->cover_image
            ? route('content.blog-cover', $post)
            : rtrim((string) config('seo.site_url'), '/').(string) config('seo.image');

        $response = response()->view('public.blog-post', [
            'post' => $post,
            'relatedPosts' => $related,
            'canonical' => $canonical,
            'description' => $description,
            'bodyBlocks' => $bodyBlocks,
            'readMinutes' => $readMinutes,
            'imageUrl' => $imageUrl,
            'publishedDate' => $this->formatDate($post->published_at ?? $post->created_at),
        ]);

        $response->headers->set('X-Robots-Tag', 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');

        return $response;
    }

    private function relatedPosts(BlogPost $post)
    {
        $related = collect();

        if (filled($post->category)) {
            $related = $this->publishedPosts()
                ->whereKeyNot($post->getKey())
                ->where('category', $post->category)
                ->limit(3)
                ->get();
        }

        if ($related->count() < 3) {
            $extra = $this->publishedPosts()
                ->whereKeyNot($post->getKey())
                ->whereNotIn('id', $related->pluck('id'))
                ->limit(3 - $related->count())
                ->get();

            $related = $related->concat($extra);
        }

        return $related->values();
    }

    private function publishedPosts(): Builder
    {
        return BlogPost::query()
            ->where('status', 'published')
            ->where(function ($query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    private function formatDate($date): string
    {
        if (! $date) {
            return '';
        }

        $months = [
            1 => 'იანვარი',
            2 => 'თებერვალი',
            3 => 'მარტი',
            4 => 'აპრილი',
            5 => 'მაისი',
            6 => 'ივნისი',
            7 => 'ივლისი',
            8 => 'აგვისტო',
            9 => 'სექტემბერი',
            10 => 'ოქტომბერი',
            11 => 'ნოემბერი',
            12 => 'დეკემბერი',
        ];

        return $date->format('j').' '.$months[(int) $date->format('n')].', '.$date->format('Y');
    }
}
