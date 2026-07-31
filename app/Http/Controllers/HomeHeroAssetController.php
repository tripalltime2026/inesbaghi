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

        return response($hero->image, 200, [
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

        $hero = SiteItem::query()->firstOrNew(['type' => self::TYPE]);

        $hero->fill([
            'title' => 'მთავარი გვერდის სურათი',
            'subtitle' => 'Home hero',
            'body' => null,
            'badge' => null,
            'color' => '#A9D3C9',
            'meta' => [
                'recommended_ratio' => '16:10',
                'placement' => 'home_hero',
            ],
            'sort_order' => 0,
            'updated_by' => $request->user()?->id,
        ]);

        if ($request->boolean('remove_image')) {
            $hero->fill([
                'image' => null,
                'image_mime' => null,
                'image_name' => null,
                'is_active' => false,
            ]);
        } elseif ($request->hasFile('hero_image')) {
            $file = $request->file('hero_image');
            $hero->fill([
                'image' => file_get_contents($file->getRealPath()),
                'image_mime' => $file->getMimeType(),
                'image_name' => $file->getClientOriginalName(),
                'is_active' => true,
            ]);
        } elseif (! $hero->exists || ! $hero->image) {
            return back()
                ->withErrors(['hero_image' => 'აირჩიეთ JPG, PNG ან WebP სურათი.'])
                ->withInput();
        }

        $hero->image_alt = trim((string) ($validated['image_alt'] ?? ''))
            ?: 'ინეს ბაღი — ბავშვზე ორიენტირებული საბავშვო ბაღი ბათუმში';
        $hero->save();

        return back()->with(
            'success',
            $request->boolean('remove_image')
                ? 'მთავარ გვერდზე ნაგულისხმევი ილუსტრაცია აღდგა.'
                : 'მთავარი გვერდის სურათი განახლდა.',
        );
    }
}
