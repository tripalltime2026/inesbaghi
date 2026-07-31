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

        $content = $this->replaceLoginButtonsWithGoogleLinks($content);
        $content = $this->removeLegacyLoginModal($content);
        $content = $this->injectGoogleError($content, (string) $request->session()->get('google_auth_error', ''));

        if (! str_contains($content, '/css/google-auth.css')) {
            $content = str_replace(
                '</head>',
                '    <link rel="stylesheet" href="/css/google-auth.css?v=20260731c">'."\n</head>",
                $content,
            );
        }

        $response->setContent($content);
        $response->headers->remove('Content-Length');

        return $response;
    }

    private function isLegacyAuthRequest(Request $request): bool
    {
        return $request->is('auth/mode') || $request->is('auth/demo/*') || $request->is('auth/phone/*');
    }

    private function replaceLoginButtonsWithGoogleLinks(string $content): string
    {
        $authUrl = e(route('auth.google.redirect'));

        return preg_replace_callback(
            '/<button(?P<before>[^>]*)\sdata-open-login(?P<after>[^>]*)>(?P<label>.*?)<\/button>/is',
            function (array $matches) use ($authUrl): string {
                $attributes = ($matches['before'] ?? '').($matches['after'] ?? '');
                $attributes = preg_replace('/\s+type=("|\')button\1/i', '', $attributes) ?? $attributes;
                $attributes = preg_replace('/\s+data-open-login(?:=("|\')[^"\']*\1)?/i', '', $attributes) ?? $attributes;
                $attributes = rtrim($attributes);

                return '<a'.$attributes.' href="'.$authUrl.'" data-google-auth-link>'
                    .($matches['label'] ?? '')
                    .'</a>';
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

    private function injectGoogleError(string $content, string $error): string
    {
        if ($error === '' || ! str_contains($content, '<main id="publicApp">')) {
            return $content;
        }

        $message = '<div class="google-auth-inline-error" role="alert">'.e($error).'</div>';

        return str_replace('<main id="publicApp">', $message."\n<main id=\"publicApp\">", $content);
    }
}
