<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $children = $request->user()
            ->children()
            ->with([
                'enrollments' => fn ($query) => $query
                    ->with(['group', 'payments' => fn ($paymentQuery) => $paymentQuery->latest('period')])
                    ->latest(),
                'attendanceRecords' => fn ($query) => $query
                    ->with('group')
                    ->latest('attendance_date')
                    ->limit(14),
            ])
            ->orderBy('first_name')
            ->get();

        return view('parent.dashboard', compact('children'));
    }
}
