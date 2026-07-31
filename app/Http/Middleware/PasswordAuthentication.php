<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PasswordAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $contentType = (string) $response->headers->get('Content-Type', '');

        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content)) {
            return $response;
        }

        if (str_contains($content, 'final-site')) {
            $content = $this->replacePublicButtons($content);
            $content = $this->removeLegacyLoginModal($content);
        }

        if (str_contains($content, 'account-status-body') && $request->user()?->password) {
            $content = str_replace(
                [
                    'ტელეფონის დადასტურება დარჩენილია.',
                    'ტელეფონის ნომერი დადასტურებულია.',
                    'ამ ტელეფონის ნომერზე ჩარიცხვის განაცხადი არ მოიძებნა.',
                    '<div><span>ტელეფონი</span><strong></strong></div>',
                ],
                [
                    'ანგარიში დაცულია სახელითა და პაროლით.',
                    'ანგარიში დაცულია სახელითა და პაროლით.',
                    'თქვენს ანგარიშთან ჩარიცხვის განაცხადი ჯერ არ არის დაკავშირებული.',
                    '<div><span>ტელეფონი</span><strong>ჯერ არ არის დამატებული</strong></div>',
                ],
                $content,
            );
        }

        $response->setContent($content);
        $response->headers->remove('Content-Length');

        return $response;
    }

    private function replacePublicButtons(string $content): string
    {
        $pageRoutes = [
            'home' => route('home'),
            'about' => route('public.about'),
            'methodology' => route('public.methodology'),
            'groups' => route('public.groups'),
            'team' => route('public.team'),
            'gallery' => route('auth.credentials.login.form'),
            'blog' => route('public.blog'),
            'faq' => route('public.faq'),
            'contact' => route('public.contact'),
            'admission' => route('public.admission'),
        ];

        return preg_replace_callback(
            '/<button(?P<attributes>[^>]*)>(?P<label>.*?)<\/button>/is',
            function (array $matches) use ($pageRoutes): string {
                $attributes = (string) ($matches['attributes'] ?? '');
                $href = null;

                if (preg_match('/\sdata-page-target=("|\')([^"\']+)\1/i', $attributes, $pageMatch)) {
                    $href = $pageRoutes[$pageMatch[2]] ?? route('home');
                    $attributes = preg_replace('/\sdata-page-target=("|\')[^"\']+\1/i', '', $attributes) ?? $attributes;
                } elseif (preg_match('/\sdata-open-login(?:=("|\')[^"\']*\1)?/i', $attributes)) {
                    $href = route('auth.credentials.login.form');
                    $attributes = preg_replace('/\sdata-open-login(?:=("|\')[^"\']*\1)?/i', '', $attributes) ?? $attributes;
                }

                if ($href === null) {
                    return $matches[0];
                }

                $attributes = preg_replace('/\stype=("|\')button\1/i', '', $attributes) ?? $attributes;
                $attributes = trim($attributes);
                $attributes = $attributes !== '' ? ' '.$attributes : '';

                return '<a'.$attributes.' href="'.e($href).'">'.($matches['label'] ?? '').'</a>';
            },
            $content,
        ) ?? $content;
    }

    private function removeLegacyLoginModal(string $content): string
    {
        $modalStart = strpos($content, '<div class="modal" id="loginModal"');
        if ($modalStart === false) {
            return $content;
        }

        $nextScript = strpos($content, '<script>', $modalStart);
        if ($nextScript === false) {
            return $content;
        }

        return substr_replace($content, '', $modalStart, $nextScript - $modalStart);
    }
}
