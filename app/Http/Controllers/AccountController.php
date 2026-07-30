<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Models\PrivacyConsent;
use App\Services\PrivacyConsentRecorder;
use App\Support\PrivacyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $children = $user->children()
            ->with(['enrollments' => fn ($query) => $query->with('group')->latest()])
            ->orderBy('first_name')
            ->get();

        $user->setRelation('children', $children);

        $applications = AdmissionApplication::query()
            ->where(function ($query) use ($user): void {
                $query->where('guardian_user_id', $user->id)
                    ->orWhere('phone', $user->phone);
            })
            ->latest()
            ->get();

        $latestEnrollment = $children
            ->flatMap(fn ($child) => $child->enrollments)
            ->sortByDesc('created_at')
            ->first();

        $marketingConsent = PrivacyConsent::query()
            ->where('user_id', $user->id)
            ->where('consent_type', 'marketing_updates')
            ->whereNull('withdrawn_at')
            ->exists();

        return view('account.status', [
            'user' => $user,
            'children' => $children,
            'applications' => $applications,
            'latestEnrollment' => $latestEnrollment,
            'clubAccess' => $user->canAccessParentClub(),
            'marketingConsent' => $marketingConsent,
        ]);
    }

    public function updatePreferences(Request $request, PrivacyConsentRecorder $recorder): RedirectResponse
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
                ['source' => 'account_preferences', 'phone' => $user->phone],
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
}
