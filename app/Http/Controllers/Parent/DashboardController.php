<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ForumTopic;
use App\Models\KindergartenGroup;
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

        $forumGroupIds = $children
            ->flatMap(fn ($child) => $child->enrollments
                ->filter(fn ($enrollment) => $enrollment->status === 'active' && $enrollment->group?->is_active)
                ->pluck('kindergarten_group_id'))
            ->unique()
            ->values();

        $forumGroups = KindergartenGroup::query()
            ->whereIn('id', $forumGroupIds)
            ->where('is_active', true)
            ->orderBy('age_min_months')
            ->get();

        $forumTopics = ForumTopic::query()
            ->whereIn('kindergarten_group_id', $forumGroupIds)
            ->with([
                'group',
                'author:id,name',
                'comments.author:id,name',
            ])
            ->withCount('comments')
            ->latest()
            ->limit(50)
            ->get();

        return view('parent.dashboard', compact('children', 'forumGroups', 'forumTopics'));
    }
}
