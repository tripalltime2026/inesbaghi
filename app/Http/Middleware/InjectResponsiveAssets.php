<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectResponsiveAssets
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

        if (! is_string($content) || ! str_contains($content, '</head>')) {
            return $response;
        }

        $content = str_replace(
            'width=device-width, initial-scale=1',
            'width=device-width, initial-scale=1, viewport-fit=cover',
            $content,
        );

        $isPublicSite = str_contains($content, 'class="final-site"');
        if ($isPublicSite) {
            $content = $this->injectPublicLegalControls($content);
        }

        $headAssets = [];

        if (! str_contains($content, '/css/mobile.css')) {
            $headAssets[] = '<meta name="theme-color" content="#FBF7EC">';
            $headAssets[] = '<link rel="stylesheet" href="/css/mobile.css?v=20260729">';
        }

        if (! str_contains($content, '/css/experience-v2.css')) {
            $headAssets[] = '<link rel="stylesheet" href="/css/experience-v2.css?v=20260729b">';
        }

        if (! str_contains($content, '/css/home-mobile-v3.css')) {
            $headAssets[] = '<link rel="stylesheet" href="/css/home-mobile-v3.css?v=20260729c">';
        }

        if (! str_contains($content, '/css/privacy-compliance.css')) {
            $headAssets[] = '<link rel="stylesheet" href="/css/privacy-compliance.css?v=20260730b">';
        }

        if (! str_contains($content, '/css/access-control.css')) {
            $headAssets[] = '<link rel="stylesheet" href="/css/access-control.css?v=20260730">';
        }

        $isParentClub = str_contains($content, 'class="club-body"');
        if ($isParentClub && ! str_contains($content, '/css/parent-forum.css')) {
            $headAssets[] = '<link rel="stylesheet" href="/css/parent-forum.css?v=20260730">';
        }

        if ($headAssets !== []) {
            $content = str_replace('</head>', '    '.implode("\n    ", $headAssets)."\n</head>", $content);
        }

        if (str_contains($content, '</body>')) {
            $scripts = [];
            if (! str_contains($content, '/js/experience-v2.js')) {
                $scripts[] = '<script src="/js/experience-v2.js?v=20260729b" defer></script>';
            }
            if (! str_contains($content, '/js/experience-v2-compat.js')) {
                $scripts[] = '<script src="/js/experience-v2-compat.js?v=20260729b" defer></script>';
            }
            if (! str_contains($content, '/js/privacy-compliance.js')) {
                $scripts[] = '<script src="/js/privacy-compliance.js?v=20260730b" defer></script>';
            }
            if ($isPublicSite && ! str_contains($content, '/js/auth-access-control.js')) {
                $scripts[] = '<script src="/js/auth-access-control.js?v=20260730" defer></script>';
            }
            if ($isParentClub && ! str_contains($content, '/js/parent-forum.js')) {
                $scripts[] = '<script src="/js/parent-forum.js?v=20260730" defer></script>';
            }
            if ($scripts !== []) {
                $content = str_replace('</body>', '    '.implode("\n    ", $scripts)."\n</body>", $content);
            }
        }

        $response->setContent($content);
        $response->headers->remove('Content-Length');

        return $response;
    }

    private function injectPublicLegalControls(string $content): string
    {
        $content = preg_replace(
            '/\s*<label><span>ბავშვის სახელი და გვარი \*<\/span><input name="child_name"[^>]*><\/label>/u',
            '',
            $content,
            1,
        ) ?? $content;

        if (! str_contains($content, 'data-admission-privacy')) {
            $admissionConsent = <<<'HTML'
                <div class="legal-consent-stack" data-admission-privacy>
                    <label class="legal-consent-box required"><input type="checkbox" name="guardian_authority_confirmed" value="1" required><span>ვადასტურებ, რომ ვარ ბავშვის მშობელი ან სხვა კანონიერი წარმომადგენელი და უფლებამოსილი ვარ ბავშვის მონაცემების მიწოდებაზე.</span></label>
                    <label class="legal-consent-box required"><input type="checkbox" name="privacy_accepted" value="1" required><span>გავეცანი <a href="/privacy" target="_blank" rel="noopener">კონფიდენციალურობის პოლიტიკას</a> და <a href="/terms" target="_blank" rel="noopener">სარგებლობის პირობებს</a>. ვადასტურებ განაცხადის განხილვისთვის აუცილებელი ჩემი და ბავშვის მონაცემების დამუშავებას.</span></label>
                    <label class="legal-consent-box required"><input type="checkbox" name="special_category_consent" value="1" required><span>ვაძლევ წერილობით ელექტრონულ თანხმობას ჩემ მიერ ნებაყოფლობით მითითებული ბავშვის ჯანმრთელობის ან სხვა განსაკუთრებული კატეგორიის მონაცემების დამუშავებაზე ბავშვის უსაფრთხოებისა და ინდივიდუალური საჭიროებების გათვალისწინებისთვის.</span></label>
                    <label class="legal-consent-box optional"><input type="checkbox" name="marketing_consent" value="1"><span>მსურს მივიღო ბაღის სიახლეები, ღონისძიებების ინფორმაცია და შეთავაზებები მითითებულ ნომერზე.</span></label>
                    <p class="legal-consent-summary">სარეკლამო შეტყობინებების მიღება არჩევითია. ფოტო/ვიდეომასალის გამოყენება საჭიროებს ცალკე თანხმობას და ჩარიცხვის პირობა არ არის.</p>
                    <p class="legal-field-error">გასაგრძელებლად მონიშნეთ ყველა სავალდებულო დადასტურება.</p>
                </div>
HTML;
            $content = str_replace(
                '                <p class="form-note">',
                $admissionConsent."\n                <p class=\"form-note\">",
                $content,
            );
        }

        if (! str_contains($content, 'data-account-privacy')) {
            $accountConsent = <<<'HTML'
                <button class="registration-consent-toggle" type="button" data-account-registration-toggle>ახალი მომხმარებელი ხართ? რეგისტრაციის პირობები</button>
                <div class="legal-consent-stack account-registration-consent" data-account-privacy hidden>
                    <p class="legal-consent-summary"><strong>ეს ნაწილი საჭიროა მხოლოდ ახალი ანგარიშის შექმნისას.</strong> უკვე რეგისტრირებულ მომხმარებელს ხელახალი თანხმობა არ მოეთხოვება.</p>
                    <label class="legal-consent-box required"><input type="checkbox" name="privacy_accepted" value="1"><span>გავეცანი <a href="/privacy" target="_blank" rel="noopener">კონფიდენციალურობის პოლიტიკას</a> და <a href="/terms" target="_blank" rel="noopener">სარგებლობის პირობებს</a>. ვადასტურებ ანგარიშის შექმნისა და მომსახურებისთვის აუცილებელი მონაცემების დამუშავებას.</span></label>
                    <label class="legal-consent-box optional"><input type="checkbox" name="marketing_consent" value="1"><span>მსურს მივიღო ბაღის სიახლეები და ღონისძიებების ინფორმაცია მითითებულ ნომერზე. ეს არჩევითია და მოგვიანებით ანგარიშიდან შეიცვლება.</span></label>
                    <p class="legal-field-error">ახალი ანგარიშის შესაქმნელად დაადასტურეთ კონფიდენციალურობის პირობები.</p>
                </div>
HTML;
            $content = str_replace(
                '                <div class="demo-auth-note"',
                $accountConsent."\n                <div class=\"demo-auth-note\"",
                $content,
            );
        }

        if (str_contains($content, 'class="site-footer"') && ! str_contains($content, 'aria-label="სამართლებრივი ინფორმაცია"')) {
            $footerLinks = <<<'HTML'
    <nav class="legal-footer-links" aria-label="სამართლებრივი ინფორმაცია"><a href="/privacy">კონფიდენციალურობა</a><a href="/terms">სარგებლობის პირობები</a><a href="/privacy/request">მონაცემთა მოთხოვნა</a><span>შპს ინეს ბაღი · ს/კ 445602465</span></nav>
HTML;
            $content = preg_replace_callback(
                '/(<footer class="site-footer">.*?)(<\/footer>)/s',
                fn (array $matches): string => $matches[1].$footerLinks.$matches[2],
                $content,
                1,
            ) ?? $content;
        }

        return $content;
    }
}
