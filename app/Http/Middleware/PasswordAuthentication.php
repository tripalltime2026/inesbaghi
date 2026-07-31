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
            $content = $this->injectStaticMobileNav($content, $request);
        }

        if (str_contains($content, 'account-status-body') && $request->user()?->password) {
            $content = str_replace(
                [
                    'status-step waiting"><span>1</span><h2>ანგარიში</h2>',
                    'ტელეფონის დადასტურება დარჩენილია.',
                    'ტელეფონის ნომერი დადასტურებულია.',
                    'ამ ტელეფონის ნომერზე ჩარიცხვის განაცხადი არ მოიძებნა.',
                    '<div><span>ტელეფონი</span><strong></strong></div>',
                    '<h2>ანგარიშის ინფორმაცია</h2>',
                ],
                [
                    'status-step done"><span>1</span><h2>ანგარიში</h2>',
                    'ანგარიში დაცულია სახელითა და პაროლით.',
                    'ანგარიში დაცულია სახელითა და პაროლით.',
                    'თქვენს ანგარიშთან ჩარიცხვის განაცხადი ჯერ არ არის დაკავშირებული.',
                    '<div><span>ტელეფონი</span><strong>ჯერ არ არის დამატებული</strong></div>',
                    '<h2>ანგარიშის ინფორმაცია</h2><a class="account-cta" href="'.e(route('account.profile')).'">პროფილის რედაქტირება →</a>',
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
                $label = (string) ($matches['label'] ?? '');
                $href = null;

                if (preg_match('/\sdata-page-target=("|\')([^"\']+)\1/i', $attributes, $pageMatch)) {
                    $href = $pageRoutes[$pageMatch[2]] ?? route('home');
                    $attributes = preg_replace('/\sdata-page-target=("|\')[^"\']+\1/i', '', $attributes) ?? $attributes;
                } elseif (preg_match('/\sdata-open-login(?:=("|\')[^"\']*\1)?/i', $attributes)) {
                    $plainLabel = strip_tags($label);
                    $registerAction = str_contains($plainLabel, 'შემოგვიერთდი')
                        || str_contains($plainLabel, 'რეგისტრაცია')
                        || str_contains($plainLabel, 'მშობელთა კლუბი');
                    $href = $registerAction
                        ? route('auth.credentials.register.form')
                        : route('auth.credentials.login.form');
                    $attributes = preg_replace('/\sdata-open-login(?:=("|\')[^"\']*\1)?/i', '', $attributes) ?? $attributes;
                }

                if ($href === null) {
                    return $matches[0];
                }

                $attributes = preg_replace('/\stype=("|\')button\1/i', '', $attributes) ?? $attributes;
                $attributes = trim($attributes);
                $attributes = $attributes !== '' ? ' '.$attributes : '';

                return '<a'.$attributes.' href="'.e($href).'">'.$label.'</a>';
            },
            $content,
        ) ?? $content;
    }

    private function injectStaticMobileNav(string $content, Request $request): string
    {
        if (str_contains($content, 'password-mobile-nav') || ! str_contains($content, '</body>')) {
            return $content;
        }

        $user = $request->user();
        $accountUrl = match (true) {
            $user?->hasRole('admin') => route('admin.dashboard'),
            $user?->hasRole('finance') => route('admin.payments.index'),
            $user?->hasRole('teacher') => route('admin.attendance.index'),
            $user !== null => route('account.status'),
            default => route('auth.credentials.login.form'),
        };
        $accountLabel = $user ? 'კაბინეტი' : 'შესვლა';

        $nav = '<nav class="mobile-app-nav public-mobile-nav password-mobile-nav" aria-label="მობილური ნავიგაცია">'
            .'<a data-mobile-key="home" href="'.e(route('home')).'"><i>⌂</i><span>მთავარი</span></a>'
            .'<a data-mobile-key="groups" href="'.e(route('public.groups')).'"><i>◫</i><span>ჯგუფები</span></a>'
            .'<a class="accent" data-mobile-key="admission" href="'.e(route('public.admission')).'"><i>＋</i><span>ჩარიცხვა</span></a>'
            .'<a data-mobile-key="account" href="'.e($accountUrl).'"><i>●</i><span>'.$accountLabel.'</span></a>'
            .'</nav>';

        return str_replace('</body>', $nav."\n</body>", $content);
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
