<?php

namespace App\Http\Controllers;

use App\Models\DataSubjectRequest;
use App\Models\PrivacyConsent;
use App\Support\PrivacyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('legal.privacy', $this->legalData());
    }

    public function terms(): View
    {
        return view('legal.terms', $this->legalData());
    }

    public function requestForm(): View
    {
        return view('legal.data-request', [
            ...$this->legalData(),
            'requestTypes' => DataSubjectRequest::TYPES,
        ]);
    }

    public function storeRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'regex:/^(?:\+?995)?5\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'request_type' => ['required', Rule::in(array_keys(DataSubjectRequest::TYPES))],
            'details' => ['nullable', 'string', 'max:5000'],
            'privacy_accepted' => ['accepted'],
        ]);

        $dataRequest = DataSubjectRequest::create([
            ...$validated,
            'phone' => $this->normalizePhone($validated['phone']),
            'user_id' => $request->user()?->id,
            'status' => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        if ($request->user() && $validated['request_type'] === 'withdraw_consent') {
            PrivacyConsent::query()
                ->where('user_id', $request->user()->id)
                ->where('legal_basis', 'consent')
                ->whereNull('withdrawn_at')
                ->update(['withdrawn_at' => now(), 'updated_at' => now()]);
        }

        return redirect()
            ->route('privacy.request')
            ->with('success', 'მოთხოვნა დარეგისტრირდა. ნომერი: #'.$dataRequest->id.'. საჭიროების შემთხვევაში ვინაობის დასადასტურებლად დაგიკავშირდებით.');
    }

    private function legalData(): array
    {
        return [
            'policyVersion' => PrivacyPolicy::VERSION,
            'companyName' => PrivacyPolicy::COMPANY_NAME,
            'identificationCode' => PrivacyPolicy::IDENTIFICATION_CODE,
            'companyAddress' => PrivacyPolicy::ADDRESS,
            'companyPhone' => PrivacyPolicy::PHONE,
        ];
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
