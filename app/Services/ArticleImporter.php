<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ArticleImporter
{
    private const ALLOWED_HOSTS = ['marketer.ge', 'www.marketer.ge'];

    public function import(string $url): array
    {
        $url = trim($url);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($scheme !== 'https' || ! in_array($host, self::ALLOWED_HOSTS, true)) {
            throw ValidationException::withMessages([
                'article_url' => 'ამ ეტაპზე ავტომატური იმპორტი მხოლოდ marketer.ge-ის HTTPS ბმულიდანაა დაშვებული.',
            ]);
        }

        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->retry(2, 250)
                ->withUserAgent('InesBaghi Article Importer/1.0')
                ->withOptions(['allow_redirects' => ['max' => 3, 'strict' => true]])
                ->get($url);
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'article_url' => 'სტატიის ბმულთან დაკავშირება ვერ მოხერხდა. გადაამოწმეთ ბმული და სცადეთ ხელახლა.',
            ]);
        }

        if (! $response->successful() || ! str_contains(strtolower($response->header('Content-Type')), 'text/html')) {
            throw ValidationException::withMessages([
                'article_url' => 'ბმულიდან სტატიის HTML გვერდი ვერ მივიღეთ.',
            ]);
        }

        $html = $response->body();
        if ($html === '' || strlen($html) > 5_000_000) {
            throw ValidationException::withMessages([
                'article_url' => 'სტატიის გვერდი ცარიელია ან დასაშვებზე დიდია.',
            ]);
        }

        return $this->extract($html, $url);
    }

    private function extract(string $html, string $sourceUrl): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw ValidationException::withMessages(['article_url' => 'სტატიის ტექსტის დამუშავება ვერ მოხერხდა.']);
        }

        $xpath = new \DOMXPath($document);
        $jsonLd = $this->articleJsonLd($xpath);

        $title = $jsonLd['headline'] ?? $this->meta($xpath, 'property', 'og:title') ?? $this->firstText($xpath, '//h1');
        $excerpt = $jsonLd['description'] ?? $this->meta($xpath, 'property', 'og:description') ?? $this->meta($xpath, 'name', 'description');
        $imageUrl = $jsonLd['image'] ?? $this->meta($xpath, 'property', 'og:image');
        if (is_array($imageUrl)) {
            $imageUrl = $imageUrl['url'] ?? $imageUrl[0] ?? null;
        }

        $body = $jsonLd['articleBody'] ?? null;
        if (! is_string($body) || trim($body) === '') {
            $body = $this->paragraphText($xpath);
        }

        $title = trim(strip_tags((string) $title));
        $excerpt = trim(strip_tags((string) $excerpt));
        $body = trim((string) $body);

        if ($title === '') {
            throw ValidationException::withMessages([
                'article_url' => 'ბმულიდან სტატიის სათაური ვერ ამოვიღეთ. სათაური ხელით შეავსეთ.',
            ]);
        }

        return [
            'title' => mb_substr($title, 0, 255),
            'excerpt' => mb_substr($excerpt ?: mb_substr($body, 0, 350), 0, 2000),
            'body' => mb_substr($body, 0, 50000),
            'category' => 'მედია ჩვენ შესახებ',
            'source_url' => $sourceUrl,
            'cover' => $this->downloadImage(is_string($imageUrl) ? $imageUrl : null),
        ];
    }

    private function articleJsonLd(\DOMXPath $xpath): array
    {
        foreach ($xpath->query('//script[@type="application/ld+json"]') ?: [] as $node) {
            $decoded = json_decode(trim($node->textContent), true);
            foreach ($this->flattenJsonLd($decoded) as $item) {
                $type = $item['@type'] ?? null;
                $types = is_array($type) ? $type : [$type];
                if (array_intersect($types, ['Article', 'NewsArticle', 'BlogPosting'])) {
                    return $item;
                }
            }
        }

        return [];
    }

    private function flattenJsonLd(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }
        if (array_is_list($value)) {
            return array_merge(...array_map(fn ($item) => $this->flattenJsonLd($item), $value));
        }
        if (isset($value['@graph']) && is_array($value['@graph'])) {
            return $this->flattenJsonLd($value['@graph']);
        }

        return [$value];
    }

    private function paragraphText(\DOMXPath $xpath): string
    {
        $queries = [
            '//article//p[not(ancestor::footer)]',
            '//*[contains(concat(" ", normalize-space(@class), " "), " entry-content ")]//p',
            '//*[contains(concat(" ", normalize-space(@class), " "), " post-content ")]//p',
        ];

        foreach ($queries as $query) {
            $paragraphs = [];
            foreach ($xpath->query($query) ?: [] as $node) {
                $text = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
                if (mb_strlen($text) >= 30) {
                    $paragraphs[] = $text;
                }
            }
            if ($paragraphs !== []) {
                return implode("\n\n", array_values(array_unique($paragraphs)));
            }
        }

        return '';
    }

    private function meta(\DOMXPath $xpath, string $attribute, string $value): ?string
    {
        $nodes = $xpath->query('//meta[@'.$attribute.'="'.$value.'"]/@content');
        return $nodes && $nodes->length ? trim($nodes->item(0)->nodeValue) : null;
    }

    private function firstText(\DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);
        return $nodes && $nodes->length ? trim($nodes->item(0)->textContent) : null;
    }

    private function downloadImage(?string $url): ?array
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return null;
        }

        try {
            $response = Http::timeout(15)->connectTimeout(5)->withOptions(['allow_redirects' => ['max' => 3]])->get($url);
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }

        $mime = strtolower(trim(strtok((string) $response->header('Content-Type'), ';')));
        $bytes = $response->body();
        if (! $response->successful() || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || $bytes === '' || strlen($bytes) > 5_242_880) {
            return null;
        }

        return [
            'bytes' => $bytes,
            'mime' => $mime,
            'name' => basename((string) parse_url($url, PHP_URL_PATH)) ?: 'imported-cover',
        ];
    }
}
