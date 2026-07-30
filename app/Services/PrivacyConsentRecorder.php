<?php

namespace App\Services;

use App\Models\PrivacyConsent;
use App\Support\PrivacyPolicy;
use Illuminate\Http\Request;

class PrivacyConsentRecorder
{
    public function record(
        Request $request,
        string $type,
        string $text,
        string $legalBasis,
        ?int $userId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $metadata = [],
    ): PrivacyConsent {
        return PrivacyConsent::create([
            'user_id' => $userId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'consent_type' => $type,
            'policy_version' => PrivacyPolicy::VERSION,
            'legal_basis' => $legalBasis,
            'consent_text_hash' => PrivacyPolicy::textHash($text),
            'accepted_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'metadata' => $metadata,
        ]);
    }

    public function recordForUserIfMissing(
        Request $request,
        int $userId,
        string $type,
        string $text,
        string $legalBasis,
        array $metadata = [],
    ): PrivacyConsent {
        $existing = PrivacyConsent::query()
            ->where('user_id', $userId)
            ->where('consent_type', $type)
            ->where('policy_version', PrivacyPolicy::VERSION)
            ->whereNull('withdrawn_at')
            ->latest('accepted_at')
            ->first();

        return $existing ?: $this->record(
            $request,
            $type,
            $text,
            $legalBasis,
            $userId,
            metadata: $metadata,
        );
    }
}
