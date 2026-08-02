<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\SiteItem;
use App\Services\ArticleImporter;
use App\Services\ManagedContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ContentController extends Controller
{
    public function index(ManagedContent $content): View
    {
        $content->ensureDefaults();

        return view('admin.content.index', [
            'textSections' => $content->groupedTextEntries(),
            'sectionLabels' => $content->sectionLabels(),
            'itemTypeLabels' => array_merge($content->itemTypeLabels(), [
                'club_post' => 'კლუბის ლენტა',
                'club_event' => 'კლუბის ღონისძიებები',
                'club_poll' => 'კლუბის გამოკითხვები',
                'club_topic' => 'კლუბის ფორუმის თემები',
            ]),
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

        return back()->with('success', 'ახალი ჩანაწერი დაემატა და შესაბამის გვერდზე გამოჩნდება.');
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

    public function importBlog(Request $request, ArticleImporter $importer, ManagedContent $content): RedirectResponse
    {
        $validated = $request->validate([
            'source_url' => ['required', 'url:http,https', 'max:2048'],
        ]);

        try {
            $imported = $importer->import($validated['source_url']);

            DB::transaction(function () use ($imported, $content, $request): void {
                BlogPost::create(array_merge($imported, [
                    'slug' => $content->uniqueSlug($imported['title']),
                    'status' => 'draft',
                    'published_at' => null,
                    'sort_order' => 0,
                    'updated_by' => $request->user()?->id,
                ]));
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['source_url' => $exception->getMessage() ?: 'სტატიის იმპორტი ვერ მოხერხდა.'])
                ->withInput();
        }

        return back()->with('success', 'Marketer.ge-ის სტატია დრაფტად დაემატა. გადაამოწმეთ ტექსტი და შემდეგ გამოაქვეყნეთ.');
    }

    public function storeBlog(Request $request, ManagedContent $content): RedirectResponse
    {
        $validated = $this->validateBlog($request);

        try {
            DB::transaction(function () use ($request, $content, $validated): void {
                $payload = $this->blogPayload($request, $validated);
                $payload['slug'] = $content->uniqueSlug($validated['title']);
                $payload['updated_by'] = $request->user()?->id;

                BlogPost::create($payload);
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['cover' => 'სტატიის შენახვა ვერ მოხერხდა. გადაამოწმეთ ქავერის ფორმატი და სცადეთ თავიდან.'])
                ->withInput();
        }

        return back()->with('success', 'ბლოგის სტატია შეიქმნა და სტატუსის შესაბამისად გამოქვეყნდა.');
    }

    public function updateBlog(Request $request, BlogPost $post, ManagedContent $content): RedirectResponse
    {
        $validated = $this->validateBlog($request);

        try {
            DB::transaction(function () use ($request, $post, $content, $validated): void {
                $payload = $this->blogPayload($request, $validated, $post);
                $payload['slug'] = $content->uniqueSlug($validated['title'], $post);
                $payload['updated_by'] = $request->user()?->id;

                $post->update($payload);
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['cover' => 'სტატიის განახლება ვერ მოხერხდა. გადაამოწმეთ ქავერის ფორმატი და სცადეთ თავიდან.'])
                ->withInput();
        }

        return back()->with('success', 'ბლოგის სტატია განახლდა და სტატუსის შესაბამისად გამოქვეყნდა.');
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
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'source_name' => ['nullable', 'string', 'max:120'],
            'source_published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'cover_alt' => ['nullable', 'string', 'max:255'],
            'remove_cover' => ['nullable', 'boolean'],
        ]);
    }

    private function blogPayload(Request $request, array $validated, ?BlogPost $post = null): array
    {
        $status = $validated['status'];
        $publishedAt = $validated['published_at'] ?? null;

        if ($status === 'published' && blank($publishedAt)) {
            $publishedAt = now();
        }

        $payload = [
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'] ?? null,
            'category' => $validated['category'] ?? null,
            'status' => $status,
            'published_at' => $publishedAt,
            'source_url' => filled($validated['source_url'] ?? null) ? trim($validated['source_url']) : null,
            'source_name' => filled($validated['source_name'] ?? null) ? trim($validated['source_name']) : null,
            'source_published_at' => $validated['source_published_at'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'cover_alt' => $validated['cover_alt'] ?? null,
        ];

        if ($request->boolean('remove_cover')) {
            $payload = array_merge($payload, [
                'cover_image' => null,
                'cover_encoding' => null,
                'cover_mime' => null,
                'cover_name' => null,
            ]);
        }

        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $rawImage = file_get_contents($file->getRealPath());

            if (! is_string($rawImage) || $rawImage === '') {
                throw new \RuntimeException('ქავერის წაკითხვა ვერ მოხერხდა.');
            }

            $payload['cover_image'] = base64_encode($rawImage);
            $payload['cover_encoding'] = 'base64';
            $payload['cover_mime'] = $file->getMimeType();
            $payload['cover_name'] = $file->getClientOriginalName();
        } elseif ($post && ! $request->boolean('remove_cover')) {
            unset($payload['cover_image'], $payload['cover_encoding'], $payload['cover_mime'], $payload['cover_name']);
        }

        return $payload;
    }
}
