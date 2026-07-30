<?php

namespace App\Http\Controllers;

use App\Models\PrivacyConsent;
use App\Services\PrivacyConsentRecorder;
use App\Support\PrivacyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load([
            'children.enrollments' => fn ($query) => $query->with('group')->latest(),
        ]);

        return view('account', [
            'user' => $user,
            'parentAccess' => $user->hasVerifiedParentAccess(),
            'privacyAcknowledged' => $this->hasActiveConsent($user->id, 'account_privacy_acknowledgement'),
            'marketingEnabled' => $this->hasActiveConsent($user->id, 'marketing_updates'),
        ]);
    }

    public function updateMarketing(Request $request, PrivacyConsentRecorder $recorder): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_consent' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $enabled = (bool) $validated['marketing_consent'];

        if ($enabled) {
            $recorder->recordForUserIfMissing(
                $request,
                $user->id,
                'marketing_updates',
                PrivacyPolicy::MARKETING_CONSENT,
                'consent',
                ['source' => 'account_preferences'],
            );
        } else {
            PrivacyConsent::query()
                ->where('user_id', $user->id)
                ->where('consent_type', 'marketing_updates')
                ->whereNull('withdrawn_at')
                ->update(['withdrawn_at' => now()]);
        }

        return back()->with('success', $enabled
            ? 'საინფორმაციო და მარკეტინგული შეტყობინებები ჩაირთო.'
            : 'საინფორმაციო და მარკეტინგული შეტყობინებები გამოირთო.');
    }

    private function hasActiveConsent(int $userId, string $type): bool
    {
        return PrivacyConsent::query()
            ->where('user_id', $userId)
            ->where('consent_type', $type)
            ->where('policy_version', PrivacyPolicy::VERSION)
            ->whereNull('withdrawn_at')
            ->exists();
    }
}
