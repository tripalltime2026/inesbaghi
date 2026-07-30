<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Models\PrivacyConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PrivacyController extends Controller
{
    public function index(Request $request): View
    {
        $requests = DataSubjectRequest::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('request_type', $request->string('type')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.privacy.index', [
            'requests' => $requests,
            'requestTypes' => DataSubjectRequest::TYPES,
            'statuses' => DataSubjectRequest::STATUSES,
            'newCount' => DataSubjectRequest::where('status', 'new')->count(),
            'consentCount' => PrivacyConsent::count(),
            'withdrawnCount' => PrivacyConsent::whereNotNull('withdrawn_at')->count(),
        ]);
    }

    public function update(Request $request, DataSubjectRequest $dataRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(DataSubjectRequest::STATUSES))],
            'response_notes' => ['nullable', 'string', 'max:5000'],
            'identity_verified' => ['nullable', 'boolean'],
        ]);

        $dataRequest->status = $validated['status'];
        $dataRequest->response_notes = $validated['response_notes'] ?? null;

        if ($request->boolean('identity_verified') && ! $dataRequest->verified_at) {
            $dataRequest->verified_at = now();
        }

        if (in_array($validated['status'], ['completed', 'rejected'], true)) {
            $dataRequest->completed_at ??= now();
        } else {
            $dataRequest->completed_at = null;
        }

        $dataRequest->save();

        return back()->with('success', 'მონაცემთა სუბიექტის მოთხოვნა განახლდა.');
    }
}
