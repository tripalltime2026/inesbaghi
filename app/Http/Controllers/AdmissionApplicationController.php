<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Services\PrivacyConsentRecorder;
use App\Support\PrivacyPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdmissionApplicationController extends Controller
{
    public function store(Request $request, PrivacyConsentRecorder $recorder): JsonResponse
    {
        $validated = $request->validate([
            'parent_name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'regex:/^(?:\+?995)?5\d{8}$/'],
            'child_name' => ['nullable', 'string', 'min:2', 'max:120'],
            'birth_year' => ['nullable', 'integer', 'between:2018,2026'],
            'preferred_group' => ['required', 'in:2-3,3-4,4-5,5-6'],
            'academic_year' => ['required', 'in:2026,2027'],
            'wants_tour' => ['required', 'boolean'],
            'preferred_tour_date' => ['nullable', 'date', 'after_or_equal:today'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'privacy_accepted' => ['accepted'],
            'guardian_authority_confirmed' => ['accepted'],
            'special_category_consent' => ['accepted'],
            'marketing_consent' => ['nullable', 'boolean'],
            'privacy_policy_version' => ['required', Rule::in([PrivacyPolicy::VERSION])],
        ]);

        $application = DB::transaction(function () use ($request, $validated, $recorder): AdmissionApplication {
            $application = AdmissionApplication::create([
                'parent_name' => $validated['parent_name'],
                'phone' => $this->normalizePhone($validated['phone']),
                'child_name' => $validated['child_name'] ?? null,
                'birth_year' => $validated['birth_year'] ?? null,
                'preferred_group' => $validated['preferred_group'],
                'academic_year' => $validated['academic_year'],
                'wants_tour' => $validated['wants_tour'],
                'preferred_tour_date' => $validated['preferred_tour_date'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'guardian_user_id' => $request->user()?->id,
                'status' => 'new',
                'source' => 'website',
            ]);

            $subjectType = AdmissionApplication::class;
            $metadata = ['phone' => $application->phone, 'policy_version' => PrivacyPolicy::VERSION];

            $recorder->record($request, 'admission_privacy_acknowledgement', PrivacyPolicy::ADMISSION_ACKNOWLEDGEMENT, 'pre_contract_service_request', $request->user()?->id, $subjectType, $application->id, $metadata);
            $recorder->record($request, 'guardian_authority_confirmation', PrivacyPolicy::GUARDIAN_CONFIRMATION, 'legal_representative_confirmation', $request->user()?->id, $subjectType, $application->id, $metadata);
            $recorder->record($request, 'child_special_category_consent', PrivacyPolicy::SPECIAL_CATEGORY_CONSENT, 'consent', $request->user()?->id, $subjectType, $application->id, $metadata);

            if ($request->boolean('marketing_consent')) {
                $recorder->record($request, 'marketing_updates', PrivacyPolicy::MARKETING_CONSENT, 'consent', $request->user()?->id, $subjectType, $application->id, $metadata);
            }

            return $application;
        });

        return response()->json([
            'message' => 'განაცხადი მიღებულია. მალე დაგიკავშირდებით.',
            'application_id' => $application->id,
        ], 201);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '995')) {
            $digits = substr($digits, 3);
        }

        return '+995'.$digits;
    }
}
