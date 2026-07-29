<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\SiteItem;
use App\Services\ManagedContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(ManagedContent $content): View
    {
        $content->ensureDefaults();

        return view('admin.content.index', [
            'textSections' => $content->groupedTextEntries(),
            'sectionLabels' => $content->sectionLabels(),
            'itemTypeLabels' => $content->itemTypeLabels(),
            'itemsByType' => SiteItem::query()->orderBy('type')->orderBy('sort_order')->orderBy('id')->get()->groupBy('type'),
            'posts' => BlogPost::query()->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('id')->get(),
            'postStatuses' => BlogPost::STATUSES,
        ]);
    }

    public function updateTexts(Request $request, ManagedContent $content): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'array'],
            'content.*' => ['nullable', 'string', 'max:20000'],
        ]);

        $content->saveTextValues($validated['content'], $request->user()?->id);

        return back()->with('success', 'საიტის ტექსტები და საკონტაქტო ინფორმაცია შენახულია.');
    }

    public function storeItem(Request $request, string $type): RedirectResponse
    {
        abort_unless(in_array($type, SiteItem::TYPES, true), 404);

        $validated = $this->validateItem($request);
        $payload = $this->itemPayload($request, $validated);
        $payload['type'] = $type;
        $payload['updated_by'] = $request->user()?->id;

        SiteItem::create($payload);

        return back()->with('success', 'ახალი ჩანაწერი დაემატა და საჯარო საიტზე გამოჩნდება.');
    }

    public function updateItem(Request $request, SiteItem $item): RedirectResponse
    {
        $validated = $this->validateItem($request);
        $payload = $this->itemPayload($request, $validated, $item);
        $payload['updated_by'] = $request->user()?->id;

        $item->update($payload);

        return back()->with('success', 'სექციის ჩანაწერი განახლდა.');
    }

    public function destroyItem(SiteItem $item): RedirectResponse
    {
        $item->delete();

        return back()->with('success', 'ჩანაწერი წაიშალა.');
    }

    public function storeBlog(Request $request, ManagedContent $content): RedirectResponse
    {
        $validated = $this->validateBlog($request);
        $payload = $this->blogPayload($request, $validated);
        $payload['slug'] = $content->uniqueSlug($validated['title']);
        $payload['updated_by'] = $request->user()?->id;

        BlogPost::create($payload);

        return back()->with('success', 'ბლოგის სტატია შეიქმნა.');
    }

    public function updateBlog(Request $request, BlogPost $post, ManagedContent $content): RedirectResponse
    {
        $validated = $this->validateBlog($request);
        $payload = $this->blogPayload($request, $validated, $post);
        $payload['slug'] = $content->uniqueSlug($validated['title'], $post);
        $payload['updated_by'] = $request->user()?->id;

        $post->update($payload);

        return back()->with('success', 'ბლოგის სტატია განახლდა.');
    }

    public function destroyBlog(BlogPost $post): RedirectResponse
    {
        $post->delete();

        return back()->with('success', 'ბლოგის სტატია წაიშალა.');
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:20000'],
            'badge' => ['nullable', 'string', 'max:120'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'meta_json' => ['nullable', 'json', 'max:20000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }

    private function itemPayload(Request $request, array $validated, ?SiteItem $item = null): array
    {
        $payload = [
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'body' => $validated['body'] ?? null,
            'badge' => $validated['badge'] ?? null,
            'color' => $validated['color'] ?? '#A9D3C9',
            'meta' => filled($validated['meta_json'] ?? null) ? json_decode($validated['meta_json'], true, 512, JSON_THROW_ON_ERROR) : [],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
            'image_alt' => $validated['image_alt'] ?? null,
        ];

        if ($request->boolean('remove_image')) {
            $payload = array_merge($payload, ['image' => null, 'image_mime' => null, 'image_name' => null]);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $payload['image'] = file_get_contents($file->getRealPath());
            $payload['image_mime'] = $file->getMimeType();
            $payload['image_name'] = $file->getClientOriginalName();
        } elseif ($item && ! $request->boolean('remove_image')) {
            unset($payload['image'], $payload['image_mime'], $payload['image_name']);
        }

        return $payload;
    }

    private function validateBlog(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'body' => ['nullable', 'string', 'max:50000'],
            'category' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(array_keys(BlogPost::STATUSES))],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'cover_alt' => ['nullable', 'string', 'max:255'],
            'remove_cover' => ['nullable', 'boolean'],
        ]);
    }

    private function blogPayload(Request $request, array $validated, ?BlogPost $post = null): array
    {
        $payload = [
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'] ?? null,
            'category' => $validated['category'] ?? null,
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'cover_alt' => $validated['cover_alt'] ?? null,
        ];

        if ($request->boolean('remove_cover')) {
            $payload = array_merge($payload, ['cover_image' => null, 'cover_mime' => null, 'cover_name' => null]);
        }

        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $payload['cover_image'] = file_get_contents($file->getRealPath());
            $payload['cover_mime'] = $file->getMimeType();
            $payload['cover_name'] = $file->getClientOriginalName();
        } elseif ($post && ! $request->boolean('remove_cover')) {
            unset($payload['cover_image'], $payload['cover_mime'], $payload['cover_name']);
        }

        return $payload;
    }
}
