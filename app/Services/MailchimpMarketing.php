<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MailchimpMarketing
{
    public function requestDoubleOptIn(User $user, array $tags = []): bool
    {
        return $this->upsertMember($user, ['status' => 'pending'], $tags);
    }

    public function syncActiveConsent(User $user, array $tags = []): bool
    {
        return $this->upsertMember($user, ['status_if_new' => 'pending'], $tags);
    }

    public function unsubscribe(?string $email): bool
    {
        $email = $this->normalizeEmail($email);
        if (! $this->configured() || $email === null) {
            return false;
        }

        try {
            $response = $this->client()->patch($this->memberUrl($email), [
                'status' => 'unsubscribed',
            ]);

            if ($response->successful() || $response->status() === 404) {
                return true;
            }

            $this->logFailure('unsubscribe', $response, $email);
        } catch (Throwable $exception) {
            $this->logException('unsubscribe', $exception, $email);
        }

        return false;
    }

    public function configured(): bool
    {
        return (bool) config('services.mailchimp.enabled')
            && filled(config('services.mailchimp.api_key'))
            && preg_match('/^us\d+$/', (string) config('services.mailchimp.server_prefix')) === 1
            && filled(config('services.mailchimp.audience_id'));
    }

    private function upsertMember(User $user, array $subscriptionState, array $tags): bool
    {
        $email = $this->normalizeEmail($user->email);
        if (! $this->configured() || $email === null) {
            return false;
        }

        $payload = [
            'email_address' => $email,
            ...$subscriptionState,
            'language' => 'ka',
            'merge_fields' => $this->mergeFields($user),
        ];

        try {
            $response = $this->client()->put($this->memberUrl($email), $payload);

            // Some audiences rename or remove the default FNAME/LNAME merge fields.
            // Keep the subscription working even when those optional fields differ.
            if ($response->status() === 400 && isset($payload['merge_fields'])) {
                unset($payload['merge_fields']);
                $response = $this->client()->put($this->memberUrl($email), $payload);
            }

            if (! $response->successful()) {
                $this->logFailure('upsert', $response, $email);

                return false;
            }

            $this->applyTags($email, $tags);

            return true;
        } catch (Throwable $exception) {
            $this->logException('upsert', $exception, $email);

            return false;
        }
    }

    private function applyTags(string $email, array $tags): void
    {
        $tags = collect($tags)
            ->filter(fn ($tag): bool => is_string($tag) && trim($tag) !== '')
            ->map(fn (string $tag): array => ['name' => trim($tag), 'status' => 'active'])
            ->values()
            ->all();

        if ($tags === []) {
            return;
        }

        $response = $this->client()->post($this->memberUrl($email).'/tags', [
            'tags' => $tags,
        ]);

        if (! $response->successful()) {
            $this->logFailure('tags', $response, $email);
        }
    }

    private function mergeFields(User $user): array
    {
        $parts = preg_split('/\s+/u', trim((string) $user->name), 2) ?: [];

        return array_filter([
            'FNAME' => $parts[0] ?? null,
            'LNAME' => $parts[1] ?? null,
        ], fn ($value): bool => filled($value));
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withBasicAuth('key', (string) config('services.mailchimp.api_key'))
            ->connectTimeout(2)
            ->timeout((int) config('services.mailchimp.timeout_seconds', 5));
    }

    private function memberUrl(string $email): string
    {
        return $this->baseUrl()
            .'/lists/'.rawurlencode((string) config('services.mailchimp.audience_id'))
            .'/members/'.md5($email);
    }

    private function baseUrl(): string
    {
        $server = (string) config('services.mailchimp.server_prefix');

        return "https://{$server}.api.mailchimp.com/3.0";
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = mb_strtolower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function logFailure(string $action, Response $response, string $email): void
    {
        Log::warning('Mailchimp request failed.', [
            'action' => $action,
            'status' => $response->status(),
            'email_hash' => hash('sha256', $email),
            'title' => $response->json('title'),
            'detail' => $response->json('detail'),
        ]);
    }

    private function logException(string $action, Throwable $exception, string $email): void
    {
        Log::warning('Mailchimp connection failed.', [
            'action' => $action,
            'email_hash' => hash('sha256', $email),
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }
}
