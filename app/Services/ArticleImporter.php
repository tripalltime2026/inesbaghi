<?php

namespace App\Services;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ArticleImporter
{
    private const ALLOWED_HOSTS = ['marketer.ge', 'www.marketer.ge'];

    private const MAX_HTML_BYTES = 2_000_000;

    private const MAX_IMAGE_BYTES = 5_242_880;

    public function import(string $url): array
    {
        $url = $this->normalizeAndValidateUrl($url);
        $response = $this->client()->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Marketer.ge-ის სტატიის წამოღება ვერ მოხერხდა.');
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        $html = $response->body();

        if (! str_contains($contentType, 'text/html') || strlen($html) > self::MAX_HTML_BYTES) {
            throw new RuntimeException('მითითებული ბმული HTML სტატია არ არის ან ზედმეტად დიდია.');
        }

        [, $xpath] = $this->document($html);

        $title = $this->firstValue($xpath, [
            '//meta[@property="og:title"]/@content',
            '//meta[@name="twitter:title"]/@content',
            '//h1[1]',
            '//title[1]',
        ]);

        if (! filled($title)) {
            throw new RuntimeException('სტატიის სათაურის ამოცნობა ვერ მოხერხდა.');
        }

        $description = $this->firstValue($xpath, [
            '//meta[@property="og:description"]/@content',
            '//meta[@name="description"]/@content',
            '//meta[@name="twitter:description"]/@content',
        ]) ?: $this->paragraphExcerpt($xpath);

        $canonical = $this->firstValue($xpath, [
            '//link[@rel="canonical"]/@href',
            '//meta[@property="og:url"]/@content',
        ]);
        $canonical = filled($canonical) ? $this->absoluteUrl($canonical, $url) : $url;
        $canonical = $this->normalizeAndValidateUrl($canonical);

        $sourceName = $this->firstValue($xpath, [
            '//meta[@property="og:site_name"]/@content',
        ]) ?: 'Marketer.ge';

        $publishedAt = $this->parseDate($this->firstValue($xpath, [
            '//meta[@property="article:published_time"]/@content',
            '//meta[@name="article:published_time"]/@content',
            '//time[1]/@datetime',
        ]));

        $cover = $this->importCover(
            $this->firstValue($xpath, [
                '//meta[@property="og:image"]/@content',
                '//meta[@name="twitter:image"]/@content',
            ]),
            $url,
            $title,
        );

        $excerpt = Str::limit($this->clean($description ?: ''), 600, '…');
        $body = collect([
            filled($excerpt) ? $excerpt : null,
            'მასალა თავდაპირველად გამოქვეყნდა '.trim($sourceName).'-ზე. სრულად წასაკითხად გამოიყენეთ ქვემოთ მითითებული პირველწყარო.',
        ])->filter()->implode("\n\n");

        return array_merge([
            'title' => Str::limit($this->clean($title), 255, ''),
            'excerpt' => filled($excerpt) ? $excerpt : null,
            'body' => $body,
            'category' => 'მედია',
            'source_url' => $canonical,
            'source_name' => Str::limit($this->clean($sourceName), 120, ''),
            'source_published_at' => $publishedAt,
        ], $cover);
    }

    private function client(): PendingRequest
    {
        return Http::connectTimeout(5)
            ->timeout(12)
            ->retry(1, 250, throw: false)
            ->withHeaders([
                'Accept' => 'text/html,application/xhtml+xml',
                'User-Agent' => 'InesBaghiBlogImporter/1.0 (+https://ines.ge)',
            ]);
    }

    private function normalizeAndValidateUrl(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || ! in_array($host, self::ALLOWED_HOSTS, true)) {
            throw new RuntimeException('ამ ეტაპზე შესაძლებელია მხოლოდ Marketer.ge-ის სტატიის ბმულის იმპორტი.');
        }

        return $url;
    }

    private function document(string $html): array
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return [$document, new DOMXPath($document)];
    }

    private function firstValue(DOMXPath $xpath, array $queries): ?string
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if (! $nodes || $nodes->length === 0) {
                continue;
            }

            $value = $this->clean((string) $nodes->item(0)?->nodeValue);
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function paragraphExcerpt(DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query('//article//p | //main//p');
        if (! $nodes) {
            return null;
        }

        $paragraphs = [];
        foreach ($nodes as $node) {
            $text = $this->clean((string) $node->textContent);
            if (mb_strlen($text) < 70) {
                continue;
            }

            $paragraphs[] = $text;
            if (count($paragraphs) === 2) {
                break;
            }
        }

        return $paragraphs ? implode(' ', $paragraphs) : null;
    }

    private function importCover(?string $imageUrl, string $articleUrl, string $title): array
    {
        if (! filled($imageUrl)) {
            return [];
        }

        try {
            $imageUrl = $this->absoluteUrl($imageUrl, $articleUrl);
            $imageUrl = $this->normalizeAndValidateUrl($imageUrl);
            $response = Http::connectTimeout(5)
                ->timeout(12)
                ->retry(1, 250, throw: false)
                ->withHeaders([
                    'Accept' => 'image/avif,image/webp,image/png,image/jpeg,*/*;q=0.8',
                    'User-Agent' => 'InesBaghiBlogImporter/1.0 (+https://ines.ge)',
                ])
                ->get($imageUrl);

            $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
            $bytes = $response->body();

            if (! $response->successful()
                || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
                || $bytes === ''
                || strlen($bytes) > self::MAX_IMAGE_BYTES) {
                return [];
            }

            $extension = match ($mime) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };

            return [
                'cover_image' => base64_encode($bytes),
                'cover_encoding' => 'base64',
                'cover_mime' => $mime,
                'cover_name' => 'marketer-'.Str::slug(Str::limit($title, 60, '')).'.'.$extension,
                'cover_alt' => Str::limit($this->clean($title), 255, ''),
            ];
        } catch (Throwable) {
            return [];
        }
    }

    private function absoluteUrl(string $candidate, string $base): string
    {
        $candidate = trim($candidate);
        if (preg_match('#^https?://#i', $candidate)) {
            return $candidate;
        }

        $parts = parse_url($base);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? 'www.marketer.ge';

        if (str_starts_with($candidate, '//')) {
            return $scheme.':'.$candidate;
        }

        return $scheme.'://'.$host.'/'.ltrim($candidate, '/');
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?: '');
    }
}
