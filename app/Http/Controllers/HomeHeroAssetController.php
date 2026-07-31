<?php

namespace App\Http\Controllers;

use App\Models\SiteItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeHeroAssetController extends Controller
{
    private const TYPE = 'home_hero';

    public function image(): Response
    {
        $hero = SiteItem::query()
            ->where('type', self::TYPE)
            ->where('is_active', true)
            ->whereNotNull('image')
            ->first();

        abort_unless($hero && $hero->image_mime, 404);

        $storedImage = $this->readStoredValue($hero->image);
        $imageBytes = ($hero->meta['image_encoding'] ?? null) === 'base64'
            ? base64_decode($storedImage, true)
            : $storedImage;

        abort_unless(is_string($imageBytes) && $imageBytes !== '', 404);

        return response($imageBytes, 200, [
            'Content-Type' => $hero->image_mime,
            'Content-Disposition' => 'inline; filename="'.($hero->image_name ?: 'ines-home-hero').'"',
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        try {
            $hero = SiteItem::query()->firstOrNew(['type' => self::TYPE]);
            $meta = [
                'recommended_ratio' => '16:10',
                'placement' => 'home_hero',
            ];

            $hero->fill([
                'title' => 'მთავარი გვერდის სურათი',
                'subtitle' => 'Home hero',
                'body' => null,
                'badge' => null,
                'color' => '#A9D3C9',
                'sort_order' => 0,
                'updated_by' => $request->user()?->id,
            ]);

            if ($request->boolean('remove_image')) {
                $hero->fill([
                    'image' => null,
                    'image_mime' => null,
                    'image_name' => null,
                    'is_active' => false,
                    'meta' => $meta,
                ]);
            } elseif ($request->hasFile('hero_image')) {
                $file = $request->file('hero_image');
                $rawImage = file_get_contents($file->getRealPath());

                if (! is_string($rawImage) || $rawImage === '') {
                    return back()
                        ->withErrors(['hero_image' => 'სურათის წაკითხვა ვერ მოხერხდა. სცადეთ სხვა JPG, PNG ან WebP ფაილი.'])
                        ->withInput();
                }

                // Store ASCII-safe content so PostgreSQL never interprets raw image
                // bytes as UTF-8 text when binding the bytea column.
                $hero->fill([
                    'image' => base64_encode($rawImage),
                    'image_mime' => $file->getMimeType(),
                    'image_name' => $file->getClientOriginalName(),
                    'is_active' => true,
                    'meta' => array_merge($meta, ['image_encoding' => 'base64']),
                ]);
            } elseif (! $hero->exists || ! $hero->image) {
                return back()
                    ->withErrors(['hero_image' => 'აირჩიეთ JPG, PNG ან WebP სურათი.'])
                    ->withInput();
            } else {
                $hero->meta = array_merge($meta, is_array($hero->meta) ? $hero->meta : []);
            }

            $hero->image_alt = trim((string) ($validated['image_alt'] ?? ''))
                ?: 'ინეს ბაღი — ბავშვზე ორიენტირებული საბავშვო ბაღი ბათუმში';
            $hero->save();
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['hero_image' => 'სურათის შენახვა დროებით ვერ მოხერხდა. სცადეთ უფრო მცირე JPG, PNG ან WebP ფაილი.'])
                ->withInput();
        }

        return back()->with(
            'success',
            $request->boolean('remove_image')
                ? 'მთავარ გვერდზე ნაგულისხმევი ილუსტრაცია აღდგა.'
                : 'მთავარი გვერდის სურათი განახლდა.',
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
