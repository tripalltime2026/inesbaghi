<?php
namespace App\Http\Controllers;
use App\Models\AdmissionApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class AdmissionApplicationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'regex:/^(?:\+?995)?5\d{8}$/'],
            'child_name' => ['required', 'string', 'min:2', 'max:120'],
            'birth_year' => ['nullable', 'integer', 'between:2018,2026'],
            'preferred_group' => ['required', 'in:2-3,3-4,4-5,5-6'],
            'academic_year' => ['required', 'in:2026,2027'],
            'wants_tour' => ['required', 'boolean'],
            'preferred_tour_date' => ['nullable', 'date', 'after_or_equal:today'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);
        $validated['phone'] = $this->normalizePhone($validated['phone']);
        $validated['guardian_user_id'] = $request->user()?->id;
        $validated['status'] = 'new';
        $validated['source'] = 'website';
        $application = AdmissionApplication::create($validated);
        return response()->json(['message' => 'განაცხადი მიღებულია. მალე დაგიკავშირდებით.', 'application_id' => $application->id], 201);
    }
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '995')) $digits = substr($digits, 3);
        return '+995'.$digits;
    }
}
