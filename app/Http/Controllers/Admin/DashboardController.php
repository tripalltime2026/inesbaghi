<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\Payment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $period = now()->format('Y-m');

        return view('admin.dashboard', [
            'users' => User::count(),
            'parents' => User::where('role', 'parent')->count(),
            'members' => User::whereIn('role', ['member', 'parent'])->count(),
            'pendingUsers' => User::where('status', 'pending')->count(),
            'pendingApprovals' => User::where('status', 'pending')->latest()->limit(10)->get(),
            'recentUsers' => User::latest()->limit(12)->get(),
            'applications' => AdmissionApplication::count(),
            'newApplications' => AdmissionApplication::where('status', 'new')->count(),
            'toursToday' => AdmissionApplication::whereDate('tour_scheduled_at', today())->count(),
            'children' => Child::count(),
            'activeEnrollments' => Enrollment::where('status', 'active')->count(),
            'recentApplications' => AdmissionApplication::with('assignedTo')->latest()->limit(8)->get(),
            'groupStats' => KindergartenGroup::query()
                ->withCount(['enrollments as active_enrollments_count' => fn ($query) => $query->where('status', 'active')])
                ->orderBy('age_min_months')
                ->get(),
            'monthlyCollected' => (float) Payment::where('period', $period)->sum('paid_amount'),
            'monthlyOutstanding' => (float) Payment::where('period', $period)->get()->sum(fn (Payment $payment) => $payment->outstandingAmount()),
        ]);
    }
}
