<?php

namespace App\Http\Middleware;

use App\Models\SiteItem;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class InjectHomeHeroManager
{
    private const TYPE = 'home_hero';

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! ($request->routeIs('home') || $request->routeIs('admin.content.index'))) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || $html === '') {
            return $response;
        }

        try {
            $hero = $this->hero();

            if ($request->routeIs('home')) {
                $html = $this->replacePublicHero($html, $hero);
            }

            if ($request->routeIs('admin.content.index')) {
                $html = $this->injectAdminManager($html, $hero);
            }

            $response->setContent($html);
            $response->headers->remove('Content-Length');
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function hero(): ?SiteItem
    {
        if (! Schema::hasTable('site_items')) {
            return null;
        }

        return SiteItem::query()->where('type', self::TYPE)->first();
    }

    private function replacePublicHero(string $html, ?SiteItem $hero): string
    {
        if (! $hero || ! $hero->is_active || ! $hero->image) {
            return $html;
        }

        $src = $this->escape(route('content.home-hero', [
            'v' => $hero->updated_at?->timestamp ?: $hero->id,
        ]));
        $alt = $this->escape($hero->image_alt ?: 'ინეს ბაღი — საბავშვო ბაღი ბათუმში');
        $replacement = '<div class="hero-art" role="img" aria-label="'.$alt.'">'
            .'<img src="'.$src.'" alt="'.$alt.'" decoding="async" fetchpriority="high">'
            .'</div>';

        return preg_replace(
            '/<div class="hero-art"[^>]*>\s*<img\b[^>]*>\s*<\/div>/i',
            $replacement,
            $html,
            1,
        ) ?? $html;
    }

    private function injectAdminManager(string $html, ?SiteItem $hero): string
    {
        if (str_contains($html, 'data-home-hero-manager')) {
            return $html;
        }

        $stylesheet = '<link rel="stylesheet" href="'.asset('css/home-hero-manager.css').'?v=20260731">';
        if (! str_contains($html, 'home-hero-manager.css')) {
            $html = str_replace('</head>', $stylesheet."\n</head>", $html);
        }

        $navLink = '<a href="#cms-home-hero">მთავარი სურათი</a>';
        if (str_contains($html, '<a href="#cms-texts">')) {
            $html = str_replace('<a href="#cms-texts">', $navLink."\n    ".'<a href="#cms-texts">', $html);
        }

        $previewSrc = $hero && $hero->is_active && $hero->image
            ? route('content.home-hero', ['v' => $hero->updated_at?->timestamp ?: $hero->id])
            : asset('images/ines-final-hero.svg');
        $previewAlt = $hero?->image_alt ?: 'ინეს ბაღის მთავარი გვერდის სურათი';
        $hasCustomImage = (bool) ($hero && $hero->is_active && $hero->image);
        $fileName = $hasCustomImage ? ($hero->image_name ?: 'ატვირთული სურათი') : 'ნაგულისხმევი ილუსტრაცია';

        $section = $this->adminSection(
            $previewSrc,
            $previewAlt,
            $fileName,
            $hasCustomImage,
        );

        return str_replace(
            '<section class="cms-block" id="cms-texts">',
            $section."\n\n".'<section class="cms-block" id="cms-texts">',
            $html,
        );
    }

    private function adminSection(
        string $previewSrc,
        string $previewAlt,
        string $fileName,
        bool $hasCustomImage,
    ): string {
        $action = $this->escape(route('admin.content.hero.update'));
        $previewSrc = $this->escape($previewSrc);
        $previewAlt = $this->escape($previewAlt);
        $fileName = $this->escape($fileName);
        $csrf = $this->escape(csrf_token());
        $removeControl = $hasCustomImage
            ? '<label class="hero-remove-option"><input type="checkbox" name="remove_image" value="1"><span>ატვირთული სურათის წაშლა და ნაგულისხმევი ილუსტრაციის დაბრუნება</span></label>'
            : '';
        $status = $hasCustomImage ? 'ატვირთული სურათი აქტიურია' : 'ამჟამად ნაგულისხმევი ილუსტრაცია გამოიყენება';

        return <<<HTML
<section class="cms-block cms-home-hero-manager" id="cms-home-hero" data-home-hero-manager>
    <div class="cms-block-head">
        <div>
            <p class="eyebrow">მთავარი გვერდი</p>
            <h2>მთავარი სურათი</h2>
            <p>ატვირთეთ სასურველი ფოტო ან ილუსტრაცია. ცვლილება მთავარ გვერდზე ავტომატურად გამოჩნდება.</p>
        </div>
        <span class="cms-count">{$status}</span>
    </div>
    <div class="hero-manager-grid">
        <figure class="hero-manager-preview">
            <img src="{$previewSrc}" alt="{$previewAlt}">
            <figcaption>{$fileName}</figcaption>
        </figure>
        <form method="post" action="{$action}" enctype="multipart/form-data" class="hero-manager-form">
            <input type="hidden" name="_token" value="{$csrf}">
            <input type="hidden" name="_method" value="PUT">
            <label>
                <span>ახალი სურათის ატვირთვა</span>
                <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp">
                <small>ფორმატი: JPG, PNG ან WebP · მაქსიმუმ 8 MB · რეკომენდებული ზომა 1600×1000 px.</small>
            </label>
            <label>
                <span>სურათის აღწერა (ALT)</span>
                <input type="text" name="image_alt" maxlength="255" value="{$previewAlt}">
                <small>მოკლედ აღწერეთ, რა ჩანს სურათზე. ეს ეხმარება SEO-სა და ხელმისაწვდომობას.</small>
            </label>
            {$removeControl}
            <button class="primary" type="submit">მთავარი სურათის შენახვა</button>
        </form>
    </div>
</section>
HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
