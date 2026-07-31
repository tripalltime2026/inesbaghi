<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GoogleOnlyAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $google = (array) config('services.google', []);
        $googleConfigured = filled($google['client_id'] ?? null)
            && filled($google['client_secret'] ?? null)
            && filled($google['redirect'] ?? null);
        $legacyEnabled = ! $googleConfigured
            || app()->environment('testing')
            || config('services.legacy_phone_auth.enabled', false);

        if (! $legacyEnabled && $this->isLegacyAuthRequest($request)) {
            abort(404);
        }

        $response = $next($request);
        $contentType = (string) $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content)) {
            return $response;
        }

        if (str_contains($content, 'account-status-body') && $request->user()?->google_id) {
            $content = str_replace(
                [
                    'status-step waiting"><span>1</span><h2>ანგარიში</h2><p>ტელეფონის დადასტურება დარჩენილია.',
                    '<div><span>ტელეფონი</span><strong></strong></div>',
                    'ამ ტელეფონის ნომერზე ჩარიცხვის განაცხადი არ მოიძებნა.',
                ],
                [
                    'status-step done"><span>1</span><h2>ანგარიში</h2><p>Google ანგარიში და ელფოსტა დადასტურებულია.',
                    '<div><span>ელფოსტა</span><strong>'.e((string) $request->user()->email).'</strong></div>',
                    'თქვენს ანგარიშთან ჩარიცხვის განაცხადი ჯერ არ არის დაკავშირებული.',
                ],
                $content,
            );
            $response->setContent($content);
            $response->headers->remove('Content-Length');

            return $response;
        }

        if (! $googleConfigured || ! str_contains($content, 'final-site')) {
            return $response;
        }

        $modal = $this->modal((string) $request->session()->get('google_auth_error', ''));
        $modalStart = strpos($content, '<div class="modal" id="loginModal"');
        if ($modalStart !== false && ($nextScript = strpos($content, '<script>', $modalStart)) !== false) {
            $content = substr_replace($content, $modal."\n\n", $modalStart, $nextScript - $modalStart);
        } elseif (str_contains($content, '</body>')) {
            $content = str_replace('</body>', $modal."\n</body>", $content);
        }

        $content = str_replace('</head>', '    <link rel="stylesheet" href="/css/google-auth.css?v=20260731b">'."\n</head>", $content);
        $content = str_replace('</body>', '    <script src="/js/google-auth.js?v=20260731b" defer></script>'."\n</body>", $content);

        $response->setContent($content);
        $response->headers->remove('Content-Length');

        return $response;
    }

    private function isLegacyAuthRequest(Request $request): bool
    {
        return $request->is('auth/mode') || $request->is('auth/demo/*') || $request->is('auth/phone/*');
    }

    private function modal(string $error): string
    {
        $errorBlock = $error !== '' ? '<div class="google-auth-error" data-google-auth-error>'.e($error).'</div>' : '';

        return '<div class="modal google-auth-modal" id="loginModal" role="dialog" aria-modal="true" aria-labelledby="loginTitle"><div class="modal-card compact google-auth-card">'
            .'<button class="modal-close" type="button" data-close-login aria-label="დახურვა">×</button><span class="section-badge mint">შესვლა / რეგისტრაცია</span>'
            .'<h2 id="loginTitle">შესვლა Google-ით</h2><p>ერთი მოქმედებით შედით ან შექმენით თქვენი ანგარიში.</p>'.$errorBlock
            .'<a class="google-auth-button" href="'.e(route('auth.google.redirect')).'" data-google-auth-start><span class="google-auth-mark">G</span><span>Google-ით გაგრძელება</span></a>'
            .'<div class="google-auth-trust"><strong>Google-იდან მივიღებთ</strong><span>სახელს, დადასტურებულ ელფოსტას და პროფილის ფოტოს, როცა ის ხელმისაწვდომია.</span></div>'
            .'<p class="google-auth-privacy">Google-ით გაგრძელებით ადასტურებთ, რომ გაეცანით <a href="/privacy">კონფიდენციალურობის პოლიტიკას</a>. ახალი ანგარიში იქმნება ჩვეულებრივი მომხმარებლის სტატუსით.</p></div></div>';
    }
}
