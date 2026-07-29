<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'users' => User::count(),
            'pendingUsers' => User::where('status', 'pending')->count(),
            'applications' => AdmissionApplication::count(),
            'newApplications' => AdmissionApplication::where('status', 'new')->count(),
            'toursToday' => AdmissionApplication::whereDate('tour_scheduled_at', today())->count(),
            'children' => Child::count(),
            'activeEnrollments' => Enrollment::where('status', 'active')->count(),
            'recentApplications' => AdmissionApplication::with('assignedTo')->latest()->limit(8)->get(),
        ]);
    }
}
